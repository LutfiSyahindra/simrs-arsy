<?php

namespace App\Services\keuangan\premi;

use App\Models\dbSimrs\generatePremiBersamaModel;
use App\Models\User;
use App\Repositories\keuangan\premi\generatePremiBersamaRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generatePremiBersamaService
{
    public function __construct(
        protected generatePremiBersamaRepository $repository
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
                'jumlah_source' => $row->sources_count,
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

    public function getPlotingOptions(): Collection
    {
        return $this->repository
            ->getPlotingOptions()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'ploting' => $item->ploting,
                'text' => trim($item->kode.' - '.$item->ploting),
            ])
            ->values();
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

    public function getConfig(): array
    {
        return $this->configPayload($this->repository->getConfig());
    }

    public function updateConfig(
        int $jnsPremiUmumId,
        ?int $jnsPremiBpjsId,
        ?int $ugdPlotingId,
        ?int $vkPlotingId,
        ?int $kamarPlotingId,
        ?int $bhpPlotingId,
        string $bpjsSourceMode,
        bool $ignoreIcu,
        bool $ignoreNicu,
        array $sourceMappings,
        array $includedUmumActionIds,
        array $doctorCodes,
        array $doctorActionIds
    ): array {
        $jnsPremiBpjsId = $jnsPremiBpjsId ?: $jnsPremiUmumId;
        $bpjsSourceMode = PremiSourcePeriod::normalizeMode($bpjsSourceMode, 'bpjs');
        $sourceMappings = $this->normalizeSourceMappings($sourceMappings);
        $includedUmumActionIds = $this->normalizeTindakanIds($includedUmumActionIds);
        $doctorCodes = $this->normalizeDoctorCodes($doctorCodes);
        $doctorActionIds = $this->normalizeTindakanIds($doctorActionIds);
        $premiIds = [$jnsPremiUmumId, $jnsPremiBpjsId];

        $this->assertSourceMappingsBelongToPremi($premiIds, $sourceMappings);
        $this->assertActionsBelongToPremi($premiIds, $includedUmumActionIds, 'included_umum_action_ids');
        $this->assertActionsBelongToPremi($premiIds, $doctorActionIds, 'doctor_tindakan_ids');
        $this->assertDoctorsExist($doctorCodes);

        return $this->configPayload(
            $this->repository->saveConfig(
                $jnsPremiUmumId,
                $jnsPremiBpjsId,
                $ugdPlotingId,
                $vkPlotingId,
                $kamarPlotingId,
                $bhpPlotingId,
                $bpjsSourceMode,
                $ignoreIcu,
                $ignoreNicu,
                $sourceMappings,
                $includedUmumActionIds,
                $doctorCodes,
                $doctorActionIds
            )
        );
    }

    public function getSummary(
        string $periode,
        string $jenis,
        ?int $jnsPremiId = null
    ): array {
        if ($jenis === 'bpjs') {
            return $this->comingSoonPayload($periode, $jenis);
        }

        $config = $this->repository->getConfig();
        $jnsPremiId = $this->selectedConfigPremiId($jnsPremiId, $config, $jenis);
        $sourceMappings = $this->repository->getConfigSourceMappings((int) $config->id);
        $existing = $this->repository->findByPeriodAndType($periode, $jenis, $jnsPremiId);

        if ($existing?->is_locked) {
            return [
                ...$this->resultPayload($existing),
                'ready' => false,
                'readiness_message' => 'Data sudah terkunci. Buka kunci jika ingin generate ulang.',
                'readiness_steps' => $this->lockedReadinessSteps($existing),
                'is_generated' => true,
                'is_locked' => true,
                'config' => $this->configPayload($config),
                'sources' => $existing->sources->map(fn ($source) => $this->sourcePayload($source))->values(),
                'preview_details' => $existing->details->map(fn ($detail) => $this->detailPayload($detail))->values(),
                'distributions' => $this->distributionPayload($existing->distributions)->values(),
                'source_insight' => $this->sourceInsight($existing->sources),
                'preview_insight' => $this->previewInsight($existing->details),
                'distribution_insight' => $this->distributionInsight($existing->distributions, (float) $existing->grand_total),
            ];
        }

        $calculation = $this->repository->calculate(
            $periode,
            $jenis,
            $jnsPremiId,
            $config,
            $sourceMappings
        );
        $grandTotal = round(
            (float) $calculation['total_tindakan_rawat']
            + (float) $calculation['total_generator_sumber'],
            2
        );
        $pegawai = $this->repository->getMappedPegawaiWithScores($jnsPremiId);
        $distributions = $this->distributeByScore($grandTotal, $pegawai, $jnsPremiId);
        $pendingSources = $this->pendingGeneratorSources($calculation['generator_sources']);
        $totalScore = round((float) $pegawai->sum('skor_pegawai'), 2);
        $ready = $grandTotal > 0
            && $pegawai->isNotEmpty()
            && $totalScore > 0
            && $pendingSources->isEmpty();

        return [
            'periode' => $periode,
            'source_periode' => $periode,
            'source_tgl_awal' => $calculation['source_tgl_awal'],
            'source_tgl_akhir' => $calculation['source_tgl_akhir'],
            'jenis_pelayanan' => $jenis,
            'jenis_pelayanan_label' => $this->typeLabel($jenis),
            'jnsPremi_id' => $jnsPremiId,
            'kode_premi' => $calculation['kode_premi'],
            'nama_premi' => $calculation['nama_premi'],
            'config' => $this->configPayload($config),
            'bpjs_source_mode' => $calculation['bpjs_source_mode'],
            'bpjs_source_mode_label' => PremiSourcePeriod::modeLabel($calculation['bpjs_source_mode'], 'bpjs'),
            'bpjs_source_periode' => $calculation['bpjs_source_periode'],
            'ready' => $ready,
            'readiness_message' => $this->readinessMessage(
                $grandTotal,
                $pegawai,
                $totalScore,
                $pendingSources
            ),
            'readiness_steps' => $this->readinessSteps($calculation, $pegawai, $totalScore, $pendingSources),
            'is_generated' => (bool) $existing,
            'is_locked' => (bool) ($existing?->is_locked ?? false),
            'locked_at' => optional($existing?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $existing?->lockedBy?->name,
            'ignore_icu' => (bool) ($config->ignore_icu ?? true),
            'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
            'jumlah_transaksi' => $calculation['jumlah_transaksi'],
            'jumlah_pasien' => $calculation['jumlah_pasien'],
            'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan'],
            'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi'],
            'jumlah_terabaikan_icu' => $calculation['jumlah_terabaikan_icu'],
            'jumlah_terabaikan_nicu' => $calculation['jumlah_terabaikan_nicu'],
            'total_biaya_rawat' => $calculation['total_biaya_rawat'],
            'total_tindakan_rawat' => $calculation['total_tindakan_rawat'],
            'jumlah_sumber_generator' => $calculation['jumlah_sumber_generator'],
            'jumlah_sumber_terkunci' => $calculation['jumlah_sumber_terkunci'],
            'total_generator_sumber' => $calculation['total_generator_sumber'],
            'grand_total' => $grandTotal,
            'jumlah_penerima' => $distributions->count(),
            'total_skor' => $totalScore,
            'total_dibagikan' => round((float) $distributions->sum('total_received'), 2),
            'total_belum_dibagikan' => max(0, round($grandTotal - (float) $distributions->sum('total_received'), 2)),
            'sources' => collect($calculation['generator_sources'])
                ->map(fn ($source) => $this->sourcePayload($source))
                ->values(),
            'source_insight' => $this->sourceInsight($calculation['generator_sources']),
            'preview_details' => $this->previewDetails($calculation['details']),
            'preview_insight' => $this->previewInsight($calculation['details']),
            'distributions' => $distributions->values(),
            'distribution_insight' => $this->distributionInsight($distributions, $grandTotal),
            'source_mappings' => $this->sourceMappingsPayload($sourceMappings),
        ];
    }

    public function generate(string $periode, string $jenis, ?int $jnsPremiId = null): array
    {
        if ($jenis === 'bpjs') {
            throw ValidationException::withMessages([
                'jenis_pelayanan' => 'Generate Premi Bersama BPJS masih coming soon.',
            ]);
        }

        return DB::transaction(function () use ($periode, $jenis, $jnsPremiId) {
            $config = $this->repository->getConfig();
            $jnsPremiId = $this->selectedConfigPremiId($jnsPremiId, $config, $jenis);
            $sourceMappings = $this->repository->getConfigSourceMappings((int) $config->id);
            $existing = $this->repository
                ->findByPeriodAndTypeForUpdate($periode, $jenis, $jnsPremiId);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Premi Bersama {$this->typeLabel($jenis)} periode {$periode} sudah dikunci.",
                ]);
            }

            $calculation = $this->repository->calculate(
                $periode,
                $jenis,
                $jnsPremiId,
                $config,
                $sourceMappings,
                true
            );
            $pendingSources = $this->pendingGeneratorSources($calculation['generator_sources']);

            if ($pendingSources->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'sources' => 'Kunci sumber generator terlebih dahulu: '.$pendingSources->pluck('source_label')->implode(', ').'.',
                ]);
            }

            $grandTotal = round(
                (float) $calculation['total_tindakan_rawat']
                + (float) $calculation['total_generator_sumber'],
                2
            );

            if ($grandTotal <= 0) {
                throw ValidationException::withMessages([
                    'mapping' => "Tidak ada nilai Premi Bersama UMUM periode {$periode} yang dapat digenerate.",
                ]);
            }

            $pegawai = $this->repository->getMappedPegawaiWithScores($jnsPremiId);

            if ($pegawai->isEmpty()) {
                throw ValidationException::withMessages([
                    'pegawai' => 'Mapping premi terpilih belum memiliki pegawai penerima.',
                ]);
            }

            if ((float) $pegawai->sum('skor_pegawai') <= 0) {
                throw ValidationException::withMessages([
                    'skor' => 'Pegawai penerima belum memiliki skor pada Mapping Skor.',
                ]);
            }

            $result = $this->repository->saveResult(
                $periode,
                $jenis,
                $jnsPremiId,
                $config,
                $calculation,
                $sourceMappings
            );
            $this->repository->replaceSources($result, collect($calculation['generator_sources']));
            $this->repository->replaceDetails($result, $calculation['details']);
            $this->repository->replaceDistributions(
                $result,
                $this->distributeByScore($grandTotal, $pegawai, $jnsPremiId)
            );

            return $this->resultPayload(
                $result->fresh([
                    'jnsPremi:id,pembagi',
                    'sources',
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
            abort(404, 'Data generate Premi Bersama tidak ditemukan.');
        }

        return [
            ...$this->resultPayload($result),
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'generate_by_name' => $result->generateBy?->name,
            'config_snapshot' => $result->config_snapshot,
            'sources' => $result->sources->map(fn ($source) => $this->sourcePayload($source))->values(),
            'details' => $result->details->map(fn ($detail) => $this->detailPayload($detail))->values(),
            'distributions' => $this->distributionPayload($result->distributions)->values(),
            'source_insight' => $this->sourceInsight($result->sources),
            'preview_insight' => $this->previewInsight($result->details),
            'distribution_insight' => $this->distributionInsight($result->distributions, (float) $result->grand_total),
        ];
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate Premi Bersama tidak ditemukan.');
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
                abort(404, 'Data generate Premi Bersama tidak ditemukan.');
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

    private function distributeByScore(float $grandTotal, Collection $pegawai, int $jnsPremiId): Collection
    {
        $pegawai = $pegawai->values();
        $totalScore = round((float) $pegawai->sum('skor_pegawai'), 2);

        if ($pegawai->isEmpty() || $grandTotal <= 0 || $totalScore <= 0) {
            return collect();
        }

        $totalCents = (int) round($grandTotal * 100);
        $rows = $pegawai
            ->map(function ($item, int $index) use ($jnsPremiId, $grandTotal, $totalScore, $totalCents) {
                $score = max(0, (float) $item->skor_pegawai);
                $rawCents = $score > 0 ? ($totalCents * $score / $totalScore) : 0;
                $floorCents = (int) floor($rawCents);

                return [
                    'index' => $index,
                    'jnsPremi_id' => $jnsPremiId,
                    'nik' => $item->nik,
                    'pegawai_name' => $item->pegawai_name,
                    'pegawai_position' => $item->pegawai_position,
                    'stts_kerja' => $item->stts_kerja,
                    'skor_pegawai' => $score,
                    'total_skor' => $totalScore,
                    'allocation_percent' => $totalScore > 0
                        ? round($score / $totalScore * 100, 4)
                        : 0,
                    'grand_total' => round($grandTotal, 2),
                    'total_received_cents' => $floorCents,
                    'fraction' => $rawCents - $floorCents,
                    'skor_detail' => $item->skor_detail,
                ];
            });
        $distributedCents = (int) $rows->sum('total_received_cents');
        $remainder = max(0, $totalCents - $distributedCents);
        $remainderIndexes = $rows
            ->sortByDesc('fraction')
            ->take($remainder)
            ->pluck('index')
            ->flip();

        return $rows
            ->map(function (array $row) use ($remainderIndexes) {
                $receivedCents = $row['total_received_cents']
                    + ($remainderIndexes->has($row['index']) ? 1 : 0);

                unset($row['index'], $row['fraction'], $row['total_received_cents']);
                $row['total_received'] = round($receivedCents / 100, 2);

                return $row;
            })
            ->values();
    }

    private function resultPayload(generatePremiBersamaModel $result): array
    {
        return [
            'id' => $result->id,
            'periode' => $result->periode,
            'source_periode' => $result->source_periode,
            'bpjs_source_mode' => PremiSourcePeriod::normalizeMode(
                $result->bpjs_source_mode ?? data_get($result->config_snapshot, 'bpjs_source_mode'),
                'bpjs'
            ),
            'bpjs_source_mode_label' => PremiSourcePeriod::modeLabel(
                $result->bpjs_source_mode ?? data_get($result->config_snapshot, 'bpjs_source_mode'),
                'bpjs'
            ),
            'bpjs_source_periode' => $result->bpjs_source_periode
                ?? data_get($result->config_snapshot, 'bpjs_source_periode'),
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d'),
            'jenis_pelayanan' => $result->jenis_pelayanan,
            'jenis_pelayanan_label' => $this->typeLabel($result->jenis_pelayanan),
            'jnsPremi_id' => $result->jnsPremi_id,
            'kode_premi' => $result->kode_premi,
            'nama_premi' => $result->nama_premi,
            'ignore_icu' => $result->ignore_icu,
            'ignore_nicu' => $result->ignore_nicu,
            'jumlah_transaksi' => $result->jumlah_transaksi,
            'jumlah_pasien' => $result->jumlah_pasien,
            'jumlah_jenis_tindakan' => $result->jumlah_jenis_tindakan,
            'jumlah_mapping_premi' => $result->jumlah_mapping_premi,
            'jumlah_terabaikan_icu' => $result->jumlah_terabaikan_icu,
            'jumlah_terabaikan_nicu' => $result->jumlah_terabaikan_nicu,
            'total_biaya_rawat' => $result->total_biaya_rawat,
            'total_tindakan_rawat' => $result->total_tindakan_rawat,
            'jumlah_sumber_generator' => $result->jumlah_sumber_generator,
            'jumlah_sumber_terkunci' => $result->jumlah_sumber_terkunci,
            'total_generator_sumber' => $result->total_generator_sumber,
            'grand_total' => $result->grand_total,
            'jumlah_penerima' => $result->relationLoaded('distributions')
                ? $result->distributions->count()
                : (int) ($result->jumlah_penerima ?? $result->distributions_count ?? 0),
            'total_skor' => $result->relationLoaded('distributions')
                ? round((float) data_get($result->distributions->first(), 'total_skor', 0), 2)
                : round((float) ($result->total_skor ?? 0), 2),
            'total_dibagikan' => $result->relationLoaded('distributions')
                ? round((float) $result->distributions->sum('total_received'), 2)
                : round((float) ($result->total_dibagikan ?? 0), 2),
            'total_belum_dibagikan' => max(
                0,
                round(
                    (float) $result->grand_total
                    - ($result->relationLoaded('distributions')
                        ? (float) $result->distributions->sum('total_received')
                        : (float) ($result->total_dibagikan ?? 0)),
                    2
                )
            ),
            'ugd_ploting_label' => $this->plotingLabel($result->ugd_kode_ploting, $result->ugd_nama_ploting),
            'vk_ploting_label' => $this->plotingLabel($result->vk_kode_ploting, $result->vk_nama_ploting),
            'kamar_ploting_label' => $this->plotingLabel($result->kamar_kode_ploting, $result->kamar_nama_ploting),
            'bhp_ploting_label' => $this->plotingLabel($result->bhp_kode_ploting, $result->bhp_nama_ploting),
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'generate_by_name' => $result->generateBy?->name,
            'generated_at' => optional($result->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function sourcePayload($source): array
    {
        $rawSnapshot = data_get($source, 'raw_snapshot', []);

        return [
            'source_key' => data_get($source, 'source_key'),
            'source_label' => data_get($source, 'source_label'),
            'source_table' => data_get($source, 'source_table'),
            'source_periode' => data_get($source, 'source_periode'),
            'source_type' => data_get($source, 'source_type'),
            'plotingPremi_id' => data_get($source, 'plotingPremi_id'),
            'ploting_label' => $this->plotingLabel(
                data_get($source, 'kode_ploting'),
                data_get($source, 'nama_ploting')
            ),
            'jumlah_data' => (int) data_get($source, 'jumlah_data', 0),
            'total_asal' => round((float) data_get($source, 'total_asal', 0), 2),
            'total_diambil' => round((float) data_get($source, 'total_diambil', 0), 2),
            'is_locked' => (bool) data_get($source, 'is_locked', false),
            'status_label' => data_get($source, 'status_label'),
            'note' => data_get($source, 'note'),
            'generated_count' => (int) data_get($rawSnapshot, 'generated_count', 0),
            'locked_count' => (int) data_get($rawSnapshot, 'locked_count', 0),
            'items' => collect(data_get($rawSnapshot, 'items', []))->values()->all(),
        ];
    }

    private function detailPayload($detail): array
    {
        $rawat = collect(data_get($detail, 'data_rawat', []));
        $breakdowns = $this->rawatBreakdowns($rawat);
        $mappingSnapshot = data_get($detail, 'mapping_snapshot')
            ?: $this->mappingSnapshotFallback($detail, $rawat, $breakdowns);

        return [
            'id' => data_get($detail, 'id'),
            'mapping_premi_id' => data_get($detail, 'mapping_premi_id'),
            'jnsPremi_id' => data_get($detail, 'jnsPremi_id'),
            'jnsTindakan_id' => data_get($detail, 'jnsTindakan_id'),
            'kode_premi' => data_get($detail, 'kode_premi'),
            'nama_premi' => data_get($detail, 'nama_premi'),
            'kode_jenis_tindakan' => data_get($detail, 'kode_jenis_tindakan'),
            'nama_jenis_tindakan' => data_get($detail, 'nama_jenis_tindakan'),
            'jenis_mapping' => data_get($detail, 'jenis_mapping'),
            'nilai_mapping' => data_get($detail, 'nilai_mapping'),
            'source_rules' => data_get($detail, 'source_rules', []),
            'mapping_snapshot' => $mappingSnapshot,
            'jumlah_data' => data_get($detail, 'jumlah_data'),
            'jumlah_data_icu' => data_get($detail, 'jumlah_data_icu'),
            'jumlah_data_nicu' => data_get($detail, 'jumlah_data_nicu'),
            'jumlah_data_dokter' => $breakdowns['jumlah_data_dokter'],
            'jumlah_data_paramedis' => $breakdowns['jumlah_data_paramedis'],
            'jumlah_data_drpr' => $breakdowns['jumlah_data_drpr'],
            'jumlah_data_dialihkan_perawat' => $breakdowns['jumlah_data_dialihkan_perawat'],
            'total_biaya_rawat' => data_get($detail, 'total_biaya_rawat'),
            'dasar_hitung' => data_get($detail, 'dasar_hitung'),
            'hasil_mapping' => data_get($detail, 'hasil_mapping'),
            'source_breakdown' => $breakdowns['source_breakdown'],
            'doctor_breakdown' => $breakdowns['doctor_breakdown'],
            'paramedic_breakdown' => $breakdowns['paramedic_breakdown'],
            'data_rawat' => $rawat->values()->all(),
        ];
    }

    private function mappingSnapshotFallback($detail, Collection $rawat, array $breakdowns): array
    {
        $jenisMapping = (string) data_get($detail, 'jenis_mapping', '');
        $nilaiMapping = (float) data_get($detail, 'nilai_mapping', 0);
        $jumlahData = (int) data_get($detail, 'jumlah_data', $rawat->count());
        $totalBiaya = (float) data_get($detail, 'total_biaya_rawat', 0);
        $isPercent = $jenisMapping === 'persen';

        return [
            'mapping_premi_id' => data_get($detail, 'mapping_premi_id'),
            'jnsPremi_id' => data_get($detail, 'jnsPremi_id'),
            'jnsTindakan_id' => data_get($detail, 'jnsTindakan_id'),
            'kode_premi' => data_get($detail, 'kode_premi'),
            'nama_premi' => data_get($detail, 'nama_premi'),
            'kode_jenis_tindakan' => data_get($detail, 'kode_jenis_tindakan'),
            'nama_jenis_tindakan' => data_get($detail, 'nama_jenis_tindakan'),
            'jenis_mapping' => $jenisMapping,
            'jenis_mapping_label' => $isPercent
                ? 'Persentase dari total biaya rawat'
                : 'Nominal per data tindakan',
            'nilai_mapping' => round($nilaiMapping, 4),
            'formula_text' => $isPercent
                ? number_format($nilaiMapping, 2, ',', '.').'% x Rp '.number_format($totalBiaya, 0, ',', '.')
                : number_format($jumlahData, 0, ',', '.').' data x Rp '.number_format($nilaiMapping, 0, ',', '.'),
            'jumlah_data' => $jumlahData,
            'jumlah_pasien' => $rawat->pluck('no_rawat')->unique()->count(),
            'jumlah_source_table' => $rawat->pluck('source_table')->filter()->unique()->count(),
            'jumlah_dokter' => $rawat->pluck('kd_dokter')->filter()->unique()->count(),
            'jumlah_paramedis' => $rawat->pluck('nip')->filter()->unique()->count(),
            'jumlah_dialihkan_perawat' => $breakdowns['jumlah_data_dialihkan_perawat'],
            'total_biaya_rawat' => round($totalBiaya, 2),
            'dasar_hitung' => round((float) data_get($detail, 'dasar_hitung', 0), 2),
            'hasil_mapping' => round((float) data_get($detail, 'hasil_mapping', 0), 2),
            'source_rules' => data_get($detail, 'source_rules', []),
            'source_breakdown' => $breakdowns['source_breakdown'],
            'doctor_breakdown' => $breakdowns['doctor_breakdown'],
            'paramedic_breakdown' => $breakdowns['paramedic_breakdown'],
            'sample_rows' => $rawat->take(20)->values()->all(),
        ];
    }

    private function previewDetails($details): Collection
    {
        return collect($details)
            ->map(fn ($detail) => $this->detailPayload($detail))
            ->values();
    }

    private function distributionPayload($distributions): Collection
    {
        return collect($distributions)
            ->map(fn ($item) => [
                'nik' => data_get($item, 'nik'),
                'pegawai_name' => data_get($item, 'pegawai_name'),
                'pegawai_position' => data_get($item, 'pegawai_position'),
                'stts_kerja' => data_get($item, 'stts_kerja'),
                'stts_kerja_label' => $this->statusKerjaLabel(data_get($item, 'stts_kerja')),
                'skor_pegawai' => round((float) data_get($item, 'skor_pegawai', 0), 2),
                'total_skor' => round((float) data_get($item, 'total_skor', 0), 2),
                'allocation_percent' => round((float) data_get($item, 'allocation_percent', 0), 4),
                'grand_total' => round((float) data_get($item, 'grand_total', 0), 2),
                'total_received' => round((float) data_get($item, 'total_received', 0), 2),
                'skor_detail' => collect(data_get($item, 'skor_detail', []))->values()->all(),
            ])
            ->values();
    }

    private function configPayload(object $config): array
    {
        $jnsPremiUmumId = (int) ($config->jnsPremi_umum_id ?? 0) ?: null;
        $jnsPremiBpjsId = (int) ($config->jnsPremi_bpjs_id ?? 0) ?: null;
        $sourceMappings = $this->repository->getConfigSourceMappings((int) $config->id);
        $includedActions = $this->repository->getIncludedActions((int) $config->id, 'umum');
        $doctorFilter = $this->doctorFilterPayload((int) $config->id);
        $actionOptions = collect([$jnsPremiUmumId, $jnsPremiBpjsId])
            ->filter()
            ->unique()
            ->flatMap(fn ($premiId) => $this->actionOptions((int) $premiId))
            ->unique('id')
            ->sortBy('jenis')
            ->values();

        return [
            'id' => (int) $config->id,
            'jnsPremi_umum_id' => $jnsPremiUmumId,
            'jnsPremi_bpjs_id' => $jnsPremiBpjsId,
            'bpjs_source_mode' => PremiSourcePeriod::normalizeMode(
                $config->bpjs_source_mode ?? null,
                'bpjs'
            ),
            'bpjs_source_mode_label' => PremiSourcePeriod::modeLabel(
                $config->bpjs_source_mode ?? null,
                'bpjs'
            ),
            'premi_umum' => $jnsPremiUmumId
                ? $this->premiPayload($this->repository->findPremi($jnsPremiUmumId))
                : null,
            'premi_bpjs' => $jnsPremiBpjsId
                ? $this->premiPayload($this->repository->findPremi($jnsPremiBpjsId))
                : null,
            'ugd_plotingPremi_id' => $config->ugd_plotingPremi_id ? (int) $config->ugd_plotingPremi_id : null,
            'vk_plotingPremi_id' => $config->vk_plotingPremi_id ? (int) $config->vk_plotingPremi_id : null,
            'kamar_plotingPremi_id' => $config->kamar_plotingPremi_id ? (int) $config->kamar_plotingPremi_id : null,
            'bhp_plotingPremi_id' => $config->bhp_plotingPremi_id ? (int) $config->bhp_plotingPremi_id : null,
            'ignore_icu' => (bool) ($config->ignore_icu ?? true),
            'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
            'source_mappings' => $this->sourceMappingsPayload($sourceMappings),
            'included_umum_action_ids' => $includedActions->pluck('id')->map(fn ($id) => (int) $id)->values(),
            'included_umum_actions' => $includedActions
                ->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'kode' => $item->kode,
                    'jenis' => $item->jenis,
                    'text' => trim($item->kode.' - '.$item->jenis),
                ])
                ->values(),
            'doctor_filter' => $doctorFilter,
            'action_options' => $actionOptions,
            'source_pattern_options' => $this->repository->sourcePatternOptions(),
            'bpjs_status' => 'coming_soon',
        ];
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
            'jumlah_data_dialihkan_perawat' => $rawat
                ->where('route_reason', 'doctor_filter_non_selected')
                ->count(),
            'source_breakdown' => $sourceBreakdown,
            'doctor_breakdown' => $doctorBreakdown,
            'paramedic_breakdown' => $paramedicBreakdown,
        ];
    }

    private function pendingGeneratorSources(Collection $sources): Collection
    {
        return collect($sources)
            ->filter(function ($source) {
                $generated = (int) data_get($source, 'raw_snapshot.generated_count', 0);
                $locked = (int) data_get($source, 'raw_snapshot.locked_count', 0);

                return $generated > 0 && $locked < $generated;
            })
            ->values();
    }

    private function readinessMessage(
        float $grandTotal,
        Collection $pegawai,
        float $totalScore,
        Collection $pendingSources
    ): string {
        if ($pendingSources->isNotEmpty()) {
            return 'Masih ada sumber generator yang belum dikunci.';
        }

        if ($pegawai->isEmpty()) {
            return 'Mapping premi belum memiliki pegawai penerima.';
        }

        if ($totalScore <= 0) {
            return 'Pegawai penerima belum memiliki skor pada Mapping Skor.';
        }

        if ($grandTotal <= 0) {
            return 'Belum ada nilai Premi Bersama dari sumber generator atau tindakan rawat.';
        }

        return 'Preview siap. Grand total akan dibagikan berdasarkan proporsi skor pegawai.';
    }

    private function readinessSteps(
        array $calculation,
        Collection $pegawai,
        float $totalScore,
        Collection $pendingSources
    ): array {
        return [
            [
                'key' => 'generator',
                'label' => 'Sumber Generator',
                'status' => $pendingSources->isEmpty() ? 'success' : 'warning',
                'value' => $pendingSources->isEmpty()
                    ? 'Siap'
                    : $pendingSources->count().' perlu dikunci',
                'note' => 'Total sumber terkunci: '.$this->rupiah((float) $calculation['total_generator_sumber']).'.',
            ],
            [
                'key' => 'rawat',
                'label' => 'Tindakan Rawat',
                'status' => (float) $calculation['total_tindakan_rawat'] > 0 ? 'success' : 'muted',
                'value' => (int) $calculation['jumlah_mapping_premi'].' mapping',
                'note' => (int) $calculation['jumlah_transaksi'].' transaksi rawat sesuai mapping premi.',
            ],
            [
                'key' => 'pegawai',
                'label' => 'Pegawai Skor',
                'status' => $pegawai->isNotEmpty() && $totalScore > 0 ? 'success' : 'danger',
                'value' => $pegawai->count().' pegawai',
                'note' => 'Total skor penerima: '.number_format($totalScore, 2, ',', '.').'.',
            ],
        ];
    }

    private function lockedReadinessSteps(generatePremiBersamaModel $result): array
    {
        return [
            [
                'key' => 'locked',
                'label' => 'Status Generate',
                'status' => 'success',
                'value' => 'Terkunci',
                'note' => 'Snapshot perhitungan tersimpan pada '.optional($result->updated_at)->format('d-m-Y H:i').'.',
            ],
        ];
    }

    private function sourceInsight($sources): array
    {
        $rows = collect($sources);

        return [
            'jumlah_sumber' => $rows->count(),
            'jumlah_siap' => $rows->where('is_locked', true)->count(),
            'jumlah_bernilai' => $rows->filter(fn ($row) => (float) data_get($row, 'total_diambil', 0) > 0)->count(),
            'total_diambil' => round((float) $rows->sum('total_diambil'), 2),
            'top_sources' => $rows
                ->sortByDesc(fn ($row) => (float) data_get($row, 'total_diambil', 0))
                ->take(4)
                ->map(fn ($row) => [
                    'source_label' => data_get($row, 'source_label'),
                    'total_diambil' => round((float) data_get($row, 'total_diambil', 0), 2),
                ])
                ->values()
                ->all(),
        ];
    }

    private function previewInsight($details): array
    {
        $rows = collect($details);

        return [
            'jumlah_tindakan' => $rows->count(),
            'jumlah_data' => (int) $rows->sum('jumlah_data'),
            'total_biaya_rawat' => round((float) $rows->sum('total_biaya_rawat'), 2),
            'total_hasil_mapping' => round((float) $rows->sum('hasil_mapping'), 2),
            'top_details' => $rows
                ->sortByDesc(fn ($detail) => (float) data_get($detail, 'hasil_mapping', 0))
                ->take(3)
                ->map(fn ($detail) => [
                    'kode_jenis_tindakan' => data_get($detail, 'kode_jenis_tindakan'),
                    'nama_jenis_tindakan' => data_get($detail, 'nama_jenis_tindakan'),
                    'hasil_mapping' => round((float) data_get($detail, 'hasil_mapping', 0), 2),
                    'jumlah_data' => (int) data_get($detail, 'jumlah_data', 0),
                ])
                ->values()
                ->all(),
        ];
    }

    private function distributionInsight($distributions, float $grandTotal): array
    {
        $rows = collect($distributions);
        $totals = $rows->pluck('total_received')->map(fn ($value) => (float) $value);

        return [
            'jumlah_penerima' => $rows->count(),
            'total_skor' => round((float) data_get($rows->first(), 'total_skor', 0), 2),
            'average_total' => $rows->isNotEmpty() ? round((float) $totals->avg(), 2) : 0,
            'highest_total' => $rows->isNotEmpty() ? round((float) $totals->max(), 2) : 0,
            'lowest_total' => $rows->isNotEmpty() ? round((float) $totals->min(), 2) : 0,
            'grand_total' => round($grandTotal, 2),
            'total_dibagikan' => round((float) $totals->sum(), 2),
        ];
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

    private function doctorFilterPayload(int $configId): array
    {
        $selectedDoctors = $this->repository->getSelectedDoctors($configId);
        $selectedActions = $this->repository->getSelectedDoctorActions($configId);
        $count = $selectedDoctors->count();
        $actionCount = $selectedActions->count();

        return [
            'mode' => $count > 0 && $actionCount > 0 ? 'selected' : 'all',
            'mode_label' => $count > 0 && $actionCount > 0
                ? "{$count} dokter / {$actionCount} tindakan"
                : 'Semua dokter',
            'summary' => $count > 0 && $actionCount > 0
                ? "{$count} dokter terpilih masuk tindakan dokter; dokter lain diarahkan ke tindakan perawat jika mapping sumber tersedia."
                : 'Filter dokter nonaktif.',
            'selected_count' => $count,
            'selected_codes' => $selectedDoctors->pluck('kd_dokter')->values(),
            'selected_action_count' => $actionCount,
            'selected_action_ids' => $selectedActions->pluck('id')->map(fn ($id) => (int) $id)->values(),
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

    private function selectedConfigPremiId(?int $requestPremiId, object $config, string $jenis): int
    {
        $configuredId = $jenis === 'bpjs'
            ? data_get($config, 'jnsPremi_bpjs_id')
            : data_get($config, 'jnsPremi_umum_id');
        $jnsPremiId = (int) ($configuredId ?: ($requestPremiId ?? 0));

        if ($jnsPremiId < 1 || ! $this->repository->findPremi($jnsPremiId)) {
            throw ValidationException::withMessages([
                'jnsPremi_id' => 'Konfigurasi mapping premi '.strtoupper($jenis).' wajib dipilih terlebih dahulu.',
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

    private function assertActionsBelongToPremi(array $jnsPremiIds, array $tindakanIds, string $field): void
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
                $field => 'Tindakan yang dipilih harus berasal dari mapping premi aktif.',
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

    private function comingSoonPayload(string $periode, string $jenis): array
    {
        return [
            'periode' => $periode,
            'jenis_pelayanan' => $jenis,
            'jenis_pelayanan_label' => $this->typeLabel($jenis),
            'ready' => false,
            'is_generated' => false,
            'is_locked' => false,
            'readiness_message' => 'Alur Premi Bersama BPJS masih coming soon.',
            'readiness_steps' => [[
                'key' => 'bpjs',
                'label' => 'BPJS',
                'status' => 'muted',
                'value' => 'Coming soon',
                'note' => 'Konfigurasi BPJS ditampilkan sebagai persiapan, generate belum diaktifkan.',
            ]],
            'jumlah_transaksi' => 0,
            'jumlah_mapping_premi' => 0,
            'total_biaya_rawat' => 0,
            'total_tindakan_rawat' => 0,
            'total_generator_sumber' => 0,
            'grand_total' => 0,
            'jumlah_penerima' => 0,
            'total_skor' => 0,
            'total_dibagikan' => 0,
            'sources' => [],
            'preview_details' => [],
            'distributions' => [],
        ];
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

    private function plotingLabel(?string $kode, ?string $nama): string
    {
        $label = trim(($kode ? $kode.' - ' : '').($nama ?? ''));

        return $label !== '' ? $label : '-';
    }

    private function statusKerjaLabel(?string $status): string
    {
        return match ($status) {
            'T' => 'Tetap',
            'FT' => 'Kontrak',
            'PT' => 'Part Time',
            default => $status ?: '-',
        };
    }

    private function typeLabel(string $jenis): string
    {
        return $jenis === 'bpjs' ? 'BPJS' : 'Umum';
    }

    private function rupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
