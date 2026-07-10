<?php

namespace App\Services\keuangan\penggajian;

use App\Jobs\KirimSlipGajiWhatsappJob;
use App\Repositories\keuangan\penggajian\penggajianRepository;
use App\Support\PayrollComponentLabel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class penggajianService
{
    private const STAGE2_DOCTOR_PREMIUM_TYPES = [
        'kebersamaan' => 'Kebersamaan',
        'jasa_operasi' => 'Jasa Operasi',
        'jasa_rawat_jalan' => 'Jasa Rawat Jalan',
        'jasa_poli' => 'Jasa Poli',
        'jasa_ecg' => 'Jasa ECG',
        'konsul_wa' => 'Konsul WA',
        'jasa_igd' => 'Jasa IGD',
        'kehadiran' => 'Kehadiran',
    ];

    protected $penggajianRepository;

    public function __construct()
    {
        $this->penggajianRepository = new penggajianRepository;
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
            'total_premi' => 0,
            'jumlah_lainnya' => $data->whereNotIn('status', ['T', 'FT'])->count(),
            'periode' => $periode,
        ];
    }

    public function getSummaryGajiTahap2(?string $periode)
    {
        $data = $this->penggajianRepository->getGajiTahap2Table($periode);

        return [
            'jumlah_pegawai' => $data->count(),
            'jumlah_tetap' => $data->where('status', 'T')->count(),
            'jumlah_kontrak' => $data->where('status', 'FT')->count(),
            'jumlah_lainnya' => $data->whereNotIn('status', ['T', 'FT'])->count(),
            'total_gaji' => $data->sum('total'),
            'total_gapok' => $data->sum('gaji_dibayar'),
            'total_tunjangan' => 0,
            'total_premi' => $data->sum('total_premi'),
            'total_potongan' => $data->sum('total_potongan'),
            'jumlah_sumber_premi' => $data->sum('jumlah_sumber_premi'),
            'periode' => $periode,
        ];
    }

    public function getGajiTahap2GeneratorReadiness(string $periode): array
    {
        return $this->penggajianRepository->getGajiTahap2GeneratorReadiness($periode);
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
                    $tunjanganDibayar = $tunjangan;
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
        $doctorNikLookup = $this->doctorNikLookup($gajiTahap1Table);

        $dataGajiTahap1Table = [];
        foreach ($gajiTahap1Table as $gajiTahap1) {
            $gajiDibayarkan = (int) $gajiTahap1->gaji_dibayar;
            $tunjangan = (int) $gajiTahap1->tunjangan;
            $komponenGajiLabel = PayrollComponentLabel::salaryLabel($gajiTahap1->jabatan, $gajiTahap1->status);
            $canExportSlip = ! $this->isDoctorEmployee($gajiTahap1->nik, $doctorNikLookup);

            $dataGajiTahap1Table[] = [
                'id' => $gajiTahap1->id,
                'nik' => $gajiTahap1->nik,
                'nama_pegawai' => $gajiTahap1->nama,
                'jabatan' => $gajiTahap1->jabatan,
                'status' => $gajiTahap1->status,
                'gapok' => (int) $gajiTahap1->gaji_pokok,
                'komponen_gaji_label' => $komponenGajiLabel,
                'gaji_dibayarkan' => $gajiDibayarkan,
                'komponen_gaji_dibayar_label' => PayrollComponentLabel::paidSalaryLabel($gajiTahap1->jabatan, $gajiTahap1->status),
                'tunjangan' => $tunjangan,
                'total' => $gajiDibayarkan + $tunjangan,
                'periode' => $gajiTahap1->periode,
                'can_export_slip' => $canExportSlip,
                'slip_unavailable_message' => $canExportSlip ? null : 'Slip gaji dokter belum tersedia.',
            ];
        }

        return collect($dataGajiTahap1Table);
    }

    public function generateGajiTahap2(string $periode)
    {
        $this->ensureGajiTahap2GeneratorsReady($periode);

        return DB::transaction(function () use ($periode) {
            $stage2DoctorConfig = $this->penggajianRepository
                ->getGajiTahap2DoctorConfigs()
                ->keyBy(fn ($row) => (string) $row->kd_dokter);
            $pegawaiList = $this->penggajianRepository->getPegawaiUntukGajiTahap2();
            $pegawaiList = $pegawaiList
                ->reject(function ($pegawai) use ($stage2DoctorConfig) {
                    $status = $this->normalizeStatus($pegawai->status ?? null);
                    $requiresConfig = $this->isGeneralDoctorPosition($pegawai->jbtn ?? null)
                        || PayrollComponentLabel::isUgdContractDoctor($pegawai->jbtn ?? null, $status);

                    return $requiresConfig && ! $stage2DoctorConfig->has((string) $pegawai->nik);
                })
                ->values();
            $premiumEligibleNik = $pegawaiList
                ->filter(function ($pegawai) use ($stage2DoctorConfig) {
                    $status = $this->normalizeStatus($pegawai->status ?? null);
                    $doctorConfig = $stage2DoctorConfig->get((string) $pegawai->nik);

                    return ($doctorConfig && ! empty($doctorConfig->premium_types))
                        || $status === 'T';
                })
                ->pluck('nik')
                ->values();
            $premiByNik = $this->penggajianRepository
                ->collectPremiTahap2ByPeriod($periode, $premiumEligibleNik)
                ->groupBy('nik');
            $stage1TotalsByNik = $this->penggajianRepository->getGajiTahap1TotalsByNik($periode);
            $potonganByNik = $this->penggajianRepository->getPotonganPegawaiForStage2(
                $pegawaiList->pluck('nik')->all()
            );

            $generatedNiks = [];
            $jumlahPegawai = 0;
            $jumlahTetap = 0;
            $jumlahKontrak = 0;
            $jumlahLainnya = 0;
            $totalGaji = 0;
            $totalGapok = 0;
            $totalPremi = 0;
            $totalPotonganGenerate = 0;

            foreach ($pegawaiList as $pegawai) {
                $status = $this->normalizeStatus($pegawai->status ?? null);
                $doctorConfig = $stage2DoctorConfig->get((string) $pegawai->nik);
                $gajiPokok = (int) ($pegawai->nominal_gaji_pokok ?? 0);
                $gajiDibayar = 0;
                $premiDetails = collect();

                if ($doctorConfig) {
                    $premiDetails = $this->filterStage2DoctorPremiumDetails(
                        collect($premiByNik->get((string) $pegawai->nik, []))->values(),
                        $doctorConfig->premium_types
                    );

                    if ($doctorConfig->include_salary) {
                        $gajiDibayar = $this->stage2SalaryAmount($status, $gajiPokok, $pegawai->jbtn ?? null);
                    }
                } elseif ($status === 'FT') {
                    $gajiDibayar = $this->stage2SalaryAmount($status, $gajiPokok, $pegawai->jbtn ?? null);
                } elseif ($status === 'T') {
                    $premiDetails = collect($premiByNik->get((string) $pegawai->nik, []))
                        ->values();
                }

                if ($status === 'FT') {
                    $jumlahKontrak++;
                } elseif ($status === 'T') {
                    $jumlahTetap++;
                } else {
                    $jumlahLainnya++;
                }

                $pegawaiTotalPremi = (int) round((float) $premiDetails->sum('nominal'));
                $totalBruto = $gajiDibayar + $pegawaiTotalPremi;
                $potonganDetails = $this->calculateStage2Potongan(
                    collect($potonganByNik->get((string) $pegawai->nik, []))->values(),
                    $gajiPokok,
                    (int) ($stage1TotalsByNik[(string) $pegawai->nik] ?? 0),
                    $totalBruto
                );
                $totalPotongan = (int) $potonganDetails->sum('nominal');
                $total = max(0, $totalBruto - $totalPotongan);
                $breakdown = $this->premiumBreakdown($premiDetails);

                $gaji = $this->penggajianRepository->updateOrCreateGajiTahap2([
                    'periode' => $periode,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                    'jabatan' => $pegawai->jbtn,
                    'status' => $status,
                    'gaji_pokok' => $gajiPokok,
                    'gaji_dibayar' => $gajiDibayar,
                    'total_premi' => $pegawaiTotalPremi,
                    'total_potongan' => $totalPotongan,
                    'total' => $total,
                    'jumlah_sumber_premi' => $premiDetails->count(),
                    'premi_breakdown' => $breakdown,
                    'potongan_breakdown' => $potonganDetails->all(),
                ]);

                $this->penggajianRepository->replaceGajiTahap2Details($gaji, $premiDetails);

                $generatedNiks[] = (string) $pegawai->nik;
                $jumlahPegawai++;
                $totalGapok += $gajiDibayar;
                $totalPremi += $pegawaiTotalPremi;
                $totalPotonganGenerate += $totalPotongan;
                $totalGaji += $total;
            }

            $this->penggajianRepository->deleteGajiTahap2ExceptNik($periode, $generatedNiks);

            return [
                'jumlah_pegawai' => $jumlahPegawai,
                'jumlah_tetap' => $jumlahTetap,
                'jumlah_kontrak' => $jumlahKontrak,
                'jumlah_lainnya' => $jumlahLainnya,
                'total_gaji' => $totalGaji,
                'total_gapok' => $totalGapok,
                'total_premi' => $totalPremi,
                'total_potongan' => $totalPotonganGenerate,
                'periode' => $periode,
            ];
        });
    }

    public function getGajiTahap2Table($periode)
    {
        $rows = $this->penggajianRepository->getGajiTahap2Table($periode);
        $doctorNikLookup = $this->doctorNikLookup($rows);

        return $rows
            ->map(function ($row) use ($doctorNikLookup) {
                $salaryLabel = PayrollComponentLabel::salaryLabel($row->jabatan, $row->status);
                $canExportSlip = ! $this->isDoctorEmployee($row->nik, $doctorNikLookup);

                return [
                    'id' => $row->id,
                    'nik' => $row->nik,
                    'nama_pegawai' => $row->nama,
                    'jabatan' => $row->jabatan,
                    'status' => $row->status,
                    'status_label' => $this->getStatusLabel($row->status),
                    'gaji_pokok' => (int) $row->gaji_pokok,
                    'komponen_gaji_label' => $salaryLabel,
                    'gaji_dibayarkan' => (int) $row->gaji_dibayar,
                    'komponen_gaji_dibayar_label' => $this->stage2PaidSalaryLabel($row->jabatan, $row->status, (int) $row->gaji_dibayar),
                    'total_premi' => (int) $row->total_premi,
                    'total_potongan' => (int) ($row->total_potongan ?? 0),
                    'jumlah_sumber_premi' => (int) $row->jumlah_sumber_premi,
                    'total_bruto' => (int) $row->gaji_dibayar + (int) $row->total_premi,
                    'total' => (int) $row->total,
                    'premi_breakdown' => $row->premi_breakdown ?? [],
                    'potongan_breakdown' => $row->potongan_breakdown ?? [],
                    'periode' => $row->periode,
                    'can_export_slip' => $canExportSlip,
                    'slip_unavailable_message' => $canExportSlip ? null : 'Slip gaji dokter belum tersedia.',
                ];
            })
            ->values();
    }

    public function getPenerimaSlipWhatsappTahap1(string $periode)
    {
        $requestedRows = $this->penggajianRepository->getPenerimaSlipWhatsappTahap1($periode);
        $doctorNikLookup = $this->doctorNikLookup($requestedRows);
        $rows = $requestedRows
            ->reject(fn ($row) => $this->isDoctorEmployee($row->nik, $doctorNikLookup))
            ->values();
        $stage2ByNik = $this->penggajianRepository
            ->getGajiTahap2RowsByNik($periode, $rows->pluck('nik')->all());

        return $rows
            ->map(function ($row) use ($stage2ByNik) {
                $gajiDibayar = (int) $row->gaji_dibayar;
                $tunjangan = (int) $row->tunjangan;
                $totalTahap1 = $gajiDibayar + $tunjangan;
                $stage2 = $stage2ByNik->get((string) $row->nik);
                $totalTahap2 = $stage2 ? (int) $stage2->total : 0;
                $komponenGajiLabel = PayrollComponentLabel::salaryLabel($row->jabatan, $row->status);

                return [
                    'id' => $row->id,
                    'nik' => $row->nik,
                    'nama' => $row->nama,
                    'jabatan' => $row->jabatan,
                    'status' => $row->status,
                    'status_label' => $this->getStatusLabel($row->status),
                    'komponen_gaji_label' => $komponenGajiLabel,
                    'komponen_gaji_dibayar_label' => PayrollComponentLabel::paidSalaryLabel($row->jabatan, $row->status),
                    'no_telp' => $row->no_telp,
                    'no_whatsapp' => $this->normalizeWhatsappNumber($row->no_telp),
                    'gaji_dibayar' => $gajiDibayar,
                    'tunjangan' => $tunjangan,
                    'total_tahap1' => $totalTahap1,
                    'total_tahap2' => $totalTahap2,
                    'total' => $totalTahap1 + $totalTahap2,
                    'periode' => $row->periode,
                ];
            })
            ->values();
    }

    private function ensureGajiTahap2GeneratorsReady(string $periode): void
    {
        $readiness = $this->getGajiTahap2GeneratorReadiness($periode);

        if ($readiness['ready'] ?? false) {
            return;
        }

        throw ValidationException::withMessages([
            'periode' => [$readiness['message'] ?? 'Data generator tahap 2 belum lengkap.'],
        ]);
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

        $requestedRows = $this->penggajianRepository->getPenerimaSlipWhatsappTahap1($periode, $ids);
        $doctorNikLookup = $this->doctorNikLookup($requestedRows);
        $rows = $requestedRows
            ->reject(fn ($row) => $this->isDoctorEmployee($row->nik, $doctorNikLookup))
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'gaji_ids' => ['Tidak ada pegawai non-dokter terpilih yang memiliki nomor Whatsapp pada periode ini.'],
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
            'skipped' => $requestedRows->count() - $validRows->count(),
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

        if (! $row) {
            throw new RuntimeException('Data slip atau nomor Whatsapp pegawai tidak ditemukan.');
        }

        if ($this->isDoctorEmployee($row->nik)) {
            throw new RuntimeException('Slip gaji dokter belum tersedia.');
        }

        $number = $this->normalizeWhatsappNumber($row->no_telp);

        if (! $number) {
            throw new RuntimeException('Nomor Whatsapp pegawai tidak valid.');
        }

        $detail = $this->detailSlipGajiTahap1($row->id);
        $fileName = $this->makeSlipPdfFilename($row->nik, $periode);
        $tempDir = storage_path('app/slip-gaji-whatsapp');
        $filePath = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-'.$fileName;

        File::ensureDirectoryExists($tempDir);

        Pdf::loadView('simrs.backOffice.keuangan.penggajian.slipGaji', [
            'data' => $detail,
        ])->setPaper('a4', 'portrait')->save($filePath);

        try {
            $response = $this->goWaHttpClient((int) config('services.go_wa.timeout', 60))
                ->post($this->getGoWaBaseUrl().'/send/pdf', [
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

        if (! $response->successful()) {
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

        if (! $gaji) {
            abort(404, 'Data gaji tahap 1 tidak ditemukan');
        }

        $tunjanganList = $this->penggajianRepository
            ->getTunjanganPegawai($gaji->nik)
            ->map(function ($tunjangan) use ($gaji) {
                return [
                    'nama' => $tunjangan->nama_tunjangan ?? 'Tunjangan',
                    'nominal' => $gaji->status ? (int) $tunjangan->nominal : 0,
                ];
            })
            ->values();

        $gajiDibayar = (int) $gaji->gaji_dibayar;
        $tunjangan = (int) $gaji->tunjangan;
        $komponenGajiLabel = PayrollComponentLabel::salaryLabel($gaji->jabatan, $gaji->status);
        $canExportSlip = ! $this->isDoctorEmployee($gaji->nik);
        $stage2Gaji = $this->penggajianRepository
            ->findGajiTahap2ByPeriodNik($gaji->periode, (string) $gaji->nik);
        $stage2Detail = $stage2Gaji ? $this->stage2DetailPayload($stage2Gaji) : null;
        $totalTahap1 = $gajiDibayar + $tunjangan;

        return [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'komponen_gaji_label' => $komponenGajiLabel,
            'gaji_dibayar' => $gajiDibayar,
            'komponen_gaji_dibayar_label' => PayrollComponentLabel::paidSalaryLabel($gaji->jabatan, $gaji->status),
            'tunjangan' => $tunjangan,
            'tunjangan_detail' => $tunjanganList,
            'total' => $totalTahap1,
            'tahap2' => $stage2Detail,
            'total_slip' => $totalTahap1 + (int) ($stage2Detail['total'] ?? 0),
            'can_export_slip' => $canExportSlip,
            'slip_unavailable_message' => $canExportSlip ? null : 'Slip gaji dokter belum tersedia.',
        ];
    }

    public function detailSlipGajiTahap1($id): array
    {
        $detail = $this->detailGajiTahap1($id);

        if (! ($detail['can_export_slip'] ?? false)) {
            abort(404, $detail['slip_unavailable_message'] ?? 'Slip gaji belum tersedia.');
        }

        return $detail;
    }

    public function detailSlipGajiTahap2($id): array
    {
        $gajiTahap2 = $this->penggajianRepository->findGajiTahap2ById($id);

        if (! $gajiTahap2) {
            abort(404, 'Data gaji tahap 2 tidak ditemukan');
        }

        if ($this->isDoctorEmployee($gajiTahap2->nik)) {
            abort(404, 'Slip gaji dokter belum tersedia.');
        }

        $gajiTahap1 = $this->penggajianRepository
            ->findGajiTahap1ByPeriodNik($gajiTahap2->periode, (string) $gajiTahap2->nik);

        $detail = $gajiTahap1
            ? $this->detailGajiTahap1($gajiTahap1->id)
            : $this->stage2OnlySlipPayload($gajiTahap2);

        if (! ($detail['can_export_slip'] ?? false)) {
            abort(404, $detail['slip_unavailable_message'] ?? 'Slip gaji belum tersedia.');
        }

        return $detail;
    }

    public function detailGajiTahap2($id)
    {
        $gaji = $this->penggajianRepository->findGajiTahap2ById($id);

        if (! $gaji) {
            abort(404, 'Data gaji tahap 2 tidak ditemukan');
        }

        return $this->stage2DetailPayload($gaji);
    }

    private function stage2OnlySlipPayload($gaji): array
    {
        $stage2Detail = $this->stage2DetailPayload($gaji);
        $salaryLabel = PayrollComponentLabel::salaryLabel($gaji->jabatan, $gaji->status);
        $canExportSlip = ! $this->isDoctorEmployee($gaji->nik);

        return [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'komponen_gaji_label' => $salaryLabel,
            'gaji_dibayar' => 0,
            'komponen_gaji_dibayar_label' => PayrollComponentLabel::paidSalaryLabel($gaji->jabatan, $gaji->status),
            'tunjangan' => 0,
            'tunjangan_detail' => collect(),
            'total' => 0,
            'tahap2' => $stage2Detail,
            'total_slip' => (int) ($stage2Detail['total'] ?? 0),
            'can_export_slip' => $canExportSlip,
            'slip_unavailable_message' => $canExportSlip ? null : 'Slip gaji dokter belum tersedia.',
        ];
    }

    private function stage2DetailPayload($gaji): array
    {
        $salaryLabel = PayrollComponentLabel::salaryLabel($gaji->jabatan, $gaji->status);
        $totalTahap1 = (int) ($this->penggajianRepository
            ->getGajiTahap1TotalsByNik($gaji->periode)[(string) $gaji->nik] ?? 0);
        $totalTahap2Bruto = (int) $gaji->gaji_dibayar + (int) $gaji->total_premi;
        $premiDetails = $gaji->details
            ->map(fn ($detail) => [
                'nama' => trim($detail->source_label.' - '.($detail->role_label ?: ''), ' -'),
                'source_key' => $detail->source_key,
                'source_label' => $detail->source_label,
                'source_table' => $detail->source_table,
                'role_label' => $detail->role_label,
                'nominal' => (int) $detail->nominal,
            ])
            ->values();

        return [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'komponen_gaji_label' => $salaryLabel,
            'gaji_dibayar' => (int) $gaji->gaji_dibayar,
            'komponen_gaji_dibayar_label' => $this->stage2PaidSalaryLabel($gaji->jabatan, $gaji->status, (int) $gaji->gaji_dibayar),
            'total_premi' => (int) $gaji->total_premi,
            'total_potongan' => (int) ($gaji->total_potongan ?? 0),
            'total_bruto' => $totalTahap2Bruto,
            'premi_detail' => $premiDetails,
            'slip_pendapatan_umum' => $this->stage2SlipPendapatanUmum($gaji->details),
            'potongan_detail' => $this->sortStage2PotonganBreakdown(collect($gaji->potongan_breakdown ?? []))
                ->map(function ($detail) use ($totalTahap1, $totalTahap2Bruto) {
                    $tipe = $detail['tipe'] ?? null;
                    $isTotalSalaryDeduction = $tipe === 'persen_total_gaji';

                    return [
                        'nama' => $detail['nama'] ?? 'Potongan',
                        'tipe' => $tipe,
                        'nilai' => (float) ($detail['nilai'] ?? 0),
                        'total_tahap1' => (int) ($detail['total_tahap1'] ?? ($isTotalSalaryDeduction ? $totalTahap1 : 0)),
                        'total_tahap2' => (int) ($detail['total_tahap2'] ?? ($isTotalSalaryDeduction ? $totalTahap2Bruto : 0)),
                        'basis' => (int) ($detail['basis'] ?? ($isTotalSalaryDeduction ? $totalTahap1 + $totalTahap2Bruto : 0)),
                        'nominal' => (int) ($detail['nominal'] ?? 0),
                        'keterangan' => $detail['keterangan'] ?? null,
                    ];
                })
                ->values(),
            'total' => (int) $gaji->total,
        ];
    }

    private function stage2SlipPendapatanUmum(Collection $details): array
    {
        $jasaTindakan = [
            'umum' => 0,
            'bpjs' => 0,
        ];
        $premiBersama = 0;

        $details->each(function ($detail) use (&$jasaTindakan, &$premiBersama) {
            $nominal = (int) $detail->nominal;

            if ($nominal <= 0) {
                return;
            }

            if ($this->isStage2PremiBersamaDetail($detail)) {
                $premiBersama += $nominal;

                return;
            }

            $jasaTindakan[$this->stage2PremiumServiceType($detail)] += $nominal;
        });

        $items = collect([
            'umum' => 'UMUM',
            'bpjs' => 'BPJS',
        ])
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'nominal' => (int) $jasaTindakan[$key],
            ])
            ->values();

        $totalJasaTindakan = (int) $items->sum('nominal');

        return [
            'jasa_tindakan' => [
                'label' => 'JASA TINDAKAN',
                'items' => $items->all(),
                'total' => $totalJasaTindakan,
            ],
            'premi_bersama' => [
                'label' => 'PREMI BERSAMA',
                'nominal' => $premiBersama,
            ],
            'total_premi' => $totalJasaTindakan + $premiBersama,
        ];
    }

    private function isStage2PremiBersamaDetail($detail): bool
    {
        $sourceTable = strtolower((string) $detail->source_table);
        $sourceKey = strtolower((string) $detail->source_key);
        $sourceLabel = strtolower((string) $detail->source_label);

        return $sourceTable === 'generate_premi_bersama_distribution'
            || str_starts_with($sourceKey, 'premi_bersama')
            || str_contains($sourceLabel, 'premi bersama');
    }

    private function stage2PremiumServiceType($detail): string
    {
        $text = strtolower(trim(
            (string) $detail->source_key.' '.
            (string) $detail->source_label.' '.
            (string) $detail->source_table
        ));

        if (preg_match('/(^|[^a-z])bpjs([^a-z]|$)/', $text) || str_contains($text, 'casemix')) {
            return 'bpjs';
        }

        return 'umum';
    }

    public function getGajiTahap2ExportPayload(string $periode): array
    {
        return [
            'periode' => $periode,
            'summary' => $this->getSummaryGajiTahap2($periode),
            'rows' => $this->getGajiTahap2Table($periode),
            'details' => $this->penggajianRepository->getGajiTahap2DetailsByPeriod($periode),
        ];
    }

    public function dokterUmumTahap2Options(?string $keyword = null)
    {
        return $this->penggajianRepository
            ->dokterUmumOptions($keyword)
            ->map(fn ($row) => $this->dokterUmumPayload($row))
            ->values();
    }

    public function getGajiTahap2DoctorConfig(): array
    {
        return [
            'premium_type_options' => $this->stage2DoctorPremiumTypeOptions(),
            'salary_component' => [
                'id' => 'include_salary',
                'label' => 'STR/Gaji Pokok',
            ],
            'rows' => $this->penggajianRepository
                ->getGajiTahap2DoctorConfigs(false)
                ->map(fn ($row) => $this->doctorConfigPayload($row))
                ->values(),
        ];
    }

    public function updateGajiTahap2DoctorConfig(array $rows): array
    {
        $normalizedRows = $this->normalizeDoctorConfigRows($rows);
        $savedRows = $this->penggajianRepository->saveGajiTahap2DoctorConfigs($normalizedRows);

        return [
            'premium_type_options' => $this->stage2DoctorPremiumTypeOptions(),
            'salary_component' => [
                'id' => 'include_salary',
                'label' => 'STR/Gaji Pokok',
            ],
            'rows' => $savedRows
                ->map(fn ($row) => $this->doctorConfigPayload($row))
                ->values(),
        ];
    }

    private function getStatusLabel(?string $status)
    {
        return match ($this->normalizeStatus($status)) {
            'T' => 'Pegawai Tetap',
            'FT' => 'Pegawai Kontrak',
            'PT' => 'Pegawai Casual',
            'MT' => 'Mitra',
            default => '-',
        };
    }

    private function calculateStage2Potongan(
        Collection $potonganList,
        int $gajiPokok,
        int $totalTahap1,
        int $totalTahap2Bruto
    ): Collection {
        return $potonganList
            ->values()
            ->map(fn ($potongan, int $index) => [
                'potongan' => $potongan,
                'index' => $index,
            ])
            ->sort(function (array $left, array $right) {
                $priorityComparison = $this->stage2PotonganPriority($left['potongan'])
                    <=> $this->stage2PotonganPriority($right['potongan']);

                return $priorityComparison !== 0
                    ? $priorityComparison
                    : $left['index'] <=> $right['index'];
            })
            ->pluck('potongan')
            ->map(function ($potongan) use ($gajiPokok, $totalTahap1, $totalTahap2Bruto) {
                $tipe = $potongan->tipe ?? 'manual';
                $nilai = (float) ($potongan->nilai ?? 0);
                $nominalMapping = (float) ($potongan->nominal_mapping ?? 0);
                $basis = 0;

                $nominal = match ($tipe) {
                    'nominal' => $nilai,
                    'persen_gapok' => tap($gajiPokok, function ($value) use (&$basis) {
                        $basis = $value;
                    }) * ($nilai / 100),
                    'persen_total_gaji' => tap($totalTahap1 + $totalTahap2Bruto, function ($value) use (&$basis) {
                        $basis = $value;
                    }) * ($nilai / 100),
                    default => $nominalMapping,
                };

                $nominal = (int) round((float) $nominal);

                return [
                    'potongan_id' => (int) $potongan->potongan_id,
                    'kode' => $potongan->kode,
                    'nama' => $potongan->nama,
                    'tipe' => $tipe,
                    'nilai' => $nilai,
                    'total_tahap1' => $totalTahap1,
                    'total_tahap2' => $totalTahap2Bruto,
                    'basis' => $basis,
                    'nominal' => $nominal,
                    'keterangan' => $this->stage2PotonganDescription($tipe, $nilai, $basis),
                ];
            })
            ->filter(fn (array $potongan) => $potongan['nominal'] > 0)
            ->values();
    }

    private function sortStage2PotonganBreakdown(Collection $potonganList): Collection
    {
        return $potonganList
            ->values()
            ->map(fn ($potongan, int $index) => [
                'potongan' => $potongan,
                'index' => $index,
            ])
            ->sort(function (array $left, array $right) {
                $priorityComparison = $this->stage2PotonganPriority($left['potongan'])
                    <=> $this->stage2PotonganPriority($right['potongan']);

                return $priorityComparison !== 0
                    ? $priorityComparison
                    : $left['index'] <=> $right['index'];
            })
            ->pluck('potongan')
            ->values();
    }

    private function stage2PotonganPriority($potongan): int
    {
        $tipe = is_array($potongan)
            ? ($potongan['tipe'] ?? 'manual')
            : ($potongan->tipe ?? 'manual');
        $nilai = (float) (is_array($potongan)
            ? ($potongan['nilai'] ?? 0)
            : ($potongan->nilai ?? 0));

        if ($tipe === 'persen_total_gaji' && abs($nilai - 1.0) < 0.00001) {
            return 0;
        }

        return 1;
    }

    private function stage2PotonganDescription(string $tipe, float $nilai, int $basis): ?string
    {
        return match ($tipe) {
            'persen_gapok' => rtrim(rtrim((string) $nilai, '0'), '.').'% x gaji pokok',
            'persen_total_gaji' => rtrim(rtrim((string) $nilai, '0'), '.').'% x gaji tahap 1 + 2',
            default => null,
        };
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(preg_replace('/\s+/', ' ', trim((string) $status)));

        return match ($status) {
            'TETAP', 'PEGAWAI TETAP' => 'T',
            'KONTRAK', 'PEGAWAI KONTRAK', 'FT>1' => 'FT',
            'CASUAL', 'PEGAWAI CASUAL' => 'PT',
            'MITRA' => 'MT',
            default => $status,
        };
    }

    private function stage2PaidSalaryLabel(?string $position, ?string $status, ?int $paidAmount = null): string
    {
        $status = $this->normalizeStatus($status);

        if (PayrollComponentLabel::isUgdContractDoctor($position, $status)) {
            return 'Tidak Dibayarkan';
        }

        if ($status === 'FT' && $paidAmount !== null && $paidAmount <= 0) {
            return 'Tidak Dibayarkan';
        }

        return $status === 'FT'
            ? PayrollComponentLabel::salaryLabel($position, $status).' 50%'
            : 'Komponen Gaji';
    }

    private function stage2SalaryAmount(string $status, int $gajiPokok, ?string $position = null): int
    {
        if (PayrollComponentLabel::isUgdContractDoctor($position, $status)) {
            return 0;
        }

        return $status === 'FT'
            ? (int) round($gajiPokok * 0.5)
            : 0;
    }

    private function filterStage2DoctorPremiumDetails($details, array $premiumTypes): Collection
    {
        $premiumTypes = collect($premiumTypes)
            ->filter()
            ->map(fn ($type) => (string) $type)
            ->values();

        if ($premiumTypes->isEmpty()) {
            return collect();
        }

        return collect($details)
            ->filter(fn (array $detail) => ($detail['source_table'] ?? null) === 'generate_premi_dokter_detail'
                && $premiumTypes->contains((string) ($detail['source_premium_type'] ?? '')))
            ->values();
    }

    private function normalizeDoctorConfigRows(array $rows): array
    {
        $allowedPremiumTypes = array_keys(self::STAGE2_DOCTOR_PREMIUM_TYPES);

        return collect($rows)
            ->map(function (array $row) use ($allowedPremiumTypes) {
                $premiumTypes = collect($row['premium_types'] ?? [])
                    ->map(fn ($type) => (string) $type)
                    ->filter(fn ($type) => in_array($type, $allowedPremiumTypes, true))
                    ->unique()
                    ->values()
                    ->all();
                $includeSalary = filter_var($row['include_salary'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $includeSalary && empty($premiumTypes)) {
                    throw ValidationException::withMessages([
                        'rows' => ['Setiap dokter tahap 2 wajib memiliki minimal satu komponen.'],
                    ]);
                }

                return [
                    'kd_dokter' => trim((string) ($row['kd_dokter'] ?? '')),
                    'nm_dokter' => trim((string) ($row['nm_dokter'] ?? '')),
                    'kd_sps' => filled($row['kd_sps'] ?? null) ? trim((string) $row['kd_sps']) : null,
                    'nm_sps' => filled($row['nm_sps'] ?? null) ? trim((string) $row['nm_sps']) : null,
                    'include_salary' => $includeSalary,
                    'premium_types' => $premiumTypes,
                ];
            })
            ->filter(fn (array $row) => filled($row['kd_dokter']) && filled($row['nm_dokter']))
            ->unique(fn (array $row) => $row['kd_dokter'])
            ->values()
            ->all();
    }

    private function doctorConfigPayload(object $row): array
    {
        $premiumTypes = collect($row->premium_types ?? [])
            ->map(fn ($type) => (string) $type)
            ->filter(fn ($type) => isset(self::STAGE2_DOCTOR_PREMIUM_TYPES[$type]))
            ->values()
            ->all();

        return [
            'id' => $row->kd_dokter,
            'kd_dokter' => $row->kd_dokter,
            'nm_dokter' => $row->nm_dokter,
            'kd_sps' => $row->kd_sps,
            'nm_sps' => $row->nm_sps,
            'include_salary' => (bool) $row->include_salary,
            'premium_types' => $premiumTypes,
            'text' => trim($row->kd_dokter.' - '.$row->nm_dokter.' ('.($row->nm_sps ?: 'Umum').')'),
        ];
    }

    private function dokterUmumPayload(object $row): array
    {
        return [
            'id' => $row->kd_dokter,
            'kd_dokter' => $row->kd_dokter,
            'nm_dokter' => $row->nm_dokter,
            'kd_sps' => $row->kd_sps,
            'nm_sps' => $row->nm_sps,
            'text' => trim($row->kd_dokter.' - '.$row->nm_dokter.' ('.($row->nm_sps ?: 'Umum').')'),
        ];
    }

    private function stage2DoctorPremiumTypeOptions(): array
    {
        return collect(self::STAGE2_DOCTOR_PREMIUM_TYPES)
            ->map(fn (string $label, string $id) => [
                'id' => $id,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function doctorNikLookup(Collection $rows): Collection
    {
        return $this->penggajianRepository->getDoctorNikLookup(
            $rows
                ->pluck('nik')
                ->all()
        );
    }

    private function isDoctorEmployee(?string $nik, ?Collection $doctorNikLookup = null): bool
    {
        $nik = trim((string) $nik);

        if ($nik === '') {
            return false;
        }

        $doctorNikLookup ??= $this->penggajianRepository->getDoctorNikLookup([$nik]);

        return (bool) $doctorNikLookup->get('nik:'.$nik, false);
    }

    private function isGeneralDoctorPosition(?string $position): bool
    {
        return str_contains($this->normalizeText($position), 'dokter umum');
    }

    private function normalizeText(?string $value): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private function premiumBreakdown($details): array
    {
        return collect($details)
            ->groupBy('source_label')
            ->map(fn ($rows, $label) => [
                'source_label' => (string) $label,
                'jumlah_data' => $rows->count(),
                'total' => (int) round((float) $rows->sum('nominal')),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
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
            $response = Http::timeout(5)->acceptJson()->get($baseUrl.'/health');
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages([
                'go_wa' => ['API Go WA tidak bisa dihubungi. Periksa WA_GATEWAY_URL.'],
            ]);
        }

        if (! $response->successful()) {
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
            return '62'.substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62'.$number;
        }

        return $number;
    }

    private function makeSlipWhatsappCaption(array $detail)
    {
        return 'Assalamualaikum '.($detail['nama'] ?? '').
            ', berikut slip gaji periode '.$this->formatPeriode($detail['periode'] ?? null).
            '. Terima kasih.';
    }

    private function makeSlipPdfFilename($nik, string $periode)
    {
        $safeNik = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $nik);

        return 'slip-gaji-'.trim($safeNik, '-').'-'.$periode.'.pdf';
    }

    private function formatPeriode(?string $periode)
    {
        if (! $periode) {
            return '-';
        }

        try {
            return Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return $periode;
        }
    }
}
