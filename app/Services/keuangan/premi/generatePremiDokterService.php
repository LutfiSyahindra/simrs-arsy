<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generatePremiDokterRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generatePremiDokterService
{
    private const CATEGORY_DEFAULTS = [
        generatePremiDokterRepository::CATEGORY_UMUM => [
            'label' => 'Dokter Umum',
            'percent' => 50,
        ],
        generatePremiDokterRepository::CATEGORY_SPESIALIS_65 => [
            'label' => 'Dokter Spesialis 65%',
            'percent' => 65,
        ],
        generatePremiDokterRepository::CATEGORY_SPESIALIS_80 => [
            'label' => 'Dokter Spesialis 80%',
            'percent' => 80,
        ],
    ];

    public function __construct(
        protected generatePremiDokterRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisPelayanan = null): Collection
    {
        return $this->repository
            ->getResults($periode, $jenisPelayanan)
            ->map(fn ($row) => [
                ...$this->resultPayload($row),
                'jumlah_detail' => $row->details_count,
                'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
                'is_locked' => $row->is_locked,
                'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
                'locked_by_name' => $row->lockedBy?->name,
                'generate_by_name' => $row->generateBy?->name,
            ]);
    }

    public function getConfig(): array
    {
        return $this->configPayload($this->repository->getConfig());
    }

    public function updateConfig(
        float $visiteUmumPercent,
        float $visiteBpjsPercent,
        int $visiteBpjsNominal,
        string $sourcePeriodMode,
        array $jnsTindakanIds,
        array $doctorConfigs
    ): array {
        $jnsTindakanIds = collect($jnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->assertJenisTindakanExists($jnsTindakanIds);

        $doctorRows = $this->normalizeDoctorConfigs($doctorConfigs);

        return $this->configPayload(
            $this->repository->saveConfig(
                $visiteUmumPercent,
                $visiteBpjsPercent,
                $visiteBpjsNominal,
                $this->normalizeSourcePeriodMode($sourcePeriodMode),
                $jnsTindakanIds,
                $doctorRows
            )
        );
    }

    public function mappingTindakanOptions(?string $keyword = null): Collection
    {
        return $this->repository
            ->mappingTindakanOptions($keyword)
            ->map(fn ($item) => $this->mappingTindakanPayload($item))
            ->values();
    }

    public function dokterOptions(?string $keyword = null): Collection
    {
        return $this->repository
            ->dokterOptions($keyword)
            ->map(fn ($item) => $this->dokterPayload($item))
            ->values();
    }

    public function getSummary(string $periode, string $jenisPelayanan): array
    {
        $config = $this->repository->getConfig();
        $existing = $this->repository->findByPeriodAndType(
            $periode,
            $jenisPelayanan,
            generatePremiDokterRepository::TYPE_VISITE
        );

        if ($existing?->is_locked) {
            return [
                ...$this->resultPayload($existing),
                'ready' => false,
                'readiness_message' => 'Data sudah dikunci. Buka kunci jika ingin generate ulang.',
                'readiness_steps' => $this->readinessStepsFromResult($existing),
                'is_generated' => true,
                'is_locked' => true,
                'config' => $this->configPayload($config),
                'details' => $existing->details->map(fn ($detail) => $this->detailPayload($detail))->values(),
            ];
        }

        $calculation = $this->repository->calculate($periode, $jenisPelayanan, $config);
        $ready = $this->isReady($jenisPelayanan, $calculation);

        return [
            ...$this->summaryPayload($calculation),
            'ready' => $ready,
            'readiness_message' => $this->readinessMessage($jenisPelayanan, $calculation),
            'readiness_steps' => $this->readinessSteps($jenisPelayanan, $calculation),
            'is_generated' => (bool) $existing,
            'is_locked' => false,
            'locked_at' => optional($existing?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $existing?->lockedBy?->name,
            'config' => $this->configPayload($config),
        ];
    }

    public function generate(string $periode, string $jenisPelayanan): array
    {
        $config = $this->repository->getConfig();
        $calculation = $this->repository->calculate($periode, $jenisPelayanan, $config);

        if (! $this->isReady($jenisPelayanan, $calculation)) {
            throw ValidationException::withMessages([
                'periode' => $this->readinessMessage($jenisPelayanan, $calculation),
            ]);
        }

        $result = $this->repository->saveResult($periode, $jenisPelayanan, $config, $calculation);

        return [
            ...$this->resultPayload($result),
            'details' => $result->details->map(fn ($detail) => $this->detailPayload($detail))->values(),
        ];
    }

    public function getDetail(int $id): array
    {
        $result = $this->repository->findById($id);

        return [
            ...$this->resultPayload($result),
            'details' => $result->details->map(fn ($detail) => $this->detailPayload($detail))->values(),
            'config_snapshot' => $result->config_snapshot ?? [],
        ];
    }

    public function lock(int $id, ?User $user): array
    {
        $result = $this->repository->lock($id, $user?->id);

        return $this->resultPayload($result);
    }

    public function unlock(int $id, ?User $user): array
    {
        if (! ($user?->hasRole('Admin') ?? false)) {
            throw new AuthorizationException('Hanya Admin yang dapat membuka kunci data.');
        }

        $result = $this->repository->unlock($id);

        return $this->resultPayload($result);
    }

    private function normalizeDoctorConfigs(array $doctorConfigs): array
    {
        $configs = collect($doctorConfigs)
            ->map(function (array $item) {
                $kategori = (string) ($item['kategori'] ?? '');
                $default = self::CATEGORY_DEFAULTS[$kategori] ?? null;

                if (! $default) {
                    return null;
                }

                return [
                    'kategori' => $kategori,
                    'kd_dokter' => trim((string) ($item['kd_dokter'] ?? '')),
                    'percent' => (float) ($item['percent'] ?? $default['percent']),
                ];
            })
            ->filter(fn (?array $item) => $item && filled($item['kd_dokter']))
            ->values();

        $duplicates = $configs
            ->groupBy('kd_dokter')
            ->filter(fn (Collection $rows) => $rows->count() > 1)
            ->keys();

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'doctor_configs' => 'Dokter tidak boleh masuk lebih dari satu kategori: '.$duplicates->implode(', '),
            ]);
        }

        $doctorMasters = $this->repository
            ->getDoctorsByCodes($configs->pluck('kd_dokter')->all())
            ->keyBy('kd_dokter');
        $missing = $configs
            ->pluck('kd_dokter')
            ->reject(fn ($code) => $doctorMasters->has($code))
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'doctor_configs' => 'Dokter tidak ditemukan: '.$missing->implode(', '),
            ]);
        }

        return $configs
            ->map(function (array $item) use ($doctorMasters) {
                $doctor = $doctorMasters->get($item['kd_dokter']);

                return [
                    'kategori' => $item['kategori'],
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'kd_sps' => $doctor->kd_sps,
                    'nm_sps' => $doctor->nm_sps,
                    'percent' => $item['percent'],
                ];
            })
            ->values()
            ->all();
    }

    private function assertJenisTindakanExists(array $ids): void
    {
        if (! $ids) {
            return;
        }

        $found = DB::table('master_jenis_tindakan')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
        $missing = collect($ids)
            ->reject(fn ($id) => $found->contains((int) $id))
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'mapping_tindakan_ids' => 'Master Mapping Tindakan visite tidak ditemukan: '.$missing->implode(', '),
            ]);
        }
    }

    private function isReady(string $jenisPelayanan, array $calculation): bool
    {
        if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
            return false;
        }

        if ((int) $calculation['jumlah_mapping_tindakan'] <= 0) {
            return false;
        }

        if ($jenisPelayanan === 'bpjs' && (int) $calculation['visite_bpjs_nominal'] <= 0) {
            return false;
        }

        return (float) $calculation['total_premi'] > 0
            && (int) $calculation['jumlah_dokter'] > 0;
    }

    private function readinessMessage(string $jenisPelayanan, array $calculation): string
    {
        if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
            return 'Pilih mapping tindakan visite dokter pada konfigurasi.';
        }

        if ((int) $calculation['jumlah_mapping_tindakan'] <= 0) {
            return 'Mapping tindakan visite dokter yang dipilih belum memiliki rincian RAJAL/RANAP.';
        }

        if ($jenisPelayanan === 'bpjs' && (int) $calculation['visite_bpjs_nominal'] <= 0) {
            return 'Isi nominal per tindakan BPJS pada konfigurasi visite.';
        }

        if ((int) $calculation['jumlah_dokter'] <= 0) {
            return $jenisPelayanan === 'bpjs'
                ? 'Belum ada dokter umum terkonfigurasi yang memiliki data BPJS.'
                : 'Belum ada dokter umum/spesialis terkonfigurasi yang memiliki data UMUM.';
        }

        if ((float) $calculation['total_premi'] <= 0) {
            return 'Data ditemukan, tetapi total premi masih Rp 0.';
        }

        return 'Siap generate premi dokter visite '.$this->typeLabel($jenisPelayanan).'.';
    }

    private function readinessSteps(string $jenisPelayanan, array $calculation): array
    {
        $steps = [
            [
                'label' => 'Periode sumber data',
                'status' => 'success',
                'value' => ($calculation['source_periode'] ?? '-').' / '.($calculation['source_period_mode_label'] ?? '-'),
            ],
            [
                'label' => 'Master Mapping Tindakan visite',
                'status' => ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                    && (int) $calculation['jumlah_mapping_tindakan'] > 0) ? 'success' : 'danger',
                'value' => (int) ($calculation['jumlah_jenis_tindakan'] ?? 0).' jenis / '
                    .(int) $calculation['jumlah_mapping_tindakan'].' tindakan',
            ],
            [
                'label' => $jenisPelayanan === 'bpjs' ? 'Dokter umum' : 'Dokter terkonfigurasi',
                'status' => (int) $calculation['jumlah_dokter'] > 0 ? 'success' : 'warning',
                'value' => (int) $calculation['jumlah_dokter'].' dokter',
            ],
            [
                'label' => $jenisPelayanan === 'bpjs' ? 'Nominal BPJS' : 'Total biaya rawat',
                'status' => $jenisPelayanan === 'bpjs'
                    ? ((int) $calculation['visite_bpjs_nominal'] > 0 ? 'success' : 'danger')
                    : ((float) $calculation['total_biaya_rawat'] > 0 ? 'success' : 'warning'),
                'value' => $jenisPelayanan === 'bpjs'
                    ? $this->formatRupiah((int) $calculation['visite_bpjs_nominal'])
                    : $this->formatRupiah((float) $calculation['total_biaya_rawat']),
            ],
        ];

        if ($jenisPelayanan === 'bpjs') {
            $steps[] = [
                'label' => 'Spesialis BPJS',
                'status' => 'success',
                'value' => (int) ($calculation['jumlah_spesialis_diabaikan'] ?? 0).' data diabaikan',
            ];
        }

        return $steps;
    }

    private function readinessStepsFromResult($result): array
    {
        return [
            [
                'label' => 'Periode sumber data',
                'status' => 'success',
                'value' => $result->source_periode.' / '.$this->sourcePeriodModeLabel(
                    $result->source_period_mode ?? data_get($result->config_snapshot, 'source_period_mode')
                ),
            ],
            [
                'label' => 'Master Mapping Tindakan visite',
                'status' => 'success',
                'value' => (int) $result->jumlah_mapping_tindakan.' mapping',
            ],
            [
                'label' => 'Dokter penerima',
                'status' => 'success',
                'value' => (int) $result->jumlah_dokter.' dokter',
            ],
            [
                'label' => 'Total premi',
                'status' => 'success',
                'value' => $this->formatRupiah((float) $result->total_premi),
            ],
        ];
    }

    private function summaryPayload(array $calculation): array
    {
        return [
            'periode' => $calculation['periode'],
            'source_periode' => $calculation['source_periode'],
            'source_period_mode' => $calculation['source_period_mode'],
            'source_period_mode_label' => $calculation['source_period_mode_label'],
            'source_tgl_awal' => $calculation['source_tgl_awal'],
            'source_tgl_akhir' => $calculation['source_tgl_akhir'],
            'jenis_premi_dokter' => $calculation['jenis_premi_dokter'],
            'jenis_premi_dokter_label' => 'Jasa Visite',
            'jenis_pelayanan' => $calculation['jenis_pelayanan'],
            'jenis_pelayanan_label' => $this->typeLabel($calculation['jenis_pelayanan']),
            'visite_umum_percent' => $calculation['visite_umum_percent'],
            'visite_bpjs_percent' => $calculation['visite_bpjs_percent'],
            'visite_bpjs_nominal' => $calculation['visite_bpjs_nominal'],
            'jumlah_transaksi' => $calculation['jumlah_transaksi'],
            'jumlah_pasien' => $calculation['jumlah_pasien'],
            'jumlah_dokter' => $calculation['jumlah_dokter'],
            'jumlah_tindakan' => $calculation['jumlah_tindakan'],
            'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan'] ?? 0,
            'jumlah_mapping_tindakan' => $calculation['jumlah_mapping_tindakan'],
            'jumlah_tidak_terkonfigurasi' => $calculation['jumlah_tidak_terkonfigurasi'],
            'jumlah_spesialis_diabaikan' => $calculation['jumlah_spesialis_diabaikan'] ?? 0,
            'total_biaya_rawat' => $calculation['total_biaya_rawat'],
            'total_grand' => $calculation['total_grand'],
            'total_premi' => $calculation['total_premi'],
            'details' => collect($calculation['details'])->values(),
            'unconfigured_doctors' => $calculation['unconfigured_doctors'],
            'ignored_specialists' => $calculation['ignored_specialists'] ?? [],
        ];
    }

    private function resultPayload($result): array
    {
        return [
            'id' => (int) $result->id,
            'periode' => $result->periode,
            'source_periode' => $result->source_periode,
            'source_period_mode' => $this->normalizeSourcePeriodMode(
                $result->source_period_mode ?? data_get($result->config_snapshot, 'source_period_mode')
            ),
            'source_period_mode_label' => $this->sourcePeriodModeLabel(
                $result->source_period_mode ?? data_get($result->config_snapshot, 'source_period_mode')
            ),
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d') ?? $result->source_tgl_awal,
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d') ?? $result->source_tgl_akhir,
            'jenis_premi_dokter' => $result->jenis_premi_dokter,
            'jenis_premi_dokter_label' => 'Jasa Visite',
            'jenis_pelayanan' => $result->jenis_pelayanan,
            'jenis_pelayanan_label' => $this->typeLabel($result->jenis_pelayanan),
            'visite_umum_percent' => (float) $result->visite_umum_percent,
            'visite_bpjs_percent' => (float) $result->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $result->visite_bpjs_nominal,
            'jumlah_transaksi' => (int) $result->jumlah_transaksi,
            'jumlah_pasien' => (int) $result->jumlah_pasien,
            'jumlah_dokter' => (int) $result->jumlah_dokter,
            'jumlah_tindakan' => (int) $result->jumlah_tindakan,
            'jumlah_mapping_tindakan' => (int) $result->jumlah_mapping_tindakan,
            'jumlah_tidak_terkonfigurasi' => (int) $result->jumlah_tidak_terkonfigurasi,
            'jumlah_spesialis_diabaikan' => (int) data_get($result->config_snapshot, 'jumlah_spesialis_diabaikan', 0),
            'total_biaya_rawat' => (float) $result->total_biaya_rawat,
            'total_grand' => (float) $result->total_grand,
            'total_premi' => (float) $result->total_premi,
            'is_locked' => (bool) $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'generate_by_name' => $result->generateBy?->name,
            'generated_at' => optional($result->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function detailPayload($detail): array
    {
        return [
            'id' => (int) $detail->id,
            'kd_dokter' => $detail->kd_dokter,
            'nm_dokter' => $detail->nm_dokter,
            'kd_sps' => $detail->kd_sps,
            'nm_sps' => $detail->nm_sps,
            'kategori' => $detail->kategori,
            'kategori_label' => $this->categoryLabel($detail->kategori),
            'percent' => (float) $detail->percent,
            'jumlah_data' => (int) $detail->jumlah_data,
            'jumlah_pasien' => (int) $detail->jumlah_pasien,
            'total_biaya_rawat' => (float) $detail->total_biaya_rawat,
            'grand_total' => (float) $detail->grand_total,
            'total_premi' => (float) $detail->total_premi,
            'source_breakdown' => $detail->source_breakdown ?? [],
            'action_breakdown' => $detail->action_breakdown ?? [],
            'data_rawat' => $detail->data_rawat ?? [],
        ];
    }

    private function configPayload(object $config): array
    {
        $tindakan = $this->repository
            ->getConfigTindakan((int) $config->id)
            ->map(fn ($item) => $this->mappingTindakanPayload($item))
            ->values();
        $doctors = $this->repository
            ->getConfigDoctors((int) $config->id)
            ->map(fn ($item) => [
                'kategori' => $item->kategori,
                'kategori_label' => $this->categoryLabel($item->kategori),
                'kd_dokter' => $item->kd_dokter,
                'nm_dokter' => $item->nm_dokter,
                'kd_sps' => $item->kd_sps,
                'nm_sps' => $item->nm_sps,
                'percent' => (float) $item->percent,
                'id' => $item->kd_dokter,
                'text' => trim($item->kd_dokter.' - '.$item->nm_dokter.' ('.$this->categoryLabel($item->kategori).')'),
            ])
            ->values();

        return [
            'id' => (int) $config->id,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'source_period_mode' => $this->normalizeSourcePeriodMode($config->source_period_mode ?? null),
            'source_period_mode_label' => $this->sourcePeriodModeLabel($config->source_period_mode ?? null),
            'source_period_options' => [
                [
                    'id' => generatePremiDokterRepository::SOURCE_PERIOD_CURRENT,
                    'label' => 'Periode Berjalan',
                ],
                [
                    'id' => generatePremiDokterRepository::SOURCE_PERIOD_PREVIOUS,
                    'label' => 'Bulan Sebelumnya',
                ],
            ],
            'jnsTindakan_ids' => $tindakan->pluck('id')->values(),
            'mapping_tindakan_ids' => $tindakan->pluck('id')->values(),
            'mapping_tindakan' => $tindakan,
            'doctor_configs' => $doctors,
            'doctor_groups' => $doctors->groupBy('kategori')->map(fn (Collection $rows) => $rows->values())->all(),
            'category_options' => collect(self::CATEGORY_DEFAULTS)
                ->map(fn (array $row, string $key) => [
                    'id' => $key,
                    'label' => $row['label'],
                    'default_percent' => $row['percent'],
                ])
                ->values(),
            'premium_types' => $this->premiumTypes(),
        ];
    }

    private function mappingTindakanPayload($item): array
    {
        $jenisId = (int) ($item->jnsTindakan_id ?? $item->id);
        $kode = $item->kode_jenis_tindakan ?? $item->kode ?? null;
        $jenis = $item->nama_jenis_tindakan ?? $item->jenis ?? null;
        $label = collect([$kode, $jenis])
            ->filter()
            ->implode(' - ') ?: 'Tanpa jenis tindakan';

        return [
            'id' => $jenisId,
            'jnsTindakan_id' => $jenisId,
            'kode_jenis_tindakan' => $kode,
            'nama_jenis_tindakan' => $jenis,
            'jumlah_mapping' => (int) ($item->jumlah_mapping ?? 0),
            'source_table' => 'master_jenis_tindakan',
            'text' => $label,
        ];
    }

    private function dokterPayload($item): array
    {
        return [
            'id' => $item->kd_dokter,
            'kd_dokter' => $item->kd_dokter,
            'nm_dokter' => $item->nm_dokter,
            'kd_sps' => $item->kd_sps,
            'nm_sps' => $item->nm_sps,
            'text' => trim($item->kd_dokter.' - '.$item->nm_dokter.' ('.($item->nm_sps ?: 'Umum').')'),
        ];
    }

    private function premiumTypes(): array
    {
        return [
            ['id' => 'kebersamaan', 'label' => 'Kebersamaan', 'status' => 'draft'],
            ['id' => 'jasa_operasi', 'label' => 'Jasa Operasi', 'status' => 'draft'],
            ['id' => 'jasa_rawat_jalan', 'label' => 'Jasa Rawat Jalan', 'status' => 'draft'],
            ['id' => 'jasa_visite', 'label' => 'Jasa Visite', 'status' => 'active'],
            ['id' => 'jasa_poli', 'label' => 'Jasa Poli', 'status' => 'draft'],
            ['id' => 'jasa_igd', 'label' => 'Jasa IGD', 'status' => 'draft'],
            ['id' => 'jasa_ecg', 'label' => 'Jasa ECG', 'status' => 'draft'],
            ['id' => 'konsul_wa', 'label' => 'Konsul WA', 'status' => 'draft'],
            ['id' => 'kehadiran', 'label' => 'Kehadiran', 'status' => 'draft'],
        ];
    }

    private function categoryLabel(?string $category): string
    {
        return self::CATEGORY_DEFAULTS[$category]['label'] ?? 'Dokter';
    }

    private function typeLabel(string $type): string
    {
        return $type === 'bpjs' ? 'BPJS' : 'UMUM';
    }

    private function normalizeSourcePeriodMode(?string $mode): string
    {
        return $mode === generatePremiDokterRepository::SOURCE_PERIOD_PREVIOUS
            ? generatePremiDokterRepository::SOURCE_PERIOD_PREVIOUS
            : generatePremiDokterRepository::SOURCE_PERIOD_CURRENT;
    }

    private function sourcePeriodModeLabel(?string $mode): string
    {
        return $this->normalizeSourcePeriodMode($mode) === generatePremiDokterRepository::SOURCE_PERIOD_PREVIOUS
            ? 'Bulan Sebelumnya'
            : 'Periode Berjalan';
    }

    private function formatRupiah(float|int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
