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

        $dataGajiTahap1Table = [];
        foreach ($gajiTahap1Table as $gajiTahap1) {
            $gajiDibayarkan = (int) $gajiTahap1->gaji_dibayar;
            $tunjangan = (int) $gajiTahap1->tunjangan;
            $komponenGajiLabel = PayrollComponentLabel::salaryLabel($gajiTahap1->jabatan, $gajiTahap1->status);

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

            $generatedNiks = [];
            $jumlahPegawai = 0;
            $jumlahTetap = 0;
            $jumlahKontrak = 0;
            $jumlahLainnya = 0;
            $totalGaji = 0;
            $totalGapok = 0;
            $totalPremi = 0;

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
                $total = $gajiDibayar + $pegawaiTotalPremi;
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
                    'total' => $total,
                    'jumlah_sumber_premi' => $premiDetails->count(),
                    'premi_breakdown' => $breakdown,
                ]);

                $this->penggajianRepository->replaceGajiTahap2Details($gaji, $premiDetails);

                $generatedNiks[] = (string) $pegawai->nik;
                $jumlahPegawai++;
                $totalGapok += $gajiDibayar;
                $totalPremi += $pegawaiTotalPremi;
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
                'periode' => $periode,
            ];
        });
    }

    public function getGajiTahap2Table($periode)
    {
        return $this->penggajianRepository
            ->getGajiTahap2Table($periode)
            ->map(function ($row) {
                $salaryLabel = PayrollComponentLabel::salaryLabel($row->jabatan, $row->status);

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
                    'jumlah_sumber_premi' => (int) $row->jumlah_sumber_premi,
                    'total' => (int) $row->total,
                    'premi_breakdown' => $row->premi_breakdown ?? [],
                    'periode' => $row->periode,
                ];
            })
            ->values();
    }

    public function getPenerimaSlipWhatsappTahap1(string $periode)
    {
        return $this->penggajianRepository
            ->getPenerimaSlipWhatsappTahap1($periode)
            ->map(function ($row) {
                $gajiDibayar = (int) $row->gaji_dibayar;
                $tunjangan = (int) $row->tunjangan;
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
                    'total' => $gajiDibayar + $tunjangan,
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

        if (! $row) {
            throw new RuntimeException('Data slip atau nomor Whatsapp pegawai tidak ditemukan.');
        }

        $number = $this->normalizeWhatsappNumber($row->no_telp);

        if (! $number) {
            throw new RuntimeException('Nomor Whatsapp pegawai tidak valid.');
        }

        $detail = $this->detailGajiTahap1($row->id);
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
            'total' => $gajiDibayar + $tunjangan,
        ];
    }

    public function detailGajiTahap2($id)
    {
        $gaji = $this->penggajianRepository->findGajiTahap2ById($id);

        if (! $gaji) {
            abort(404, 'Data gaji tahap 2 tidak ditemukan');
        }

        $salaryLabel = PayrollComponentLabel::salaryLabel($gaji->jabatan, $gaji->status);

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
            'premi_detail' => $gaji->details
                ->map(fn ($detail) => [
                    'nama' => trim($detail->source_label.' - '.($detail->role_label ?: ''), ' -'),
                    'nominal' => (int) $detail->nominal,
                ])
                ->values(),
            'total' => (int) $gaji->total,
        ];
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
