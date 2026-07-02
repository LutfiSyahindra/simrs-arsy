<?php

namespace App\Services\keuangan\premi;

use App\Models\dbSimrs\generateTindakanMedisModel;
use App\Models\User;
use App\Repositories\keuangan\premi\generateTindakanMedisRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateTindakanMedisService
{
    public function __construct(
        protected generateTindakanMedisRepository $repository
    ) {}

    public function getResults(
        ?string $periode = null,
        ?string $jenis = null,
        ?int $jnsPremiId = null
    ): Collection {
        return $this->repository
            ->getResults($periode, $jenis, $jnsPremiId)
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

    public function getMappingPremiOptions(): Collection
    {
        return $this->repository
            ->getMappingPremiOptions()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'jenis' => $item->jenis,
                'pembagi' => max(1, (int) ($item->pembagi ?? 1)),
                'jumlah_tindakan' => (int) $item->jumlah_tindakan,
                'jumlah_pegawai' => (int) $item->jumlah_pegawai,
                'text' => trim($item->kode.' - '.$item->jenis),
            ])
            ->values();
    }

    public function getActionOptions(int $jnsPremiId): Collection
    {
        return $this->actionOptions($jnsPremiId);
    }

    public function getConfig(): array
    {
        return $this->configPayload($this->repository->getConfig());
    }

    public function updateConfig(
        int $jnsPremiUmumId,
        int $jnsPremiBpjsId,
        string $bpjsSourceMode,
        string $distributionMode,
        bool $ignoreIcu,
        bool $ignoreNicu,
        bool $bpjsIgnoreUgd,
        bool $bpjsIgnoreVk,
        bool $includeBpjsIcuPool,
        array $sourceMappings,
        array $karcisTindakanIds = [],
        array $doctorCodes = [],
        array $doctorTindakanIds = []
    ): array {
        $sourceMappings = $this->normalizeSourceMappings($sourceMappings);
        $premiIds = [$jnsPremiUmumId, $jnsPremiBpjsId];
        $this->assertSourceMappingsBelongToPremi($premiIds, $sourceMappings);
        $karcisTindakanIds = collect($karcisTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $doctorCodes = $this->normalizeDoctorCodes($doctorCodes);
        $this->assertDoctorsExist($doctorCodes);
        $doctorTindakanIds = $this->normalizeTindakanIds($doctorTindakanIds);
        $this->assertDoctorActionsBelongToPremi($premiIds, $doctorTindakanIds);

        return $this->configPayload(
            $this->repository->saveConfig(
                $jnsPremiUmumId,
                $jnsPremiBpjsId,
                PremiSourcePeriod::normalizeMode($bpjsSourceMode, 'bpjs'),
                $this->distributionMode($distributionMode),
                $ignoreIcu,
                $ignoreNicu,
                $bpjsIgnoreUgd,
                $bpjsIgnoreVk,
                $includeBpjsIcuPool,
                $sourceMappings,
                $karcisTindakanIds,
                $doctorCodes,
                $doctorTindakanIds
            )
        );
    }

    public function dokterOptions(?string $keyword = null): Collection
    {
        return $this->repository
            ->getDokterOptions($keyword)
            ->map(fn ($item) => [
                'id' => $item->kd_dokter,
                'kd_dokter' => $item->kd_dokter,
                'nm_dokter' => $item->nm_dokter,
                'text' => trim($item->kd_dokter.' - '.$item->nm_dokter),
            ])
            ->values();
    }

    public function getKarcisConfig(): array
    {
        $selectedIds = $this->repository->getKarcisTindakanIds()
            ->map(fn ($id) => (int) $id)
            ->values();

        return [
            'selected_ids' => $selectedIds,
            'options' => $this->repository
                ->getKarcisConfigOptions()
                ->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'kode' => $item->kode,
                    'jenis' => $item->jenis,
                    'jumlah_mapping_tindakan' => (int) $item->jumlah_mapping_tindakan,
                    'jumlah_mapping_premi' => (int) $item->jumlah_mapping_premi,
                    'selected' => $selectedIds->contains((int) $item->id),
                ])
                ->values(),
        ];
    }

    public function saveKarcisConfig(array $tindakanIds): void
    {
        $this->repository->saveKarcisConfig(
            collect($tindakanIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all()
        );
    }

    public function getSummary(
        string $periode,
        string $jenis,
        ?int $jnsPremiId = null,
        ?int $ugdPlotingId = null,
        ?int $vkPlotingId = null
    ): array {
        $config = $this->repository->getConfig();
        $jnsPremiId = $this->selectedConfigPremiId($jnsPremiId, $config, $jenis);
        $distributionMode = $this->distributionMode($config->distribution_mode ?? null);
        $sourceMappings = $this->repository->getConfigSourceMappings((int) $config->id);
        $bpjsSourceMode = PremiSourcePeriod::normalizeMode(
            $config->bpjs_source_mode ?? null,
            $jenis
        );
        $configuredBpjsSourceMode = PremiSourcePeriod::normalizeMode(
            $config->bpjs_source_mode ?? null,
            'bpjs'
        );
        $sourcePeriode = PremiSourcePeriod::resolve($periode, $jenis, $bpjsSourceMode);
        $dependencySourcePeriode = $periode;
        $doctorFilter = $this->doctorFilterPayload((int) $config->id);
        $dependencyOptions = $this->repository->getDependencyOptions($dependencySourcePeriode, $jenis);
        $existing = $this->repository->findByPeriodAndType($periode, $jenis, $jnsPremiId);
        $ignoredDependencies = $this->ignoredDependencies($jenis, $existing?->is_locked ? $existing : $config);

        if ($existing?->is_locked) {
            $ugdPlotingId = $existing->ugd_plotingPremi_id;
            $vkPlotingId = $existing->vk_plotingPremi_id;
        } else {
            $ugdPlotingId = $ignoredDependencies['ugd']
                ? null
                : $this->resolveDependencyId(
                    $ugdPlotingId,
                    $existing?->ugd_plotingPremi_id,
                    $dependencyOptions['ugd']
                );
            $vkPlotingId = $ignoredDependencies['vk']
                ? null
                : $this->resolveDependencyId(
                    $vkPlotingId,
                    $existing?->vk_plotingPremi_id,
                    $dependencyOptions['vk']
                );
        }

        $dependencies = $this->repository->getDependencies(
            $dependencySourcePeriode,
            $jenis,
            $ignoredDependencies['ugd'] ? null : $ugdPlotingId,
            $ignoredDependencies['vk'] ? null : $vkPlotingId
        );
        if ($existing?->is_locked) {
            $dependencies = [
                'ugd' => $ignoredDependencies['ugd']
                    ? null
                    : $this->existingDependencySnapshot($existing, 'ugd'),
                'vk' => $ignoredDependencies['vk']
                    ? null
                    : $this->existingDependencySnapshot($existing, 'vk'),
            ];
        }

        $dependenciesReady = $existing?->is_locked
            ? true
            : $this->dependenciesAreLocked($dependencies, $ignoredDependencies);
        $calculation = null;

        if ($dependenciesReady && ! $existing?->is_locked) {
            $calculation = $this->repository->calculate(
                $periode,
                $jenis,
                $jnsPremiId,
                $config,
                $sourceMappings
            );
        }

        $selectedPremi = $this->repository->findPremi($jnsPremiId);
        $pembagi = max(1, (int) (
            $calculation['pembagi']
            ?? $existing?->pembagi
            ?? $selectedPremi?->pembagi
            ?? 1
        ));
        $totalMapping = (float) (
            $calculation['total_mapping_premi']
            ?? $existing?->total_mapping_premi
            ?? 0
        );
        $totalUgd = $ignoredDependencies['ugd']
            ? 0
            : (float) (
                data_get($dependencies, 'ugd.total')
                ?? $existing?->total_ugd
                ?? 0
            );
        $totalVk = $ignoredDependencies['vk']
            ? 0
            : (float) (
                data_get($dependencies, 'vk.total')
                ?? $existing?->total_vk
                ?? 0
            );
        $totalIcuPoolBpjs = $jenis === 'bpjs' && (bool) ($config->include_bpjs_icu_pool ?? true)
            ? (float) (
                $calculation['total_icu_pool_bpjs']
                ?? $existing?->total_icu_pool_bpjs
                ?? 0
            )
            : 0;
        $grandTotal = round($totalMapping + $totalUgd + $totalVk + $totalIcuPoolBpjs, 2);
        $totalFinal = $calculation
            ? round($grandTotal / $pembagi, 2)
            : ($existing?->total_final ?? 0);
        $pegawai = $this->repository->getMappedPegawai($jnsPremiId);
        $recipientBonuses = $this->repository->getRecipientBonuses(
            $periode,
            $jenis,
            $pegawai->pluck('nik')
        );
        $distributions = $existing?->is_locked
            ? $this->distributionPayload($existing->distributions ?? collect())
            : $this->distributeFinal(
                (float) $totalFinal,
                $pegawai,
                $jnsPremiId,
                $distributionMode,
                $recipientBonuses
            );
        $distributionMode = data_get($distributions->first(), 'distribution_mode')
            ?? $distributionMode;
        $totalDasarDibagikan = round((float) $distributions->sum('total_dasar'), 2);
        $totalTambahanIcu = round((float) $distributions->sum('total_icu'), 2);
        $totalTambahanNicu = round((float) $distributions->sum('total_nicu'), 2);
        $totalDibagikan = round((float) $distributions->sum('total_diterima'), 2);
        $hasRecipients = $distributions->isNotEmpty();
        $readinessMessage = $dependenciesReady && ! $hasRecipients
            ? 'Mapping premi belum memiliki pegawai penerima.'
            : $this->readinessMessage($dependencies, $dependencyOptions, $ignoredDependencies);
        $previewDetails = $calculation
            ? $this->previewDetails($calculation['details'])
            : $this->previewDetails($existing?->details ?? []);

        return [
            'periode' => $periode,
            'source_periode' => $sourcePeriode,
            'dependency_source_periode' => $dependencySourcePeriode,
            'dependency_source_mode_label' => 'Periode Generate',
            'source_tgl_awal' => $calculation['source_tgl_awal']
                ?? optional($existing?->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => $calculation['source_tgl_akhir']
                ?? optional($existing?->source_tgl_akhir)->format('Y-m-d'),
            'jenis_pelayanan' => $jenis,
            'jenis_pelayanan_label' => $this->typeLabel($jenis),
            'bpjs_source_mode' => $bpjsSourceMode,
            'bpjs_source_mode_label' => PremiSourcePeriod::modeLabel($bpjsSourceMode, $jenis),
            'doctor_filter' => $doctorFilter,
            'jnsPremi_id' => $jnsPremiId,
            'kode_premi' => $calculation['kode_premi']
                ?? $existing?->kode_premi
                ?? $selectedPremi?->kode,
            'nama_premi' => $calculation['nama_premi']
                ?? $existing?->nama_premi
                ?? $selectedPremi?->jenis,
            'dependency_ugd' => $this->dependencyPayload(
                $dependencies['ugd'],
                $existing,
                'ugd',
                $ignoredDependencies['ugd']
            ),
            'dependency_vk' => $this->dependencyPayload(
                $dependencies['vk'],
                $existing,
                'vk',
                $ignoredDependencies['vk']
            ),
            'dependency_options' => $dependencyOptions,
            'selected_ugd_plotingPremi_id' => $ignoredDependencies['ugd']
                ? null
                : data_get($dependencies, 'ugd.plotingPremi_id'),
            'selected_vk_plotingPremi_id' => $ignoredDependencies['vk']
                ? null
                : data_get($dependencies, 'vk.plotingPremi_id'),
            'ignored_dependencies' => $ignoredDependencies,
            'dependency_policy_label' => $this->dependencyPolicyLabel($ignoredDependencies),
            'ready' => $dependenciesReady && $hasRecipients,
            'readiness_message' => $readinessMessage,
            'readiness_steps' => $this->readinessSteps(
                $dependencies,
                $dependencyOptions,
                $hasRecipients,
                $calculation,
                $existing,
                $sourcePeriode,
                $dependencySourcePeriode,
                $doctorFilter,
                $ignoredDependencies
            ),
            'is_generated' => (bool) $existing,
            'is_locked' => $existing?->is_locked ?? false,
            'locked_at' => optional($existing?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $existing?->lockedBy?->name,
            'ignore_icu' => (bool) ($config->ignore_icu ?? true),
            'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
            'bpjs_ignore_ugd' => $ignoredDependencies['ugd'],
            'bpjs_ignore_vk' => $ignoredDependencies['vk'],
            'karcis_config_count' => $this->repository->getKarcisTindakanIds()->count(),
            'karcis_source_period' => PremiSourcePeriod::resolve(
                $periode,
                'bpjs',
                $configuredBpjsSourceMode
            ),
            'karcis_rule_message' => $this->karcisRuleMessage($jenis),
            'jumlah_transaksi' => $calculation['jumlah_transaksi']
                ?? $existing?->jumlah_transaksi
                ?? 0,
            'jumlah_pasien' => $calculation['jumlah_pasien']
                ?? $existing?->jumlah_pasien
                ?? 0,
            'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan']
                ?? $existing?->jumlah_jenis_tindakan
                ?? 0,
            'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi']
                ?? $existing?->jumlah_mapping_premi
                ?? 0,
            'jumlah_terabaikan_icu' => $calculation['jumlah_terabaikan_icu']
                ?? $existing?->jumlah_terabaikan_icu
                ?? 0,
            'jumlah_terabaikan_nicu' => $calculation['jumlah_terabaikan_nicu']
                ?? $existing?->jumlah_terabaikan_nicu
                ?? 0,
            'total_biaya_rawat' => $calculation['total_biaya_rawat']
                ?? $existing?->total_biaya_rawat
                ?? 0,
            'total_mapping_premi' => $totalMapping,
            'total_ugd' => $totalUgd,
            'total_vk' => $totalVk,
            'total_icu_pool_bpjs' => $totalIcuPoolBpjs,
            'include_bpjs_icu_pool' => (bool) ($config->include_bpjs_icu_pool ?? true),
            'icu_pool_bpjs' => $this->icuPoolPayload(
                $calculation['icu_pool_bpjs']
                ?? [
                    'total' => $totalIcuPoolBpjs,
                    'source_count' => 0,
                    'locked_count' => 0,
                ]
            ),
            'grand_total' => $grandTotal,
            'pembagi' => $pembagi,
            'total_final' => $totalFinal,
            'distribution_mode' => $distributionMode,
            'distribution_mode_label' => $this->distributionModeLabel($distributionMode),
            'jumlah_penerima' => $distributions->count(),
            'total_dasar_dibagikan' => $totalDasarDibagikan,
            'total_tambahan_icu' => $totalTambahanIcu,
            'total_tambahan_nicu' => $totalTambahanNicu,
            'total_dibagikan' => $totalDibagikan,
            'total_belum_dibagikan' => max(
                0,
                round((float) $totalFinal - $totalDasarDibagikan, 2)
            ),
            'distributions' => $distributions->values(),
            'distribution_insight' => $this->distributionInsight($distributions, (float) $totalFinal),
            'preview_details' => $previewDetails,
            'preview_insight' => $this->previewInsight($previewDetails),
            'source_mappings' => $this->sourceMappingsPayload($sourceMappings),
        ];
    }

    public function generate(
        string $periode,
        string $jenis,
        ?int $jnsPremiId,
        ?int $ugdPlotingId,
        ?int $vkPlotingId
    ): array {
        return DB::transaction(function () use (
            $periode,
            $jenis,
            $jnsPremiId,
            $ugdPlotingId,
            $vkPlotingId
        ) {
            $config = $this->repository->getConfig();
            $jnsPremiId = $this->selectedConfigPremiId($jnsPremiId, $config, $jenis);
            $sourceMappings = $this->repository->getConfigSourceMappings((int) $config->id);
            $ignoredDependencies = $this->ignoredDependencies($jenis, $config);
            $existing = $this->repository
                ->findByPeriodAndTypeForUpdate($periode, $jenis, $jnsPremiId);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Tindakan Medis {$this->typeLabel($jenis)} periode {$periode} sudah dikunci.",
                ]);
            }

            $sourcePeriode = PremiSourcePeriod::resolve(
                $periode,
                $jenis,
                $config->bpjs_source_mode ?? null
            );
            $dependencySourcePeriode = $periode;
            $dependencies = $this->repository->getDependencies(
                $dependencySourcePeriode,
                $jenis,
                $ignoredDependencies['ugd'] ? null : $ugdPlotingId,
                $ignoredDependencies['vk'] ? null : $vkPlotingId,
                true
            );
            $missing = collect([
                'UGD' => $dependencies['ugd'],
                'VK' => $dependencies['vk'],
            ])->reject(fn ($value, string $key) => $ignoredDependencies[strtolower($key)] ?? false)
                ->filter(fn ($value) => ! $value)
                ->keys();

            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => 'Generate terlebih dahulu: '.$missing->implode(' dan ').'.',
                ]);
            }

            $unlocked = collect([
                'UGD' => $dependencies['ugd'],
                'VK' => $dependencies['vk'],
            ])->reject(fn ($value, string $key) => $ignoredDependencies[strtolower($key)] ?? false)
                ->filter(fn ($value) => ! data_get($value, 'is_locked'))
                ->keys();

            if ($unlocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => 'Kunci terlebih dahulu: '.$unlocked->implode(' dan ').'.',
                ]);
            }

            $calculation = $this->repository->calculate(
                $periode,
                $jenis,
                $jnsPremiId,
                $config,
                $sourceMappings
            );

            if (
                $calculation['jumlah_mapping_premi'] === 0
                && (float) data_get($dependencies, 'ugd.total', 0) <= 0
                && (float) data_get($dependencies, 'vk.total', 0) <= 0
                && (float) ($calculation['total_icu_pool_bpjs'] ?? 0) <= 0
            ) {
                $selectedPremi = $this->repository->findPremi($jnsPremiId);
                $premiName = trim(
                    ($selectedPremi?->kode ? $selectedPremi->kode.' - ' : '')
                    .($selectedPremi?->jenis ?? 'mapping premi terpilih')
                );

                throw ValidationException::withMessages([
                    'mapping' => "Tidak ada tindakan periode sumber {$sourcePeriode} yang sesuai dengan {$premiName}.",
                ]);
            }

            $pegawai = $this->repository->getMappedPegawai($jnsPremiId);

            if ($pegawai->isEmpty()) {
                $selectedPremi = $this->repository->findPremi($jnsPremiId);
                $premiName = trim(
                    ($selectedPremi?->kode ? $selectedPremi->kode.' - ' : '')
                    .($selectedPremi?->jenis ?? 'mapping premi terpilih')
                );

                throw ValidationException::withMessages([
                    'pegawai' => "Mapping {$premiName} belum memiliki pegawai penerima.",
                ]);
            }

            $recipientBonuses = $this->repository->getRecipientBonuses(
                $periode,
                $jenis,
                $pegawai->pluck('nik')
            );
            $result = $this->repository->saveResult(
                $periode,
                $jenis,
                $jnsPremiId,
                $config,
                $dependencies,
                $calculation,
                $sourceMappings
            );
            $this->repository->replaceDetails($result, $calculation['details']);
            $this->repository->replaceDistributions(
                $result,
                $this->distributeFinal(
                    (float) $result->total_final,
                    $pegawai,
                    $jnsPremiId,
                    $this->distributionMode($config->distribution_mode ?? null),
                    $recipientBonuses
                )
            );

            return $this->resultPayload(
                $result->fresh([
                    'jnsPremi:id,pembagi',
                    'details',
                    'distributions',
                    'lockedBy:id,name',
                    'generateBy:id,name',
                ])
            );
        });
    }

    public function getDetail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate tindakan medis tidak ditemukan.');
        }

        return [
            ...$this->resultPayload($result),
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'generate_by_name' => $result->generateBy?->name,
            'config_snapshot' => $result->config_snapshot,
            'details' => $result->details->map(function ($detail) {
                $rawat = collect($detail->data_rawat);
                $breakdowns = $this->rawatBreakdowns($rawat);

                return [
                    'id' => $detail->id,
                    'mapping_premi_id' => $detail->mapping_premi_id,
                    'jnsPremi_id' => $detail->jnsPremi_id,
                    'jnsTindakan_id' => $detail->jnsTindakan_id,
                    'kode_premi' => $detail->kode_premi,
                    'nama_premi' => $detail->nama_premi,
                    'kode_jenis_tindakan' => $detail->kode_jenis_tindakan,
                    'nama_jenis_tindakan' => $detail->nama_jenis_tindakan,
                    'jenis_mapping' => $detail->jenis_mapping,
                    'nilai_mapping' => $detail->nilai_mapping,
                    'source_rules' => $detail->source_rules,
                    'jumlah_data' => $detail->jumlah_data,
                    'jumlah_data_icu' => $detail->jumlah_data_icu,
                    'jumlah_data_nicu' => $detail->jumlah_data_nicu,
                    'jumlah_data_dokter' => $breakdowns['jumlah_data_dokter'],
                    'jumlah_data_paramedis' => $breakdowns['jumlah_data_paramedis'],
                    'jumlah_data_drpr' => $breakdowns['jumlah_data_drpr'],
                    'jumlah_data_karcis_bpjs' => $breakdowns['jumlah_data_karcis_bpjs'],
                    'jumlah_data_dialihkan_perawat' => $breakdowns['jumlah_data_dialihkan_perawat'],
                    'total_biaya_rawat' => $detail->total_biaya_rawat,
                    'dasar_hitung' => $detail->dasar_hitung,
                    'hasil_mapping' => $detail->hasil_mapping,
                    'source_breakdown' => $breakdowns['source_breakdown'],
                    'doctor_breakdown' => $breakdowns['doctor_breakdown'],
                    'paramedic_breakdown' => $breakdowns['paramedic_breakdown'],
                    'data_rawat' => $detail->data_rawat,
                ];
            })->values(),
            'distributions' => $this->distributionPayload($result->distributions)->values(),
        ];
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate tindakan medis tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data sudah dalam keadaan terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, true, $user->id)
            );
        });
    }

    public function unlock(int $id, User $user): array
    {
        if (! $user->hasRole('Admin')) {
            throw new AuthorizationException(
                'Hanya user dengan role Admin yang dapat membuka kunci data.'
            );
        }

        return DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate tindakan medis tidak ditemukan.');
            }

            if (! $result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data tidak sedang terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, false)
            );
        });
    }

    private function resultPayload(generateTindakanMedisModel $result): array
    {
        return [
            'id' => $result->id,
            'periode' => $result->periode,
            'source_periode' => $result->source_periode,
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d'),
            'jenis_pelayanan' => $result->jenis_pelayanan,
            'jenis_pelayanan_label' => $this->typeLabel($result->jenis_pelayanan),
            'bpjs_source_mode' => $result->bpjs_source_mode,
            'bpjs_source_mode_label' => PremiSourcePeriod::modeLabel(
                $result->bpjs_source_mode,
                $result->jenis_pelayanan
            ),
            'jnsPremi_id' => $result->jnsPremi_id,
            'kode_premi' => $result->kode_premi,
            'nama_premi' => $result->nama_premi,
            'jumlah_transaksi' => $result->jumlah_transaksi,
            'jumlah_pasien' => $result->jumlah_pasien,
            'jumlah_jenis_tindakan' => $result->jumlah_jenis_tindakan,
            'jumlah_mapping_premi' => $result->jumlah_mapping_premi,
            'jumlah_terabaikan_icu' => $result->jumlah_terabaikan_icu,
            'jumlah_terabaikan_nicu' => $result->jumlah_terabaikan_nicu,
            'total_biaya_rawat' => $result->total_biaya_rawat,
            'total_mapping_premi' => $result->total_mapping_premi,
            'total_ugd' => $result->total_ugd,
            'total_vk' => $result->total_vk,
            'total_icu_pool_bpjs' => $result->total_icu_pool_bpjs ?? 0,
            'grand_total' => $result->grand_total,
            'pembagi' => max(1, (int) ($result->pembagi ?? $result->jnsPremi?->pembagi ?? 1)),
            'total_final' => $result->total_final,
            'distribution_mode' => $this->distributionMode($result->distribution_mode),
            'distribution_mode_label' => $this->distributionModeLabel($result->distribution_mode),
            'jumlah_penerima' => $result->relationLoaded('distributions')
                ? $result->distributions->count()
                : (int) ($result->jumlah_penerima ?? $result->distributions_count ?? 0),
            'total_dasar_dibagikan' => $result->relationLoaded('distributions')
                ? round((float) $result->distributions->sum('total_dasar'), 2)
                : round((float) ($result->total_dasar_dibagikan ?? 0), 2),
            'total_tambahan_icu' => $result->relationLoaded('distributions')
                ? round((float) $result->distributions->sum('total_icu'), 2)
                : round((float) ($result->total_tambahan_icu ?? 0), 2),
            'total_tambahan_nicu' => $result->relationLoaded('distributions')
                ? round((float) $result->distributions->sum('total_nicu'), 2)
                : round((float) ($result->total_tambahan_nicu ?? 0), 2),
            'total_dibagikan' => $result->relationLoaded('distributions')
                ? round((float) $result->distributions->sum('total_diterima'), 2)
                : round((float) ($result->total_dibagikan ?? 0), 2),
            'total_belum_dibagikan' => max(
                0,
                round(
                    (float) $result->total_final
                    - ($result->relationLoaded('distributions')
                        ? (float) $result->distributions->sum('total_dasar')
                        : (float) ($result->total_dasar_dibagikan ?? 0)),
                    2
                )
            ),
            'ignore_icu' => $result->ignore_icu,
            'ignore_nicu' => $result->ignore_nicu,
            'bpjs_ignore_ugd' => (bool) ($result->bpjs_ignore_ugd ?? false),
            'bpjs_ignore_vk' => (bool) ($result->bpjs_ignore_vk ?? false),
            'ignored_dependencies' => $this->ignoredDependencies($result->jenis_pelayanan, $result),
            'dependency_policy_label' => $this->dependencyPolicyLabel(
                $this->ignoredDependencies($result->jenis_pelayanan, $result)
            ),
            'ugd_plotingPremi_id' => $result->ugd_plotingPremi_id,
            'ugd_ploting_label' => $this->plotingLabel(
                $result->ugd_kode_ploting,
                $result->ugd_nama_ploting
            ),
            'vk_plotingPremi_id' => $result->vk_plotingPremi_id,
            'vk_ploting_label' => $this->plotingLabel(
                $result->vk_kode_ploting,
                $result->vk_nama_ploting
            ),
        ];
    }

    private function configPayload(object $config): array
    {
        $legacyPremiId = $config->jnsPremi_id ? (int) $config->jnsPremi_id : null;
        $jnsPremiUmumId = (int) (
            data_get($config, 'jnsPremi_umum_id')
            ?: $legacyPremiId
            ?: 0
        ) ?: null;
        $jnsPremiBpjsId = (int) (
            data_get($config, 'jnsPremi_bpjs_id')
            ?: $legacyPremiId
            ?: 0
        ) ?: null;
        $premiUmum = $jnsPremiUmumId ? $this->repository->findPremi($jnsPremiUmumId) : null;
        $premiBpjs = $jnsPremiBpjsId ? $this->repository->findPremi($jnsPremiBpjsId) : null;
        $sourceMappings = $this->repository->getConfigSourceMappings((int) $config->id);
        $doctorFilter = $this->doctorFilterPayload((int) $config->id);
        $pegawai = $jnsPremiUmumId
            ? $this->repository->getMappedPegawai($jnsPremiUmumId)
            : collect();
        $actionOptions = collect([$jnsPremiUmumId, $jnsPremiBpjsId])
            ->filter()
            ->unique()
            ->flatMap(fn ($premiId) => $this->actionOptions((int) $premiId))
            ->unique('id')
            ->sortBy('jenis')
            ->values();

        return [
            'id' => (int) $config->id,
            'jnsPremi_id' => $jnsPremiUmumId,
            'jnsPremi_umum_id' => $jnsPremiUmumId,
            'jnsPremi_bpjs_id' => $jnsPremiBpjsId,
            'distribution_mode' => $this->distributionMode($config->distribution_mode ?? null),
            'distribution_mode_label' => $this->distributionModeLabel(
                $this->distributionMode($config->distribution_mode ?? null)
            ),
            'bpjs_source_mode' => PremiSourcePeriod::normalizeMode(
                $config->bpjs_source_mode ?? null,
                'bpjs'
            ),
            'bpjs_source_mode_label' => PremiSourcePeriod::modeLabel(
                $config->bpjs_source_mode ?? null,
                'bpjs'
            ),
            'ignore_icu' => (bool) $config->ignore_icu,
            'ignore_nicu' => (bool) $config->ignore_nicu,
            'bpjs_ignore_ugd' => (bool) ($config->bpjs_ignore_ugd ?? false),
            'bpjs_ignore_vk' => (bool) ($config->bpjs_ignore_vk ?? false),
            'include_bpjs_icu_pool' => (bool) ($config->include_bpjs_icu_pool ?? true),
            'premi' => $premiUmum ? [
                'id' => (int) $premiUmum->id,
                'kode' => $premiUmum->kode,
                'jenis' => $premiUmum->jenis,
                'pembagi' => max(1, (int) ($premiUmum->pembagi ?? 1)),
                'label' => trim($premiUmum->kode.' - '.$premiUmum->jenis),
            ] : null,
            'premi_umum' => $this->premiPayload($premiUmum),
            'premi_bpjs' => $this->premiPayload($premiBpjs),
            'mapping_options' => $this->getMappingPremiOptions(),
            'action_options' => $actionOptions,
            'source_options' => $this->repository->sourcePatternOptions(),
            'source_mappings' => $this->sourceMappingsPayload($sourceMappings),
            'karcis' => $this->getKarcisConfig(),
            'doctor_filter' => $doctorFilter,
            'pegawai' => $pegawai
                ->map(fn ($item) => [
                    'nik' => $item->nik,
                    'pegawai_name' => $item->pegawai_name,
                    'pegawai_position' => $item->pegawai_position,
                    'text' => trim($item->nik.' - '.$item->pegawai_name),
                ])
                ->values(),
        ];
    }

    private function distributeFinal(
        float $totalFinal,
        $pegawai,
        int $jnsPremiId,
        string $distributionMode,
        Collection $recipientBonuses
    ): Collection {
        $pegawai = collect($pegawai)->values();
        $count = $pegawai->count();

        if ($count === 0) {
            return collect();
        }

        $distributionMode = $this->distributionMode($distributionMode);
        $totalCents = (int) round($totalFinal * 100);
        $baseCents = $distributionMode === 'full_amount'
            ? $totalCents
            : intdiv($totalCents, $count);
        $remainder = $distributionMode === 'full_amount'
            ? 0
            : $totalCents % $count;
        $icuBonuses = collect($recipientBonuses->get('icu', collect()));
        $nicuBonuses = collect($recipientBonuses->get('nicu', collect()));

        return $pegawai
            ->map(function ($item, int $index) use (
                $jnsPremiId,
                $totalFinal,
                $count,
                $baseCents,
                $remainder,
                $distributionMode,
                $icuBonuses,
                $nicuBonuses
            ) {
                $nik = (string) $item->nik;
                $amountCents = $baseCents + ($index < $remainder ? 1 : 0);
                $baseAmount = round($amountCents / 100, 2);
                $icuInfo = $icuBonuses->get($nik, []);
                $nicuInfo = $nicuBonuses->get($nik, []);
                $totalIcu = round((float) data_get($icuInfo, 'total', 0), 2);
                $totalNicu = round((float) data_get($nicuInfo, 'total', 0), 2);
                $icuPayload = $this->bonusInfoPayload($icuInfo);
                $nicuPayload = $this->bonusInfoPayload($nicuInfo);

                return [
                    'jnsPremi_id' => $jnsPremiId,
                    'nik' => $nik,
                    'pegawai_name' => $item->pegawai_name,
                    'pegawai_position' => $item->pegawai_position,
                    'distribution_mode' => $distributionMode,
                    'distribution_mode_label' => $this->distributionModeLabel($distributionMode),
                    'total_final' => round($totalFinal, 2),
                    'jumlah_penerima' => $count,
                    'total_dasar' => $baseAmount,
                    'has_icu_bonus' => $totalIcu > 0,
                    'total_icu' => $totalIcu,
                    'icu_bonus_info' => $icuPayload,
                    'icu_bonus_summary' => $this->bonusSummary($icuPayload, 'ICU'),
                    'has_nicu_bonus' => $totalNicu > 0,
                    'total_nicu' => $totalNicu,
                    'nicu_bonus_info' => $nicuPayload,
                    'nicu_bonus_summary' => $this->bonusSummary($nicuPayload, 'NICU'),
                    'total_diterima' => round($baseAmount + $totalIcu + $totalNicu, 2),
                ];
            });
    }

    private function distributionPayload($distributions): Collection
    {
        return collect($distributions)
            ->map(function ($item) {
                $mode = $this->distributionMode(data_get($item, 'distribution_mode'));
                $icuInfo = $this->bonusInfoPayload(data_get($item, 'icu_bonus_info', []));
                $nicuInfo = $this->bonusInfoPayload(data_get($item, 'nicu_bonus_info', []));

                return [
                    'nik' => data_get($item, 'nik'),
                    'pegawai_name' => data_get($item, 'pegawai_name'),
                    'pegawai_position' => data_get($item, 'pegawai_position'),
                    'distribution_mode' => $mode,
                    'distribution_mode_label' => $this->distributionModeLabel($mode),
                    'total_final' => data_get($item, 'total_final'),
                    'jumlah_penerima' => data_get($item, 'jumlah_penerima'),
                    'total_dasar' => data_get($item, 'total_dasar'),
                    'has_icu_bonus' => (bool) data_get($item, 'has_icu_bonus'),
                    'total_icu' => data_get($item, 'total_icu'),
                    'icu_bonus_info' => $icuInfo,
                    'icu_bonus_summary' => $this->bonusSummary($icuInfo, 'ICU'),
                    'has_nicu_bonus' => (bool) data_get($item, 'has_nicu_bonus'),
                    'total_nicu' => data_get($item, 'total_nicu'),
                    'nicu_bonus_info' => $nicuInfo,
                    'nicu_bonus_summary' => $this->bonusSummary($nicuInfo, 'NICU'),
                    'total_diterima' => data_get($item, 'total_diterima'),
                ];
            })
            ->values();
    }

    private function distributionMode(?string $mode): string
    {
        return in_array($mode, ['split_evenly', 'full_amount'], true)
            ? $mode
            : 'split_evenly';
    }

    private function distributionModeLabel(?string $mode): string
    {
        return $this->distributionMode($mode) === 'full_amount'
            ? 'Nilai final penuh per pegawai'
            : 'Dibagi rata ke pegawai';
    }

    private function icuPoolPayload(array $pool): array
    {
        $items = collect($pool['items'] ?? []);

        return [
            'total' => round((float) ($pool['total'] ?? 0), 2),
            'source_count' => (int) ($pool['source_count'] ?? $items->count()),
            'locked_count' => (int) ($pool['locked_count'] ?? $items->where('is_locked', true)->count()),
            'sources' => $items
                ->map(fn ($item) => [
                    'id' => (int) data_get($item, 'id'),
                    'periode' => data_get($item, 'periode'),
                    'source_periode' => data_get($item, 'source_periode'),
                    'grand_total' => round((float) data_get($item, 'grand_total', 0), 2),
                    'total_premi_medis_pool' => round((float) data_get($item, 'total_premi_medis_pool', 0), 2),
                    'is_locked' => (bool) data_get($item, 'is_locked'),
                ])
                ->values()
                ->all(),
        ];
    }

    private function premiPayload(?object $premi): ?array
    {
        if (! $premi) {
            return null;
        }

        return [
            'id' => (int) $premi->id,
            'kode' => $premi->kode,
            'jenis' => $premi->jenis,
            'pembagi' => max(1, (int) ($premi->pembagi ?? 1)),
            'label' => trim($premi->kode.' - '.$premi->jenis),
        ];
    }

    private function bonusInfoPayload($info): array
    {
        if (is_string($info)) {
            $decoded = json_decode($info, true);
            $info = is_array($decoded) ? $decoded : [];
        }

        $info = is_array($info) ? $info : [];

        return [
            'total' => round((float) data_get($info, 'total', 0), 2),
            'source_count' => (int) data_get($info, 'source_count', 0),
            'roles' => collect(data_get($info, 'roles', []))->filter()->values()->all(),
            'sources' => collect(data_get($info, 'sources', []))->values()->all(),
        ];
    }

    private function bonusSummary(array $info, string $label): string
    {
        if ((float) data_get($info, 'total', 0) <= 0) {
            return "Tidak ada penerimaan {$label}.";
        }

        $roles = collect(data_get($info, 'roles', []))->filter()->implode(', ');
        $sourceCount = (int) data_get($info, 'source_count', 0);

        return trim("Premi medis per orang {$label} dari {$sourceCount} sumber terkunci"
            .($roles ? " ({$roles})" : '').'.');
    }

    private function actionOptions(int $jnsPremiId): Collection
    {
        return $this->repository
            ->getPremiActionOptions($jnsPremiId)
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'jenis' => $item->jenis,
                'mapping_premi_id' => (int) $item->mapping_premi_id,
                'jenis_mapping' => $item->jenis_umum,
                'jenis_umum' => $item->jenis_umum,
                'jenis_bpjs' => $item->jenis_bpjs,
                'nilai_umum' => (float) $item->nilai_umum,
                'nilai_bpjs' => (float) $item->nilai_bpjs,
                'nilai_bersama_umum' => (float) $item->nilai_bersama_umum,
                'nilai_bersama_bpjs' => (float) $item->nilai_bersama_bpjs,
                'jumlah_mapping_tindakan' => (int) $item->jumlah_mapping_tindakan,
                'text' => trim($item->kode.' - '.$item->jenis),
            ])
            ->values();
    }

    private function doctorFilterPayload(int $configId): array
    {
        $selectedDoctors = $this->repository->getSelectedDoctors($configId);
        $selectedActions = $this->repository->getSelectedDoctorActions($configId);
        $count = $selectedDoctors->count();
        $actionCount = $selectedActions->count();
        $summary = 'Filter dokter nonaktif.';

        if ($count > 0 && $actionCount > 0) {
            $summary = "{$count} dokter terpilih masuk tindakan dokter pada {$actionCount} tindakan; dokter lain diarahkan ke tindakan perawat bila mapping sumbernya tersedia.";
        } elseif ($count > 0) {
            $summary = 'Dokter sudah dipilih, tetapi belum ada tindakan yang difilter dokter.';
        }

        return [
            'mode' => $count > 0 && $actionCount > 0 ? 'selected' : 'all',
            'mode_label' => $count > 0 && $actionCount > 0
                ? "{$count} dokter / {$actionCount} tindakan"
                : 'Semua dokter',
            'summary' => $summary,
            'selected_count' => $count,
            'selected_codes' => $selectedDoctors
                ->pluck('kd_dokter')
                ->values(),
            'selected_action_count' => $actionCount,
            'selected_action_ids' => $selectedActions
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values(),
            'selected_actions' => $selectedActions
                ->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'kode' => $item->kode,
                    'jenis' => $item->jenis,
                    'text' => trim($item->kode.' - '.$item->jenis),
                ])
                ->values(),
            'selected_doctors' => $selectedDoctors
                ->map(fn ($item) => [
                    'id' => $item->kd_dokter,
                    'kd_dokter' => $item->kd_dokter,
                    'nm_dokter' => $item->nm_dokter,
                    'text' => trim($item->kd_dokter.' - '.$item->nm_dokter),
                ])
                ->values(),
        ];
    }

    private function selectedConfigPremiId(
        ?int $requestPremiId,
        ?object $config = null,
        string $jenis = 'umum'
    ): int
    {
        $config ??= $this->repository->getConfig();
        $configuredId = $jenis === 'bpjs'
            ? data_get($config, 'jnsPremi_bpjs_id')
            : data_get($config, 'jnsPremi_umum_id');
        $jnsPremiId = (int) ($configuredId ?: ($config->jnsPremi_id ?? $requestPremiId ?? 0));

        if ($jnsPremiId < 1 || ! $this->repository->findPremi($jnsPremiId)) {
            throw ValidationException::withMessages([
                'jnsPremi_id' => 'Konfigurasi sumber mapping premi '.strtoupper($jenis).' wajib dipilih terlebih dahulu.',
            ]);
        }

        return $jnsPremiId;
    }

    private function normalizeSourceMappings(array $sourceMappings): array
    {
        return collect($sourceMappings)
            ->map(fn ($item) => [
                'source_pattern' => (string) ($item['source_pattern'] ?? ''),
                'jnsTindakan_id' => (int) ($item['jnsTindakan_id'] ?? 0),
            ])
            ->filter(fn ($item) => $item['source_pattern'] !== '' && $item['jnsTindakan_id'] > 0)
            ->unique(fn ($item) => $item['source_pattern'].'|'.$item['jnsTindakan_id'])
            ->values()
            ->all();
    }

    private function normalizeTindakanIds(array $tindakanIds): array
    {
        return collect($tindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeDoctorCodes(array $doctorCodes): array
    {
        return collect($doctorCodes)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function assertDoctorsExist(array $doctorCodes): void
    {
        if (empty($doctorCodes)) {
            return;
        }

        $foundCodes = $this->repository
            ->findDoctors(collect($doctorCodes))
            ->pluck('kd_dokter')
            ->map(fn ($code) => (string) $code);
        $invalidCodes = collect($doctorCodes)
            ->reject(fn ($code) => $foundCodes->contains((string) $code))
            ->values();

        if ($invalidCodes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'doctor_codes' => 'Dokter tidak ditemukan di data Khanza: '.$invalidCodes->implode(', '),
            ]);
        }
    }

    private function assertDoctorActionsBelongToPremi(array $jnsPremiIds, array $tindakanIds): void
    {
        if (empty($tindakanIds)) {
            return;
        }

        $allowedIds = $this->premiActionIds($jnsPremiIds);
        $invalidIds = collect($tindakanIds)
            ->reject(fn ($id) => $allowedIds->contains((int) $id))
            ->values();

        if ($invalidIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'doctor_tindakan_ids' => 'Tindakan filter dokter hanya boleh dari mapping premi aktif.',
            ]);
        }
    }

    private function assertSourceMappingsBelongToPremi(array $jnsPremiIds, array $sourceMappings): void
    {
        if (empty($sourceMappings)) {
            return;
        }

        $allowedIds = $this->premiActionIds($jnsPremiIds);
        $invalidIds = collect($sourceMappings)
            ->pluck('jnsTindakan_id')
            ->unique()
            ->reject(fn ($id) => $allowedIds->contains((int) $id))
            ->values();

        if ($invalidIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'source_mappings' => 'Mapping sumber hanya boleh memilih tindakan dari mapping premi aktif.',
            ]);
        }
    }

    private function premiActionIds(array $jnsPremiIds): Collection
    {
        return collect($jnsPremiIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->flatMap(fn ($id) => $this->repository->getPremiActionOptions($id)->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function resolveDependencyId(?int $requestedId, ?int $existingId, Collection $options): ?int
    {
        $optionIds = $options->pluck('id')->map(fn ($id) => (int) $id);

        if ($requestedId && $optionIds->contains($requestedId)) {
            return $requestedId;
        }

        if ($existingId && $optionIds->contains((int) $existingId)) {
            return (int) $existingId;
        }

        if ($options->count() === 1) {
            return (int) $options->first()['id'];
        }

        return null;
    }

    private function ignoredDependencies(string $jenis, ?object $source): array
    {
        return [
            'ugd' => $jenis === 'bpjs' && (bool) data_get($source, 'bpjs_ignore_ugd', false),
            'vk' => $jenis === 'bpjs' && (bool) data_get($source, 'bpjs_ignore_vk', false),
        ];
    }

    private function dependencyPolicyLabel(array $ignoredDependencies): string
    {
        $ignored = collect([
            'UGD' => $ignoredDependencies['ugd'] ?? false,
            'VK' => $ignoredDependencies['vk'] ?? false,
        ])->filter()->keys();

        return $ignored->isEmpty()
            ? 'UGD dan VK aktif'
            : $ignored->implode(' dan ').' diabaikan untuk BPJS';
    }

    private function dependenciesAreLocked(array $dependencies, array $ignoredDependencies = []): bool
    {
        return collect(['ugd', 'vk'])
            ->every(fn (string $key) => ($ignoredDependencies[$key] ?? false)
                || (bool) data_get($dependencies, "{$key}.is_locked"));
    }

    private function readinessMessage(
        array $dependencies,
        array $dependencyOptions,
        array $ignoredDependencies = []
    ): string {
        $activeLabels = collect(['UGD', 'VK'])
            ->reject(fn (string $label) => $ignoredDependencies[strtolower($label)] ?? false)
            ->values();

        if ($activeLabels->isEmpty()) {
            return 'UGD dan VK BPJS diabaikan sesuai konfigurasi.';
        }

        $notSelected = collect([
            'UGD' => [
                'dependency' => $dependencies['ugd'],
                'options' => $dependencyOptions['ugd'],
            ],
            'VK' => [
                'dependency' => $dependencies['vk'],
                'options' => $dependencyOptions['vk'],
            ],
        ])->reject(fn ($item, string $key) => $ignoredDependencies[strtolower($key)] ?? false)
            ->filter(fn ($item) => ! $item['dependency'] && $item['options']->isNotEmpty())
            ->keys();

        if ($notSelected->isNotEmpty()) {
            return 'Pilih sumber data '.$notSelected->implode(' dan ').' terlebih dahulu.';
        }

        $missing = collect([
            'UGD' => $dependencies['ugd'],
            'VK' => $dependencies['vk'],
        ])->reject(fn ($value, string $key) => $ignoredDependencies[strtolower($key)] ?? false)
            ->filter(fn ($value) => ! $value)
            ->keys();

        if ($missing->isNotEmpty()) {
            return 'Generate terlebih dahulu: '.$missing->implode(' dan ').'.';
        }

        $unlocked = collect([
            'UGD' => $dependencies['ugd'],
            'VK' => $dependencies['vk'],
        ])->reject(fn ($value, string $key) => $ignoredDependencies[strtolower($key)] ?? false)
            ->filter(fn ($value) => ! data_get($value, 'is_locked'))
            ->keys();

        if ($unlocked->isNotEmpty()) {
            return 'Kunci terlebih dahulu: '.$unlocked->implode(' dan ').'.';
        }

        return 'Data '.$activeLabels->implode(' dan ').' sudah tersedia, terpilih, dan terkunci.';
    }

    private function readinessSteps(
        array $dependencies,
        array $dependencyOptions,
        bool $hasRecipients,
        ?array $calculation,
        ?generateTindakanMedisModel $existing,
        string $sourcePeriode,
        string $dependencySourcePeriode,
        array $doctorFilter,
        array $ignoredDependencies = []
    ): array {
        $jumlahMapping = (int) (
            $calculation['jumlah_mapping_premi']
            ?? $existing?->jumlah_mapping_premi
            ?? 0
        );
        $jumlahTransaksi = (int) (
            $calculation['jumlah_transaksi']
            ?? $existing?->jumlah_transaksi
            ?? 0
        );
        $dependenciesReady = $this->dependenciesAreLocked($dependencies, $ignoredDependencies);
        $dependencyWaitLabel = collect(['UGD', 'VK'])
            ->reject(fn (string $label) => $ignoredDependencies[strtolower($label)] ?? false)
            ->implode(' dan ') ?: 'konfigurasi aktif';

        $steps = [
            [
                'key' => 'rawat',
                'label' => 'Tindakan Rawat',
                'status' => $jumlahMapping > 0
                    ? 'success'
                    : ($dependenciesReady ? 'danger' : 'muted'),
                'value' => $jumlahMapping > 0
                    ? "{$jumlahMapping} mapping"
                    : 'Belum terhitung',
                'note' => $jumlahMapping > 0
                    ? "{$jumlahTransaksi} transaksi dari periode sumber {$sourcePeriode}. ".$doctorFilter['summary']
                    : "Preview tindakan menunggu {$dependencyWaitLabel} terpilih serta terkunci. Periode tindakan {$sourcePeriode}.",
            ],
            $this->dependencyStep('ugd', 'UGD', $dependencies['ugd'], $dependencyOptions['ugd'], $dependencySourcePeriode, $ignoredDependencies['ugd'] ?? false),
            $this->dependencyStep('vk', 'VK', $dependencies['vk'], $dependencyOptions['vk'], $dependencySourcePeriode, $ignoredDependencies['vk'] ?? false),
            [
                'key' => 'pegawai',
                'label' => 'Penerima',
                'status' => $hasRecipients ? 'success' : 'danger',
                'value' => $hasRecipients ? 'Pegawai tersedia' : 'Belum ada pegawai',
                'note' => $hasRecipients
                    ? 'Distribusi mengikuti pegawai pada mapping premi aktif.'
                    : 'Tambahkan pegawai penerima pada mapping premi aktif.',
            ],
        ];

        return $steps;
    }

    private function dependencyStep(
        string $key,
        string $label,
        ?array $dependency,
        Collection $options,
        string $sourcePeriode,
        bool $ignored = false
    ): array {
        if ($ignored) {
            return [
                'key' => $key,
                'label' => $label,
                'status' => 'success',
                'value' => 'Diabaikan',
                'note' => "{$label} BPJS tidak masuk hitungan dan tidak wajib dipilih.",
            ];
        }

        if ($dependency) {
            $locked = (bool) data_get($dependency, 'is_locked');
            $generatedCount = data_get($dependency, 'generated_count');
            $lockNote = $generatedCount === null
                ? 'snapshot tersimpan'
                : (int) data_get($dependency, 'locked_count', 0)
                    .' dari '
                    .(int) $generatedCount
                    .' data terkunci';

            return [
                'key' => $key,
                'label' => $label,
                'status' => $locked ? 'success' : 'warning',
                'value' => $locked ? 'Terkunci' : 'Belum dikunci',
                'note' => trim(
                    (data_get($dependency, 'ploting_label') ?: "Sumber {$label}")
                    ." / {$lockNote} / periode {$sourcePeriode}."
                ),
            ];
        }

        if ($options->isNotEmpty()) {
            return [
                'key' => $key,
                'label' => $label,
                'status' => 'warning',
                'value' => 'Perlu dipilih',
                'note' => "{$options->count()} sumber {$label} periode {$sourcePeriode} tersedia.",
            ];
        }

        return [
            'key' => $key,
            'label' => $label,
            'status' => 'danger',
            'value' => 'Belum tersedia',
            'note' => "Generate {$label} periode {$sourcePeriode} terlebih dahulu.",
        ];
    }

    private function dependencyPayload(
        ?array $dependency,
        ?generateTindakanMedisModel $existing,
        string $key,
        bool $ignored = false
    ): array
    {
        if ($ignored) {
            return [
                'exists' => false,
                'ignored' => true,
                'is_locked' => true,
                'total' => 0,
                'jumlah_data' => 0,
                'ploting_label' => strtoupper($key).' BPJS diabaikan',
            ];
        }

        if ($dependency) {
            return collect($dependency)
                ->except('items')
                ->all();
        }

        if (! $existing) {
            return [
                'exists' => false,
                'is_locked' => false,
                'total' => 0,
                'jumlah_data' => 0,
            ];
        }

        return [
            'exists' => true,
            'plotingPremi_id' => $key === 'ugd'
                ? $existing->ugd_plotingPremi_id
                : $existing->vk_plotingPremi_id,
            'ploting_label' => $key === 'ugd'
                ? $this->plotingLabel($existing->ugd_kode_ploting, $existing->ugd_nama_ploting)
                : $this->plotingLabel($existing->vk_kode_ploting, $existing->vk_nama_ploting),
            'generated_count' => null,
            'locked_count' => null,
            'jumlah_data' => null,
            'total' => $key === 'ugd' ? $existing->total_ugd : $existing->total_vk,
            'is_locked' => $existing->is_locked,
            'updated_at' => optional($existing->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function existingDependencySnapshot(
        generateTindakanMedisModel $existing,
        string $key
    ): array {
        return [
            'exists' => true,
            'plotingPremi_id' => $key === 'ugd'
                ? $existing->ugd_plotingPremi_id
                : $existing->vk_plotingPremi_id,
            'ploting_label' => $key === 'ugd'
                ? $this->plotingLabel($existing->ugd_kode_ploting, $existing->ugd_nama_ploting)
                : $this->plotingLabel($existing->vk_kode_ploting, $existing->vk_nama_ploting),
            'generated_count' => null,
            'locked_count' => null,
            'jumlah_data' => null,
            'total' => $key === 'ugd' ? $existing->total_ugd : $existing->total_vk,
            'is_locked' => true,
            'updated_at' => optional($existing->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function rawatBreakdowns(Collection $rawat): array
    {
        $sourceBreakdown = $rawat
            ->groupBy(fn ($row) => data_get($row, 'source_label') ?: data_get($row, 'source_table') ?: '-')
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'count' => $items->count(),
                'total_biaya_rawat' => round((float) $items->sum('biaya_rawat'), 2),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
        $doctorBreakdown = $rawat
            ->filter(fn ($row) => filled(data_get($row, 'kd_dokter')))
            ->groupBy(fn ($row) => data_get($row, 'kd_dokter'))
            ->map(function (Collection $items, string $code) {
                $first = $items->first();

                return [
                    'kd_dokter' => $code,
                    'nm_dokter' => data_get($first, 'nm_dokter'),
                    'count' => $items->count(),
                    'total_biaya_rawat' => round((float) $items->sum('biaya_rawat'), 2),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
        $paramedicBreakdown = $rawat
            ->filter(fn ($row) => filled(data_get($row, 'nip')))
            ->groupBy(fn ($row) => data_get($row, 'nip'))
            ->map(function (Collection $items, string $nip) {
                $first = $items->first();

                return [
                    'nip' => $nip,
                    'nama_petugas' => data_get($first, 'nama_petugas'),
                    'count' => $items->count(),
                    'total_biaya_rawat' => round((float) $items->sum('biaya_rawat'), 2),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'jumlah_data_dokter' => $rawat
                ->filter(fn ($row) => filled(data_get($row, 'kd_dokter')))
                ->count(),
            'jumlah_data_paramedis' => $rawat
                ->filter(fn ($row) => filled(data_get($row, 'nip')))
                ->count(),
            'jumlah_data_drpr' => $rawat
                ->filter(fn ($row) => in_array(data_get($row, 'source_table'), ['rawat_jl_drpr', 'rawat_inap_drpr'], true))
                ->count(),
            'jumlah_data_karcis_bpjs' => $rawat
                ->where('jenis_pelayanan_sumber', 'bpjs_karcis')
                ->count(),
            'jumlah_data_dialihkan_perawat' => $rawat
                ->where('route_reason', 'doctor_filter_non_selected')
                ->count(),
            'source_breakdown' => $sourceBreakdown,
            'doctor_breakdown' => $doctorBreakdown,
            'paramedic_breakdown' => $paramedicBreakdown,
        ];
    }

    private function previewDetails($details): array
    {
        return collect($details)
            ->map(function ($detail) {
                $rawat = collect(data_get($detail, 'data_rawat', []));
                $breakdowns = $this->rawatBreakdowns($rawat);

                return [
                    'mapping_premi_id' => data_get($detail, 'mapping_premi_id'),
                    'jnsTindakan_id' => data_get($detail, 'jnsTindakan_id'),
                    'kode_jenis_tindakan' => data_get($detail, 'kode_jenis_tindakan'),
                    'nama_jenis_tindakan' => data_get($detail, 'nama_jenis_tindakan'),
                    'jenis_mapping' => data_get($detail, 'jenis_mapping'),
                    'nilai_mapping' => data_get($detail, 'nilai_mapping'),
                    'source_rules' => data_get($detail, 'source_rules', []),
                    'jumlah_data' => data_get($detail, 'jumlah_data'),
                    'jumlah_data_dokter' => $breakdowns['jumlah_data_dokter'],
                    'jumlah_data_paramedis' => $breakdowns['jumlah_data_paramedis'],
                    'jumlah_data_drpr' => $breakdowns['jumlah_data_drpr'],
                    'jumlah_data_karcis_bpjs' => $breakdowns['jumlah_data_karcis_bpjs'],
                    'jumlah_data_dialihkan_perawat' => $breakdowns['jumlah_data_dialihkan_perawat'],
                    'total_biaya_rawat' => data_get($detail, 'total_biaya_rawat'),
                    'dasar_hitung' => data_get($detail, 'dasar_hitung'),
                    'hasil_mapping' => data_get($detail, 'hasil_mapping'),
                    'source_breakdown' => $breakdowns['source_breakdown'],
                    'doctor_breakdown' => $breakdowns['doctor_breakdown'],
                    'paramedic_breakdown' => $breakdowns['paramedic_breakdown'],
                ];
            })
            ->values()
            ->all();
    }

    private function distributionInsight(Collection $distributions, float $totalFinal): array
    {
        $count = $distributions->count();
        $totals = $distributions->pluck('total_diterima')->map(fn ($value) => (float) $value);
        $totalBonus = round(
            (float) $distributions->sum('total_icu')
            + (float) $distributions->sum('total_nicu'),
            2
        );

        return [
            'jumlah_penerima' => $count,
            'average_total' => $count > 0 ? round((float) $totals->avg(), 2) : 0,
            'highest_total' => $count > 0 ? round((float) $totals->max(), 2) : 0,
            'lowest_total' => $count > 0 ? round((float) $totals->min(), 2) : 0,
            'total_bonus' => $totalBonus,
            'bonus_recipient_count' => $distributions
                ->filter(fn ($item) => (float) data_get($item, 'total_icu', 0) > 0
                    || (float) data_get($item, 'total_nicu', 0) > 0)
                ->count(),
            'total_final' => round($totalFinal, 2),
        ];
    }

    private function previewInsight(array $details): array
    {
        $rows = collect($details);
        $sourceCount = $rows
            ->flatMap(fn ($detail) => collect(data_get($detail, 'source_breakdown', []))->pluck('label'))
            ->filter()
            ->unique()
            ->count();
        $topDetails = $rows
            ->sortByDesc(fn ($detail) => (float) data_get($detail, 'hasil_mapping', 0))
            ->take(3)
            ->map(fn ($detail) => [
                'kode_jenis_tindakan' => data_get($detail, 'kode_jenis_tindakan'),
                'nama_jenis_tindakan' => data_get($detail, 'nama_jenis_tindakan'),
                'hasil_mapping' => round((float) data_get($detail, 'hasil_mapping', 0), 2),
                'jumlah_data' => (int) data_get($detail, 'jumlah_data', 0),
            ])
            ->values()
            ->all();

        return [
            'jumlah_tindakan' => $rows->count(),
            'jumlah_data' => (int) $rows->sum('jumlah_data'),
            'jumlah_data_dokter' => (int) $rows->sum('jumlah_data_dokter'),
            'jumlah_data_paramedis' => (int) $rows->sum('jumlah_data_paramedis'),
            'jumlah_data_drpr' => (int) $rows->sum('jumlah_data_drpr'),
            'jumlah_data_dialihkan_perawat' => (int) $rows->sum('jumlah_data_dialihkan_perawat'),
            'jumlah_sumber' => $sourceCount,
            'top_details' => $topDetails,
        ];
    }

    private function karcisRuleMessage(string $jenis): string
    {
        $count = $this->repository->getKarcisTindakanIds()->count();

        if ($count < 1) {
            return 'Belum ada tindakan karcis BPJS yang dikonfigurasi.';
        }

        return $jenis === 'bpjs'
            ? "{$count} tindakan karcis BPJS dikecualikan dari Generate BPJS."
            : "{$count} tindakan karcis BPJS ikut ditambahkan ke Generate UMUM dengan nilai hitung BPJS.";
    }

    private function sourceMappingsPayload(Collection $sourceMappings): array
    {
        return $sourceMappings
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'source_pattern' => $item->source_pattern,
                'jnsTindakan_id' => (int) $item->jnsTindakan_id,
                'kode_jenis_tindakan' => $item->kode_jenis_tindakan,
                'nama_jenis_tindakan' => $item->nama_jenis_tindakan,
                'tindakan_label' => trim($item->kode_jenis_tindakan.' - '.$item->nama_jenis_tindakan),
            ])
            ->values()
            ->all();
    }

    private function lockPayload($result): array
    {
        return [
            'id' => $result->id,
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
        ];
    }

    private function typeLabel(string $jenis): string
    {
        return $jenis === 'bpjs' ? 'BPJS' : 'Umum';
    }

    private function plotingLabel(?string $kode, ?string $nama): string
    {
        return trim(($kode ? $kode.' - ' : '').($nama ?? '-'));
    }
}
