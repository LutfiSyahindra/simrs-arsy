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
        generatePremiDokterRepository::CATEGORY_OPERASI => [
            'label' => 'Dokter Operasi',
            'percent' => 100,
        ],
        generatePremiDokterRepository::CATEGORY_RAWAT_JALAN => [
            'label' => 'Dokter Rawat Jalan',
            'percent' => 0,
        ],
        generatePremiDokterRepository::CATEGORY_POLI => [
            'label' => 'Dokter Sumber Poli',
            'percent' => 0,
        ],
        generatePremiDokterRepository::CATEGORY_ECG => [
            'label' => 'Dokter ECG',
            'percent' => 0,
        ],
        generatePremiDokterRepository::CATEGORY_KONSUL_WA => [
            'label' => 'Dokter Konsul WA',
            'percent' => 0,
        ],
    ];

    private const RAWAT_JALAN_SPECIAL_GROUPS = [
        generatePremiDokterRepository::CATEGORY_RAWAT_JALAN_SPECIAL_45000 => [
            'label' => 'Dokter Khusus 45.000',
            'nominal' => 45000,
        ],
        generatePremiDokterRepository::CATEGORY_RAWAT_JALAN_SPECIAL_72000 => [
            'label' => 'Dokter Khusus 72.000',
            'nominal' => 72000,
        ],
    ];

    public function __construct(
        protected generatePremiDokterRepository $repository
    ) {}

    public function getResults(
        ?string $periode = null,
        ?string $jenisPelayanan = null,
        ?string $jenisPremiDokter = generatePremiDokterRepository::TYPE_VISITE
    ): Collection {
        $jenisPremiDokter = $this->normalizePremiumType($jenisPremiDokter);
        $jenisPelayanan = $this->normalizeServiceType($jenisPremiDokter, $jenisPelayanan);

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

    public function poliSourceTableNames(): array
    {
        return $this->repository
            ->poliSourceTableOptions()
            ->pluck('source_table')
            ->values()
            ->all();
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
        bool $kebersamaanOnlyUmum,
        int $ecgNominal,
        int $ecgDivider,
        string $ecgDistributionMode,
        float $poliPercent,
        string $poliDistributionMode,
        int $konsulWaNominal,
        array $jnsTindakanIds,
        array $doctorConfigs,
        array $ecgJnsTindakanIds = [],
        array $poliJnsTindakanIds = [],
        array $poliFilterDoctorCodes = [],
        array $poliFilterSourceTables = [],
        array $poliFilterJnsTindakanIds = [],
        array $konsulWaJnsTindakanIds = [],
        array $rawatJalanMappingConfigs = [],
        array $rawatJalanSpecialDoctors = []
    ): array {
        $jnsTindakanIds = collect($jnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->assertJenisTindakanExists($jnsTindakanIds);
        $ecgJnsTindakanIds = collect($ecgJnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->assertJenisTindakanExists($ecgJnsTindakanIds);
        $poliJnsTindakanIds = collect($poliJnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->assertJenisTindakanExists($poliJnsTindakanIds);
        $poliFilterJnsTindakanIds = collect($poliFilterJnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->assertJenisTindakanExists($poliFilterJnsTindakanIds);
        $invalidPoliFilterTindakanIds = collect($poliFilterJnsTindakanIds)
            ->reject(fn ($id) => collect($poliJnsTindakanIds)->contains((int) $id))
            ->values();

        if ($invalidPoliFilterTindakanIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'poli_filter_tindakan_ids' => 'Tindakan filter dokter Poli harus dipilih juga pada Mapping Tindakan Poli.',
            ]);
        }

        $konsulWaJnsTindakanIds = collect($konsulWaJnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->assertJenisTindakanExists($konsulWaJnsTindakanIds);

        $doctorRows = $this->normalizeDoctorConfigs($doctorConfigs);
        $rawatJalanMappingRows = $this->normalizeRawatJalanMappingConfigs($rawatJalanMappingConfigs);
        $rawatJalanSpecialDoctorRows = $this->normalizeRawatJalanSpecialDoctorConfigs(
            $rawatJalanSpecialDoctors,
            $doctorRows
        );
        $poliFilterDoctorRows = $this->normalizePoliFilterDoctorCodes($poliFilterDoctorCodes);
        $poliFilterSourceRows = $this->normalizePoliFilterSourceTables($poliFilterSourceTables);

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
                $kebersamaanOnlyUmum,
                $ecgNominal,
                $ecgDivider,
                $this->normalizeEcgDistributionMode($ecgDistributionMode),
                $poliPercent,
                $this->normalizeEcgDistributionMode($poliDistributionMode),
                $konsulWaNominal,
                $jnsTindakanIds,
                $doctorRows,
                $ecgJnsTindakanIds,
                $poliJnsTindakanIds,
                $poliFilterDoctorRows,
                $poliFilterSourceRows,
                $poliFilterJnsTindakanIds,
                $konsulWaJnsTindakanIds,
                $rawatJalanMappingRows,
                $rawatJalanSpecialDoctorRows
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
        ?string $jenisPremiDokter = generatePremiDokterRepository::TYPE_VISITE,
        ?int $nominalOperasi = null
    ): array {
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

        $calculation = match ($jenisPremiDokter) {
            generatePremiDokterRepository::TYPE_KEBERSAMAAN => $this->repository->calculateKebersamaan($periode, $config),
            generatePremiDokterRepository::TYPE_OPERASI => $this->repository->calculateOperasi($periode, $config, (int) ($nominalOperasi ?? 0)),
            generatePremiDokterRepository::TYPE_RAWAT_JALAN => $this->repository->calculateRawatJalan($periode, $jenisPelayanan, $config),
            generatePremiDokterRepository::TYPE_POLI => $this->repository->calculatePoli($periode, $jenisPelayanan, $config),
            generatePremiDokterRepository::TYPE_ECG => $this->repository->calculateEcg($periode, $jenisPelayanan, $config),
            generatePremiDokterRepository::TYPE_KONSUL_WA => $this->repository->calculateKonsulWa($periode, $jenisPelayanan, $config),
            default => $this->repository->calculate($periode, $jenisPelayanan, $config),
        };
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
        ?string $jenisPremiDokter = generatePremiDokterRepository::TYPE_VISITE,
        ?int $nominalOperasi = null
    ): array {
        $config = $this->repository->getConfig();
        $jenisPremiDokter = $this->normalizePremiumType($jenisPremiDokter);
        $jenisPelayanan = $this->normalizeServiceType($jenisPremiDokter, $jenisPelayanan);
        $calculation = match ($jenisPremiDokter) {
            generatePremiDokterRepository::TYPE_KEBERSAMAAN => $this->repository->calculateKebersamaan($periode, $config),
            generatePremiDokterRepository::TYPE_OPERASI => $this->repository->calculateOperasi($periode, $config, (int) ($nominalOperasi ?? 0)),
            generatePremiDokterRepository::TYPE_RAWAT_JALAN => $this->repository->calculateRawatJalan($periode, $jenisPelayanan, $config),
            generatePremiDokterRepository::TYPE_POLI => $this->repository->calculatePoli($periode, $jenisPelayanan, $config),
            generatePremiDokterRepository::TYPE_ECG => $this->repository->calculateEcg($periode, $jenisPelayanan, $config),
            generatePremiDokterRepository::TYPE_KONSUL_WA => $this->repository->calculateKonsulWa($periode, $jenisPelayanan, $config),
            default => $this->repository->calculate($periode, $jenisPelayanan, $config),
        };

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
            ->whereIn('kategori', [
                generatePremiDokterRepository::CATEGORY_UMUM,
                generatePremiDokterRepository::CATEGORY_SPESIALIS_65,
                generatePremiDokterRepository::CATEGORY_SPESIALIS_80,
            ])
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

    private function normalizeRawatJalanMappingConfigs(array $mappingConfigs): array
    {
        $configs = collect($mappingConfigs)
            ->map(function (array $item) {
                $jenisTindakanId = (int) ($item['jnsTindakan_id'] ?? $item['id'] ?? 0);
                $type = (string) ($item['multiplier_type'] ?? generatePremiDokterRepository::MULTIPLIER_NOMINAL);
                $value = (float) ($item['multiplier_value'] ?? 0);

                if (! in_array($type, [
                    generatePremiDokterRepository::MULTIPLIER_NOMINAL,
                    generatePremiDokterRepository::MULTIPLIER_PERCENT,
                ], true)) {
                    $type = generatePremiDokterRepository::MULTIPLIER_NOMINAL;
                }

                return [
                    'jnsTindakan_id' => $jenisTindakanId,
                    'multiplier_type' => $type,
                    'multiplier_value' => $value,
                ];
            })
            ->filter(fn (array $item) => $item['jnsTindakan_id'] > 0)
            ->values();

        $duplicates = $configs
            ->groupBy('jnsTindakan_id')
            ->filter(fn (Collection $rows) => $rows->count() > 1)
            ->keys();

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'rawat_jalan_mapping_configs' => 'Mapping tindakan Rawat Jalan tidak boleh dipilih lebih dari satu kali: '.$duplicates->implode(', '),
            ]);
        }

        $invalidPercent = $configs
            ->filter(fn (array $item) => $item['multiplier_type'] === generatePremiDokterRepository::MULTIPLIER_PERCENT
                && ((float) $item['multiplier_value'] < 0 || (float) $item['multiplier_value'] > 100))
            ->values();

        if ($invalidPercent->isNotEmpty()) {
            throw ValidationException::withMessages([
                'rawat_jalan_mapping_configs' => 'Pengkali persen Rawat Jalan harus berada di antara 0 sampai 100.',
            ]);
        }

        $invalidNominal = $configs
            ->filter(fn (array $item) => $item['multiplier_type'] === generatePremiDokterRepository::MULTIPLIER_NOMINAL
                && (float) $item['multiplier_value'] < 0)
            ->values();

        if ($invalidNominal->isNotEmpty()) {
            throw ValidationException::withMessages([
                'rawat_jalan_mapping_configs' => 'Pengkali nominal Rawat Jalan tidak boleh kurang dari 0.',
            ]);
        }

        $this->assertJenisTindakanExists($configs->pluck('jnsTindakan_id')->all());

        return $configs
            ->map(fn (array $item) => [
                'jnsTindakan_id' => (int) $item['jnsTindakan_id'],
                'multiplier_type' => $item['multiplier_type'],
                'multiplier_value' => round((float) $item['multiplier_value'], 4),
            ])
            ->values()
            ->all();
    }

    private function normalizePoliFilterDoctorCodes(array $doctorCodes): array
    {
        $codes = collect($doctorCodes)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        $doctorMasters = $this->repository
            ->getDoctorsByCodes($codes->all())
            ->keyBy('kd_dokter');
        $missing = $codes
            ->reject(fn ($code) => $doctorMasters->has($code))
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'poli_filter_doctor_codes' => 'Dokter filter Poli tidak ditemukan: '.$missing->implode(', '),
            ]);
        }

        return $codes
            ->map(function (string $code) use ($doctorMasters) {
                $doctor = $doctorMasters->get($code);

                return [
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'kd_sps' => $doctor->kd_sps,
                    'nm_sps' => $doctor->nm_sps,
                ];
            })
            ->values()
            ->all();
    }

    private function normalizePoliFilterSourceTables(array $sourceTables): array
    {
        $options = $this->repository
            ->poliSourceTableOptions()
            ->keyBy('source_table');
        $tables = collect($sourceTables)
            ->map(fn ($table) => trim((string) $table))
            ->filter()
            ->unique()
            ->values();

        if ($tables->isEmpty()) {
            return [];
        }

        $invalid = $tables
            ->reject(fn ($table) => $options->has($table))
            ->values();

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'poli_filter_source_tables' => 'Sumber data Poli tidak valid: '.$invalid->implode(', '),
            ]);
        }

        return $tables
            ->map(function (string $table) use ($options) {
                $source = $options->get($table);

                return [
                    'source_table' => $source->source_table,
                    'source_label' => $source->source_label,
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeRawatJalanSpecialDoctorConfigs(array $specialDoctorConfigs, array $doctorRows): array
    {
        $configs = collect($specialDoctorConfigs)
            ->map(function (array $item) {
                $groupKey = (string) ($item['group_key'] ?? '');
                $group = self::RAWAT_JALAN_SPECIAL_GROUPS[$groupKey] ?? null;

                if (! $group) {
                    return null;
                }

                return [
                    'group_key' => $groupKey,
                    'kd_dokter' => trim((string) ($item['kd_dokter'] ?? '')),
                    'nominal' => (int) ($item['nominal'] ?? $group['nominal']),
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
                'rawat_jalan_special_doctors' => 'Dokter khusus Rawat Jalan hanya boleh masuk satu grup: '.$duplicates->implode(', '),
            ]);
        }

        $normalRawatJalanDoctorCodes = collect($doctorRows)
            ->where('kategori', generatePremiDokterRepository::CATEGORY_RAWAT_JALAN)
            ->pluck('kd_dokter')
            ->map(fn ($code) => (string) $code);
        $overlaps = $configs
            ->pluck('kd_dokter')
            ->filter(fn ($code) => $normalRawatJalanDoctorCodes->contains((string) $code))
            ->values();

        if ($overlaps->isNotEmpty()) {
            throw ValidationException::withMessages([
                'rawat_jalan_special_doctors' => 'Dokter khusus Rawat Jalan tidak boleh dipilih juga sebagai Dokter Rawat Jalan normal: '.$overlaps->implode(', '),
            ]);
        }

        $invalidNominal = $configs
            ->filter(fn (array $item) => (int) $item['nominal'] < 0)
            ->values();

        if ($invalidNominal->isNotEmpty()) {
            throw ValidationException::withMessages([
                'rawat_jalan_special_doctors' => 'Nominal dokter khusus Rawat Jalan tidak boleh kurang dari 0.',
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
                'rawat_jalan_special_doctors' => 'Dokter khusus Rawat Jalan tidak ditemukan: '.$missing->implode(', '),
            ]);
        }

        return $configs
            ->map(function (array $item) use ($doctorMasters) {
                $doctor = $doctorMasters->get($item['kd_dokter']);

                return [
                    'group_key' => $item['group_key'],
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'kd_sps' => $doctor->kd_sps,
                    'nm_sps' => $doctor->nm_sps,
                    'nominal' => (int) $item['nominal'],
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
                'mapping_tindakan_ids' => 'Master Mapping Tindakan tidak ditemukan: '.$missing->implode(', '),
            ]);
        }
    }

    private function isReady(string $jenisPremiDokter, string $jenisPelayanan, array $calculation): bool
    {
        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_OPERASI) {
            return (int) ($calculation['nominal_operasi'] ?? 0) > 0
                && (int) $calculation['jumlah_dokter'] > 0
                && (float) $calculation['total_premi'] > 0;
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_RAWAT_JALAN) {
            return (int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0
                && ((int) ($calculation['rawat_jalan_doctor_config_count'] ?? 0)
                    + (int) ($calculation['rawat_jalan_special_doctor_count'] ?? 0)) > 0
                && (int) $calculation['jumlah_dokter'] > 0
                && (float) $calculation['total_premi'] > 0;
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_ECG) {
            return (int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0
                && (int) ($calculation['ecg_doctor_config_count'] ?? 0) > 0
                && (int) ($calculation['ecg_nominal'] ?? 0) > 0
                && (int) ($calculation['ecg_divider'] ?? 0) > 0
                && (int) ($calculation['jumlah_transaksi'] ?? 0) > 0
                && (float) ($calculation['total_grand'] ?? 0) > 0
                && (float) ($calculation['total_premi'] ?? 0) > 0;
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_POLI) {
            return (int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0
                && (int) ($calculation['poli_doctor_config_count'] ?? 0) > 0
                && (int) ($calculation['poli_filter_doctor_count'] ?? 0) > 0
                && (int) ($calculation['poli_filter_source_count'] ?? 0) > 0
                && (int) ($calculation['poli_filter_tindakan_count'] ?? 0) > 0
                && (float) ($calculation['poli_percent'] ?? 0) > 0
                && (int) ($calculation['jumlah_transaksi'] ?? 0) > 0
                && (float) ($calculation['total_grand'] ?? 0) > 0
                && (float) ($calculation['total_premi'] ?? 0) > 0;
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KONSUL_WA) {
            return (int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0
                && (int) ($calculation['konsul_wa_doctor_config_count'] ?? 0) > 0
                && (int) ($calculation['konsul_wa_nominal'] ?? 0) > 0
                && (int) ($calculation['jumlah_transaksi'] ?? 0) > 0
                && (int) ($calculation['jumlah_dokter'] ?? 0) > 0
                && (float) ($calculation['total_premi'] ?? 0) > 0;
        }

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
        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_OPERASI) {
            if ((int) ($calculation['nominal_operasi'] ?? 0) <= 0) {
                return 'Input nominal Jasa Operasi sebelum generate.';
            }

            if ((int) $calculation['jumlah_dokter'] <= 0) {
                return 'Pilih dokter penerima Jasa Operasi pada konfigurasi.';
            }

            if ((float) ($calculation['operasi_total_percent'] ?? 0) <= 0) {
                return 'Isi persentase dokter penerima Jasa Operasi pada konfigurasi.';
            }

            return 'Siap generate premi dokter Jasa Operasi.';
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_RAWAT_JALAN) {
            if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
                return 'Pilih mapping tindakan Jasa Rawat Jalan pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_mapping_tindakan'] ?? 0) <= 0) {
                return 'Mapping tindakan Jasa Rawat Jalan yang dipilih belum memiliki rincian RAJAL/RANAP.';
            }

            if (((int) ($calculation['rawat_jalan_doctor_config_count'] ?? 0)
                + (int) ($calculation['rawat_jalan_special_doctor_count'] ?? 0)) <= 0) {
                return 'Pilih dokter penerima Jasa Rawat Jalan pada konfigurasi.';
            }

            if ((int) $calculation['jumlah_dokter'] <= 0) {
                return 'Belum ada dokter Rawat Jalan terkonfigurasi yang memiliki data pada periode ini.';
            }

            if ((float) $calculation['total_premi'] <= 0) {
                return 'Data Jasa Rawat Jalan ditemukan, tetapi total premi masih Rp 0. Cek pengkali mapping atau nominal dokter khusus.';
            }

            return 'Siap generate premi dokter Jasa Rawat Jalan.';
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_ECG) {
            if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
                return 'Pilih mapping tindakan Jasa ECG pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_mapping_tindakan'] ?? 0) <= 0) {
                return 'Mapping tindakan Jasa ECG yang dipilih belum memiliki rincian RAJAL/RANAP.';
            }

            if ((int) ($calculation['ecg_doctor_config_count'] ?? 0) <= 0) {
                return 'Pilih dokter penerima Jasa ECG pada konfigurasi.';
            }

            if ((int) ($calculation['ecg_nominal'] ?? 0) <= 0) {
                return 'Isi nominal per data ECG pada konfigurasi.';
            }

            if ((int) ($calculation['ecg_divider'] ?? 0) <= 0) {
                return 'Isi pembagi Jasa ECG pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_transaksi'] ?? 0) <= 0) {
                return 'Belum ada data ECG pada periode sumber ini.';
            }

            if ((float) ($calculation['total_grand'] ?? 0) <= 0) {
                return 'Data ECG ditemukan, tetapi grand total masih Rp 0.';
            }

            return 'Siap generate premi dokter Jasa ECG '.$this->typeLabel($jenisPelayanan).'.';
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_POLI) {
            if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
                return 'Pilih mapping tindakan Jasa Poli pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_mapping_tindakan'] ?? 0) <= 0) {
                return 'Mapping tindakan Jasa Poli yang dipilih belum memiliki rincian RAJAL/RANAP.';
            }

            if ((int) ($calculation['poli_doctor_config_count'] ?? 0) <= 0) {
                return 'Pilih dokter penerima Jasa Poli pada konfigurasi.';
            }

            if ((int) ($calculation['poli_filter_doctor_count'] ?? 0) <= 0) {
                return 'Pilih dokter filter sumber data Jasa Poli pada konfigurasi.';
            }

            if ((int) ($calculation['poli_filter_source_count'] ?? 0) <= 0) {
                return 'Pilih sumber tabel rawat Jasa Poli pada konfigurasi.';
            }

            if ((int) ($calculation['poli_filter_tindakan_count'] ?? 0) <= 0) {
                return 'Pilih tindakan Poli yang akan difilter dokter pada konfigurasi Poli.';
            }

            if ((float) ($calculation['poli_percent'] ?? 0) <= 0) {
                return 'Isi persentase Jasa Poli pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_transaksi'] ?? 0) <= 0) {
                return 'Belum ada data Poli sesuai dokter filter dan tindakan filter pada periode sumber ini.';
            }

            if ((float) ($calculation['total_grand'] ?? 0) <= 0) {
                return 'Data Poli ditemukan, tetapi grand total masih Rp 0.';
            }

            return 'Siap generate premi dokter Jasa Poli '.$this->typeLabel($jenisPelayanan).'.';
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KONSUL_WA) {
            if ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) <= 0) {
                return 'Pilih mapping tindakan Konsul WA pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_mapping_tindakan'] ?? 0) <= 0) {
                return 'Mapping tindakan Konsul WA yang dipilih belum memiliki rincian RAJAL/RANAP.';
            }

            if ((int) ($calculation['konsul_wa_doctor_config_count'] ?? 0) <= 0) {
                return 'Pilih dokter penerima Konsul WA pada konfigurasi.';
            }

            if ((int) ($calculation['konsul_wa_nominal'] ?? 0) <= 0) {
                return 'Isi nominal per data Konsul WA pada konfigurasi.';
            }

            if ((int) ($calculation['jumlah_transaksi'] ?? 0) <= 0) {
                return 'Belum ada data Konsul WA sesuai dokter dan mapping pada periode sumber ini.';
            }

            if ((int) ($calculation['jumlah_dokter'] ?? 0) <= 0) {
                return 'Belum ada dokter Konsul WA terkonfigurasi yang memiliki data pada periode ini.';
            }

            return 'Siap generate premi dokter Konsul WA '.$this->typeLabel($jenisPelayanan).'.';
        }

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
        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_OPERASI) {
            return [
                [
                    'label' => 'Nominal operasi',
                    'status' => (int) ($calculation['nominal_operasi'] ?? 0) > 0 ? 'success' : 'danger',
                    'value' => $this->formatRupiah((int) ($calculation['nominal_operasi'] ?? 0)),
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => (int) $calculation['jumlah_dokter'] > 0 ? 'success' : 'warning',
                    'value' => (int) $calculation['jumlah_dokter'].' dokter',
                ],
                [
                    'label' => 'Total persen',
                    'status' => (float) ($calculation['operasi_total_percent'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => $this->formatPercent((float) ($calculation['operasi_total_percent'] ?? 0)),
                ],
                [
                    'label' => 'Total diterima dokter',
                    'status' => (float) $calculation['total_premi'] > 0 ? 'success' : 'warning',
                    'value' => $this->formatRupiah((float) $calculation['total_premi']),
                ],
            ];
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_RAWAT_JALAN) {
            $doctorConfigCount = (int) ($calculation['rawat_jalan_doctor_config_count'] ?? 0)
                + (int) ($calculation['rawat_jalan_special_doctor_count'] ?? 0);

            return [
                [
                    'label' => 'Jenis pelayanan',
                    'status' => 'success',
                    'value' => $this->typeLabel($jenisPelayanan).' / '.($calculation['source_period_text'] ?? '-'),
                ],
                [
                    'label' => 'Mapping rawat jalan',
                    'status' => ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                        && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['jumlah_jenis_tindakan'] ?? 0).' jenis / '
                        .(int) ($calculation['jumlah_mapping_tindakan'] ?? 0).' tindakan',
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => $doctorConfigCount > 0 ? 'success' : 'warning',
                    'value' => $doctorConfigCount.' dokter konfigurasi',
                ],
                [
                    'label' => 'Transaksi',
                    'status' => (int) ($calculation['jumlah_transaksi'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => (int) ($calculation['jumlah_transaksi'] ?? 0).' data / '
                        .(int) ($calculation['jumlah_pasien'] ?? 0).' pasien',
                ],
                [
                    'label' => 'Total premi',
                    'status' => (float) ($calculation['total_premi'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => $this->formatRupiah((float) ($calculation['total_premi'] ?? 0)),
                ],
            ];
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_ECG) {
            return [
                [
                    'label' => 'Jenis pelayanan',
                    'status' => 'success',
                    'value' => $this->typeLabel($jenisPelayanan).' / '.($calculation['source_period_text'] ?? '-'),
                ],
                [
                    'label' => 'Mapping ECG',
                    'status' => ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                        && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['jumlah_jenis_tindakan'] ?? 0).' jenis / '
                        .(int) ($calculation['jumlah_mapping_tindakan'] ?? 0).' tindakan',
                ],
                [
                    'label' => 'Formula',
                    'status' => ((int) ($calculation['ecg_nominal'] ?? 0) > 0
                        && (int) ($calculation['ecg_divider'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['jumlah_transaksi'] ?? 0).' data x '
                        .$this->formatRupiah((int) ($calculation['ecg_nominal'] ?? 0))
                        .' / '.(int) ($calculation['ecg_divider'] ?? 0),
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => (int) ($calculation['ecg_doctor_config_count'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => (int) ($calculation['ecg_doctor_config_count'] ?? 0).' dokter konfigurasi',
                ],
                [
                    'label' => 'Pembagian',
                    'status' => (float) ($calculation['total_grand'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => ($calculation['ecg_distribution_mode_label'] ?? $this->ecgDistributionModeLabel(null))
                        .' - '.$this->formatRupiah((float) ($calculation['ecg_allocation_per_doctor'] ?? 0))
                        .' per dokter',
                ],
            ];
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_POLI) {
            return [
                [
                    'label' => 'Jenis pelayanan',
                    'status' => 'success',
                    'value' => $this->typeLabel($jenisPelayanan).' / '.($calculation['source_period_text'] ?? '-'),
                ],
                [
                    'label' => 'Mapping Poli',
                    'status' => ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                        && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['jumlah_jenis_tindakan'] ?? 0).' jenis / '
                        .(int) ($calculation['jumlah_mapping_tindakan'] ?? 0).' tindakan',
                ],
                [
                    'label' => 'Formula',
                    'status' => (float) ($calculation['poli_percent'] ?? 0) > 0 ? 'success' : 'danger',
                    'value' => $this->formatRupiah((float) ($calculation['total_biaya_rawat'] ?? 0))
                        .' x '.$this->formatPercent((float) ($calculation['poli_percent'] ?? 0)),
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => (int) ($calculation['poli_doctor_config_count'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => (int) ($calculation['poli_doctor_config_count'] ?? 0).' dokter penerima',
                ],
                [
                    'label' => 'Filter sumber',
                    'status' => ((int) ($calculation['poli_filter_doctor_count'] ?? 0) > 0
                        && (int) ($calculation['poli_filter_source_count'] ?? 0) > 0
                        && (int) ($calculation['poli_filter_tindakan_count'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['poli_filter_source_count'] ?? 0).' sumber / '
                        .(int) ($calculation['poli_filter_doctor_count'] ?? 0).' dokter / '
                        .(int) ($calculation['poli_filter_tindakan_count'] ?? 0).' tindakan',
                ],
                [
                    'label' => 'Pembagian',
                    'status' => (float) ($calculation['total_grand'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => ($calculation['poli_distribution_mode_label'] ?? $this->ecgDistributionModeLabel(null))
                        .' - '.$this->formatRupiah((float) ($calculation['poli_allocation_per_doctor'] ?? 0))
                        .' per dokter',
                ],
            ];
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KONSUL_WA) {
            return [
                [
                    'label' => 'Jenis pelayanan',
                    'status' => 'success',
                    'value' => $this->typeLabel($jenisPelayanan).' / '.($calculation['source_period_text'] ?? '-'),
                ],
                [
                    'label' => 'Mapping Konsul WA',
                    'status' => ((int) ($calculation['jumlah_jenis_tindakan'] ?? 0) > 0
                        && (int) ($calculation['jumlah_mapping_tindakan'] ?? 0) > 0) ? 'success' : 'danger',
                    'value' => (int) ($calculation['jumlah_jenis_tindakan'] ?? 0).' jenis / '
                        .(int) ($calculation['jumlah_mapping_tindakan'] ?? 0).' tindakan',
                ],
                [
                    'label' => 'Formula',
                    'status' => (int) ($calculation['konsul_wa_nominal'] ?? 0) > 0 ? 'success' : 'danger',
                    'value' => (int) ($calculation['jumlah_transaksi'] ?? 0).' data x '
                        .$this->formatRupiah((int) ($calculation['konsul_wa_nominal'] ?? 0)),
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => (int) ($calculation['konsul_wa_doctor_config_count'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => (int) ($calculation['konsul_wa_doctor_config_count'] ?? 0).' dokter konfigurasi',
                ],
                [
                    'label' => 'Total premi',
                    'status' => (float) ($calculation['total_premi'] ?? 0) > 0 ? 'success' : 'warning',
                    'value' => $this->formatRupiah((float) ($calculation['total_premi'] ?? 0)),
                ],
            ];
        }

        if ($jenisPremiDokter === generatePremiDokterRepository::TYPE_KEBERSAMAAN) {
            return [
                [
                    'label' => 'Periode sumber data',
                    'status' => 'success',
                    'value' => $calculation['source_period_text'] ?? '-',
                ],
                [
                    'label' => 'Sumber dokter',
                    'status' => 'success',
                    'value' => $calculation['kebersamaan_sumber_dokter_label'] ?? 'Dokter Umum & Spesialis',
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
        if ($result->jenis_premi_dokter === generatePremiDokterRepository::TYPE_OPERASI) {
            return [
                [
                    'label' => 'Nominal operasi',
                    'status' => 'success',
                    'value' => $this->formatRupiah((float) $result->total_grand),
                ],
                [
                    'label' => 'Dokter penerima',
                    'status' => 'success',
                    'value' => (int) $result->jumlah_dokter.' dokter',
                ],
                [
                    'label' => 'Total persen',
                    'status' => 'success',
                    'value' => $this->formatPercent((float) data_get($result->config_snapshot, 'operasi_total_percent', 0)),
                ],
                [
                    'label' => 'Total diterima dokter',
                    'status' => 'success',
                    'value' => $this->formatRupiah((float) $result->total_premi),
                ],
            ];
        }

        if ($result->jenis_premi_dokter === generatePremiDokterRepository::TYPE_RAWAT_JALAN) {
            return [
                [
                    'label' => 'Periode sumber data',
                    'status' => 'success',
                    'value' => data_get($result->config_snapshot, 'source_period_text') ?: 'Sumber rawat periode '.$result->source_periode,
                ],
                [
                    'label' => 'Mapping rawat jalan',
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

        if ($result->jenis_premi_dokter === generatePremiDokterRepository::TYPE_ECG) {
            return [
                [
                    'label' => 'Periode sumber data',
                    'status' => 'success',
                    'value' => data_get($result->config_snapshot, 'source_period_text') ?: 'Sumber ECG periode '.$result->source_periode,
                ],
                [
                    'label' => 'Mapping ECG',
                    'status' => 'success',
                    'value' => (int) $result->jumlah_mapping_tindakan.' mapping',
                ],
                [
                    'label' => 'Formula',
                    'status' => 'success',
                    'value' => (int) $result->jumlah_transaksi.' data x '
                        .$this->formatRupiah((int) data_get($result->config_snapshot, 'ecg_nominal', 5000))
                        .' / '.(int) data_get($result->config_snapshot, 'ecg_divider', 3),
                ],
                [
                    'label' => 'Mode pembagian',
                    'status' => 'success',
                    'value' => data_get($result->config_snapshot, 'ecg_distribution_mode_label')
                        ?: $this->ecgDistributionModeLabel(data_get($result->config_snapshot, 'ecg_distribution_mode')),
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

        if ($result->jenis_premi_dokter === generatePremiDokterRepository::TYPE_POLI) {
            return [
                [
                    'label' => 'Periode sumber data',
                    'status' => 'success',
                    'value' => data_get($result->config_snapshot, 'source_period_text') ?: 'Sumber Poli periode '.$result->source_periode,
                ],
                [
                    'label' => 'Mapping Poli',
                    'status' => 'success',
                    'value' => (int) $result->jumlah_mapping_tindakan.' mapping',
                ],
                [
                    'label' => 'Formula',
                    'status' => 'success',
                    'value' => $this->formatRupiah((float) $result->total_biaya_rawat)
                        .' x '.$this->formatPercent((float) data_get($result->config_snapshot, 'poli_percent', 30)),
                ],
                [
                    'label' => 'Mode pembagian',
                    'status' => 'success',
                    'value' => data_get($result->config_snapshot, 'poli_distribution_mode_label')
                        ?: $this->ecgDistributionModeLabel(data_get($result->config_snapshot, 'poli_distribution_mode')),
                ],
                [
                    'label' => 'Filter sumber',
                    'status' => 'success',
                    'value' => (int) data_get($result->config_snapshot, 'poli_filter_source_count', 0).' sumber / '
                        .(int) data_get($result->config_snapshot, 'poli_filter_doctor_count', 0).' dokter / '
                        .(int) data_get($result->config_snapshot, 'poli_filter_tindakan_count', 0).' tindakan',
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

        if ($result->jenis_premi_dokter === generatePremiDokterRepository::TYPE_KONSUL_WA) {
            return [
                [
                    'label' => 'Periode sumber data',
                    'status' => 'success',
                    'value' => data_get($result->config_snapshot, 'source_period_text') ?: 'Sumber Konsul WA periode '.$result->source_periode,
                ],
                [
                    'label' => 'Mapping Konsul WA',
                    'status' => 'success',
                    'value' => (int) $result->jumlah_mapping_tindakan.' mapping',
                ],
                [
                    'label' => 'Formula',
                    'status' => 'success',
                    'value' => (int) $result->jumlah_transaksi.' data x '
                        .$this->formatRupiah((int) data_get($result->config_snapshot, 'konsul_wa_nominal', 0)),
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
            'kebersamaan_only_umum' => (bool) ($calculation['kebersamaan_only_umum'] ?? false),
            'kebersamaan_sumber_dokter_label' => $calculation['kebersamaan_sumber_dokter_label'] ?? 'Dokter Umum & Spesialis',
            'kebersamaan_allocation_percent' => $calculation['kebersamaan_allocation_percent'] ?? 0,
            'kebersamaan_allocation_per_doctor' => $calculation['kebersamaan_allocation_per_doctor'] ?? 0,
            'kebersamaan_visite_umum_total_premi' => $calculation['kebersamaan_visite_umum_total_premi'] ?? 0,
            'kebersamaan_visite_umum_total' => $calculation['kebersamaan_visite_umum_total'] ?? 0,
            'kebersamaan_visite_bpjs_jumlah_transaksi' => $calculation['kebersamaan_visite_bpjs_jumlah_transaksi'] ?? 0,
            'kebersamaan_visite_bpjs_jumlah_tindakan' => $calculation['kebersamaan_visite_bpjs_jumlah_tindakan'] ?? 0,
            'kebersamaan_visite_bpjs_dasar_hitung' => $calculation['kebersamaan_visite_bpjs_dasar_hitung'] ?? 0,
            'kebersamaan_visite_bpjs_total' => $calculation['kebersamaan_visite_bpjs_total'] ?? 0,
            'nominal_operasi' => $calculation['nominal_operasi'] ?? 0,
            'operasi_total_percent' => $calculation['operasi_total_percent'] ?? 0,
            'ecg_nominal' => $calculation['ecg_nominal'] ?? 5000,
            'ecg_divider' => $calculation['ecg_divider'] ?? 3,
            'ecg_distribution_mode' => $calculation['ecg_distribution_mode'] ?? generatePremiDokterRepository::ECG_DISTRIBUTION_SPLIT_EVENLY,
            'ecg_distribution_mode_label' => $calculation['ecg_distribution_mode_label']
                ?? $this->ecgDistributionModeLabel($calculation['ecg_distribution_mode'] ?? null),
            'ecg_doctor_config_count' => $calculation['ecg_doctor_config_count'] ?? 0,
            'ecg_allocation_percent' => $calculation['ecg_allocation_percent'] ?? 0,
            'ecg_allocation_per_doctor' => $calculation['ecg_allocation_per_doctor'] ?? 0,
            'poli_percent' => $calculation['poli_percent'] ?? 30,
            'poli_distribution_mode' => $calculation['poli_distribution_mode'] ?? generatePremiDokterRepository::ECG_DISTRIBUTION_SPLIT_EVENLY,
            'poli_distribution_mode_label' => $calculation['poli_distribution_mode_label']
                ?? $this->ecgDistributionModeLabel($calculation['poli_distribution_mode'] ?? null),
            'poli_doctor_config_count' => $calculation['poli_doctor_config_count'] ?? 0,
            'poli_filter_doctor_count' => $calculation['poli_filter_doctor_count'] ?? 0,
            'poli_filter_source_count' => $calculation['poli_filter_source_count'] ?? 0,
            'poli_filter_tindakan_count' => $calculation['poli_filter_tindakan_count'] ?? 0,
            'poli_allocation_percent' => $calculation['poli_allocation_percent'] ?? 0,
            'poli_allocation_per_doctor' => $calculation['poli_allocation_per_doctor'] ?? 0,
            'konsul_wa_nominal' => $calculation['konsul_wa_nominal'] ?? 0,
            'konsul_wa_doctor_config_count' => $calculation['konsul_wa_doctor_config_count'] ?? 0,
            'rawat_jalan_mapping_config_count' => $calculation['rawat_jalan_mapping_config_count'] ?? 0,
            'rawat_jalan_special_doctor_count' => $calculation['rawat_jalan_special_doctor_count'] ?? 0,
            'rawat_jalan_doctor_config_count' => $calculation['rawat_jalan_doctor_config_count'] ?? 0,
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
            'kebersamaan_only_umum' => (bool) data_get($result->config_snapshot, 'kebersamaan_only_umum', false),
            'kebersamaan_sumber_dokter_label' => data_get($result->config_snapshot, 'kebersamaan_sumber_dokter_label', 'Dokter Umum & Spesialis'),
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
            'nominal_operasi' => (float) data_get($result->config_snapshot, 'nominal_operasi', $result->total_grand),
            'operasi_total_percent' => (float) data_get($result->config_snapshot, 'operasi_total_percent', 0),
            'ecg_nominal' => (int) data_get($result->config_snapshot, 'ecg_nominal', 5000),
            'ecg_divider' => (int) data_get($result->config_snapshot, 'ecg_divider', 3),
            'ecg_distribution_mode' => $this->normalizeEcgDistributionMode(data_get($result->config_snapshot, 'ecg_distribution_mode')),
            'ecg_distribution_mode_label' => data_get($result->config_snapshot, 'ecg_distribution_mode_label')
                ?: $this->ecgDistributionModeLabel(data_get($result->config_snapshot, 'ecg_distribution_mode')),
            'ecg_doctor_config_count' => (int) data_get($result->config_snapshot, 'ecg_doctor_config_count', 0),
            'ecg_allocation_percent' => (float) data_get($result->config_snapshot, 'ecg_allocation_percent', 0),
            'ecg_allocation_per_doctor' => (float) data_get($result->config_snapshot, 'ecg_allocation_per_doctor', 0),
            'poli_percent' => (float) data_get($result->config_snapshot, 'poli_percent', 30),
            'poli_distribution_mode' => $this->normalizeEcgDistributionMode(data_get($result->config_snapshot, 'poli_distribution_mode')),
            'poli_distribution_mode_label' => data_get($result->config_snapshot, 'poli_distribution_mode_label')
                ?: $this->ecgDistributionModeLabel(data_get($result->config_snapshot, 'poli_distribution_mode')),
            'poli_doctor_config_count' => (int) data_get($result->config_snapshot, 'poli_doctor_config_count', 0),
            'poli_filter_doctor_count' => (int) data_get($result->config_snapshot, 'poli_filter_doctor_count', 0),
            'poli_filter_source_count' => (int) data_get($result->config_snapshot, 'poli_filter_source_count', 0),
            'poli_filter_tindakan_count' => (int) data_get($result->config_snapshot, 'poli_filter_tindakan_count', 0),
            'poli_allocation_percent' => (float) data_get($result->config_snapshot, 'poli_allocation_percent', 0),
            'poli_allocation_per_doctor' => (float) data_get($result->config_snapshot, 'poli_allocation_per_doctor', 0),
            'konsul_wa_nominal' => (int) data_get($result->config_snapshot, 'konsul_wa_nominal', 0),
            'konsul_wa_doctor_config_count' => (int) data_get($result->config_snapshot, 'konsul_wa_doctor_config_count', 0),
            'rawat_jalan_mapping_config_count' => (int) data_get($result->config_snapshot, 'rawat_jalan_mapping_config_count', 0),
            'rawat_jalan_special_doctor_count' => (int) data_get($result->config_snapshot, 'rawat_jalan_special_doctor_count', 0),
            'rawat_jalan_doctor_config_count' => (int) data_get($result->config_snapshot, 'rawat_jalan_doctor_config_count', 0),
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
        $rawatJalanTindakan = $this->repository
            ->getRawatJalanConfigTindakan((int) $config->id)
            ->map(fn ($item) => $this->mappingTindakanPayload($item))
            ->values();
        $ecgTindakan = $this->repository
            ->getEcgConfigTindakan((int) $config->id)
            ->map(fn ($item) => $this->mappingTindakanPayload($item))
            ->values();
        $poliTindakan = $this->repository
            ->getPoliConfigTindakan((int) $config->id)
            ->map(fn ($item) => $this->mappingTindakanPayload($item))
            ->values();
        $poliFilterDoctors = $this->repository
            ->getPoliFilterDoctors((int) $config->id)
            ->map(fn ($item) => [
                'id' => $item->kd_dokter,
                'kd_dokter' => $item->kd_dokter,
                'nm_dokter' => $item->nm_dokter,
                'kd_sps' => $item->kd_sps,
                'nm_sps' => $item->nm_sps,
                'text' => trim($item->kd_dokter.' - '.$item->nm_dokter.' ('.($item->nm_sps ?: 'Umum').')'),
            ])
            ->values();
        $poliSourceTableOptions = $this->repository
            ->poliSourceTableOptions()
            ->map(fn ($item) => [
                'id' => $item->source_table,
                'source_table' => $item->source_table,
                'source_label' => $item->source_label,
                'sumber_tindakan' => $item->sumber_tindakan,
                'provider' => $item->provider,
                'text' => $item->source_table.' - '.$item->source_label,
            ])
            ->values();
        $poliFilterSources = $this->repository
            ->getPoliFilterSources((int) $config->id)
            ->map(fn ($item) => [
                'id' => $item->source_table,
                'source_table' => $item->source_table,
                'source_label' => $item->source_label,
                'text' => $item->source_table.' - '.$item->source_label,
            ])
            ->values();
        $poliFilterTindakan = $this->repository
            ->getPoliFilterTindakan((int) $config->id)
            ->map(fn ($item) => $this->mappingTindakanPayload($item))
            ->values();
        $konsulWaTindakan = $this->repository
            ->getKonsulWaConfigTindakan((int) $config->id)
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
        $rawatJalanSpecialDoctors = $this->repository
            ->getRawatJalanSpecialDoctors((int) $config->id)
            ->map(fn ($item) => [
                'group_key' => $item->group_key,
                'group_label' => $this->categoryLabel($item->group_key),
                'kd_dokter' => $item->kd_dokter,
                'nm_dokter' => $item->nm_dokter,
                'kd_sps' => $item->kd_sps,
                'nm_sps' => $item->nm_sps,
                'nominal' => (int) $item->nominal,
                'id' => $item->kd_dokter,
                'text' => trim($item->kd_dokter.' - '.$item->nm_dokter.' ('.$this->categoryLabel($item->group_key).')'),
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
            'kebersamaan_only_umum' => (bool) ($config->kebersamaan_only_umum ?? false),
            'ecg_nominal' => (int) ($config->ecg_nominal ?? 5000),
            'ecg_divider' => (int) ($config->ecg_divider ?? 3),
            'ecg_distribution_mode' => $this->normalizeEcgDistributionMode($config->ecg_distribution_mode ?? null),
            'ecg_distribution_mode_label' => $this->ecgDistributionModeLabel($config->ecg_distribution_mode ?? null),
            'ecg_distribution_mode_options' => [
                [
                    'id' => generatePremiDokterRepository::ECG_DISTRIBUTION_SPLIT_EVENLY,
                    'label' => 'Dibagi rata',
                ],
                [
                    'id' => generatePremiDokterRepository::ECG_DISTRIBUTION_FULL_AMOUNT,
                    'label' => 'Diberikan penuh',
                ],
            ],
            'poli_percent' => (float) ($config->poli_percent ?? 30),
            'poli_distribution_mode' => $this->normalizeEcgDistributionMode($config->poli_distribution_mode ?? null),
            'poli_distribution_mode_label' => $this->ecgDistributionModeLabel($config->poli_distribution_mode ?? null),
            'poli_distribution_mode_options' => [
                [
                    'id' => generatePremiDokterRepository::ECG_DISTRIBUTION_SPLIT_EVENLY,
                    'label' => 'Dibagi rata',
                ],
                [
                    'id' => generatePremiDokterRepository::ECG_DISTRIBUTION_FULL_AMOUNT,
                    'label' => 'Diberikan penuh',
                ],
            ],
            'konsul_wa_nominal' => (int) ($config->konsul_wa_nominal ?? 0),
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
            'ecg_mapping_tindakan_ids' => $ecgTindakan->pluck('id')->values(),
            'ecg_mapping_tindakan' => $ecgTindakan,
            'poli_mapping_tindakan_ids' => $poliTindakan->pluck('id')->values(),
            'poli_mapping_tindakan' => $poliTindakan,
            'poli_filter_doctor_codes' => $poliFilterDoctors->pluck('id')->values(),
            'poli_filter_doctors' => $poliFilterDoctors,
            'poli_source_table_options' => $poliSourceTableOptions,
            'poli_filter_source_tables' => $poliFilterSources->pluck('id')->values(),
            'poli_filter_sources' => $poliFilterSources,
            'poli_filter_tindakan_ids' => $poliFilterTindakan->pluck('id')->values(),
            'poli_filter_tindakan' => $poliFilterTindakan,
            'konsul_wa_mapping_tindakan_ids' => $konsulWaTindakan->pluck('id')->values(),
            'konsul_wa_mapping_tindakan' => $konsulWaTindakan,
            'rawat_jalan_mapping_tindakan_ids' => $rawatJalanTindakan->pluck('id')->values(),
            'rawat_jalan_mapping_configs' => $rawatJalanTindakan,
            'rawat_jalan_special_doctors' => $rawatJalanSpecialDoctors,
            'rawat_jalan_special_groups' => collect(self::RAWAT_JALAN_SPECIAL_GROUPS)
                ->map(fn (array $row, string $key) => [
                    'id' => $key,
                    'label' => $row['label'],
                    'default_nominal' => $row['nominal'],
                ])
                ->values(),
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
            'multiplier_type' => $item->multiplier_type ?? generatePremiDokterRepository::MULTIPLIER_NOMINAL,
            'multiplier_value' => isset($item->multiplier_value) ? (float) $item->multiplier_value : 0,
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
            ['id' => generatePremiDokterRepository::TYPE_OPERASI, 'label' => 'Jasa Operasi', 'status' => 'active'],
            ['id' => generatePremiDokterRepository::TYPE_RAWAT_JALAN, 'label' => 'Jasa Rawat Jalan', 'status' => 'active'],
            ['id' => generatePremiDokterRepository::TYPE_VISITE, 'label' => 'Jasa Visite', 'status' => 'active'],
            ['id' => generatePremiDokterRepository::TYPE_POLI, 'label' => 'Jasa Poli', 'status' => 'active'],
            ['id' => 'jasa_igd', 'label' => 'Jasa IGD', 'status' => 'draft'],
            ['id' => generatePremiDokterRepository::TYPE_ECG, 'label' => 'Jasa ECG', 'status' => 'active'],
            ['id' => generatePremiDokterRepository::TYPE_KONSUL_WA, 'label' => 'Konsul WA', 'status' => 'active'],
            ['id' => 'kehadiran', 'label' => 'Kehadiran', 'status' => 'draft'],
        ];
    }

    private function categoryLabel(?string $category): string
    {
        return self::CATEGORY_DEFAULTS[$category]['label']
            ?? self::RAWAT_JALAN_SPECIAL_GROUPS[$category]['label']
            ?? 'Dokter';
    }

    private function premiumTypeLabel(?string $type): string
    {
        return match ($type) {
            generatePremiDokterRepository::TYPE_KEBERSAMAAN => 'Kebersamaan',
            generatePremiDokterRepository::TYPE_OPERASI => 'Jasa Operasi',
            generatePremiDokterRepository::TYPE_RAWAT_JALAN => 'Jasa Rawat Jalan',
            generatePremiDokterRepository::TYPE_POLI => 'Jasa Poli',
            generatePremiDokterRepository::TYPE_ECG => 'Jasa ECG',
            generatePremiDokterRepository::TYPE_KONSUL_WA => 'Konsul WA',
            default => 'Jasa Visite',
        };
    }

    private function typeLabel(string $type): string
    {
        if ($type === generatePremiDokterRepository::SERVICE_KEBERSAMAAN) {
            return 'Tanpa Jenis';
        }

        if ($type === generatePremiDokterRepository::SERVICE_MANUAL) {
            return 'Input Manual';
        }

        return $type === 'bpjs' ? 'BPJS' : 'UMUM';
    }

    private function normalizePremiumType(?string $type): string
    {
        return match ($type) {
            generatePremiDokterRepository::TYPE_KEBERSAMAAN => generatePremiDokterRepository::TYPE_KEBERSAMAAN,
            generatePremiDokterRepository::TYPE_OPERASI => generatePremiDokterRepository::TYPE_OPERASI,
            generatePremiDokterRepository::TYPE_RAWAT_JALAN => generatePremiDokterRepository::TYPE_RAWAT_JALAN,
            generatePremiDokterRepository::TYPE_POLI => generatePremiDokterRepository::TYPE_POLI,
            generatePremiDokterRepository::TYPE_ECG => generatePremiDokterRepository::TYPE_ECG,
            generatePremiDokterRepository::TYPE_KONSUL_WA => generatePremiDokterRepository::TYPE_KONSUL_WA,
            default => generatePremiDokterRepository::TYPE_VISITE,
        };
    }

    private function normalizeServiceType(string $premiumType, ?string $serviceType): string
    {
        if ($premiumType === generatePremiDokterRepository::TYPE_KEBERSAMAAN) {
            return generatePremiDokterRepository::SERVICE_KEBERSAMAAN;
        }

        if ($premiumType === generatePremiDokterRepository::TYPE_OPERASI) {
            return generatePremiDokterRepository::SERVICE_MANUAL;
        }

        if ($premiumType === generatePremiDokterRepository::TYPE_RAWAT_JALAN) {
            return $serviceType === 'bpjs' ? 'bpjs' : 'umum';
        }

        if (in_array($premiumType, [
            generatePremiDokterRepository::TYPE_POLI,
            generatePremiDokterRepository::TYPE_ECG,
            generatePremiDokterRepository::TYPE_KONSUL_WA,
        ], true)) {
            return $serviceType === 'bpjs' ? 'bpjs' : 'umum';
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

    private function normalizeEcgDistributionMode(?string $mode): string
    {
        return $mode === generatePremiDokterRepository::ECG_DISTRIBUTION_FULL_AMOUNT
            ? generatePremiDokterRepository::ECG_DISTRIBUTION_FULL_AMOUNT
            : generatePremiDokterRepository::ECG_DISTRIBUTION_SPLIT_EVENLY;
    }

    private function ecgDistributionModeLabel(?string $mode): string
    {
        return $this->normalizeEcgDistributionMode($mode) === generatePremiDokterRepository::ECG_DISTRIBUTION_FULL_AMOUNT
            ? 'Diberikan penuh'
            : 'Dibagi rata';
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
