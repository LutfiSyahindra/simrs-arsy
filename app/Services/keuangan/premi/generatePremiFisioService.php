<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generatePremiFisioRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generatePremiFisioService
{
    public function __construct(
        protected generatePremiFisioRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisFisio = null)
    {
        return $this->repository
            ->getResults($periode, $jenisFisio)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisFisio): array
    {
        return [
            'periode' => $periode,
            'jenis_fisio' => $jenisFisio,
            'jenis_fisio_label' => $this->typeLabel($jenisFisio),
            ...$this->repository->getSummary($periode, $jenisFisio),
        ];
    }

    public function getConfig(string $jenisFisio): array
    {
        return $this->configPayload(
            $this->repository->getConfig($jenisFisio),
            $this->repository->getActions()
        );
    }

    public function updateConfig(string $jenisFisio, array $data): array
    {
        $recipients = $this->hydrateRecipients([
            'petugas1' => $data['petugas1_id'] ?? null,
            'petugas2' => $data['petugas2_id'] ?? null,
        ]);
        $payload = $this->configFormPayload($data, $jenisFisio);

        $config = $this->repository->saveConfig(
            $jenisFisio,
            $payload,
            $recipients,
            $data['tindakan'] ?? null
        );

        return $this->configPayload($config, $this->repository->getActions());
    }

    public function pegawaiOptions(?string $keyword = null)
    {
        return $this->repository
            ->searchPegawai($keyword)
            ->map(fn ($item) => [
                'id' => $item->nik,
                'nik' => $item->nik,
                'nama' => $item->nama,
                'jbtn' => $item->jbtn,
                'text' => trim($item->nik.' - '.$item->nama.' ('.($item->jbtn ?: '-').')'),
            ])
            ->values();
    }

    public function tindakanOptions(?string $keyword = null)
    {
        return $this->repository
            ->getActions(true, $keyword)
            ->map(fn ($item) => $this->actionPayload($item))
            ->values();
    }

    public function preview(array $data): array
    {
        return $this->calculate($data);
    }

    public function generate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $calculation = $this->calculate($data, true);
            $existing = $this->repository->findExistingForUpdate(
                $calculation['periode'],
                $calculation['jenis_fisio'],
                $calculation['kode_generate']
            );

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data fisio untuk periode, jenis, dan sumber ini sudah dikunci.',
                ]);
            }

            return $this->resultPayload($this->repository->saveResult($calculation));
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate premi fisio tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $payload['source'] = [
            'source_label' => $result->jenis_fisio === 'bpjs' ? 'Jumlah pasien BPJS' : $result->nama_tindakan,
            'tindakan_config_id' => $result->tindakan_config_id,
            'nama_tindakan' => $result->nama_tindakan,
            'harga_tindakan' => $result->harga_tindakan,
            'jumlah_tindakan' => $result->jumlah_tindakan,
            'jumlah_pasien' => $result->jumlah_pasien,
            'grand_total' => $result->grand_total,
        ];
        $payload['pools'] = [
            'petugas1' => $result->total_petugas1,
            'petugas2' => $result->total_petugas2,
            'premi_bersama' => $result->total_premi_bersama,
        ];
        $payload['source_details'] = $result->details
            ->where('detail_type', 'source')
            ->values()
            ->map(fn ($detail) => $this->sourceDetailPayload($detail));
        $payload['recipients'] = $result->details
            ->where('detail_type', 'recipient')
            ->values()
            ->map(fn ($detail) => $this->recipientDetailPayload($detail));

        return $payload;
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate premi fisio tidak ditemukan.');
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
                abort(404, 'Data generate premi fisio tidak ditemukan.');
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

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate premi fisio tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data fisio yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(array $data, bool $strict = false): array
    {
        $jenisFisio = $data['jenis_fisio'];
        $periode = $data['periode'];
        $config = $this->configPayload($this->repository->getConfig($jenisFisio));
        $source = $this->sourcePayload($jenisFisio, $data, $config);
        $unit = $jenisFisio === 'bpjs' ? max(1, (int) $source['jumlah_pasien']) : 1;
        $grandTotal = $source['grand_total'];
        $petugas1 = $this->configuredAmount(
            $config['petugas1_mode'],
            $grandTotal,
            $config['petugas1_percent'],
            $config['petugas1_nominal'],
            $unit
        );
        $petugas2 = $this->configuredAmount(
            $config['petugas2_mode'],
            $petugas1,
            $config['petugas2_percent'],
            $config['petugas2_nominal'],
            $unit
        );
        $premiBersama = $this->configuredAmount(
            $config['bersama_mode'],
            $petugas1,
            $config['bersama_percent'],
            $config['bersama_nominal'],
            $unit
        );
        $recipients = $this->recipientRows($config, $grandTotal, $petugas1, $petugas2, $strict);
        $warnings = $this->warnings($config, $source, $petugas1, $petugas2);
        $configSnapshot = [
            ...$config,
            'source_label' => $source['source_label'],
            'formula_labels' => [
                'grand_total' => $jenisFisio === 'bpjs'
                    ? 'Jumlah pasien x nominal grand total BPJS'
                    : 'Total subtotal semua tindakan',
                'petugas1' => 'Petugas 1 dari grand total',
                'petugas2' => 'Petugas 2 dari nilai Petugas 1',
                'premi_bersama' => 'Premi bersama dari nilai Petugas 1',
            ],
        ];

        if ($strict && $warnings['blocking']) {
            throw ValidationException::withMessages([
                'config' => implode(' ', $warnings['blocking']),
            ]);
        }

        return [
            'periode' => $periode,
            'jenis_fisio' => $jenisFisio,
            'jenis_fisio_label' => $this->typeLabel($jenisFisio),
            'kode_generate' => $source['kode_generate'],
            'source' => $source,
            'source_details' => $source['details'],
            'grand_total' => $grandTotal,
            'pools' => [
                'petugas1' => $petugas1,
                'petugas2' => $petugas2,
                'premi_bersama' => $premiBersama,
            ],
            'recipients' => $recipients,
            'total_dibagikan' => collect($recipients)->sum('total_received'),
            'config' => $configSnapshot,
            'config_snapshot' => $configSnapshot,
            'warnings' => $warnings,
            'can_generate' => empty($warnings['blocking']),
        ];
    }

    private function sourcePayload(string $jenisFisio, array $data, array $config): array
    {
        if ($jenisFisio === 'bpjs') {
            $jumlahPasien = max(0, (int) ($data['jumlah_pasien'] ?? 0));

            if ($jumlahPasien < 1) {
                throw ValidationException::withMessages([
                    'jumlah_pasien' => 'Jumlah pasien BPJS wajib diisi minimal 1.',
                ]);
            }

            return [
                'source_label' => 'Jumlah pasien BPJS',
                'kode_generate' => 'bpjs',
                'tindakan_config_id' => null,
                'nama_tindakan' => null,
                'harga_tindakan' => (int) $config['grand_nominal'],
                'jumlah' => $jumlahPasien,
                'jumlah_tindakan' => 0,
                'jumlah_pasien' => $jumlahPasien,
                'subtotal' => $jumlahPasien * (int) $config['grand_nominal'],
                'grand_total' => $jumlahPasien * (int) $config['grand_nominal'],
                'details' => [[
                    'source_label' => 'Jumlah pasien BPJS',
                    'tindakan_config_id' => null,
                    'nama_tindakan' => null,
                    'harga_tindakan' => (int) $config['grand_nominal'],
                    'jumlah' => $jumlahPasien,
                    'subtotal' => $jumlahPasien * (int) $config['grand_nominal'],
                ]],
            ];
        }

        $items = collect($data['tindakan_items'] ?? [])
            ->map(fn ($item) => [
                'tindakan_id' => (int) ($item['tindakan_id'] ?? 0),
                'jumlah' => (int) ($item['jumlah'] ?? 0),
            ])
            ->filter(fn ($item) => $item['tindakan_id'] > 0 && $item['jumlah'] > 0)
            ->groupBy('tindakan_id')
            ->map(fn ($group) => [
                'tindakan_id' => (int) $group->first()['tindakan_id'],
                'jumlah' => (int) $group->sum('jumlah'),
            ])
            ->values();

        if ($items->isEmpty() && ! empty($data['tindakan_id'])) {
            $items = collect([[
                'tindakan_id' => (int) $data['tindakan_id'],
                'jumlah' => (int) ($data['jumlah_tindakan'] ?? 0),
            ]])->filter(fn ($item) => $item['tindakan_id'] > 0 && $item['jumlah'] > 0);
        }

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'tindakan_items' => 'Minimal satu tindakan fisio UMUM wajib dipilih.',
            ]);
        }

        $details = $items->map(function ($item) {
            $tindakan = $this->repository->findTindakan($item['tindakan_id']);

            if (! $tindakan) {
                throw ValidationException::withMessages([
                    'tindakan_items' => 'Ada tindakan fisio UMUM yang tidak valid atau tidak aktif.',
                ]);
            }

            $jumlah = max(1, (int) $item['jumlah']);
            $harga = (int) $tindakan->harga;

            return [
                'source_label' => $tindakan->nama_tindakan,
                'tindakan_config_id' => $tindakan->id,
                'nama_tindakan' => $tindakan->nama_tindakan,
                'harga_tindakan' => $harga,
                'jumlah' => $jumlah,
                'subtotal' => $jumlah * $harga,
            ];
        })->values();

        $jumlahTindakan = (int) $details->sum('jumlah');
        $grandTotal = (int) $details->sum('subtotal');
        $firstDetail = $details->first();
        $sourceLabel = $details->count() === 1
            ? $firstDetail['nama_tindakan']
            : $details->count().' tindakan fisio';

        return [
            'source_label' => $sourceLabel,
            'kode_generate' => 'umum',
            'tindakan_config_id' => $details->count() === 1 ? $firstDetail['tindakan_config_id'] : null,
            'nama_tindakan' => $sourceLabel,
            'harga_tindakan' => $details->count() === 1 ? $firstDetail['harga_tindakan'] : 0,
            'jumlah' => $jumlahTindakan,
            'jumlah_tindakan' => $jumlahTindakan,
            'jumlah_pasien' => 0,
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'details' => $details->all(),
        ];
    }

    private function configuredAmount(
        string $mode,
        int $base,
        float $percent,
        int $nominal,
        int $unit = 1
    ): int {
        return $mode === 'nominal'
            ? (int) round($nominal * max(1, $unit))
            : (int) round($base * $percent / 100);
    }

    private function recipientRows(array $config, int $grandTotal, int $petugas1, int $petugas2, bool $strict): array
    {
        $rows = [];

        foreach ([
            'petugas1' => ['label' => 'Petugas 1', 'amount' => $petugas1, 'basis' => 'grand_total'],
            'petugas2' => ['label' => 'Petugas 2', 'amount' => $petugas2, 'basis' => 'petugas1'],
        ] as $role => $meta) {
            $recipient = $config['recipients'][$role] ?? null;

            if ($strict && $meta['amount'] > 0 && ! $recipient) {
                throw ValidationException::withMessages([
                    $role => $meta['label'].' wajib dipilih pada konfigurasi sebelum generate.',
                ]);
            }

            if (! $recipient) {
                continue;
            }

            $mode = $role === 'petugas1' ? $config['petugas1_mode'] : $config['petugas2_mode'];
            $percent = $role === 'petugas1' ? $config['petugas1_percent'] : $config['petugas2_percent'];

            $rows[] = [
                'role' => $role,
                'role_label' => $meta['label'],
                'pegawai_id' => $recipient['pegawai_id'],
                'pegawai_name' => $recipient['pegawai_name'],
                'pegawai_position' => $recipient['pegawai_position'],
                'allocation_percent' => $mode === 'percent' ? $percent : null,
                'basis_amount' => $role === 'petugas1' ? $grandTotal : $petugas1,
                'total_received' => $meta['amount'],
            ];
        }

        return $rows;
    }

    private function warnings(array $config, array $source, int $petugas1, int $petugas2): array
    {
        $blocking = [];
        $info = [];

        if (($source['grand_total'] ?? 0) <= 0) {
            $blocking[] = 'Grand total masih Rp 0. Periksa harga tindakan atau nominal BPJS.';
        }

        if ($petugas1 > 0 && empty($config['recipients']['petugas1'])) {
            $blocking[] = 'Petugas 1 belum dipilih.';
        }

        if ($petugas2 > 0 && empty($config['recipients']['petugas2'])) {
            $blocking[] = 'Petugas 2 belum dipilih.';
        }

        if (! $blocking) {
            $info[] = 'Konfigurasi siap dipakai untuk generate.';
        }

        return compact('blocking', 'info');
    }

    private function hydrateRecipients(array $input): array
    {
        $rows = [];

        foreach (['petugas1' => 'Petugas 1', 'petugas2' => 'Petugas 2'] as $role => $label) {
            $id = trim((string) ($input[$role] ?? ''));

            if ($id === '') {
                continue;
            }

            $pegawai = $this->repository->findPegawai($id);

            if (! $pegawai) {
                throw ValidationException::withMessages([
                    $role => $label.' tidak valid atau tidak aktif.',
                ]);
            }

            $rows[] = [
                'role' => $role,
                'pegawai_id' => $pegawai->nik,
                'pegawai_name' => $pegawai->nama,
                'pegawai_position' => $pegawai->jbtn,
            ];
        }

        return $rows;
    }

    private function configFormPayload(array $data, string $jenisFisio): array
    {
        return [
            'grand_mode' => $jenisFisio === 'bpjs' ? 'patient_nominal' : 'tindakan',
            'grand_nominal' => (int) ($data['grand_nominal'] ?? ($jenisFisio === 'bpjs' ? 8000 : 0)),
            'petugas1_mode' => $data['petugas1_mode'] ?? 'percent',
            'petugas1_percent' => (float) ($data['petugas1_percent'] ?? 50),
            'petugas1_nominal' => (int) ($data['petugas1_nominal'] ?? 0),
            'petugas2_mode' => $data['petugas2_mode'] ?? 'percent',
            'petugas2_percent' => (float) ($data['petugas2_percent'] ?? 50),
            'petugas2_nominal' => (int) ($data['petugas2_nominal'] ?? 0),
            'bersama_mode' => $data['bersama_mode'] ?? 'percent',
            'bersama_percent' => (float) ($data['bersama_percent'] ?? 50),
            'bersama_nominal' => (int) ($data['bersama_nominal'] ?? 0),
        ];
    }

    private function configPayload($config, $actions = null): array
    {
        $recipients = $config->pegawai
            ->mapWithKeys(fn ($pegawai) => [
                $pegawai->role => [
                    'role' => $pegawai->role,
                    'pegawai_id' => $pegawai->pegawai_id,
                    'pegawai_name' => $pegawai->pegawai_name,
                    'pegawai_position' => $pegawai->pegawai_position,
                    'text' => trim($pegawai->pegawai_id.' - '.$pegawai->pegawai_name),
                ],
            ])
            ->all();

        return [
            'id' => $config->id,
            'jenis_fisio' => $config->jenis_fisio,
            'jenis_fisio_label' => $this->typeLabel($config->jenis_fisio),
            'grand_mode' => $config->grand_mode,
            'grand_nominal' => (int) $config->grand_nominal,
            'petugas1_mode' => $config->petugas1_mode,
            'petugas1_percent' => (float) $config->petugas1_percent,
            'petugas1_nominal' => (int) $config->petugas1_nominal,
            'petugas2_mode' => $config->petugas2_mode,
            'petugas2_percent' => (float) $config->petugas2_percent,
            'petugas2_nominal' => (int) $config->petugas2_nominal,
            'bersama_mode' => $config->bersama_mode,
            'bersama_percent' => (float) $config->bersama_percent,
            'bersama_nominal' => (int) $config->bersama_nominal,
            'recipients' => [
                'petugas1' => $recipients['petugas1'] ?? null,
                'petugas2' => $recipients['petugas2'] ?? null,
            ],
            'tindakan' => $actions
                ? $actions->map(fn ($item) => $this->actionPayload($item))->values()->all()
                : [],
        ];
    }

    private function actionPayload($item): array
    {
        return [
            'id' => $item->id,
            'kode_tindakan' => $item->kode_tindakan,
            'nama_tindakan' => $item->nama_tindakan,
            'harga' => (int) $item->harga,
            'is_active' => (bool) $item->is_active,
            'note' => $item->note,
            'text' => trim($item->nama_tindakan.' - Rp '.number_format((int) $item->harga, 0, ',', '.')),
        ];
    }

    private function resultPayload($row): array
    {
        $details = $row->relationLoaded('details') ? $row->details : collect();
        $sourceDetails = $details->where('detail_type', 'source')->values();
        $recipients = $details->where('detail_type', 'recipient')->values();

        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'jenis_fisio' => $row->jenis_fisio,
            'jenis_fisio_label' => $this->typeLabel($row->jenis_fisio),
            'kode_generate' => $row->kode_generate,
            'source_label' => $row->jenis_fisio === 'bpjs' ? 'Jumlah pasien BPJS' : $row->nama_tindakan,
            'tindakan_config_id' => $row->tindakan_config_id,
            'nama_tindakan' => $row->nama_tindakan,
            'harga_tindakan' => $row->harga_tindakan,
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'jumlah_pasien' => $row->jumlah_pasien,
            'grand_total' => $row->grand_total,
            'total_petugas1' => $row->total_petugas1,
            'total_petugas2' => $row->total_petugas2,
            'total_premi_bersama' => $row->total_premi_bersama,
            'total_dibagikan' => $row->total_dibagikan,
            'recipient_count' => $row->recipient_count ?? $recipients->count(),
            'source_details' => $sourceDetails->map(fn ($detail) => $this->sourceDetailPayload($detail)),
            'recipients' => $recipients->map(fn ($detail) => $this->recipientDetailPayload($detail)),
            'config_snapshot' => $row->config_snapshot,
            'is_locked' => $row->is_locked,
            'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row->lockedBy?->name,
            'generate_by_name' => $row->generateBy?->name,
            'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function sourceDetailPayload($detail): array
    {
        return [
            'source_label' => $detail->source_label,
            'tindakan_config_id' => $detail->tindakan_config_id,
            'nama_tindakan' => $detail->nama_tindakan,
            'harga_tindakan' => $detail->harga_tindakan,
            'jumlah' => $detail->jumlah,
            'subtotal' => $detail->subtotal,
        ];
    }

    private function recipientDetailPayload($detail): array
    {
        return [
            'role' => $detail->role,
            'role_label' => $detail->role_label,
            'pegawai_id' => $detail->pegawai_id,
            'pegawai_name' => $detail->pegawai_name,
            'pegawai_position' => $detail->pegawai_position,
            'allocation_percent' => $detail->allocation_percent,
            'basis_amount' => $detail->basis_amount,
            'total_received' => $detail->total_received,
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

    private function typeLabel(string $jenisFisio): string
    {
        return $jenisFisio === 'bpjs' ? 'BPJS Kesehatan' : 'Umum';
    }
}
