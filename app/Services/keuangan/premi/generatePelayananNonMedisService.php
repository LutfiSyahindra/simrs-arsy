<?php

namespace App\Services\keuangan\premi;

use App\Models\dbSimrs\premiPelayananNonMedisModel;
use App\Models\User;
use App\Repositories\keuangan\premi\generatePelayananNonMedisRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generatePelayananNonMedisService
{
    public function __construct(
        protected generatePelayananNonMedisRepository $repository
    ) {}

    public function getResults(
        ?string $periode = null,
        ?string $jenis = null,
        ?int $jnsPremiId = null
    ) {
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

    public function getMappingPremiOptions()
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

    public function getConfig(): array
    {
        return $this->configPayload($this->repository->getConfig());
    }

    public function updateConfig(
        int $jnsPremiUmumId,
        int $jnsPremiBpjsId,
        string $distributionMode,
        array $karcisTindakanIds
    ): array
    {
        return $this->configPayload(
            $this->repository->saveConfig(
                $jnsPremiUmumId,
                $jnsPremiBpjsId,
                $distributionMode,
                collect($karcisTindakanIds)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all()
            )
        );
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
        ?int $generateBhpId = null,
        ?int $generateKamarId = null
    ): array {
        $config = $this->repository->getConfig();
        $jnsPremiId = $this->selectedConfigPremiId($jnsPremiId, $config, $jenis);
        $distributionMode = $this->distributionMode($config->distribution_mode ?? null);
        $existing = $this->repository->findByPeriodAndType(
            $periode,
            $jenis,
            $jnsPremiId
        );
        $dependencyOptions = $this->repository->getDependencyOptions($periode, $jenis);
        if ($existing?->is_locked) {
            $generateBhpId = $existing->generate_bhp_id;
            $generateKamarId = $existing->generate_kamar_inap_id;
        } else {
            $generateBhpId = $this->resolveDependencyId(
                $generateBhpId,
                $existing?->generate_bhp_id,
                $dependencyOptions['bhp']
            );
            $generateKamarId = $this->resolveDependencyId(
                $generateKamarId,
                $existing?->generate_kamar_inap_id,
                $dependencyOptions['kamar']
            );
        }
        $dependencies = $this->repository->getDependencies(
            $periode,
            $jenis,
            false,
            $generateBhpId,
            $generateKamarId
        );
        $selectedPremi = $this->repository->findPremi($jnsPremiId);
        $calculation = null;
        $dependenciesReady = $this->dependenciesAreLocked($dependencies);

        if ($dependenciesReady && ! $existing?->is_locked) {
            $calculation = $this->repository->calculate(
                $periode,
                $jenis,
                $jnsPremiId
            );
        }

        $totalBhp = $calculation
            ? $dependencies['bhp']->total_bhp
            : ($existing?->total_bhp ?? $dependencies['bhp']?->total_bhp ?? 0);
        $totalKamar = $calculation
            ? $dependencies['kamar']->total_lama_inap
            : ($existing?->total_kamar_inap ?? $dependencies['kamar']?->total_lama_inap ?? 0);
        $totalMapping = $calculation['total_mapping_premi']
            ?? $existing?->total_mapping_premi
            ?? 0;
        $pembagi = max(1, (int) (
            $calculation['pembagi']
            ?? $selectedPremi?->pembagi
            ?? 1
        ));
        $totalSebelumPembagi = round(
            (float) $totalMapping
            + (float) $totalBhp
            + (float) $totalKamar,
            2
        );
        $totalFinal = $calculation
            ? round($totalSebelumPembagi / $pembagi, 2)
            : ($existing?->total_final ?? 0);
        $pegawai = $this->repository->getMappedPegawai($jnsPremiId);
        $distributions = $existing?->is_locked
            ? $this->distributionPayload($existing->distributions ?? collect())
            : $this->distributeFinal($totalFinal, $pegawai, $jnsPremiId, $distributionMode);
        $distributionMode = data_get($distributions->first(), 'distribution_mode')
            ?? $distributionMode;

        return [
            'periode' => $periode,
            'periode_sumber' => PremiSourcePeriod::resolve($periode, $jenis),
            'jenis_pelayanan' => $jenis,
            'jenis_pelayanan_label' => $this->typeLabel($jenis),
            'jnsPremi_id' => $jnsPremiId,
            'kode_premi' => $calculation['kode_premi']
                ?? $existing?->kode_premi
                ?? $selectedPremi?->kode,
            'nama_premi' => $calculation['nama_premi']
                ?? $existing?->nama_premi
                ?? $selectedPremi?->jenis,
            'dependency_bhp' => $this->dependencyPayload(
                $dependencies['bhp'],
                'total_bhp'
            ),
            'dependency_kamar' => $this->dependencyPayload(
                $dependencies['kamar'],
                'total_lama_inap'
            ),
            'dependency_options' => [
                'bhp' => $dependencyOptions['bhp']
                    ->map(fn ($row) => $this->sourceOptionPayload($row, 'total_bhp'))
                    ->values(),
                'kamar' => $dependencyOptions['kamar']
                    ->map(fn ($row) => $this->sourceOptionPayload($row, 'total_lama_inap'))
                    ->values(),
            ],
            'selected_generate_bhp_id' => $dependencies['bhp']?->id,
            'selected_generate_kamar_inap_id' => $dependencies['kamar']?->id,
            'ready' => $dependenciesReady,
            'readiness_message' => $this->readinessMessage($dependencies, $dependencyOptions),
            'is_generated' => (bool) $existing,
            'jumlah_transaksi' => $calculation['jumlah_transaksi']
                ?? $existing?->jumlah_transaksi
                ?? 0,
            'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan']
                ?? $existing?->jumlah_jenis_tindakan
                ?? 0,
            'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi']
                ?? $existing?->jumlah_mapping_premi
                ?? 0,
            'total_biaya_rawat' => $calculation['total_biaya_rawat']
                ?? $existing?->total_biaya_rawat
                ?? 0,
            'total_mapping_premi' => $totalMapping,
            'total_bhp' => $totalBhp,
            'total_kamar_inap' => $totalKamar,
            'total_sebelum_pembagi' => $totalSebelumPembagi,
            'pembagi' => $pembagi,
            'total_final' => $totalFinal,
            'jumlah_penerima' => $distributions->count(),
            'distribution_mode' => $distributionMode,
            'distribution_mode_label' => $this->distributionModeLabel($distributionMode),
            'total_dibagikan' => round((float) $distributions->sum('total_diterima'), 2),
            'total_belum_dibagikan' => max(
                0,
                round((float) $totalFinal - (float) $distributions->sum('total_diterima'), 2)
            ),
            'distributions' => $distributions->values(),
            'is_locked' => $existing?->is_locked ?? false,
            'locked_at' => optional($existing?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $existing?->lockedBy?->name,
            'karcis_config_count' => $this->repository->getKarcisTindakanIds()->count(),
            'karcis_source_period' => PremiSourcePeriod::resolve($periode, 'bpjs'),
            'karcis_rule_message' => $this->karcisRuleMessage($jenis),
            'preview_details' => $calculation
                ? $this->previewDetails($calculation['details'])
                : $this->previewDetails($existing?->details ?? []),
        ];
    }

    public function generate(
        string $periode,
        string $jenis,
        ?int $jnsPremiId,
        int $generateBhpId,
        int $generateKamarId
    ): array {
        return DB::transaction(function () use (
            $periode,
            $jenis,
            $jnsPremiId,
            $generateBhpId,
            $generateKamarId
        ) {
            $config = $this->repository->getConfig();
            $jnsPremiId = $this->selectedConfigPremiId($jnsPremiId, $config, $jenis);
            $distributionMode = $this->distributionMode($config->distribution_mode ?? null);
            $existing = $this->repository
                ->findByPeriodAndTypeForUpdate($periode, $jenis, $jnsPremiId);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Pelayanan Non Medis {$this->typeLabel($jenis)} periode {$periode} sudah dikunci.",
                ]);
            }

            $dependencies = $this->repository->getDependencies(
                $periode,
                $jenis,
                true,
                $generateBhpId,
                $generateKamarId
            );
            $missing = collect([
                'BHP' => $dependencies['bhp'],
                'Kamar Inap' => $dependencies['kamar'],
            ])->filter(fn ($value) => ! $value)->keys();

            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => 'Generate terlebih dahulu: '.$missing->implode(' dan ').'.',
                ]);
            }

            $unlocked = collect([
                'BHP' => $dependencies['bhp'],
                'Kamar Inap' => $dependencies['kamar'],
            ])->filter(fn ($value) => ! $value->is_locked)->keys();

            if ($unlocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => 'Kunci terlebih dahulu: '.$unlocked->implode(' dan ').'.',
                ]);
            }

            $calculation = $this->repository->calculate(
                $periode,
                $jenis,
                $jnsPremiId
            );

            if ($calculation['jumlah_mapping_premi'] === 0) {
                $sourcePeriod = PremiSourcePeriod::resolve($periode, $jenis);
                $selectedPremi = $this->repository->findPremi($jnsPremiId);
                $premiName = trim(
                    ($selectedPremi?->kode ? $selectedPremi->kode.' - ' : '')
                    .($selectedPremi?->jenis ?? 'mapping premi terpilih')
                );

                throw ValidationException::withMessages([
                    'mapping' => "Tidak ada transaksi periode sumber {$sourcePeriod} yang sesuai dengan {$premiName}.",
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

            $result = $this->repository->saveResult(
                $periode,
                $jenis,
                $jnsPremiId,
                $dependencies,
                $calculation
            );
            $this->repository->replaceDetails($result, $calculation['details']);
            $this->repository->replaceDistributions(
                $result,
                $this->distributeFinal(
                    (float) $result->total_final,
                    $pegawai,
                    $jnsPremiId,
                    $distributionMode
                )
            );

            return $this->resultPayload(
                $result->fresh(['generateBhp', 'generateKamar', 'jnsPremi', 'distributions'])
            );
        });
    }

    public function getDetail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data premi pelayanan non medis tidak ditemukan.');
        }

        return [
            ...$this->resultPayload($result),
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'generate_by_name' => $result->generateBy?->name,
            'details' => $result->details->map(fn ($detail) => [
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
                'jumlah_data' => $detail->jumlah_data,
                'total_biaya_rawat' => $detail->total_biaya_rawat,
                'dasar_hitung' => $detail->dasar_hitung,
                'hasil_mapping' => $detail->hasil_mapping,
                'data_tindakan' => $detail->data_tindakan,
            ])->values(),
            'distributions' => $this->distributionPayload($result->distributions)->values(),
        ];
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data premi pelayanan non medis tidak ditemukan.');
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
                abort(404, 'Data premi pelayanan non medis tidak ditemukan.');
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

    private function resultPayload(premiPelayananNonMedisModel $result): array
    {
        return [
            'id' => $result->id,
            'periode' => $result->periode,
            'periode_sumber' => PremiSourcePeriod::resolve(
                $result->periode,
                $result->jenis_pelayanan
            ),
            'jenis_pelayanan' => $result->jenis_pelayanan,
            'jenis_pelayanan_label' => $this->typeLabel($result->jenis_pelayanan),
            'jnsPremi_id' => $result->jnsPremi_id,
            'kode_premi' => $result->kode_premi,
            'nama_premi' => $result->nama_premi,
            'jumlah_transaksi' => $result->jumlah_transaksi,
            'jumlah_jenis_tindakan' => $result->jumlah_jenis_tindakan,
            'jumlah_mapping_premi' => $result->jumlah_mapping_premi,
            'total_biaya_rawat' => $result->total_biaya_rawat,
            'total_mapping_premi' => $result->total_mapping_premi,
            'total_bhp' => $result->total_bhp,
            'total_kamar_inap' => $result->total_kamar_inap,
            'total_sebelum_pembagi' => round(
                (float) $result->total_mapping_premi
                + (float) $result->total_bhp
                + (float) $result->total_kamar_inap,
                2
            ),
            'pembagi' => max(1, (int) ($result->jnsPremi?->pembagi ?? 1)),
            'total_final' => $result->total_final,
            'jumlah_penerima' => $result->relationLoaded('distributions')
                ? $result->distributions->count()
                : (int) ($result->distributions_count ?? 0),
            'distribution_mode' => $result->relationLoaded('distributions')
                ? data_get($result->distributions->first(), 'distribution_mode', 'split_evenly')
                : 'split_evenly',
            'distribution_mode_label' => $this->distributionModeLabel(
                $result->relationLoaded('distributions')
                    ? data_get($result->distributions->first(), 'distribution_mode', 'split_evenly')
                    : 'split_evenly'
            ),
            'total_dibagikan' => $result->relationLoaded('distributions')
                ? round((float) $result->distributions->sum('total_diterima'), 2)
                : round((float) ($result->total_dibagikan ?? 0), 2),
            'total_belum_dibagikan' => max(
                0,
                round(
                    (float) $result->total_final
                    - ($result->relationLoaded('distributions')
                        ? (float) $result->distributions->sum('total_diterima')
                        : (float) ($result->total_dibagikan ?? 0)),
                    2
                )
            ),
            'generate_bhp_id' => $result->generate_bhp_id,
            'generate_kamar_inap_id' => $result->generate_kamar_inap_id,
            'generate_bhp_label' => $this->sourceLabel($result->generateBhp),
            'generate_kamar_label' => $this->sourceLabel($result->generateKamar),
        ];
    }

    private function dependencyPayload($row, string $totalField): array
    {
        return [
            'exists' => (bool) $row,
            'id' => $row?->id,
            'plotingPremi_id' => $row?->plotingPremi_id,
            'kode_ploting' => $row?->kode_ploting,
            'nama_ploting' => $row?->nama_ploting,
            'ploting_label' => $this->sourceLabel($row),
            'total' => $row?->{$totalField} ?? 0,
            'nominal_hitung' => $row?->nominal_hitung ?? 0,
            'is_locked' => $row?->is_locked ?? false,
            'updated_at' => optional($row?->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function resolveDependencyId(?int $requestedId, ?int $existingId, $options): ?int
    {
        $optionIds = $options->pluck('id')->map(fn ($id) => (int) $id);

        if ($requestedId && $optionIds->contains($requestedId)) {
            return $requestedId;
        }

        if ($existingId && $optionIds->contains((int) $existingId)) {
            return (int) $existingId;
        }

        if ($options->count() === 1) {
            return (int) $options->first()->id;
        }

        return null;
    }

    private function sourceOptionPayload($row, string $totalField): array
    {
        return [
            'id' => (int) $row->id,
            'plotingPremi_id' => $row->plotingPremi_id,
            'kode_ploting' => $row->kode_ploting,
            'nama_ploting' => $row->nama_ploting,
            'ploting_label' => $this->sourceLabel($row),
            'total' => $row->{$totalField},
            'nominal_hitung' => $row->nominal_hitung,
            'is_locked' => (bool) $row->is_locked,
            'updated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function sourceLabel($row): string
    {
        if (! $row) {
            return '-';
        }

        return trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-'));
    }

    private function selectedConfigPremiId(
        ?int $requestPremiId = null,
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

    private function configPayload(object $config): array
    {
        $legacyPremiId = data_get($config, 'jnsPremi_id') ? (int) data_get($config, 'jnsPremi_id') : null;
        $jnsPremiUmumId = (int) (data_get($config, 'jnsPremi_umum_id') ?: $legacyPremiId) ?: null;
        $jnsPremiBpjsId = (int) (data_get($config, 'jnsPremi_bpjs_id') ?: $legacyPremiId) ?: null;
        $premiUmum = $jnsPremiUmumId ? $this->repository->findPremi($jnsPremiUmumId) : null;
        $premiBpjs = $jnsPremiBpjsId ? $this->repository->findPremi($jnsPremiBpjsId) : null;
        $pegawaiUmum = $jnsPremiUmumId
            ? $this->repository->getMappedPegawai($jnsPremiUmumId)
            : collect();
        $pegawaiBpjs = $jnsPremiBpjsId
            ? $this->repository->getMappedPegawai($jnsPremiBpjsId)
            : collect();

        return [
            'id' => (int) $config->id,
            'jnsPremi_id' => $jnsPremiUmumId,
            'jnsPremi_umum_id' => $jnsPremiUmumId,
            'jnsPremi_bpjs_id' => $jnsPremiBpjsId,
            'distribution_mode' => $this->distributionMode($config->distribution_mode ?? null),
            'distribution_mode_label' => $this->distributionModeLabel(
                $this->distributionMode($config->distribution_mode ?? null)
            ),
            'premi' => $this->premiPayload($premiUmum),
            'premi_umum' => $this->premiPayload($premiUmum),
            'premi_bpjs' => $this->premiPayload($premiBpjs),
            'mapping_options' => $this->getMappingPremiOptions(),
            'karcis' => $this->getKarcisConfig(),
            'pegawai' => $this->pegawaiPayload($pegawaiUmum),
            'pegawai_umum' => $this->pegawaiPayload($pegawaiUmum),
            'pegawai_bpjs' => $this->pegawaiPayload($pegawaiBpjs),
        ];
    }

    private function premiPayload($premi): ?array
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

    private function pegawaiPayload($pegawai)
    {
        return collect($pegawai)
            ->map(fn ($item) => [
                'nik' => $item->nik,
                'pegawai_name' => $item->pegawai_name,
                'pegawai_position' => $item->pegawai_position,
                'text' => trim($item->nik.' - '.$item->pegawai_name),
            ])
            ->values();
    }

    private function distributeFinal(
        float $totalFinal,
        $pegawai,
        int $jnsPremiId,
        string $distributionMode
    )
    {
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

        return $pegawai
            ->map(function ($item, int $index) use (
                $jnsPremiId,
                $totalFinal,
                $count,
                $baseCents,
                $remainder,
                $distributionMode
            ) {
                $amountCents = $baseCents + ($index < $remainder ? 1 : 0);

                return [
                    'jnsPremi_id' => $jnsPremiId,
                    'nik' => $item->nik,
                    'pegawai_name' => $item->pegawai_name,
                    'pegawai_position' => $item->pegawai_position,
                    'distribution_mode' => $distributionMode,
                    'total_final' => round($totalFinal, 2),
                    'jumlah_penerima' => $count,
                    'total_diterima' => round($amountCents / 100, 2),
                ];
            });
    }

    private function distributionPayload($distributions)
    {
        return collect($distributions)
            ->map(fn ($item) => [
                'nik' => data_get($item, 'nik'),
                'pegawai_name' => data_get($item, 'pegawai_name'),
                'pegawai_position' => data_get($item, 'pegawai_position'),
                'distribution_mode' => $this->distributionMode(data_get($item, 'distribution_mode')),
                'distribution_mode_label' => $this->distributionModeLabel(
                    $this->distributionMode(data_get($item, 'distribution_mode'))
                ),
                'total_final' => data_get($item, 'total_final'),
                'jumlah_penerima' => data_get($item, 'jumlah_penerima'),
                'total_diterima' => data_get($item, 'total_diterima'),
            ])
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

    private function dependenciesAreLocked(array $dependencies): bool
    {
        return (bool) (
            $dependencies['bhp']?->is_locked
            && $dependencies['kamar']?->is_locked
        );
    }

    private function readinessMessage(array $dependencies, ?array $dependencyOptions = null): string
    {
        $notSelected = collect([
            'BHP' => [
                'dependency' => $dependencies['bhp'],
                'options' => $dependencyOptions['bhp'] ?? collect(),
            ],
            'Kamar Inap' => [
                'dependency' => $dependencies['kamar'],
                'options' => $dependencyOptions['kamar'] ?? collect(),
            ],
        ])->filter(
            fn ($item) => ! $item['dependency'] && $item['options']->isNotEmpty()
        )->keys();

        if ($notSelected->isNotEmpty()) {
            return 'Pilih sumber data '.$notSelected->implode(' dan ').' terlebih dahulu.';
        }

        $missing = collect([
            'BHP' => $dependencies['bhp'],
            'Kamar Inap' => $dependencies['kamar'],
        ])->filter(fn ($value) => ! $value)->keys();

        if ($missing->isNotEmpty()) {
            return 'Generate terlebih dahulu: '.$missing->implode(' dan ').'.';
        }

        $unlocked = collect([
            'BHP' => $dependencies['bhp'],
            'Kamar Inap' => $dependencies['kamar'],
        ])->filter(fn ($value) => ! $value->is_locked)->keys();

        if ($unlocked->isNotEmpty()) {
            return 'Kunci terlebih dahulu: '.$unlocked->implode(' dan ').'.';
        }

        return 'Data BHP dan Kamar Inap sudah tersedia dan terkunci.';
    }

    private function karcisRuleMessage(string $jenis): string
    {
        $count = $this->repository->getKarcisTindakanIds()->count();

        if ($count === 0) {
            return 'Belum ada tindakan karcis BPJS yang dikonfigurasi.';
        }

        return $jenis === 'bpjs'
            ? "{$count} tindakan karcis BPJS dikecualikan dari Generate BPJS."
            : "{$count} tindakan karcis BPJS ikut ditambahkan ke Generate UMUM dengan nilai hitung BPJS.";
    }

    private function previewDetails($details): array
    {
        return collect($details)
            ->map(fn ($detail) => [
                'mapping_premi_id' => data_get($detail, 'mapping_premi_id'),
                'jnsTindakan_id' => data_get($detail, 'jnsTindakan_id'),
                'kode_jenis_tindakan' => data_get($detail, 'kode_jenis_tindakan'),
                'nama_jenis_tindakan' => data_get($detail, 'nama_jenis_tindakan'),
                'jenis_mapping' => data_get($detail, 'jenis_mapping'),
                'nilai_mapping' => data_get($detail, 'nilai_mapping'),
                'jumlah_data' => data_get($detail, 'jumlah_data'),
                'jumlah_data_karcis_bpjs' => collect(data_get($detail, 'data_tindakan', []))
                    ->where('jenis_pelayanan_sumber', 'bpjs_karcis')
                    ->count(),
                'total_biaya_rawat' => data_get($detail, 'total_biaya_rawat'),
                'dasar_hitung' => data_get($detail, 'dasar_hitung'),
                'hasil_mapping' => data_get($detail, 'hasil_mapping'),
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
}
