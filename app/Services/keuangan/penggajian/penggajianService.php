<?php

namespace App\Services\keuangan\penggajian;

use App\Jobs\KirimSlipGajiEmailJob;
use App\Jobs\KirimSlipGajiWhatsappJob;
use App\Mail\SlipGajiMail;
use App\Repositories\keuangan\penggajian\penggajianRepository;
use App\Support\PayrollComponentLabel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class penggajianService
{
    private const SLIP_CHANNEL_WHATSAPP = 'whatsapp';

    private const SLIP_CHANNEL_EMAIL = 'email';

    private const STAGE1_DOCTOR_STR_TYPE = 'upah_str';

    private const STAGE1_DOCTOR_PREMIUM_TYPES = [
        self::STAGE1_DOCTOR_STR_TYPE => 'Upah STR',
        'kebersamaan' => 'Kebersamaan',
        'jasa_operasi' => 'Jasa Operasi',
        'jasa_rawat_jalan' => 'Jasa Rawat Jalan',
        'jasa_poli' => 'Jasa Poli',
        'jasa_ecg' => 'Jasa ECG',
        'konsul_wa' => 'Konsul WA',
        'jasa_igd' => 'Jasa IGD',
    ];

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

    private const ROUNDING_CONFIG_DEFAULTS = [
        'premium_received_enabled' => false,
        'premium_received_base' => 1000,
        'premium_received_mode' => 'up',
        'stage1_total_enabled' => true,
        'stage1_total_base' => 1000,
        'stage1_total_mode' => 'up',
        'stage2_total_enabled' => true,
        'stage2_total_base' => 1000,
        'stage2_total_mode' => 'up',
    ];

    private const DOCTOR_SLIP_JASA_ROWS = [
        'kehadiran' => 'KEHADIRAN',
        'str' => 'STR',
        'kebersamaan' => 'KEBERSAMAAN',
    ];

    private const DOCTOR_SLIP_ACTION_ROWS = [
        'ok' => 'OK',
        'rawat_jalan' => 'RAWAT JALAN',
        'visite' => 'VISITE',
        'poli' => 'POLI',
        'igd' => 'IGD',
        'ecg' => 'ECG',
        'radiologi' => 'RADIOLOGI',
        'laborat' => 'LABORAT',
    ];

    protected $penggajianRepository;

    private $payrollRoundingConfig = null;

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
                $componentTotal = (int) $row->gaji_dibayar
                    + (int) $row->tunjangan
                    + (int) ($row->premi ?? 0);
                $storedTotal = (int) ($row->total ?? 0);

                return $storedTotal !== 0 ? $storedTotal : $componentTotal;
            }),
            'total_gapok' => $data->sum('gaji_dibayar'),
            'total_tunjangan' => $data->sum('tunjangan'),
            'total_premi' => $data->sum('premi'),
            'total_pembulatan' => $data->sum('pembulatan'),
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
            'total_pembulatan' => $data->sum('pembulatan'),
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
            $roundingConfig = $this->roundingConfig();
            $stage1DoctorConfig = $this->penggajianRepository
                ->getGajiTahap1DoctorConfigs()
                ->keyBy(fn ($row) => (string) $row->kd_dokter);
            $premiDokterByNik = $this->penggajianRepository
                ->collectPremiDokterByPeriod($periode, $stage1DoctorConfig->keys()->values())
                ->pipe(fn (Collection $details) => $this->applyPremiumRoundingToDetails($details, $roundingConfig))
                ->groupBy('nik');
            $pegawaiList = $this->penggajianRepository->getPegawaiUntukGajiTahap1();

            $generatedNiks = [];
            $jumlahPegawai = 0;
            $jumlahTetap = 0;
            $jumlahKontrak = 0;
            $jumlahLainnya = 0;
            $totalGaji = 0;

            foreach ($pegawaiList as $pegawai) {
                $gajiPokok = (int) ($pegawai->nominal_gaji_pokok ?? 0);
                $tunjangan = (int) ($pegawai->nominal_tunjangan ?? 0);
                $status = $this->normalizeStatus($pegawai->status ?? null);
                $isUgdContractDoctor = PayrollComponentLabel::isUgdContractDoctor($pegawai->jbtn ?? null, $status);
                $doctorConfig = $isUgdContractDoctor
                    ? $stage1DoctorConfig->get((string) $pegawai->nik)
                    : null;
                $tunjanganBreakdown = null;
                $premiDibayar = 0;
                $premiBreakdown = null;
                $pendapatanTambahan = 0;

                if ($isUgdContractDoctor && ! $doctorConfig) {
                    continue;
                }

                if ($doctorConfig) {
                    $upahStr = (int) ($pegawai->nominal_gaji_pokok ?? 0);
                    $doctorPremiDetails = collect($premiDokterByNik->get((string) $pegawai->nik, []))->values();
                    $components = $this->stage1UgdContractComponents(
                        $upahStr,
                        $tunjangan,
                        $doctorPremiDetails,
                        $doctorConfig
                    );
                    $gajiPokok = $components['gaji_pokok'];
                    $gajiDibayar = $components['gaji_dibayar'];
                    $tunjanganDibayar = $components['tunjangan'];
                    $premiDibayar = $components['premi'];
                    $premiBreakdown = $components['premi_breakdown'];
                    $pendapatanTambahan = $components['upah_str_dibayar'];

                    if ($doctorConfig->include_salary && $gajiDibayar <= 0) {
                        throw ValidationException::withMessages([
                            'periode' => [
                                'Data kehadiran generator premi dokter untuk '.$pegawai->nama.' belum tersedia atau belum dikunci.',
                            ],
                        ]);
                    }

                    $jumlahKontrak++;
                } elseif ($status === 'T') {
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
                    $jumlahLainnya++;
                }

                $totalSebelumPembulatan = $pendapatanTambahan
                    + $gajiDibayar
                    + $tunjanganDibayar
                    + $premiDibayar;
                $totalDibayarkan = $this->applyRoundingConfig(
                    $totalSebelumPembulatan,
                    'stage1_total',
                    $roundingConfig
                );
                $pembulatan = $totalDibayarkan - $totalSebelumPembulatan;

                $this->penggajianRepository->updateOrCreateGajiTahap1([
                    'periode' => $periode,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                    'jabatan' => $pegawai->jbtn,
                    'status' => $status,
                    'gaji_pokok' => $gajiPokok,
                    'gaji_dibayar' => $gajiDibayar,
                    'tunjangan' => $tunjanganDibayar,
                    'premi' => $premiDibayar,
                    'premi_breakdown' => $premiBreakdown,
                    'pembulatan' => $pembulatan,
                    'total' => $totalDibayarkan,
                    'tunjangan_breakdown' => $tunjanganBreakdown,
                ]);

                $generatedNiks[] = (string) $pegawai->nik;
                $jumlahPegawai++;
                $totalGaji += $totalDibayarkan;
            }

            $this->penggajianRepository->deleteGajiTahap1ExceptNik($periode, $generatedNiks);

            return [
                'jumlah_pegawai' => $jumlahPegawai,
                'jumlah_tetap' => $jumlahTetap,
                'jumlah_kontrak' => $jumlahKontrak,
                'jumlah_lainnya' => $jumlahLainnya,
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
            $premi = (int) ($gajiTahap1->premi ?? 0);
            $componentTotal = $gajiDibayarkan + $tunjangan + $premi;
            $storedTotal = (int) ($gajiTahap1->total ?? 0);
            $total = $storedTotal !== 0 ? $storedTotal : $componentTotal;
            $komponenGajiLabel = PayrollComponentLabel::salaryLabel($gajiTahap1->jabatan, $gajiTahap1->status);
            $isDoctorSlip = $this->isDoctorEmployee($gajiTahap1->nik, $doctorNikLookup);

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
                'premi' => $premi,
                'pembulatan' => (int) ($gajiTahap1->pembulatan ?? ($total - $componentTotal)),
                'total' => $total,
                'periode' => $gajiTahap1->periode,
                'is_doctor_slip' => $isDoctorSlip,
                'can_export_slip' => true,
                'slip_unavailable_message' => null,
            ];
        }

        return collect($dataGajiTahap1Table);
    }

    public function generateGajiTahap2(string $periode)
    {
        $this->ensureGajiTahap2GeneratorsReady($periode);

        return DB::transaction(function () use ($periode) {
            $roundingConfig = $this->roundingConfig();
            $stage1DoctorConfig = $this->penggajianRepository
                ->getGajiTahap1DoctorConfigs()
                ->keyBy(fn ($row) => (string) $row->kd_dokter);
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
                ->pipe(fn (Collection $details) => $this->applyPremiumRoundingToDetails($details, $roundingConfig))
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
                    $premiDetails = $this->excludeStage1DoctorPremiumDetails(
                        $premiDetails,
                        $stage1DoctorConfig->get((string) $pegawai->nik)
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

                $pegawaiTotalPremi = (int) $premiDetails->sum('nominal');
                $totalBruto = $gajiDibayar + $pegawaiTotalPremi;
                $potonganDetails = $this->calculateStage2Potongan(
                    collect($potonganByNik->get((string) $pegawai->nik, []))->values(),
                    $gajiPokok,
                    (int) ($stage1TotalsByNik[(string) $pegawai->nik] ?? 0),
                    $totalBruto
                );
                $totalPotongan = (int) $potonganDetails->sum('nominal');
                $totalSebelumPembulatan = max(0, $totalBruto - $totalPotongan);
                $total = $this->applyRoundingConfig(
                    $totalSebelumPembulatan,
                    'stage2_total',
                    $roundingConfig
                );
                $pembulatan = $total - $totalSebelumPembulatan;
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
                    'pembulatan' => $pembulatan,
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
                $isDoctorSlip = $this->isDoctorEmployee($row->nik, $doctorNikLookup);

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
                    'pembulatan' => (int) ($row->pembulatan ?? 0),
                    'jumlah_sumber_premi' => (int) $row->jumlah_sumber_premi,
                    'total_bruto' => (int) $row->gaji_dibayar + (int) $row->total_premi,
                    'total' => (int) $row->total,
                    'premi_breakdown' => $row->premi_breakdown ?? [],
                    'potongan_breakdown' => $row->potongan_breakdown ?? [],
                    'periode' => $row->periode,
                    'is_doctor_slip' => $isDoctorSlip,
                    'can_export_slip' => true,
                    'slip_unavailable_message' => null,
                ];
            })
            ->values();
    }

    public function getPenerimaSlip(string $periode, int $tahap = 1, string $channel = self::SLIP_CHANNEL_WHATSAPP)
    {
        $channel = $this->normalizeSlipDeliveryChannel($channel);

        return $this->normalizeSlipWhatsappTahap($tahap) === 2
            ? $this->getPenerimaSlipTahap2($periode, $channel)
            : $this->getPenerimaSlipTahap1($periode, $channel);
    }

    public function getPenerimaSlipWhatsapp(string $periode, int $tahap = 1)
    {
        return $this->getPenerimaSlip($periode, $tahap, self::SLIP_CHANNEL_WHATSAPP);
    }

    public function getPenerimaSlipTahap1(string $periode, string $channel = self::SLIP_CHANNEL_WHATSAPP)
    {
        $channel = $this->normalizeSlipDeliveryChannel($channel);
        $rows = $this->penggajianRepository->getPenerimaSlipTahap1($periode, [], $channel);
        $doctorNikLookup = $this->doctorNikLookup($rows);
        $stage2ByNik = $this->penggajianRepository
            ->getGajiTahap2RowsByNik($periode, $rows->pluck('nik')->all());

        return $rows
            ->map(function ($row) use ($stage2ByNik, $doctorNikLookup, $channel) {
                $gajiDibayar = (int) $row->gaji_dibayar;
                $tunjangan = (int) $row->tunjangan;
                $premi = (int) ($row->premi ?? 0);
                $componentTotal = $gajiDibayar + $tunjangan + $premi;
                $storedTotal = (int) ($row->total ?? 0);
                $totalTahap1 = $storedTotal !== 0 ? $storedTotal : $componentTotal;
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
                    'email' => $row->email ?? null,
                    'gaji_dibayar' => $gajiDibayar,
                    'tunjangan' => $tunjangan,
                    'premi' => $premi,
                    'pembulatan' => (int) ($row->pembulatan ?? ($totalTahap1 - $componentTotal)),
                    'total_tahap1' => $totalTahap1,
                    'total_tahap2' => $totalTahap2,
                    'total' => $totalTahap1 + $totalTahap2,
                    'periode' => $row->periode,
                    'tahap' => 1,
                    'channel' => $channel,
                    'is_doctor_slip' => $this->isDoctorEmployee($row->nik, $doctorNikLookup),
                ];
            })
            ->values();
    }

    public function getPenerimaSlipWhatsappTahap1(string $periode)
    {
        return $this->getPenerimaSlipTahap1($periode, self::SLIP_CHANNEL_WHATSAPP);
    }

    public function getPenerimaSlipTahap2(string $periode, string $channel = self::SLIP_CHANNEL_WHATSAPP)
    {
        $channel = $this->normalizeSlipDeliveryChannel($channel);
        $rows = $this->penggajianRepository->getPenerimaSlipTahap2($periode, [], $channel);
        $doctorNikLookup = $this->doctorNikLookup($rows);
        $stage1TotalsByNik = $this->penggajianRepository->getGajiTahap1TotalsByNik($periode);

        return $rows
            ->map(function ($row) use ($doctorNikLookup, $stage1TotalsByNik, $channel) {
                $gajiDibayar = (int) $row->gaji_dibayar;
                $totalPremi = (int) ($row->total_premi ?? 0);
                $totalPotongan = (int) ($row->total_potongan ?? 0);
                $componentTotal = $gajiDibayar + $totalPremi - $totalPotongan;
                $totalTahap2 = (int) ($row->total ?? $componentTotal);
                $totalTahap1 = (int) ($stage1TotalsByNik[(string) $row->nik] ?? 0);
                $komponenGajiLabel = PayrollComponentLabel::salaryLabel($row->jabatan, $row->status);

                return [
                    'id' => $row->id,
                    'nik' => $row->nik,
                    'nama' => $row->nama,
                    'jabatan' => $row->jabatan,
                    'status' => $row->status,
                    'status_label' => $this->getStatusLabel($row->status),
                    'komponen_gaji_label' => $komponenGajiLabel,
                    'komponen_gaji_dibayar_label' => $this->stage2PaidSalaryLabel($row->jabatan, $row->status, $gajiDibayar),
                    'no_telp' => $row->no_telp,
                    'no_whatsapp' => $this->normalizeWhatsappNumber($row->no_telp),
                    'email' => $row->email ?? null,
                    'gaji_dibayar' => $gajiDibayar,
                    'total_premi' => $totalPremi,
                    'total_potongan' => $totalPotongan,
                    'pembulatan' => (int) ($row->pembulatan ?? ($totalTahap2 - $componentTotal)),
                    'total_tahap1' => $totalTahap1,
                    'total_tahap2' => $totalTahap2,
                    'total' => $totalTahap1 + $totalTahap2,
                    'periode' => $row->periode,
                    'tahap' => 2,
                    'channel' => $channel,
                    'is_doctor_slip' => $this->isDoctorEmployee($row->nik, $doctorNikLookup),
                ];
            })
            ->values();
    }

    public function getPenerimaSlipWhatsappTahap2(string $periode)
    {
        return $this->getPenerimaSlipTahap2($periode, self::SLIP_CHANNEL_WHATSAPP);
    }

    private function getPenerimaSlipRows(
        string $periode,
        array $ids,
        int $tahap,
        string $channel = self::SLIP_CHANNEL_WHATSAPP
    ): Collection {
        $channel = $this->normalizeSlipDeliveryChannel($channel);

        return $this->normalizeSlipWhatsappTahap($tahap) === 2
            ? $this->penggajianRepository->getPenerimaSlipTahap2($periode, $ids, $channel)
            : $this->penggajianRepository->getPenerimaSlipTahap1($periode, $ids, $channel);
    }

    private function getPenerimaSlipWhatsappRows(string $periode, array $ids, int $tahap): Collection
    {
        return $this->getPenerimaSlipRows($periode, $ids, $tahap, self::SLIP_CHANNEL_WHATSAPP);
    }

    private function normalizeSlipWhatsappTahap(int $tahap): int
    {
        return $tahap === 2 ? 2 : 1;
    }

    private function normalizeSlipDeliveryChannel(string $channel): string
    {
        return $channel === self::SLIP_CHANNEL_EMAIL
            ? self::SLIP_CHANNEL_EMAIL
            : self::SLIP_CHANNEL_WHATSAPP;
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

    public function kirimSlipGaji(
        string $periode,
        array $gajiIds,
        int $tahap = 1,
        string $channel = self::SLIP_CHANNEL_WHATSAPP
    )
    {
        return $this->normalizeSlipDeliveryChannel($channel) === self::SLIP_CHANNEL_EMAIL
            ? $this->kirimSlipGajiEmailTahap1($periode, $gajiIds, $tahap)
            : $this->kirimSlipGajiWhatsappTahap1($periode, $gajiIds, $tahap);
    }

    public function kirimSlipGajiWhatsappTahap1(string $periode, array $gajiIds, int $tahap = 1)
    {
        $tahap = $this->normalizeSlipWhatsappTahap($tahap);
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

        $rows = $this->getPenerimaSlipWhatsappRows($periode, $ids, $tahap);

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
            KirimSlipGajiWhatsappJob::dispatch((int) $row->id, $periode, $tahap)
                ->delay(now()->addSeconds($index * $delaySeconds));
        }

        return [
            'queued' => $validRows->count(),
            'skipped' => count($ids) - $validRows->count(),
            'requested' => count($ids),
            'periode' => $periode,
            'tahap' => $tahap,
            'channel' => self::SLIP_CHANNEL_WHATSAPP,
            'delay_seconds' => $delaySeconds,
        ];
    }

    public function kirimSlipGajiEmailTahap1(string $periode, array $gajiIds, int $tahap = 1)
    {
        $tahap = $this->normalizeSlipWhatsappTahap($tahap);
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

        $rows = $this->getPenerimaSlipRows($periode, $ids, $tahap, self::SLIP_CHANNEL_EMAIL);

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'gaji_ids' => ['Tidak ada pegawai terpilih yang memiliki email pada periode ini.'],
            ]);
        }

        $validRows = $rows
            ->filter(fn ($row) => $this->normalizeEmailAddress($row->email ?? null) !== null)
            ->values();

        if ($validRows->isEmpty()) {
            throw ValidationException::withMessages([
                'gaji_ids' => ['Email pegawai terpilih tidak valid.'],
            ]);
        }

        $delaySeconds = max(1, (int) config('services.email_gateway.queue_delay_seconds', 60));

        foreach ($validRows as $index => $row) {
            KirimSlipGajiEmailJob::dispatch((int) $row->id, $periode, $tahap)
                ->delay(now()->addSeconds($index * $delaySeconds));
        }

        return [
            'queued' => $validRows->count(),
            'skipped' => count($ids) - $validRows->count(),
            'requested' => count($ids),
            'periode' => $periode,
            'tahap' => $tahap,
            'channel' => self::SLIP_CHANNEL_EMAIL,
            'delay_seconds' => $delaySeconds,
        ];
    }

    public function sendSingleSlipWhatsappTahap1(int $gajiId, string $periode, int $tahap = 1)
    {
        return $this->sendSingleSlipWhatsapp($gajiId, $periode, $tahap);
    }

    public function sendSingleSlipEmailTahap1(int $gajiId, string $periode, int $tahap = 1)
    {
        return $this->sendSingleSlipEmail($gajiId, $periode, $tahap);
    }

    public function sendSingleSlipWhatsapp(int $gajiId, string $periode, int $tahap = 1)
    {
        $tahap = $this->normalizeSlipWhatsappTahap($tahap);
        $row = $this->getPenerimaSlipWhatsappRows($periode, [$gajiId], $tahap)->first();

        if (! $row) {
            throw new RuntimeException('Data slip atau nomor Whatsapp pegawai tidak ditemukan.');
        }

        $number = $this->normalizeWhatsappNumber($row->no_telp);

        if (! $number) {
            throw new RuntimeException('Nomor Whatsapp pegawai tidak valid.');
        }

        $detail = $tahap === 2
            ? $this->detailSlipGajiTahap2($row->id)
            : $this->detailSlipGajiTahap1($row->id);
        $view = $this->slipPdfView($detail);
        $pdfMode = $this->slipWhatsappPdfMode($detail);
        $pdfOptions = $this->slipPdfPaperOptions($detail, $pdfMode);
        $fileName = $this->makeSlipPdfFilename($row->nik, $periode);
        $tempDir = storage_path('app'.DIRECTORY_SEPARATOR.'slip-gaji-whatsapp');
        $filePath = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-'.$fileName;

        File::ensureDirectoryExists($tempDir);

        Pdf::loadView($view, [
            'data' => $detail,
            'pdfMode' => $pdfMode,
            'paperSize' => $pdfOptions['paper_size'],
        ])->setPaper($pdfOptions['paper'], 'portrait')->save($filePath);

        clearstatcache(true, $filePath);

        if (! File::exists($filePath)) {
            throw new RuntimeException('Gagal membuat file PDF slip gaji Whatsapp.');
        }

        $filePath = realpath($filePath) ?: $filePath;

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
            'tahap' => $tahap,
            'response' => $response->json() ?? $response->body(),
        ];
    }

    public function sendSingleSlipEmail(int $gajiId, string $periode, int $tahap = 1)
    {
        $tahap = $this->normalizeSlipWhatsappTahap($tahap);
        $row = $this->getPenerimaSlipRows($periode, [$gajiId], $tahap, self::SLIP_CHANNEL_EMAIL)->first();

        if (! $row) {
            throw new RuntimeException('Data slip atau email pegawai tidak ditemukan.');
        }

        $email = $this->normalizeEmailAddress($row->email ?? null);

        if (! $email) {
            throw new RuntimeException('Email pegawai tidak valid.');
        }

        $detail = $tahap === 2
            ? $this->detailSlipGajiTahap2($row->id)
            : $this->detailSlipGajiTahap1($row->id);
        $view = $this->slipPdfView($detail);
        $pdfMode = $this->slipEmailPdfMode($detail);
        $pdfOptions = $this->slipPdfPaperOptions($detail, $pdfMode);
        $fileName = $this->makeSlipPdfFilename($row->nik, $periode);
        $tempDir = storage_path('app'.DIRECTORY_SEPARATOR.'slip-gaji-email');
        $filePath = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'-'.$fileName;

        File::ensureDirectoryExists($tempDir);

        Pdf::loadView($view, [
            'data' => $detail,
            'pdfMode' => $pdfMode,
            'paperSize' => $pdfOptions['paper_size'],
        ])->setPaper($pdfOptions['paper'], 'portrait')->save($filePath);

        clearstatcache(true, $filePath);

        if (! File::exists($filePath)) {
            throw new RuntimeException('Gagal membuat file PDF slip gaji Email.');
        }

        $filePath = realpath($filePath) ?: $filePath;
        $mailer = $this->getSlipEmailMailer();

        try {
            $mail = $mailer ? Mail::mailer($mailer) : Mail::mailer();
            $mail->to($email)->send(new SlipGajiMail($detail, $filePath, $fileName));
        } finally {
            File::delete($filePath);
        }

        return [
            'gaji_id' => $row->id,
            'nik' => $row->nik,
            'nama' => $row->nama,
            'email' => $email,
            'periode' => $periode,
            'tahap' => $tahap,
        ];
    }

    public function slipPdfPaperOptions(array $detail, string $mode = 'export'): array
    {
        $paperSize = $this->slipPdfFitPaperSize($detail, $mode);

        return [
            'paper' => $this->dompdfPaperFromMillimeters(
                $paperSize['width_mm'],
                $paperSize['height_mm']
            ),
            'paper_size' => $paperSize,
        ];
    }

    public function slipPdfView(array $detail): string
    {
        return ($detail['is_doctor_slip'] ?? false)
            ? 'simrs.backOffice.keuangan.penggajian.slipGajiDokter'
            : 'simrs.backOffice.keuangan.penggajian.slipGaji';
    }

    public function slipWhatsappPdfMode(array $detail): string
    {
        return ($detail['is_doctor_slip'] ?? false) ? 'export' : 'whatsapp';
    }

    public function slipEmailPdfMode(array $detail): string
    {
        return ($detail['is_doctor_slip'] ?? false) ? $this->slipWhatsappPdfMode($detail) : 'single';
    }

    public function detailGajiTahap1($id)
    {
        $gaji = $this->penggajianRepository->findGajiTahap1ById($id);

        if (! $gaji) {
            abort(404, 'Data gaji tahap 1 tidak ditemukan');
        }

        $gajiDibayar = (int) $gaji->gaji_dibayar;
        $tunjangan = (int) $gaji->tunjangan;
        $premi = (int) ($gaji->premi ?? 0);
        $tunjanganList = $this->stage1TunjanganDetail($gaji);
        $premiList = $this->stage1PremiDetail($gaji);
        $komponenGajiLabel = PayrollComponentLabel::salaryLabel($gaji->jabatan, $gaji->status);
        $isDoctorSlip = $this->isDoctorEmployee($gaji->nik);
        $stage2Gaji = $this->penggajianRepository
            ->findGajiTahap2ByPeriodNik($gaji->periode, (string) $gaji->nik);
        $stage2Detail = $stage2Gaji ? $this->stage2DetailPayload($stage2Gaji) : null;
        $pendapatanTambahan = PayrollComponentLabel::isUgdContractDoctor($gaji->jabatan, $gaji->status)
            && $this->stage1DoctorIncludesStrForNik($gaji->nik)
                ? (int) $gaji->gaji_pokok
                : 0;
        $totalSebelumPembulatan = $pendapatanTambahan + $gajiDibayar + $tunjangan + $premi;
        $storedTotal = (int) ($gaji->total ?? 0);
        $totalTahap1 = $storedTotal !== 0 ? $storedTotal : $totalSebelumPembulatan;
        $pembulatan = (int) ($gaji->pembulatan ?? ($totalTahap1 - $totalSebelumPembulatan));

        $payload = [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'unit_kerja' => $this->unitKerjaLabelForNik($gaji->nik, $gaji->jabatan),
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'komponen_gaji_label' => $komponenGajiLabel,
            'gaji_dibayar' => $gajiDibayar,
            'komponen_gaji_dibayar_label' => PayrollComponentLabel::paidSalaryLabel($gaji->jabatan, $gaji->status),
            'tunjangan' => $tunjangan,
            'premi' => $premi,
            'pembulatan' => $pembulatan,
            'tunjangan_detail' => $tunjanganList,
            'premi_detail' => $premiList,
            'total' => $totalTahap1,
            'tahap2' => $stage2Detail,
            'total_slip' => $totalTahap1 + (int) ($stage2Detail['total'] ?? 0),
            'is_doctor_slip' => $isDoctorSlip,
            'can_export_slip' => true,
            'slip_unavailable_message' => null,
        ];

        return $this->withDoctorSlipPayload($payload);
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
        $isDoctorSlip = $this->isDoctorEmployee($gaji->nik);

        $payload = [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'unit_kerja' => $this->unitKerjaLabelForNik($gaji->nik, $gaji->jabatan),
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'komponen_gaji_label' => $salaryLabel,
            'gaji_dibayar' => 0,
            'komponen_gaji_dibayar_label' => PayrollComponentLabel::paidSalaryLabel($gaji->jabatan, $gaji->status),
            'tunjangan' => 0,
            'pembulatan' => 0,
            'tunjangan_detail' => collect(),
            'total' => 0,
            'tahap2' => $stage2Detail,
            'total_slip' => (int) ($stage2Detail['total'] ?? 0),
            'is_doctor_slip' => $isDoctorSlip,
            'can_export_slip' => true,
            'slip_unavailable_message' => null,
        ];

        return $this->withDoctorSlipPayload($payload);
    }

    private function stage2DetailPayload($gaji): array
    {
        $salaryLabel = PayrollComponentLabel::salaryLabel($gaji->jabatan, $gaji->status);
        $totalTahap1 = (int) ($this->penggajianRepository
            ->getGajiTahap1TotalsByNik($gaji->periode)[(string) $gaji->nik] ?? 0);
        $totalTahap2Bruto = (int) $gaji->gaji_dibayar + (int) $gaji->total_premi;
        $premiDokterSourceIds = $gaji->details
            ->filter(fn ($detail) => (string) $detail->source_table === 'generate_premi_dokter_detail')
            ->pluck('source_id');
        $premiDokterCounts = $premiDokterSourceIds->isEmpty()
            ? collect()
            : $this->penggajianRepository->getPremiDokterDetailCountsByIds($premiDokterSourceIds->all());
        $premiDetails = $gaji->details
            ->map(function ($detail) use ($premiDokterCounts) {
                $sourceId = (int) ($detail->source_id ?? 0);
                $counts = $premiDokterCounts->get($sourceId, []);

                return [
                    'nama' => $this->stage2PremiumDetailName($detail),
                    'source_key' => $detail->source_key,
                    'source_label' => $detail->source_label,
                    'source_table' => $detail->source_table,
                    'source_id' => $sourceId,
                    'role_label' => $detail->role_label,
                    'source_periode' => $detail->source_periode ?? null,
                    'source_period_mode' => $detail->source_period_mode ?? null,
                    'source_period_label' => $this->stage2PremiumSourcePeriodLabel($detail),
                    'source_jumlah_data' => (int) ($counts['jumlah_data'] ?? 0),
                    'source_jumlah_pasien' => (int) ($counts['jumlah_pasien'] ?? 0),
                    'nominal' => (int) $detail->nominal,
                ];
            })
            ->values();

        return [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'unit_kerja' => $this->unitKerjaLabelForNik($gaji->nik, $gaji->jabatan),
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $this->getStatusLabel($gaji->status),
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'komponen_gaji_label' => $salaryLabel,
            'gaji_dibayar' => (int) $gaji->gaji_dibayar,
            'komponen_gaji_dibayar_label' => $this->stage2PaidSalaryLabel($gaji->jabatan, $gaji->status, (int) $gaji->gaji_dibayar),
            'total_premi' => (int) $gaji->total_premi,
            'total_potongan' => (int) ($gaji->total_potongan ?? 0),
            'pembulatan' => (int) ($gaji->pembulatan ?? 0),
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
        $jasaTindakanSourcePeriods = [
            'umum' => collect(),
            'bpjs' => collect(),
        ];
        $premiBersama = 0;

        $details->each(function ($detail) use (&$jasaTindakan, &$jasaTindakanSourcePeriods, &$premiBersama) {
            $nominal = (int) $detail->nominal;

            if ($nominal <= 0) {
                return;
            }

            $serviceType = $this->stage2PremiumServiceType($detail);
            $sourcePeriodLabel = $this->stage2PremiumSourcePeriodLabel($detail);

            if ($this->isStage2PremiBersamaDetail($detail)) {
                $premiBersama += $nominal;

                return;
            }

            $jasaTindakan[$serviceType] += $nominal;

            if ($serviceType === 'bpjs' && $sourcePeriodLabel) {
                $jasaTindakanSourcePeriods[$serviceType]->push($sourcePeriodLabel);
            }
        });

        $items = collect([
            'umum' => 'UMUM',
            'bpjs' => 'BPJS',
        ])
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'nominal' => (int) $jasaTindakan[$key],
                'source_period_labels' => $jasaTindakanSourcePeriods[$key]->unique()->values()->all(),
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
                'source_period_labels' => [],
            ],
            'total_premi' => $totalJasaTindakan + $premiBersama,
        ];
    }

    private function isStage2PremiBersamaDetail($detail): bool
    {
        $sourceTable = strtolower((string) (is_array($detail) ? ($detail['source_table'] ?? '') : ($detail->source_table ?? '')));
        $sourceKey = strtolower((string) (is_array($detail) ? ($detail['source_key'] ?? '') : ($detail->source_key ?? '')));
        $sourceLabel = strtolower((string) (is_array($detail) ? ($detail['source_label'] ?? '') : ($detail->source_label ?? '')));

        return $sourceTable === 'generate_premi_bersama_distribution'
            || str_starts_with($sourceKey, 'premi_bersama')
            || str_contains($sourceLabel, 'premi bersama');
    }

    private function stage2PremiumServiceType($detail): string
    {
        $sourceKey = is_array($detail) ? ($detail['source_key'] ?? '') : ($detail->source_key ?? '');
        $sourceLabel = is_array($detail) ? ($detail['source_label'] ?? '') : ($detail->source_label ?? '');
        $sourceTable = is_array($detail) ? ($detail['source_table'] ?? '') : ($detail->source_table ?? '');
        $text = strtolower(trim(
            (string) $sourceKey.' '.
            (string) $sourceLabel.' '.
            (string) $sourceTable
        ));

        if (preg_match('/(^|[^a-z])bpjs([^a-z]|$)/', $text) || str_contains($text, 'casemix')) {
            return 'bpjs';
        }

        return 'umum';
    }

    private function stage2PremiumDetailName($detail): string
    {
        $sourceKey = strtolower(trim((string) ($detail->source_key ?? '')));
        $sourceLabel = trim((string) ($detail->source_label ?? ''));
        $sourceTable = (string) ($detail->source_table ?? '');

        if ($sourceTable === 'generate_premi_dokter_detail') {
            $premiumType = preg_replace(
                '/_(umum|bpjs|manual|all)$/',
                '',
                Str::after($sourceKey, 'premi_dokter_')
            );
            $doctorPremiumLabels = array_merge(
                ['visite' => 'Jasa Visite'],
                self::STAGE2_DOCTOR_PREMIUM_TYPES
            );

            if (isset($doctorPremiumLabels[$premiumType])) {
                return $this->stage2PremiumNameWithServiceMarker(
                    $doctorPremiumLabels[$premiumType],
                    $detail
                );
            }
        }

        if ($sourceLabel !== '') {
            return $this->stage2PremiumNameWithServiceMarker($sourceLabel, $detail);
        }

        $fallbackLabel = Str::of($sourceKey)
            ->replace('_', ' ')
            ->title()
            ->value() ?: 'Premi';

        return $this->stage2PremiumNameWithServiceMarker($fallbackLabel, $detail);
    }

    private function stage2PremiumNameWithServiceMarker(string $label, $detail): string
    {
        if ($this->stage2PremiumServiceType($detail) !== 'bpjs'
            || preg_match('/(^|[^a-z])bpjs([^a-z]|$)/i', $label)) {
            return $label;
        }

        return trim($label).' BPJS';
    }

    private function stage2PremiumSourcePeriodLabel($detail): ?string
    {
        if ($this->stage2PremiumServiceType($detail) !== 'bpjs') {
            return null;
        }

        $sourcePeriode = is_array($detail)
            ? ($detail['source_periode'] ?? null)
            : ($detail->source_periode ?? null);

        if (! $sourcePeriode) {
            return null;
        }

        return $this->formatPeriode($sourcePeriode);
    }

    public function getGajiTahap2ExportPayload(string $periode): array
    {
        $rows = $this->withoutMitraRows($this->getGajiTahap2Table($periode));
        $details = $this->withoutMitraRows(
            $this->penggajianRepository->getGajiTahap2DetailsByPeriod($periode)
        );

        return [
            'periode' => $periode,
            'summary' => $this->getGajiTahap2ExportSummary($rows, $periode),
            'rows' => $rows,
            'details' => $details,
        ];
    }

    public function getGajiTahap1ExportPayload(string $periode): array
    {
        $rows = $this->withoutMitraRows($this->getGajiTahap1Table($periode));

        return [
            'periode' => $periode,
            'summary' => $this->getGajiTahap1ExportSummary($rows, $periode),
            'rows' => $rows,
        ];
    }

    public function getGajiExcelExportPayload(string $periode, string $jenis = 'tahap2'): array
    {
        $jenis = in_array($jenis, ['tahap1', 'tahap2', 'keseluruhan'], true)
            ? $jenis
            : 'tahap2';

        $stage1Payload = in_array($jenis, ['tahap1', 'keseluruhan'], true)
            ? $this->getGajiTahap1ExportPayload($periode)
            : null;
        $stage2Payload = in_array($jenis, ['tahap2', 'keseluruhan'], true)
            ? $this->getGajiTahap2ExportPayload($periode)
            : null;

        $payload = [
            'periode' => $periode,
            'jenis' => $jenis,
            'stage1' => $stage1Payload,
            'stage2' => $stage2Payload,
        ];

        if ($jenis === 'keseluruhan') {
            $payload['combined'] = $this->getGajiKeseluruhanExportPayload(
                $periode,
                collect($stage1Payload['rows'] ?? []),
                collect($stage2Payload['rows'] ?? [])
            );
        }

        return $payload;
    }

    private function getGajiKeseluruhanExportPayload(string $periode, Collection $stage1Rows, Collection $stage2Rows): array
    {
        $stage1ByNik = $stage1Rows->keyBy(fn (array $row) => (string) ($row['nik'] ?? ''));
        $stage2ByNik = $stage2Rows->keyBy(fn (array $row) => (string) ($row['nik'] ?? ''));
        $niks = $stage1ByNik->keys()
            ->merge($stage2ByNik->keys())
            ->filter()
            ->unique()
            ->values();

        $rows = $niks
            ->map(function (string $nik) use ($periode, $stage1ByNik, $stage2ByNik) {
                $stage1 = $stage1ByNik->get($nik, []);
                $stage2 = $stage2ByNik->get($nik, []);
                $status = $stage2['status'] ?? $stage1['status'] ?? null;
                $totalTahap1 = (int) ($stage1['total'] ?? 0);
                $totalTahap2 = (int) ($stage2['total'] ?? 0);

                return [
                    'periode' => $periode,
                    'nik' => $nik,
                    'nama_pegawai' => $stage2['nama_pegawai'] ?? $stage1['nama_pegawai'] ?? '',
                    'jabatan' => $stage2['jabatan'] ?? $stage1['jabatan'] ?? '',
                    'status' => $status,
                    'status_label' => $stage2['status_label'] ?? $stage1['status_label'] ?? $this->getStatusLabel($status),
                    'tahap1_gaji_dibayarkan' => (int) ($stage1['gaji_dibayarkan'] ?? 0),
                    'tahap1_tunjangan' => (int) ($stage1['tunjangan'] ?? 0),
                    'tahap1_premi' => (int) ($stage1['premi'] ?? 0),
                    'total_tahap1' => $totalTahap1,
                    'tahap2_gaji_dibayarkan' => (int) ($stage2['gaji_dibayarkan'] ?? 0),
                    'tahap2_premi' => (int) ($stage2['total_premi'] ?? 0),
                    'tahap2_potongan' => (int) ($stage2['total_potongan'] ?? 0),
                    'total_tahap2' => $totalTahap2,
                    'total_diterima' => $totalTahap1 + $totalTahap2,
                ];
            })
            ->sortBy(fn (array $row) => strtolower((string) ($row['nama_pegawai'] ?? '')))
            ->values();

        return [
            'periode' => $periode,
            'summary' => [
                'jumlah_pegawai' => $rows->count(),
                'jumlah_tetap' => $rows->where('status', 'T')->count(),
                'jumlah_kontrak' => $rows->where('status', 'FT')->count(),
                'jumlah_lainnya' => $rows->reject(fn (array $row) => in_array($row['status'] ?? null, ['T', 'FT'], true))->count(),
                'total_tahap1' => (int) $rows->sum('total_tahap1'),
                'total_tahap2' => (int) $rows->sum('total_tahap2'),
                'total_keseluruhan' => (int) $rows->sum('total_diterima'),
                'total_premi_tahap1' => (int) $rows->sum('tahap1_premi'),
                'total_premi_tahap2' => (int) $rows->sum('tahap2_premi'),
                'total_potongan_tahap2' => (int) $rows->sum('tahap2_potongan'),
            ],
            'rows' => $rows,
        ];
    }

    private function getGajiTahap1ExportSummary(Collection $rows, ?string $periode): array
    {
        return [
            'jumlah_pegawai' => $rows->count(),
            'jumlah_tetap' => $this->countRowsByStatus($rows, 'T'),
            'jumlah_kontrak' => $this->countRowsByStatus($rows, 'FT'),
            'total_gaji' => $this->sumRows($rows, 'total'),
            'total_gapok' => $this->sumRows($rows, 'gaji_dibayarkan'),
            'total_tunjangan' => $this->sumRows($rows, 'tunjangan'),
            'total_premi' => $this->sumRows($rows, 'premi'),
            'total_pembulatan' => $this->sumRows($rows, 'pembulatan'),
            'jumlah_lainnya' => $this->countRowsOutsideStatuses($rows, ['T', 'FT']),
            'periode' => $periode,
        ];
    }

    private function getGajiTahap2ExportSummary(Collection $rows, ?string $periode): array
    {
        return [
            'jumlah_pegawai' => $rows->count(),
            'jumlah_tetap' => $this->countRowsByStatus($rows, 'T'),
            'jumlah_kontrak' => $this->countRowsByStatus($rows, 'FT'),
            'jumlah_lainnya' => $this->countRowsOutsideStatuses($rows, ['T', 'FT']),
            'total_gaji' => $this->sumRows($rows, 'total'),
            'total_gapok' => $this->sumRows($rows, 'gaji_dibayarkan'),
            'total_tunjangan' => 0,
            'total_premi' => $this->sumRows($rows, 'total_premi'),
            'total_potongan' => $this->sumRows($rows, 'total_potongan'),
            'total_pembulatan' => $this->sumRows($rows, 'pembulatan'),
            'jumlah_sumber_premi' => $this->sumRows($rows, 'jumlah_sumber_premi'),
            'periode' => $periode,
        ];
    }

    private function withoutMitraRows(Collection $rows): Collection
    {
        return $rows
            ->reject(fn ($row) => $this->isMitraPayrollRow($row))
            ->values();
    }

    private function isMitraPayrollRow($row): bool
    {
        $status = $this->rowValue($row, 'status');
        $statusLabel = strtolower(trim((string) $this->rowValue($row, 'status_label', '')));

        return $this->isMitraStatus($status) || str_contains($statusLabel, 'mitra');
    }

    private function isMitraStatus(?string $status): bool
    {
        $statusText = strtolower(trim((string) $status));

        return $this->normalizeStatus($status) === 'MT'
            || str_contains($statusText, 'mitra');
    }

    private function countRowsByStatus(Collection $rows, string $status): int
    {
        return $rows
            ->filter(fn ($row) => $this->normalizeStatus($this->rowValue($row, 'status')) === $status)
            ->count();
    }

    private function countRowsOutsideStatuses(Collection $rows, array $statuses): int
    {
        return $rows
            ->reject(fn ($row) => in_array($this->normalizeStatus($this->rowValue($row, 'status')), $statuses, true))
            ->count();
    }

    private function sumRows(Collection $rows, string $key): int
    {
        return (int) $rows->sum(fn ($row) => (int) $this->rowValue($row, $key, 0));
    }

    private function rowValue($row, string $key, $default = null)
    {
        if (is_array($row)) {
            return $row[$key] ?? $default;
        }

        if (is_object($row)) {
            return $row->{$key} ?? $default;
        }

        return $default;
    }

    public function dokterUmumTahap2Options(?string $keyword = null)
    {
        return $this->penggajianRepository
            ->dokterUmumOptions($keyword)
            ->map(fn ($row) => $this->dokterUmumPayload($row))
            ->values();
    }

    public function dokterUgdKontrakTahap1Options(?string $keyword = null)
    {
        return $this->penggajianRepository
            ->dokterUgdKontrakOptions($keyword)
            ->map(fn ($row) => $this->dokterUmumPayload($row))
            ->values();
    }

    public function getGajiTahap1DoctorConfig(): array
    {
        return [
            'premium_type_options' => $this->stage1DoctorPremiumTypeOptions(),
            'salary_component' => [
                'id' => 'include_salary',
                'label' => 'Gaji Pokok (Kehadiran)',
            ],
            'rows' => $this->penggajianRepository
                ->getGajiTahap1DoctorConfigs(false)
                ->map(fn ($row) => $this->doctorConfigPayload($row, self::STAGE1_DOCTOR_PREMIUM_TYPES))
                ->values(),
        ];
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
                ->map(fn ($row) => $this->doctorConfigPayload($row, self::STAGE2_DOCTOR_PREMIUM_TYPES))
                ->values(),
        ];
    }

    public function updateGajiTahap1DoctorConfig(array $rows): array
    {
        $normalizedRows = $this->normalizeDoctorConfigRows(
            $rows,
            self::STAGE1_DOCTOR_PREMIUM_TYPES,
            'tahap 1'
        );
        $savedRows = $this->penggajianRepository->saveGajiTahap1DoctorConfigs($normalizedRows);

        return [
            'premium_type_options' => $this->stage1DoctorPremiumTypeOptions(),
            'salary_component' => [
                'id' => 'include_salary',
                'label' => 'Gaji Pokok (Kehadiran)',
            ],
            'rows' => $savedRows
                ->map(fn ($row) => $this->doctorConfigPayload($row, self::STAGE1_DOCTOR_PREMIUM_TYPES))
                ->values(),
        ];
    }

    public function updateGajiTahap2DoctorConfig(array $rows): array
    {
        $normalizedRows = $this->normalizeDoctorConfigRows(
            $rows,
            self::STAGE2_DOCTOR_PREMIUM_TYPES,
            'tahap 2'
        );
        $savedRows = $this->penggajianRepository->saveGajiTahap2DoctorConfigs($normalizedRows);

        return [
            'premium_type_options' => $this->stage2DoctorPremiumTypeOptions(),
            'salary_component' => [
                'id' => 'include_salary',
                'label' => 'STR/Gaji Pokok',
            ],
            'rows' => $savedRows
                ->map(fn ($row) => $this->doctorConfigPayload($row, self::STAGE2_DOCTOR_PREMIUM_TYPES))
                ->values(),
        ];
    }

    public function getPayrollRoundingConfig(): array
    {
        return $this->roundingConfig();
    }

    public function updatePayrollRoundingConfig(array $data): array
    {
        $periode = filled($data['periode'] ?? null) ? (string) $data['periode'] : null;
        unset($data['periode']);

        return DB::transaction(function () use ($data, $periode) {
            $payload = $this->normalizeRoundingConfig($data);
            $saved = $this->penggajianRepository->savePayrollRoundingConfig($payload);
            $this->payrollRoundingConfig = $this->normalizeRoundingConfig($saved);

            if ($periode) {
                $this->applyPayrollRoundingToExistingPeriod($periode, $this->payrollRoundingConfig);
            }

            return $this->payrollRoundingConfig;
        });
    }

    public function applyPayrollRoundingToExistingPeriod(string $periode, ?array $config = null): array
    {
        $config ??= $this->roundingConfig();
        $stage1Updated = 0;
        $stage2Updated = 0;

        $this->penggajianRepository
            ->getGajiTahap1Table($periode)
            ->each(function ($row) use ($config, &$stage1Updated) {
                $totalBeforeRounding = $this->stage1TotalBeforeRounding($row);
                $total = $this->applyRoundingConfig($totalBeforeRounding, 'stage1_total', $config);

                $this->penggajianRepository->updateGajiTahap1Rounding(
                    (int) $row->id,
                    $total - $totalBeforeRounding,
                    $total
                );

                $stage1Updated++;
            });

        $this->penggajianRepository
            ->getGajiTahap2Table($periode)
            ->each(function ($row) use ($config, &$stage2Updated) {
                $totalBeforeRounding = $this->stage2TotalBeforeRounding($row);
                $total = $this->applyRoundingConfig($totalBeforeRounding, 'stage2_total', $config);

                $this->penggajianRepository->updateGajiTahap2Rounding(
                    (int) $row->id,
                    $total - $totalBeforeRounding,
                    $total
                );

                $stage2Updated++;
            });

        return [
            'periode' => $periode,
            'stage1_updated' => $stage1Updated,
            'stage2_updated' => $stage2Updated,
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

    private function roundingConfig(): array
    {
        if ($this->payrollRoundingConfig !== null) {
            return $this->payrollRoundingConfig;
        }

        $this->payrollRoundingConfig = $this->normalizeRoundingConfig(
            $this->penggajianRepository->getPayrollRoundingConfig()
        );

        return $this->payrollRoundingConfig;
    }

    private function normalizeRoundingConfig(array $data): array
    {
        $config = array_merge(self::ROUNDING_CONFIG_DEFAULTS, $data);

        foreach (['premium_received', 'stage1_total', 'stage2_total'] as $key) {
            $enabledKey = $key.'_enabled';
            $baseKey = $key.'_base';
            $modeKey = $key.'_mode';

            $config[$enabledKey] = filter_var($config[$enabledKey] ?? false, FILTER_VALIDATE_BOOLEAN);
            $config[$baseKey] = max(1, (int) ($config[$baseKey] ?? 1));
            $config[$modeKey] = in_array($config[$modeKey] ?? null, ['nearest', 'up', 'down'], true)
                ? $config[$modeKey]
                : self::ROUNDING_CONFIG_DEFAULTS[$modeKey];
        }

        return collect(self::ROUNDING_CONFIG_DEFAULTS)
            ->mapWithKeys(fn ($default, string $key) => [$key => $config[$key]])
            ->all();
    }

    private function applyPremiumRoundingToDetails(Collection $details, ?array $config = null): Collection
    {
        $config ??= $this->roundingConfig();

        return $details
            ->map(function (array $detail) use ($config) {
                $detail['nominal'] = $this->applyRoundingConfig(
                    $detail['nominal'] ?? 0,
                    'premium_received',
                    $config
                );

                return $detail;
            })
            ->filter(fn (array $detail) => (int) ($detail['nominal'] ?? 0) > 0)
            ->values();
    }

    private function stage1TotalBeforeRounding($row): int
    {
        $storedTotal = (int) ($row->total ?? 0);
        $storedRounding = (int) ($row->pembulatan ?? 0);

        if ($storedTotal !== 0 || $storedRounding !== 0) {
            return $storedTotal - $storedRounding;
        }

        $pendapatanTambahan = PayrollComponentLabel::isUgdContractDoctor($row->jabatan ?? null, $row->status ?? null)
            && $this->stage1DoctorIncludesStrForNik($row->nik ?? null)
                ? (int) ($row->gaji_pokok ?? 0)
                : 0;

        return $pendapatanTambahan
            + (int) ($row->gaji_dibayar ?? 0)
            + (int) ($row->tunjangan ?? 0)
            + (int) ($row->premi ?? 0);
    }

    private function stage2TotalBeforeRounding($row): int
    {
        $storedTotal = (int) ($row->total ?? 0);
        $storedRounding = (int) ($row->pembulatan ?? 0);

        if ($storedTotal !== 0 || $storedRounding !== 0) {
            return max(0, $storedTotal - $storedRounding);
        }

        return max(0, (int) ($row->gaji_dibayar ?? 0)
            + (int) ($row->total_premi ?? 0)
            - (int) ($row->total_potongan ?? 0));
    }

    private function applyRoundingConfig($amount, string $key, ?array $config = null): int
    {
        $config ??= $this->roundingConfig();
        $amount = (int) round((float) $amount);

        if (! ($config[$key.'_enabled'] ?? false)) {
            return $amount;
        }

        $base = max(1, (int) ($config[$key.'_base'] ?? 1));

        if ($base <= 1) {
            return $amount;
        }

        return match ($config[$key.'_mode'] ?? 'nearest') {
            'up' => $this->roundUpToBase($amount, $base),
            'down' => (int) (floor($amount / $base) * $base),
            default => (int) (round($amount / $base) * $base),
        };
    }

    private function roundUpToBase(int $amount, int $base): int
    {
        if ($amount === 0) {
            return 0;
        }

        $rounded = (int) (ceil(abs($amount) / $base) * $base);

        return $amount < 0 ? -$rounded : $rounded;
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

    private function stageDoctorAttendanceAmount(Collection $details): int
    {
        return (int) round((float) $details
            ->filter(fn (array $detail) => ($detail['source_table'] ?? null) === 'generate_premi_dokter_detail'
                && ($detail['source_premium_type'] ?? null) === 'kehadiran')
            ->sum('nominal'));
    }

    private function stage1UgdContractComponents(
        int $upahStr,
        int $mappedAllowance,
        Collection $doctorPremiumDetails,
        object $doctorConfig
    ): array {
        $premiumDetails = $this->filterDoctorPremiumDetails(
            $doctorPremiumDetails,
            $doctorConfig->premium_types ?? []
        );

        return [
            'gaji_pokok' => $upahStr,
            'gaji_dibayar' => $doctorConfig->include_salary
                ? $this->stageDoctorAttendanceAmount($doctorPremiumDetails)
                : 0,
            'upah_str_dibayar' => $this->stage1DoctorIncludesStr($doctorConfig) ? $upahStr : 0,
            'tunjangan' => $mappedAllowance,
            'premi' => (int) $premiumDetails->sum('nominal'),
            'premi_breakdown' => $this->stage1DoctorPremiumBreakdown($premiumDetails),
        ];
    }

    private function stage1DoctorPremiumBreakdown(Collection $details): array
    {
        return $details
            ->map(fn (array $detail) => [
                'nama' => trim(($detail['source_label'] ?? 'Premi Dokter').' - '.($detail['role_label'] ?? 'Dokter'), ' -'),
                'source_key' => $detail['source_key'] ?? null,
                'source_label' => $detail['source_label'] ?? null,
                'source_table' => $detail['source_table'] ?? null,
                'source_id' => $detail['source_id'] ?? null,
                'role_label' => $detail['role_label'] ?? null,
                'source_premium_type' => $detail['source_premium_type'] ?? null,
                'source_jumlah_data' => (int) ($detail['source_jumlah_data'] ?? $detail['jumlah_data'] ?? 0),
                'source_jumlah_pasien' => (int) ($detail['source_jumlah_pasien'] ?? $detail['jumlah_pasien'] ?? 0),
                'nominal' => (int) ($detail['nominal'] ?? 0),
            ])
            ->filter(fn (array $detail) => $detail['nominal'] > 0)
            ->values()
            ->all();
    }

    private function stage1DoctorIncludesStr($doctorConfig): bool
    {
        return collect($doctorConfig->premium_types ?? [])
            ->contains(self::STAGE1_DOCTOR_STR_TYPE);
    }

    private function stage1TunjanganDetail($gaji): Collection
    {
        if ($gaji->tunjangan_breakdown !== null) {
            return collect($gaji->tunjangan_breakdown)
                ->map(fn (array $detail) => [
                    'nama' => $detail['nama'] ?? $detail['source_label'] ?? 'Premi Dokter',
                    'nominal' => (int) ($detail['nominal'] ?? 0),
                ])
                ->values();
        }

        return $this->penggajianRepository
            ->getTunjanganPegawai($gaji->nik)
            ->map(function ($tunjangan) use ($gaji) {
                return [
                    'nama' => $tunjangan->nama_tunjangan ?? 'Tunjangan',
                    'nominal' => $gaji->status ? (int) $tunjangan->nominal : 0,
                ];
            })
            ->values();
    }

    private function unitKerjaLabelForNik(?string $nik, ?string $fallback = null): string
    {
        $unitKerja = $this->penggajianRepository->getUnitKerjaByNik((string) $nik);

        if (filled($unitKerja)) {
            return $unitKerja;
        }

        $fallback = trim((string) $fallback);

        return $fallback !== '' ? $fallback : '-';
    }

    private function stage1PremiDetail($gaji): Collection
    {
        $details = collect($gaji->premi_breakdown ?? []);
        $premiDokterSourceIds = $details
            ->filter(fn (array $detail) => (string) ($detail['source_table'] ?? '') === 'generate_premi_dokter_detail')
            ->pluck('source_id');
        $premiDokterCounts = $premiDokterSourceIds->isEmpty()
            ? collect()
            : $this->penggajianRepository->getPremiDokterDetailCountsByIds($premiDokterSourceIds->all());

        return $details
            ->map(function (array $detail) use ($premiDokterCounts) {
                $premiumType = (string) ($detail['source_premium_type'] ?? '');
                $sourceId = (int) ($detail['source_id'] ?? 0);
                $counts = $premiDokterCounts->get($sourceId, []);

                return [
                    'nama' => self::STAGE1_DOCTOR_PREMIUM_TYPES[$premiumType]
                        ?? $detail['nama']
                        ?? $detail['source_label']
                        ?? 'Premi Dokter',
                    'source_key' => $detail['source_key'] ?? null,
                    'source_label' => $detail['source_label'] ?? null,
                    'source_table' => $detail['source_table'] ?? null,
                    'source_premium_type' => $detail['source_premium_type'] ?? null,
                    'source_id' => $sourceId,
                    'source_jumlah_data' => (int) ($detail['source_jumlah_data'] ?? $detail['jumlah_data'] ?? $counts['jumlah_data'] ?? 0),
                    'source_jumlah_pasien' => (int) ($detail['source_jumlah_pasien'] ?? $detail['jumlah_pasien'] ?? $counts['jumlah_pasien'] ?? 0),
                    'nominal' => (int) ($detail['nominal'] ?? 0),
                ];
            })
            ->values();
    }

    private function withDoctorSlipPayload(array $payload): array
    {
        if (! ($payload['is_doctor_slip'] ?? false)) {
            return $payload;
        }

        $payload['doctor_slip'] = $this->doctorSlipPayload($payload);

        return $payload;
    }

    private function doctorSlipPayload(array $data): array
    {
        $stage2 = is_array($data['tahap2'] ?? null) ? $data['tahap2'] : [];
        $isUgdContract = PayrollComponentLabel::isUgdContractDoctor(
            $data['jabatan'] ?? null,
            $data['status'] ?? null
        );

        $salaryAmount = $isUgdContract
            ? 0
            : (int) ($data['gaji_dibayar'] ?? 0) + (int) ($stage2['gaji_dibayar'] ?? 0);

        $allowanceRows = [];
        $jasaRows = $this->doctorSlipRows(self::DOCTOR_SLIP_JASA_ROWS);
        $actionRows = $this->doctorSlipRows(self::DOCTOR_SLIP_ACTION_ROWS, 0);
        $deductionRows = [];
        $otherIncomeRows = [];
        $bpjsPeriods = collect();
        $bpjsPremiBersama = 0;
        $bpjsPremiBersamaJml = null;
        $bpjsPremiBersamaSourceTexts = collect();
        $stage1Rounding = (int) ($data['pembulatan'] ?? 0);
        $stage2Rounding = (int) ($stage2['pembulatan'] ?? 0);
        $totalRounding = $stage1Rounding + $stage2Rounding;
        $slipPeriod = $data['periode'] ?? null;

        if ($isUgdContract) {
            $this->doctorSlipAddRowAmount(
                $jasaRows['kehadiran'],
                (int) ($data['gaji_dibayar'] ?? 0),
                $this->doctorSlipGeneratorJml(
                    $data['periode'] ?? null,
                    $data['nik'] ?? null,
                    'kehadiran'
                )
            );

            if ($this->stage1DoctorIncludesStrForNik($data['nik'] ?? null)) {
                $this->doctorSlipAddRowAmount($jasaRows['str'], (int) ($data['gaji_pokok'] ?? 0));
            }
        }

        collect($data['tunjangan_detail'] ?? [])->each(function (array $detail, int $index) use (&$allowanceRows) {
            $nominal = (int) ($detail['nominal'] ?? 0);

            if ($nominal <= 0) {
                return;
            }

            $label = trim((string) ($detail['nama'] ?? 'Tunjangan'));
            $label = $label !== '' ? $label : 'Tunjangan';
            $key = 'tunjangan_'.(Str::slug($label, '_') ?: $index);

            if (! isset($allowanceRows[$key])) {
                $allowanceRows[$key] = $this->doctorSlipRow($key, strtoupper($label));
            }

            $allowanceRows[$key]['nominal'] += $nominal;
        });

        $premiumDetails = collect($data['premi_detail'] ?? [])
            ->map(fn (array $detail) => array_merge($detail, ['_slip_stage' => 'stage1']))
            ->merge(
                collect($stage2['premi_detail'] ?? [])
                    ->map(fn (array $detail) => array_merge($detail, ['_slip_stage' => 'stage2']))
            );

        $premiumDetails->each(function (array $detail) use (
            &$jasaRows,
            &$actionRows,
            &$otherIncomeRows,
            &$bpjsPeriods,
            &$bpjsPremiBersama,
            &$bpjsPremiBersamaJml,
            $bpjsPremiBersamaSourceTexts,
            $slipPeriod
        ) {
            $nominal = (int) ($detail['nominal'] ?? 0);
            $jml = $this->doctorSlipDetailJml($detail);

            if ($nominal <= 0) {
                return;
            }

            $isBpjs = $this->stage2PremiumServiceType($detail) === 'bpjs';
            $category = $this->doctorSlipPremiumCategory($detail);
            $bpjsSourcePeriodText = null;

            if ($category === 'kebersamaan') {
                $jml = null;
            }

            if ($isBpjs) {
                $sourcePeriodLabel = $detail['source_period_label']
                    ?? $this->stage2PremiumSourcePeriodLabel($detail);

                if ($sourcePeriodLabel) {
                    $bpjsPeriods->push($sourcePeriodLabel);
                }

                $bpjsSourcePeriodText = $this->doctorSlipBpjsSourcePeriodText(
                    $detail,
                    $slipPeriod,
                    $sourcePeriodLabel
                );

                if ($this->isStage2PremiBersamaDetail($detail)) {
                    $bpjsPremiBersama += $nominal;
                    $this->doctorSlipAddJml($bpjsPremiBersamaJml, $jml);
                    $this->doctorSlipAddSourcePeriodTextToCollection($bpjsPremiBersamaSourceTexts, $bpjsSourcePeriodText);

                    return;
                }
            }

            if (isset($jasaRows[$category])) {
                $this->doctorSlipAddRowAmount($jasaRows[$category], $nominal, $jml);

                return;
            }

            if (isset($actionRows[$category])) {
                $this->doctorSlipAddRowAmount($actionRows[$category], $nominal, $jml);
                $this->doctorSlipAddTypedActionRow(
                    $actionRows[$category],
                    $this->doctorSlipActionServiceType($detail),
                    $nominal,
                    $jml,
                    $bpjsSourcePeriodText
                );

                return;
            }

            $otherIncomeRows[] = $this->doctorSlipRow(
                'pendapatan_lain_'.count($otherIncomeRows),
                $this->doctorSlipPremiumFallbackLabel($detail),
                $nominal,
                $jml
            );
        });

        collect($stage2['potongan_detail'] ?? [])->each(function (array $detail, int $index) use (&$deductionRows) {
            $nominal = (int) ($detail['nominal'] ?? 0);

            if ($nominal <= 0) {
                return;
            }

            $label = trim((string) ($detail['nama'] ?? 'Potongan'));
            $label = $label !== '' ? $label : 'Potongan';
            $keySource = (string) ($detail['potongan_id'] ?? '');
            $key = $keySource !== ''
                ? 'potongan_'.$keySource
                : 'potongan_'.(Str::slug($label, '_') ?: $index);

            if (! isset($deductionRows[$key])) {
                $deductionRows[$key] = $this->doctorSlipRow($key, $label);
            }

            $deductionRows[$key]['nominal'] += $nominal;
        });

        $totalPotongan = (int) ($stage2['total_potongan'] ?? collect($deductionRows)->sum('nominal'));
        $deductionDifference = $totalPotongan - (int) collect($deductionRows)->sum('nominal');

        if ($deductionDifference !== 0) {
            $deductionRows['potongan_lain_lain'] = $this->doctorSlipRow(
                'potongan_lain_lain',
                'Potongan lain-lain',
                $deductionDifference
            );
        }

        if ($totalRounding < 0) {
            $totalPotongan += abs($totalRounding);
        }

        $totalPendapatan = ((int) ($data['total'] ?? 0) - $stage1Rounding)
            + (int) ($stage2['total_bruto'] ?? ((int) ($stage2['gaji_dibayar'] ?? 0) + (int) ($stage2['total_premi'] ?? 0)))
            + max(0, $totalRounding);

        $totalBersih = array_key_exists('total_slip', $data)
            ? (int) $data['total_slip']
            : max(0, $totalPendapatan - $totalPotongan);
        $bpjsPeriodText = $bpjsPeriods
            ->filter()
            ->unique()
            ->map(fn ($period) => strtoupper((string) $period))
            ->implode(', ');
        $bpjsPremiBersamaRow = $this->doctorSlipRow(
            'bpjs_premi_bersama',
            'PREMI BERSAMA',
            $bpjsPremiBersama,
            $bpjsPremiBersamaJml
        );
        $bpjsPremiBersamaSourceTexts
            ->unique()
            ->each(fn (string $text) => $this->doctorSlipAddSourcePeriodText($bpjsPremiBersamaRow, $text));

        return [
            'bulan' => strtoupper($this->formatPeriode($data['periode'] ?? null)),
            'nik' => $data['nik'] ?? '-',
            'nama' => $data['nama'] ?? '-',
            'unit_kerja' => $data['unit_kerja'] ?? ($data['jabatan'] ?? '-'),
            'status' => $data['status_label'] ?? $data['status'] ?? '-',
            'gaji_pokok' => $this->doctorSlipRow('gaji_pokok', 'GAJI POKOK', $salaryAmount),
            'tunjangan_rows' => array_values($allowanceRows),
            'jasa_rows' => array_values($jasaRows),
            'action_rows' => $this->doctorSlipActionRows($actionRows),
            'other_income_rows' => array_values($otherIncomeRows),
            'bpjs_title' => trim('BPJS '.$bpjsPeriodText),
            'bpjs_rows' => collect([
                $bpjsPremiBersamaRow,
            ])
                ->filter(fn (array $row) => (int) ($row['nominal'] ?? 0) > 0 || (int) ($row['jml'] ?? 0) > 0)
                ->values()
                ->all(),
            'deduction_rows' => array_values($deductionRows),
            'total_pendapatan' => $totalPendapatan,
            'total_potongan' => $totalPotongan,
            'total_bersih' => $totalBersih,
            'disimpan_arsy' => null,
            'total_diterima' => $totalBersih,
        ];
    }

    private function doctorSlipRows(array $labels, $defaultJml = null): array
    {
        return collect($labels)
            ->mapWithKeys(fn (string $label, string $key) => [
                $key => $this->doctorSlipRow($key, $label, 0, $defaultJml),
            ])
            ->all();
    }

    private function doctorSlipRow(string $key, string $label, int $nominal = 0, $jml = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'jml' => $jml,
            'nominal' => $nominal,
            'detail_rows' => [],
            'source_period_texts' => [],
            'source_period_text' => null,
        ];
    }

    private function doctorSlipAddRowAmount(array &$row, int $nominal, $jml = null): void
    {
        $row['nominal'] = (int) ($row['nominal'] ?? 0) + $nominal;
        $this->doctorSlipAddJml($row['jml'], $jml);
    }

    private function doctorSlipAddJml(&$currentJml, $jml): void
    {
        if ($jml === null || $jml === '') {
            return;
        }

        $currentJml = (int) ($currentJml ?? 0) + (int) $jml;
    }

    private function doctorSlipAddSourcePeriodText(array &$row, ?string $sourcePeriodText): void
    {
        $sourcePeriodText = trim((string) $sourcePeriodText);

        if ($sourcePeriodText === '') {
            return;
        }

        $row['source_period_texts'] = collect($row['source_period_texts'] ?? [])
            ->push($sourcePeriodText)
            ->unique()
            ->values()
            ->all();
        $row['source_period_text'] = implode(', ', $row['source_period_texts']);
    }

    private function doctorSlipAddSourcePeriodTextToCollection(Collection $texts, ?string $sourcePeriodText): void
    {
        $sourcePeriodText = trim((string) $sourcePeriodText);

        if ($sourcePeriodText !== '') {
            $texts->push($sourcePeriodText);
        }
    }

    private function doctorSlipDetailJml(array $detail): ?int
    {
        foreach (['jml', 'source_jumlah_data', 'jumlah_data', 'source_jumlah_pasien', 'jumlah_pasien'] as $key) {
            if (array_key_exists($key, $detail) && $detail[$key] !== null && $detail[$key] !== '') {
                return (int) $detail[$key];
            }
        }

        return null;
    }

    private function doctorSlipAddTypedActionRow(
        array &$row,
        string $serviceType,
        int $nominal,
        $jml = null,
        ?string $sourcePeriodText = null
    ): void {
        $serviceType = in_array($serviceType, ['umum', 'bpjs'], true) ? $serviceType : 'umum';

        if (! isset($row['detail_rows'][$serviceType])) {
            $row['detail_rows'][$serviceType] = $this->doctorSlipRow(
                $serviceType,
                strtoupper($serviceType),
                0,
                null
            );
        }

        $this->doctorSlipAddRowAmount($row['detail_rows'][$serviceType], $nominal, $jml);
        $this->doctorSlipAddSourcePeriodText($row['detail_rows'][$serviceType], $sourcePeriodText);
    }

    private function doctorSlipActionRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row) {
                $detailRows = collect($row['detail_rows'] ?? [])
                    ->filter(fn (array $detail) => (int) ($detail['nominal'] ?? 0) > 0 || (int) ($detail['jml'] ?? 0) > 0);
                $hasBpjsDetail = $detailRows->has('bpjs');

                $row['detail_rows'] = $hasBpjsDetail
                    ? $detailRows
                        ->sortBy(fn (array $detail) => $detail['key'] === 'umum' ? 0 : 1)
                        ->values()
                        ->all()
                    : [];

                return $row;
            })
            ->values()
            ->all();
    }

    private function doctorSlipActionServiceType(array $detail): string
    {
        return $this->stage2PremiumServiceType($detail) === 'bpjs' ? 'bpjs' : 'umum';
    }

    private function doctorSlipBpjsSourcePeriodText(
        array $detail,
        ?string $slipPeriod = null,
        ?string $sourcePeriodLabel = null
    ): ?string {
        if ($this->stage2PremiumServiceType($detail) !== 'bpjs') {
            return null;
        }

        $sourcePeriod = trim((string) ($detail['source_periode'] ?? ''));
        $sourcePeriodLabel = trim((string) ($sourcePeriodLabel ?? ($detail['source_period_label'] ?? '')));

        if ($sourcePeriodLabel === '' && $sourcePeriod !== '') {
            $sourcePeriodLabel = $this->formatPeriode($sourcePeriod);
        }

        if ($sourcePeriodLabel === '') {
            return null;
        }

        $mode = strtolower(trim((string) ($detail['source_period_mode'] ?? '')));

        if (! in_array($mode, ['current', 'previous'], true)) {
            $mode = $this->doctorSlipInferBpjsSourcePeriodMode($slipPeriod, $sourcePeriod);
        }

        $modeLabel = match ($mode) {
            'current' => 'Periode berjalan',
            'previous' => 'Periode sebelumnya',
            default => 'Periode sumber',
        };

        return $modeLabel.' - '.$sourcePeriodLabel;
    }

    private function doctorSlipInferBpjsSourcePeriodMode(?string $slipPeriod, ?string $sourcePeriod): ?string
    {
        $slipPeriod = trim((string) $slipPeriod);
        $sourcePeriod = trim((string) $sourcePeriod);

        if ($slipPeriod === '' || $sourcePeriod === '') {
            return null;
        }

        if ($sourcePeriod === $slipPeriod) {
            return 'current';
        }

        if ($sourcePeriod < $slipPeriod) {
            return 'previous';
        }

        return null;
    }

    private function doctorSlipGeneratorJml(?string $periode, ?string $nik, string $premiumType): ?int
    {
        $periode = trim((string) $periode);
        $nik = trim((string) $nik);
        $premiumType = strtolower(trim($premiumType));

        if ($periode === '' || $nik === '' || $premiumType === '') {
            return null;
        }

        $counts = $this->penggajianRepository
            ->getPremiDokterDetailCountsByPeriodNik($periode, $nik, [$premiumType])
            ->get($premiumType);

        if (! $counts) {
            return null;
        }

        return (int) ($counts['jumlah_data'] ?? 0);
    }

    private function doctorSlipPremiumCategory(array $detail): string
    {
        $sourceKey = strtolower((string) ($detail['source_key'] ?? ''));
        $premiumType = strtolower((string) ($detail['source_premium_type'] ?? ''));

        if ($premiumType === '' && str_starts_with($sourceKey, 'premi_dokter_')) {
            $premiumType = preg_replace(
                '/_(umum|bpjs|manual|all)$/',
                '',
                Str::after($sourceKey, 'premi_dokter_')
            );
        }

        $text = $this->doctorSlipSearchText(
            $premiumType,
            $sourceKey,
            $detail['source_label'] ?? '',
            $detail['nama'] ?? '',
            $detail['source_table'] ?? ''
        );

        if (in_array($premiumType, ['upah_str', 'str'], true) || preg_match('/(^| )str($| )/', $text)) {
            return 'str';
        }

        if ($premiumType === 'kehadiran' || str_contains($text, 'kehadiran')) {
            return 'kehadiran';
        }

        if ($premiumType === 'kebersamaan' || str_contains($text, 'kebersamaan')) {
            return 'kebersamaan';
        }

        if ($premiumType === 'jasa_operasi' || str_contains($text, 'operasi') || preg_match('/(^| )ok($| )/', $text)) {
            return 'ok';
        }

        if ($premiumType === 'jasa_rawat_jalan' || str_contains($text, 'rawat jalan')) {
            return 'rawat_jalan';
        }

        if ($premiumType === 'visite' || str_contains($text, 'visite')) {
            return 'visite';
        }

        if ($premiumType === 'jasa_poli' || str_contains($text, 'poli')) {
            return 'poli';
        }

        if ($premiumType === 'jasa_igd' || str_contains($text, 'igd')) {
            return 'igd';
        }

        if ($premiumType === 'jasa_ecg' || str_contains($text, 'ecg')) {
            return 'ecg';
        }

        if (str_contains($text, 'radiologi')) {
            return 'radiologi';
        }

        if (str_contains($text, 'laboratorium') || str_contains($text, 'laborat')) {
            return 'laborat';
        }

        return 'lain_lain';
    }

    private function doctorSlipPremiumFallbackLabel(array $detail): string
    {
        $label = $detail['nama'] ?? $detail['source_label'] ?? null;

        if (filled($label)) {
            return strtoupper((string) $label);
        }

        return 'JASA LAIN-LAIN';
    }

    private function doctorSlipSearchText(...$values): string
    {
        $text = strtolower(implode(' ', array_map(fn ($value) => (string) $value, $values)));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function stage1DoctorIncludesStrForNik(?string $nik): bool
    {
        $nik = trim((string) $nik);

        if ($nik === '') {
            return false;
        }

        $config = $this->penggajianRepository
            ->getGajiTahap1DoctorConfigs()
            ->firstWhere('kd_dokter', $nik);

        return $config ? $this->stage1DoctorIncludesStr($config) : false;
    }

    private function excludeStage1DoctorPremiumDetails(Collection $details, $stage1Config): Collection
    {
        if (! $stage1Config) {
            return $details->values();
        }

        $excludedTypes = collect($stage1Config->premium_types ?? []);

        if ($stage1Config->include_salary) {
            $excludedTypes->push('kehadiran');
        }

        $excludedTypes = $excludedTypes
            ->filter()
            ->map(fn ($type) => (string) $type)
            ->unique()
            ->values();

        if ($excludedTypes->isEmpty()) {
            return $details->values();
        }

        return $details
            ->reject(fn (array $detail) => ($detail['source_table'] ?? null) === 'generate_premi_dokter_detail'
                && $excludedTypes->contains((string) ($detail['source_premium_type'] ?? '')))
            ->values();
    }

    private function filterStage2DoctorPremiumDetails($details, array $premiumTypes): Collection
    {
        return $this->filterDoctorPremiumDetails($details, $premiumTypes);
    }

    private function filterDoctorPremiumDetails($details, array $premiumTypes): Collection
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

    private function normalizeDoctorConfigRows(
        array $rows,
        array $allowedPremiumTypeLabels,
        string $stageLabel
    ): array {
        $allowedPremiumTypes = array_keys($allowedPremiumTypeLabels);

        return collect($rows)
            ->map(function (array $row) use ($allowedPremiumTypes, $stageLabel) {
                $premiumTypes = collect($row['premium_types'] ?? [])
                    ->map(fn ($type) => (string) $type)
                    ->filter(fn ($type) => in_array($type, $allowedPremiumTypes, true))
                    ->unique()
                    ->values()
                    ->all();
                $includeSalary = filter_var($row['include_salary'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $includeSalary && empty($premiumTypes)) {
                    throw ValidationException::withMessages([
                        'rows' => ['Setiap dokter '.$stageLabel.' wajib memiliki minimal satu komponen.'],
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

    private function doctorConfigPayload(object $row, array $allowedPremiumTypeLabels): array
    {
        $premiumTypes = collect($row->premium_types ?? [])
            ->map(fn ($type) => (string) $type)
            ->filter(fn ($type) => isset($allowedPremiumTypeLabels[$type]))
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

    private function stage1DoctorPremiumTypeOptions(): array
    {
        return $this->doctorPremiumTypeOptions(self::STAGE1_DOCTOR_PREMIUM_TYPES);
    }

    private function stage2DoctorPremiumTypeOptions(): array
    {
        return $this->doctorPremiumTypeOptions(self::STAGE2_DOCTOR_PREMIUM_TYPES);
    }

    private function doctorPremiumTypeOptions(array $premiumTypes): array
    {
        return collect($premiumTypes)
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

    private function slipPdfFitPaperSize(array $detail, string $mode = 'export'): array
    {
        $mode = strtolower($mode);

        return ($detail['is_doctor_slip'] ?? false)
            ? $this->doctorSlipPdfPaperSize($detail, $mode)
            : $this->employeeSlipPdfPaperSize($detail, $mode);
    }

    private function employeeSlipPdfPaperSize(array $detail, string $mode): array
    {
        $singleSlip = $mode === 'single';
        $tunjanganRows = collect($detail['tunjangan_detail'] ?? []);
        $stage2 = is_array($detail['tahap2'] ?? null) ? $detail['tahap2'] : [];
        $stage2PremiRows = collect($stage2['premi_detail'] ?? []);
        $stage2PotonganRows = collect($stage2['potongan_detail'] ?? []);
        $stage2SlipPendapatan = $stage2['slip_pendapatan_umum'] ?? [];
        $stage2JasaTindakan = $stage2SlipPendapatan['jasa_tindakan'] ?? [];
        $stage2JasaItems = collect($stage2JasaTindakan['items'] ?? []);
        $stage2PremiBersama = $stage2SlipPendapatan['premi_bersama'] ?? [];
        $stage2HasGroupedPremi = (int) ($stage2JasaTindakan['total'] ?? $stage2JasaItems->sum('nominal')) > 0
            || (int) ($stage2PremiBersama['nominal'] ?? 0) > 0;
        $stage2GajiDibayar = (int) ($stage2['gaji_dibayar'] ?? 0);

        $stage2IncomeRows = $stage2GajiDibayar > 0 ? 1 : 0;
        $stage2IncomeRows += $stage2HasGroupedPremi
            ? 2 + $stage2JasaItems->count()
            : $stage2PremiRows->count();

        if (empty($stage2) || ($stage2GajiDibayar <= 0 && ! $stage2HasGroupedPremi && $stage2PremiRows->isEmpty())) {
            $stage2IncomeRows++;
        }

        $sourceRows = $stage2JasaItems
            ->filter(fn (array $item) => count(array_filter((array) ($item['source_period_labels'] ?? []))) > 0)
            ->count();

        if ($mode !== 'whatsapp' && count(array_filter((array) ($stage2PremiBersama['source_period_labels'] ?? []))) > 0) {
            $sourceRows++;
        }

        $sourceRows += $stage2PremiRows
            ->filter(fn (array $item) => trim((string) ($item['source_period_label'] ?? '')) !== '')
            ->count();

        $stage1Rows = 4 + $tunjanganRows->count();
        $stage2Rows = 7 + $stage2IncomeRows + max(1, $stage2PotonganRows->count());
        $heightMm = ($singleSlip ? 72 : 80)
            + (($stage1Rows + $stage2Rows) * ($singleSlip ? 4.1 : 4.8))
            + ($sourceRows * ($singleSlip ? 4.0 : 4.8));

        return [
            'width_mm' => match ($mode) {
                'whatsapp' => 108,
                'single' => 148,
                default => 210,
            },
            'height_mm' => (int) ceil(max(148, $heightMm)),
        ];
    }

    private function doctorSlipPdfPaperSize(array $detail, string $mode): array
    {
        $slip = $detail['doctor_slip'] ?? [];
        $actionRows = collect($slip['action_rows'] ?? []);
        $actionDetailRows = $actionRows->sum(fn (array $row) => count($row['detail_rows'] ?? []));
        $bpjsRows = collect($slip['bpjs_rows'] ?? []);

        $sourceRows = $actionRows
            ->flatMap(fn (array $row) => $row['detail_rows'] ?? [])
            ->filter(fn (array $row) => trim((string) ($row['source_period_text'] ?? '')) !== '')
            ->count();
        $sourceRows += $bpjsRows
            ->filter(fn (array $row) => trim((string) ($row['source_period_text'] ?? '')) !== '')
            ->count();

        $tableRows = 2
            + count($slip['tunjangan_rows'] ?? [])
            + 1 + count($slip['jasa_rows'] ?? [])
            + 1 + $actionRows->count() + $actionDetailRows
            + count($slip['other_income_rows'] ?? [])
            + ($bpjsRows->isNotEmpty() ? 1 + $bpjsRows->count() : 0)
            + count($slip['deduction_rows'] ?? [])
            + 6;
        $wrappedRows = $this->doctorSlipEstimatedWrappedRows($slip);
        $isWhatsapp = $mode === 'whatsapp';
        $heightMm = ($isWhatsapp ? 96 : 84)
            + (($tableRows + $wrappedRows) * ($isWhatsapp ? 6.4 : 5.4))
            + ($sourceRows * ($isWhatsapp ? 5.6 : 4.8));

        return [
            'width_mm' => $isWhatsapp ? 184 : 148,
            'height_mm' => (int) ceil(max($isWhatsapp ? 300 : 240, $heightMm)),
        ];
    }

    private function doctorSlipEstimatedWrappedRows(array $slip): int
    {
        $rows = collect()
            ->merge($slip['tunjangan_rows'] ?? [])
            ->merge($slip['jasa_rows'] ?? [])
            ->merge($slip['other_income_rows'] ?? [])
            ->merge($slip['bpjs_rows'] ?? [])
            ->merge($slip['deduction_rows'] ?? []);

        collect($slip['action_rows'] ?? [])->each(function (array $row) use ($rows) {
            $rows->push($row);

            foreach (($row['detail_rows'] ?? []) as $detailRow) {
                $detailRow['label'] = trim(($row['label'] ?? '').' '.($detailRow['label'] ?? ''));
                $rows->push($detailRow);
            }
        });

        return (int) $rows->sum(function (array $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $sourcePeriodText = trim((string) ($row['source_period_text'] ?? ''));
            $labelRows = max(1, (int) ceil(Str::length($label) / 30));
            $sourceRows = $sourcePeriodText === ''
                ? 0
                : max(1, (int) ceil(Str::length($sourcePeriodText) / 42));

            return max(0, $labelRows - 1) + max(0, $sourceRows - 1);
        });
    }

    private function dompdfPaperFromMillimeters(float $widthMm, float $heightMm): array
    {
        return [
            0,
            0,
            $this->millimetersToPoints($widthMm),
            $this->millimetersToPoints($heightMm),
        ];
    }

    private function millimetersToPoints(float $millimeters): float
    {
        return $millimeters * 72 / 25.4;
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

    private function getSlipEmailMailer(): ?string
    {
        $mailer = trim((string) config('services.email_gateway.mailer', ''));

        return $mailer !== '' ? $mailer : null;
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

    private function normalizeEmailAddress(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
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
