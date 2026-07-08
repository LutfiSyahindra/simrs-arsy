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
        generatePremiDokterRepository::CATEGORY_KEBERSAMAAN => [
            'label' => 'Dokter Kebersamaan',
            'percent' => 0,
        ],
    ];

    public function __construct(
        protected generatePremiDokterRepository $repository
    ) {}

    public function getResults(
        ?string $periode = null,
        ?string $jenisPelayanan = null,
        ?string $jenisPremiDokter = generatePremiDokterRepository::TYPE_VISITE
    ): Collection
    {
        $jenisPremiDokter = $this->normalizePremiumType($jenisPremiDokter);
        $jenisPelayanan = $jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN
            ? generatePremiDokterRepository::SERVICE_KEBERSAMAAN
            : $jenisPelayanan;

        return $this->repository
            ->getResults($periode, $jenisPelayanan, $jenisPremiDokter)
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
        float $kebersamaanUmumPercent,
        int $kebersamaanBpjsNominal,
        float $kebersamaanBpjsPercent,
        int $kebersamaanDivider,
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
                $kebersamaanUmumPercent,
                $kebersamaanBpjsNominal,
                $kebersamaanBpjsPercent,
                $kebersamaanDivider,
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

    public function getSummary(
        string $periode,
        ?string $jenisPelayanan,
        ?string $jenisPremiDokter = generatePremiDokterRepository::TYPE_VISITE
    ): array
    {
        $config = $this->repository->getConfig();
        $jenisPremiDokter = $this->normalizePremiumType($jenisPremiDokter);
        $jenisPelayanan = $this->normalizeServiceType($jenisPremiDokter, $jenisPelayanan);
        $existing = $this->repository->findByPeriodAndType(
            $periode,
            $jenisPelayanan,
            $jenisPremiDokter
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

        $calculation = $jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN
            ? $this->repository->calculateKebersamaan($periode, $config)
            : $this->repository->calculate($periode, $jenisPelayanan, $config);
        $ready = $this->isReady($jenisPremiDokter, $jenisPelayanan, $calculation);

        return [
            ...$this->summaryPayload($calculation),
            'ready' => $ready,
            'readiness_message' => $this->readinessMessage($jenisPremiDokter, $jenisPelayanan, $calculation),
            'readiness_steps' => $this->readinessSteps($jenisPremiDokter, $jenisPelayanan, $calculation),
            'is_generated' => (bool) $existing,
            'is_locked' => false,
            'locked_at' => optional($existing?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $existing?->lockedBy?->name,
            'config' => $this->configPayload($config),
        ];
    }

    public function generate(
        string $periode,
        ?string $jenisPelayanan,
        ?string $jenisPremiDokter = generatePremiDokterRepository::TYPE_VISITE
    ): array
    {
        $config = $this->repository->getConfig();
        $jenisPremiDokter = $this->normalizePremiumType($jenisPremiDokter);
        $jenisPelayanan = $this->normalizeServiceType($jenisPremiDokter, $jenisPelayanan);
        $calculation = $jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN
            ? $this->repository->calculateKebersamaan($periode, $config)
            : $this->repository->calculate($periode, $jenisPelayanan, $config);

        if (! $this->isReady($jenisPremiDokter, $jenisPelayanan, $calculation)) {
            throw ValidationException::withMessages([
                'periode' => $this->readinessMessage($jenisPremiDokter, $jenisPelayanan, $calculation),
            ]);
        }

        $result = $this->repository->saveResult($periode, $jenisPelayanan, $config, $calculation, $jenisPremiDokter);

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
            ->groupBy(fn (array $row) => $row['kategori'].'|'.$row['kd_dokter'])
            ->filter(fn (Collection $rows) => $rows->count() > 1)
            ->keys();

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'doctor_configs' => 'Dokter tidak boleh dipilih lebih dari satu kali pada kategori yang sama: '.$duplicates->implode(', '),
            ]);
        }

        $visiteDuplicates = $configs
            ->reject(fn (array $row) => $row['kategori'] === generatePremiDokterRepository::CATEGORY_KEBERSAMAAN)
            ->groupBy('kd_dokter')
            ->filter(fn (Collection $rows) => $rows->count() > 1)
            ->keys();

        if ($visiteDuplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'doctor_configs' => 'Dokter visite tidak boleh masuk lebih dari satu kategori: '.$visiteDuplicates->implode(', '),
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

    private function isReady(string $jenisPremiDokter, string $jenisPelayanan, array $calculation): bool
    {
        if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
            return false;
        }

        if ((int) $calculation['jumlah_mapping_tindakan'] <= 0) {
            return false;
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN) {
            return (float) $calculation['total_grand'] > 0
                && (int) $calculation['jumlah_dokter'] === (int) $calculation['kebersamaan_divider'];
        }

        if ($jenisPelayanan === 'bpjs' && (int) $calculation['visite_bpjs_nominal'] <= 0) {
            return false;
        }

        return (float) $calculation['total_premi'] > 0
            && (int) $calculation['jumlah_dokter'] > 0;
    }

    private function readinessMessage(string $jenisPremiDokter, string $jenisPelayanan, array $calculation): string
    {
        if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
            return 'Pilih mapping tindakan visite dokter pada konfigurasi.';
        }

        if ((int) $calculation['jumlah_mapping_tindakan'] <= 0) {
            return 'Mapping tindakan visite dokter yang dipilih belum memiliki rincian RAJAL/RANAP.';
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN) {
            if ((int) ($calculation['kebersamaan_bpjs_nominal'] ?? 0) <= 0) {
                return 'Isi nominal BPJS kebersamaan pada konfigurasi.';
            }

            if ((float) ($calculation['kebersamaan_umum_percent'] ?? 0) <= 0
                && (float) ($calculation['kebersamaan_bpjs_percent'] ?? 0) <= 0) {
                return 'Isi minimal salah satu persen kebersamaan UMUM atau BPJS pada konfigurasi.';
            }

            if ((int) $calculation['jumlah_dokter'] <= 0) {
                return 'Pilih dokter penerima Kebersamaan pada konfigurasi.';
            }

            if ((int) $calculation['jumlah_dokter'] !== (int) $calculation['kebersamaan_divider']) {
                return 'Jumlah dokter Kebersamaan harus sama dengan pembagi: '
                    .(int) $calculation['kebersamaan_divider'].' dokter.';
            }

            if ((float) $calculation['total_grand'] <= 0) {
                return 'Data ditemukan, tetapi grand total Kebersamaan masih Rp 0.';
            }

            return 'Siap generate Premi Dokter Kebersamaan.';
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

    private function readinessSteps(string $jenisPremiDokter, string $jenisPelayanan, array $calculation): array
    {
        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN) {
            return [
                [
                    'label' => 'Periode sumber data',
                    'status' => 'success',
                    'value' => $calculation['source_period_text'] ?? '-',
                ],
                [
                    'label' => 'Formula UMUM',
                    'status' => (float) ($calculation['kebersamaan_visite_umum_total'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => $this->formatRupiah((float) ($calculation['kebersamaan_visite_umum_total_premi'] ?? 0))
                        .' x '.$this->formatPercent((float) ($calculation['kebersamaan_umum_percent'] ?? 0)),
                ],
                [
                    'label' => 'Formula BPJS',
                    'status' => ((int) ($calculation['kebersamaan_bpjs_nominal'] ?? 0) > 0
                        && (float) ($calculation['kebersamaan_bpjs_percent'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['kebersamaan_visite_bpjs_jumlah_transaksi'] ?? 0)
                        .' transaksi x '.$this->formatRupiah((int) ($calculation['kebersamaan_bpjs_nominal'] ?? 0))
                        .' x '.$this->formatPercent((float) ($calculation['kebersamaan_bpjs_percent'] ?? 0)),
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => (int) $calculation['jumlah_dokter'] === (int) $calculation['kebersamaan_divider']
                        ? 'success'
                        : 'warning',
                    'value' => (int) $calculation['jumlah_dokter'].' / '
                        .(int) $calculation['kebersamaan_divider'].' dokter',
                ],
                [
                    'label' => 'Pembagian',
                    'status' => (float) $calculation['total_grand'] > 0 ? 'success' : 'warning',
                    'value' => $this->formatRupiah((float) $calculation['total_grand'])
                        .' / '.(int) $calculation['kebersamaan_divider']
                        .' = '.$this->formatRupiah((float) $calculation['kebersamaan_allocation_per_doctor']),
                ],
            ];
        }

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
                'value' => data_get($result->config_snapshot, 'source_period_text') ?: $result->source_periode.' / '.$this->sourcePeriodModeLabel(
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
            'source_period_text' => $calculation['source_period_text'] ?? null,
            'source_tgl_awal' => $calculation['source_tgl_awal'],
            'source_tgl_akhir' => $calculation['source_tgl_akhir'],
            'jenis_premi_dokter' => $calculation['jenis_premi_dokter'],
            'jenis_premi_dokter_label' => $this->premiumTypeLabel($calculation['jenis_premi_dokter']),
            'jenis_pelayanan' => $calculation['jenis_pelayanan'],
            'jenis_pelayanan_label' => $this->typeLabel($calculation['jenis_pelayanan']),
            'visite_umum_percent' => $calculation['visite_umum_percent'],
            'visite_bpjs_percent' => $calculation['visite_bpjs_percent'],
            'visite_bpjs_nominal' => $calculation['visite_bpjs_nominal'],
            'kebersamaan_umum_percent' => $calculation['kebersamaan_umum_percent'] ?? 30,
            'kebersamaan_bpjs_nominal' => $calculation['kebersamaan_bpjs_nominal'] ?? 40000,
            'kebersamaan_bpjs_percent' => $calculation['kebersamaan_bpjs_percent'] ?? 30,
            'kebersamaan_divider' => $calculation['kebersamaan_divider'] ?? 4,
            'kebersamaan_allocation_percent' => $calculation['kebersamaan_allocation_percent'] ?? 0,
            'kebersamaan_allocation_per_doctor' => $calculation['kebersamaan_allocation_per_doctor'] ?? 0,
            'kebersamaan_visite_umum_total_premi' => $calculation['kebersamaan_visite_umum_total_premi'] ?? 0,
            'kebersamaan_visite_umum_total' => $calculation['kebersamaan_visite_umum_total'] ?? 0,
            'kebersamaan_visite_bpjs_jumlah_transaksi' => $calculation['kebersamaan_visite_bpjs_jumlah_transaksi'] ?? 0,
            'kebersamaan_visite_bpjs_jumlah_tindakan' => $calculation['kebersamaan_visite_bpjs_jumlah_tindakan'] ?? 0,
            'kebersamaan_visite_bpjs_dasar_hitung' => $calculation['kebersamaan_visite_bpjs_dasar_hitung'] ?? 0,
            'kebersamaan_visite_bpjs_total' => $calculation['kebersamaan_visite_bpjs_total'] ?? 0,
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
            'source_period_text' => data_get($result->config_snapshot, 'source_period_text'),
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d') ?? $result->source_tgl_awal,
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d') ?? $result->source_tgl_akhir,
            'jenis_premi_dokter' => $result->jenis_premi_dokter,
            'jenis_premi_dokter_label' => $this->premiumTypeLabel($result->jenis_premi_dokter),
            'jenis_pelayanan' => $result->jenis_pelayanan,
            'jenis_pelayanan_label' => $this->typeLabel($result->jenis_pelayanan),
            'visite_umum_percent' => (float) $result->visite_umum_percent,
            'visite_bpjs_percent' => (float) $result->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $result->visite_bpjs_nominal,
            'kebersamaan_umum_percent' => (float) data_get($result->config_snapshot, 'kebersamaan_umum_percent', 30),
            'kebersamaan_bpjs_nominal' => (int) data_get($result->config_snapshot, 'kebersamaan_bpjs_nominal', 40000),
            'kebersamaan_bpjs_percent' => (float) data_get($result->config_snapshot, 'kebersamaan_bpjs_percent', 30),
            'kebersamaan_divider' => (int) data_get($result->config_snapshot, 'kebersamaan_divider', 4),
            'kebersamaan_allocation_percent' => (float) data_get($result->config_snapshot, 'kebersamaan_allocation_percent', 0),
            'kebersamaan_allocation_per_doctor' => (float) data_get($result->config_snapshot, 'kebersamaan_allocation_per_doctor', 0),
            'kebersamaan_visite_umum_total_premi' => (float) data_get($result->config_snapshot, 'kebersamaan_visite_umum_total_premi', 0),
            'kebersamaan_visite_umum_total' => (float) data_get($result->config_snapshot, 'kebersamaan_visite_umum_total', 0),
            'kebersamaan_visite_bpjs_jumlah_transaksi' => (int) data_get(
                $result->config_snapshot,
                'kebersamaan_visite_bpjs_jumlah_transaksi',
                data_get($result->config_snapshot, 'kebersamaan_visite_bpjs_jumlah_tindakan', 0)
            ),
            'kebersamaan_visite_bpjs_jumlah_tindakan' => (int) data_get($result->config_snapshot, 'kebersamaan_visite_bpjs_jumlah_tindakan', 0),
            'kebersamaan_visite_bpjs_dasar_hitung' => (float) data_get($result->config_snapshot, 'kebersamaan_visite_bpjs_dasar_hitung', 0),
            'kebersamaan_visite_bpjs_total' => (float) data_get($result->config_snapshot, 'kebersamaan_visite_bpjs_total', 0),
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
            'kebersamaan_umum_percent' => (float) ($config->kebersamaan_umum_percent ?? 30),
            'kebersamaan_bpjs_nominal' => (int) ($config->kebersamaan_bpjs_nominal ?? 40000),
            'kebersamaan_bpjs_percent' => (float) ($config->kebersamaan_bpjs_percent ?? 30),
            'kebersamaan_divider' => (int) ($config->kebersamaan_divider ?? 4),
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
            ['id' => generatePremiDokterRepository::TYPE_KEBERSAMAAN, 'label' => 'Kebersamaan', 'status' => 'active'],
            ['id' => 'jasa_operasi', 'label' => 'Jasa Operasi', 'status' => 'draft'],
            ['id' => 'jasa_rawat_jalan', 'label' => 'Jasa Rawat Jalan', 'status' => 'draft'],
            ['id' => generatePremiDokterRepository::TYPE_VISITE, 'label' => 'Jasa Visite', 'status' => 'active'],
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

    private function premiumTypeLabel(?string $type): string
    {
        return $type === generatePremiDokterRepository::TYPE_KEBERSAMAAN
            ? 'Kebersamaan'
            : 'Jasa Visite';
    }

    private function typeLabel(string $type): string
    {
        if ($type === generatePremiDokterRepository::SERVICE_KEBERSAMAAN) {
            return 'Tanpa Jenis';
        }

        return $type === 'bpjs' ? 'BPJS' : 'UMUM';
    }

    private function normalizePremiumType(?string $type): string
    {
        return $type === generatePremiDokterRepository::TYPE_KEBERSAMAAN
            ? generatePremiDokterRepository::TYPE_KEBERSAMAAN
            : generatePremiDokterRepository::TYPE_VISITE;
    }

    private function normalizeServiceType(string $premiumType, ?string $serviceType): string
    {
        if ($premiumType === generatePremiDokterRepository::TYPE_KEBERSAMAAN) {
            return generatePremiDokterRepository::SERVICE_KEBERSAMAAN;
        }

        return $serviceType === 'bpjs' ? 'bpjs' : 'umum';
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

    private function formatPercent(float|int $value): string
    {
        return number_format((float) $value, 2, ',', '.').'%';
    }
}
