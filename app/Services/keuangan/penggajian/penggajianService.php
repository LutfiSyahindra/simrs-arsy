<?php

namespace App\Services\keuangan\penggajian;

use App\Jobs\KirimSlipGajiWhatsappJob;
use App\Repositories\keuangan\penggajian\penggajianRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class penggajianService
{
    protected $penggajianRepository;
    public function __construct()
    {
        $this->penggajianRepository = new penggajianRepository();
    }

    public function getSummaryGajiTahap1(?string $periode)
    {
        $data = $this->penggajianRepository->getGajiTahap1Table($periode);

        return [
            'jumlah_pegawai' => $data->count(),
            'jumlah_tetap' => $data->where('status', 'T')->count(),
            'jumlah_kontrak' => $data->where('status', 'FT')->count(),
            'total_gaji' => $data->sum(function ($row) {
                return (int) $row->gaji_dibayar + (int) $row->tunjangan;
            }),
            'total_gapok' => $data->sum('gaji_dibayar'),
            'total_tunjangan' => $data->sum('tunjangan'),
            'periode' => $periode,
        ];
    }

    public function generateGajiTahap1(string $periode)
    {
        return DB::transaction(function () use ($periode) {
            $pegawaiList = $this->penggajianRepository->getPegawaiUntukGajiTahap1();

            $jumlahPegawai = 0;
            $jumlahTetap = 0;
            $jumlahKontrak = 0;
            $totalGaji = 0;

            foreach ($pegawaiList as $pegawai) {
                $gajiPokok = (int) ($pegawai->nominal_gaji_pokok ?? 0);
                $tunjangan = (int) ($pegawai->nominal_tunjangan ?? 0);
                $status = strtoupper(trim($pegawai->status ?? ''));

                if ($status === 'T') {
                    $gajiDibayar = $gajiPokok;
                    $tunjanganDibayar = $tunjangan;
                    $jumlahTetap++;
                } elseif ($status === 'FT') {
                    $gajiDibayar = (int) round($gajiPokok * 0.5);
                    $tunjanganDibayar = 0;
                    $jumlahKontrak++;
                } else {
                    $gajiDibayar = 0;
                    $tunjanganDibayar = 0;
                }

                $this->penggajianRepository->updateOrCreateGajiTahap1([
                    'periode' => $periode,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                    'jabatan' => $pegawai->jbtn,
                    'status' => $pegawai->status,
                    'gaji_pokok' => $gajiPokok,
                    'gaji_dibayar' => $gajiDibayar,
                    'tunjangan' => $tunjanganDibayar,
                ]);

                $jumlahPegawai++;
                $totalGaji += $gajiDibayar + $tunjanganDibayar;
            }

            return [
                'jumlah_pegawai' => $jumlahPegawai,
                'jumlah_tetap' => $jumlahTetap,
                'jumlah_kontrak' => $jumlahKontrak,
                'total_gaji' => $totalGaji,
            ];
        });
    }

    public function getGajiTahap1Table($periode)
    {
        $gajiTahap1Table = $this->penggajianRepository->getGajiTahap1Table($periode);

        $dataGajiTahap1Table = [];
        foreach ($gajiTahap1Table as $gajiTahap1) {
            $gajiDibayarkan = (int) $gajiTahap1->gaji_dibayar;
            $tunjangan = (int) $gajiTahap1->tunjangan;

            $dataGajiTahap1Table[] = [
                'id' => $gajiTahap1->id,
                'nik' => $gajiTahap1->nik,
                'nama_pegawai' => $gajiTahap1->nama,
                'jabatan' => $gajiTahap1->jabatan,
                'status' => $gajiTahap1->status,
                'gapok' => (int) $gajiTahap1->gaji_pokok,
                'gaji_dibayarkan' => $gajiDibayarkan,
                'tunjangan' => $tunjangan,
                'total' => $gajiDibayarkan + $tunjangan,
                'periode' => $gajiTahap1->periode,
            ];
        }

        return collect($dataGajiTahap1Table);
    }

    public function getPenerimaSlipWhatsappTahap1(string $periode)
    {
        return $this->penggajianRepository
            ->getPenerimaSlipWhatsappTahap1($periode)
            ->map(function ($row) {
                $gajiDibayar = (int) $row->gaji_dibayar;
                $tunjangan = (int) $row->tunjangan;

                return [
                    'id' => $row->id,
                    'nik' => $row->nik,
                    'nama' => $row->nama,
                    'jabatan' => $row->jabatan,
                    'status' => $row->status,
                    'status_label' => $this->getStatusLabel($row->status),
                    'no_telp' => $row->no_telp,
                    'no_whatsapp' => $this->normalizeWhatsappNumber($row->no_telp),
                    'gaji_dibayar' => $gajiDibayar,
                    'tunjangan' => $tunjangan,
                    'total' => $gajiDibayar + $tunjangan,
                    'periode' => $row->periode,
                ];
            })
            ->values();
    }

    public function kirimSlipGajiWhatsappTahap1(string $periode, array $gajiIds)
    {
        $ids = collect($gajiIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            throw ValidationException::withMessages([
                'gaji_ids' => ['Pilih minimal satu pegawai.'],
            ]);
        }

        $baseUrl = $this->getGoWaBaseUrl();
        $this->checkGoWaHealth($baseUrl);

        $rows = $this->penggajianRepository->getPenerimaSlipWhatsappTahap1($periode, $ids);

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'gaji_ids' => ['Tidak ada pegawai terpilih yang memiliki nomor Whatsapp pada periode ini.'],
            ]);
        }

        $validRows = $rows
            ->filter(fn ($row) => $this->normalizeWhatsappNumber($row->no_telp) !== null)
            ->values();

        if ($validRows->isEmpty()) {
            throw ValidationException::withMessages([
                'gaji_ids' => ['Nomor Whatsapp pegawai terpilih tidak valid.'],
            ]);
        }

        $delaySeconds = max(1, (int) config('services.go_wa.queue_delay_seconds', 8));

        foreach ($validRows as $index => $row) {
            KirimSlipGajiWhatsappJob::dispatch((int) $row->id, $periode)
                ->delay(now()->addSeconds($index * $delaySeconds));
        }

        return [
            'queued' => $validRows->count(),
            'skipped' => $rows->count() - $validRows->count(),
            'requested' => count($ids),
            'periode' => $periode,
            'delay_seconds' => $delaySeconds,
        ];
    }

    public function sendSingleSlipWhatsappTahap1(int $gajiId, string $periode)
    {
        $row = $this->penggajianRepository
            ->getPenerimaSlipWhatsappTahap1($periode, [$gajiId])
            ->first();

        if (!$row) {
            throw new RuntimeException('Data slip atau nomor Whatsapp pegawai tidak ditemukan.');
        }

        $number = $this->normalizeWhatsappNumber($row->no_telp);

        if (!$number) {
            throw new RuntimeException('Nomor Whatsapp pegawai tidak valid.');
        }

        $detail = $this->detailGajiTahap1($row->id);
        $fileName = $this->makeSlipPdfFilename($row->nik, $periode);
        $tempDir = storage_path('app/slip-gaji-whatsapp');
        $filePath = $tempDir . DIRECTORY_SEPARATOR . Str::uuid() . '-' . $fileName;

        File::ensureDirectoryExists($tempDir);

        Pdf::loadView('simrs.backOffice.keuangan.penggajian.slipGaji', [
            'data' => $detail,
        ])->setPaper('a4', 'portrait')->save($filePath);

        try {
            $response = $this->goWaHttpClient((int) config('services.go_wa.timeout', 60))
                ->post($this->getGoWaBaseUrl() . '/send/pdf', [
                    'phone' => $number,
                    'caption' => $this->makeSlipWhatsappCaption($detail),
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'API Go WA tidak bisa dihubungi. Periksa WA_GATEWAY_URL.',
                previous: $e
            );
        } finally {
            File::delete($filePath);
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                $response->json('message')
                    ?? $response->json('error')
                    ?? 'Go WA gagal mengirim slip gaji.'
            );
        }

        return [
            'gaji_id' => $row->id,
            'nik' => $row->nik,
            'nama' => $row->nama,
            'phone' => $number,
            'periode' => $periode,
            'response' => $response->json() ?? $response->body(),
        ];
    }

    public function detailGajiTahap1($id)
    {
        $gaji = $this->penggajianRepository->findGajiTahap1ById($id);

        if (!$gaji) {
            abort(404, 'Data gaji tahap 1 tidak ditemukan');
        }

        $tunjanganList = $this->penggajianRepository
        ->getTunjanganPegawai($gaji->nik)
        ->map(function ($tunjangan) use ($gaji) {
            return [
                'nama' => $tunjangan->nama_tunjangan ?? 'Tunjangan',
                'nominal' => $gaji->status === 'T' ? (int) $tunjangan->nominal : 0,
            ];
        })
        ->values();

        $gajiDibayar = (int) $gaji->gaji_dibayar;
        $tunjangan = (int) $gaji->tunjangan;

        return [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'gaji_dibayar' => $gajiDibayar,
            'tunjangan' => $tunjangan,
            'tunjangan_detail' => $tunjanganList,
            'total' => $gajiDibayar + $tunjangan,
        ];
    }

    private function getStatusLabel(?string $status)
    {
        return match ($status) {
            'T' => 'Pegawai Tetap',
            'FT' => 'Pegawai Kontrak',
            default => '-',
        };
    }

    private function getGoWaBaseUrl()
    {
        $baseUrl = rtrim((string) config('services.go_wa.base_url'), '/');

        if ($baseUrl === '') {
            throw ValidationException::withMessages([
                'go_wa' => ['WA_GATEWAY_URL belum dikonfigurasi.'],
            ]);
        }

        return $baseUrl;
    }

    private function checkGoWaHealth(string $baseUrl)
    {
        try {
            $response = Http::timeout(5)->acceptJson()->get($baseUrl . '/health');
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages([
                'go_wa' => ['API Go WA tidak bisa dihubungi. Periksa WA_GATEWAY_URL.'],
            ]);
        }

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'go_wa' => ['API Go WA tidak merespons dengan baik.'],
            ]);
        }
    }

    private function goWaHttpClient(int $timeout)
    {
        $client = Http::timeout($timeout)->acceptJson();
        $token = trim((string) config('services.go_wa.token'));

        if ($token !== '') {
            $client = $client->withToken($token);
        }

        return $client;
    }

    private function normalizeWhatsappNumber(?string $phone)
    {
        $number = preg_replace('/\D+/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            return '62' . substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62' . $number;
        }

        return $number;
    }

    private function makeSlipWhatsappCaption(array $detail)
    {
        return 'Assalamualaikum ' . ($detail['nama'] ?? '') .
            ', berikut slip gaji periode ' . $this->formatPeriode($detail['periode'] ?? null) .
            '. Terima kasih.';
    }

    private function makeSlipPdfFilename($nik, string $periode)
    {
        $safeNik = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $nik);

        return 'slip-gaji-' . trim($safeNik, '-') . '-' . $periode . '.pdf';
    }

    private function formatPeriode(?string $periode)
    {
        if (!$periode) {
            return '-';
        }

        try {
            return Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return $periode;
        }
    }

}
