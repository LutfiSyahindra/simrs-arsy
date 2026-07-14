<?php

namespace Tests\Unit;

use App\Services\keuangan\penggajian\penggajianService;
use App\Support\PayrollComponentLabel;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class PenggajianServiceTest extends TestCase
{
    public function test_stage2_potongan_prioritizes_one_percent_total_salary_before_other_deductions(): void
    {
        $result = $this->calculateStage2Potongan(collect([
            (object) [
                'potongan_id' => 2,
                'kode' => 'POT002',
                'nama' => 'Potongan Manual',
                'tipe' => 'manual',
                'nilai' => 0,
                'nominal_mapping' => 20000,
            ],
            (object) [
                'potongan_id' => 1,
                'kode' => 'POT001',
                'nama' => 'Dana Sehat',
                'tipe' => 'persen_total_gaji',
                'nilai' => 1,
                'nominal_mapping' => 0,
            ],
            (object) [
                'potongan_id' => 3,
                'kode' => 'POT003',
                'nama' => 'Potongan Gaji Pokok',
                'tipe' => 'persen_gapok',
                'nilai' => 5,
                'nominal_mapping' => 0,
            ],
        ]));

        $this->assertSame(['POT001', 'POT002', 'POT003'], $result->pluck('kode')->all());
        $this->assertSame(25000, $result->firstWhere('kode', 'POT001')['nominal']);
        $this->assertSame(2000000, $result->firstWhere('kode', 'POT001')['total_tahap1']);
        $this->assertSame(500000, $result->firstWhere('kode', 'POT001')['total_tahap2']);
        $this->assertSame(2500000, $result->firstWhere('kode', 'POT001')['basis']);
        $this->assertSame('1% x gaji tahap 1 + 2', $result->firstWhere('kode', 'POT001')['keterangan']);
    }

    public function test_doctor_employee_lookup_uses_khanza_doctor_code_not_position_text(): void
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'isDoctorEmployee');
        $method->setAccessible(true);
        $doctorNikLookup = collect([
            'nik:DR001' => true,
        ]);

        $this->assertTrue($method->invoke($service, 'DR001', $doctorNikLookup));
        $this->assertFalse($method->invoke($service, 'PG001', $doctorNikLookup));
        $this->assertFalse($method->invoke($service, 'Dokter Umum', $doctorNikLookup));
    }

    public function test_doctor_whatsapp_slip_uses_export_pdf_layout(): void
    {
        $service = new penggajianService;
        $doctorDetail = ['is_doctor_slip' => true];
        $employeeDetail = ['is_doctor_slip' => false];

        $this->assertSame(
            'simrs.backOffice.keuangan.penggajian.slipGajiDokter',
            $service->slipPdfView($doctorDetail)
        );
        $this->assertSame('export', $service->slipWhatsappPdfMode($doctorDetail));
        $this->assertSame(
            'simrs.backOffice.keuangan.penggajian.slipGaji',
            $service->slipPdfView($employeeDetail)
        );
        $this->assertSame('whatsapp', $service->slipWhatsappPdfMode($employeeDetail));
    }

    public function test_stage1_ugd_contract_separates_str_attendance_and_actual_allowance(): void
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'stage1UgdContractComponents');
        $method->setAccessible(true);
        $config = (object) [
            'include_salary' => true,
            'premium_types' => ['upah_str', 'jasa_igd'],
        ];
        $premiumDetails = collect([
            [
                'source_table' => 'generate_premi_dokter_detail',
                'source_premium_type' => 'kehadiran',
                'nominal' => 750000,
            ],
            [
                'source_table' => 'generate_premi_dokter_detail',
                'source_premium_type' => 'jasa_igd',
                'nominal' => 900000,
            ],
        ]);

        $result = $method->invoke($service, 1500000, 125000, $premiumDetails, $config);

        $this->assertSame(1500000, $result['gaji_pokok']);
        $this->assertSame(1500000, $result['upah_str_dibayar']);
        $this->assertSame(750000, $result['gaji_dibayar']);
        $this->assertSame(125000, $result['tunjangan']);
        $this->assertSame(900000, $result['premi']);
        $this->assertSame('Premi Dokter - Dokter', $result['premi_breakdown'][0]['nama']);
        $this->assertSame('jasa_igd', $result['premi_breakdown'][0]['source_premium_type']);
        $this->assertSame(3275000, array_sum([
            $result['upah_str_dibayar'],
            $result['gaji_dibayar'],
            $result['tunjangan'],
            $result['premi'],
        ]));

        $detailMethod = new ReflectionMethod($service, 'stage1PremiDetail');
        $detailMethod->setAccessible(true);
        $detail = $detailMethod->invoke($service, (object) [
            'premi_breakdown' => [[
                'source_premium_type' => 'kebersamaan',
                'nama' => 'Label sumber yang panjang',
                'nominal' => 850000,
            ]],
        ]);

        $this->assertSame('Kebersamaan', $detail->first()['nama']);
        $this->assertSame(850000, $detail->first()['nominal']);
    }

    public function test_stage1_ugd_contract_uses_distinct_component_labels(): void
    {
        $position = 'Dokter Unit Gawat Darurat';
        $status = 'KONTRAK';

        $this->assertSame('Upah STR', PayrollComponentLabel::salaryLabel($position, $status));
        $this->assertSame('Kehadiran', PayrollComponentLabel::paidSalaryLabel($position, $status));
    }

    public function test_stage2_premium_detail_uses_a_short_non_repeated_name(): void
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'stage2PremiumDetailName');
        $method->setAccessible(true);

        $this->assertSame('Kebersamaan', $method->invoke($service, (object) [
            'source_key' => 'premi_dokter_kebersamaan_all',
            'source_label' => 'Premi Dokter ALL - kebersamaan - kebersamaan',
            'source_table' => 'generate_premi_dokter_detail',
            'role_label' => 'Dokter',
        ]));
        $this->assertSame('Jasa Rawat Jalan', $method->invoke($service, (object) [
            'source_key' => 'premi_dokter_jasa_rawat_jalan_umum',
            'source_label' => 'Premi Dokter UMUM - jasa rawat jalan - jasa_rawat_jalan',
            'source_table' => 'generate_premi_dokter_detail',
            'role_label' => 'Dokter',
        ]));
        $this->assertSame('Jasa Visite BPJS', $method->invoke($service, (object) [
            'source_key' => 'premi_dokter_visite_bpjs',
            'source_label' => 'Premi Dokter BPJS - visite - umum',
            'source_table' => 'generate_premi_dokter_detail',
            'role_label' => 'Dokter',
        ]));
        $this->assertSame('Apotek BPJS - Penerima 7%', $method->invoke($service, (object) [
            'source_key' => 'apotek_bpjs',
            'source_label' => 'Apotek BPJS - Penerima 7%',
            'source_table' => 'generate_apotek_recipient',
            'role_label' => 'Penerima 7%',
        ]));
    }

    public function test_doctor_slip_payload_groups_income_and_deductions(): void
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'doctorSlipPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'periode' => '2026-01',
            'nik' => 'DR001',
            'nama' => 'dr. Contoh',
            'jabatan' => 'Dokter Umum',
            'status' => 'FT',
            'unit_kerja' => 'DOKTER',
            'gaji_dibayar' => 1250000,
            'pembulatan' => 5000,
            'tunjangan_detail' => [
                ['nama' => 'Tunjangan Jabatan', 'nominal' => 100000],
                ['nama' => 'T. Masa Kerja', 'nominal' => 200000],
                ['nama' => 'T. Fungsional', 'nominal' => 75000],
            ],
            'premi_detail' => [
                [
                    'source_key' => 'premi_dokter_jasa_igd_umum',
                    'source_label' => 'Premi Dokter UMUM - jasa igd',
                    'source_table' => 'generate_premi_dokter_detail',
                    'source_premium_type' => 'jasa_igd',
                    'source_jumlah_data' => 13,
                    'source_jumlah_pasien' => 13,
                    'nominal' => 390000,
                ],
                [
                    'source_key' => 'premi_dokter_kebersamaan_all',
                    'source_label' => 'Premi Dokter ALL - kebersamaan',
                    'source_table' => 'generate_premi_dokter_detail',
                    'source_premium_type' => 'kebersamaan',
                    'source_jumlah_data' => 9,
                    'source_jumlah_pasien' => 9,
                    'nominal' => 60000,
                ],
            ],
            'total' => 2080000,
            'total_slip' => 2149000,
            'tahap2' => [
                'gaji_dibayar' => 0,
                'total_bruto' => 120000,
                'total_potongan' => 52000,
                'pembulatan' => 1000,
                'total' => 69000,
                'premi_detail' => [
                    [
                        'source_key' => 'premi_dokter_visite_umum',
                        'source_label' => 'Premi Dokter UMUM - visite',
                        'source_table' => 'generate_premi_dokter_detail',
                        'source_jumlah_data' => 2,
                        'source_jumlah_pasien' => 2,
                        'nominal' => 40000,
                    ],
                    [
                        'source_key' => 'premi_dokter_jasa_rawat_jalan_umum',
                        'source_label' => 'Premi Dokter UMUM - jasa rawat jalan',
                        'source_table' => 'generate_premi_dokter_detail',
                        'source_period_label' => 'Januari 2026',
                        'source_jumlah_data' => 3,
                        'source_jumlah_pasien' => 3,
                        'nominal' => 30000,
                    ],
                    [
                        'source_key' => 'premi_dokter_jasa_rawat_jalan_bpjs',
                        'source_label' => 'Premi Dokter BPJS - jasa rawat jalan',
                        'source_table' => 'generate_premi_dokter_detail',
                        'source_periode' => '2025-12',
                        'source_period_mode' => 'previous',
                        'source_period_label' => 'Desember 2025',
                        'source_jumlah_data' => 7,
                        'source_jumlah_pasien' => 7,
                        'nominal' => 50000,
                    ],
                ],
                'potongan_detail' => [
                    ['nama' => 'Potongan Dana Sehat', 'nominal' => 2000],
                    ['nama' => 'Pot BPJS', 'nominal' => 50000],
                ],
            ],
        ]);

        $this->assertSame(1250000, $result['gaji_pokok']['nominal']);
        $tunjanganRows = collect($result['tunjangan_rows']);

        $this->assertSame([
            'jabatan',
            'profesi',
            'suami_istri',
            'anak',
            'khusus',
            'masa_kerja',
            'fungsional',
        ], $tunjanganRows->pluck('key')->all());
        $this->assertSame(100000, $tunjanganRows->firstWhere('key', 'jabatan')['nominal']);
        $this->assertSame(200000, $tunjanganRows->firstWhere('key', 'masa_kerja')['nominal']);
        $this->assertSame(75000, $tunjanganRows->firstWhere('key', 'fungsional')['nominal']);
        $otherIncomeRows = collect($result['other_income_rows']);
        $this->assertNull($otherIncomeRows->firstWhere('key', 'masa_kerja'));
        $this->assertNull($otherIncomeRows->firstWhere('key', 'fungsional'));
        $this->assertNull($otherIncomeRows->firstWhere('key', 'pendapatan_lain_adjustment'));
        $this->assertSame(6000, $otherIncomeRows->firstWhere('key', 'pembulatan')['nominal']);
        $this->assertSame(60000, collect($result['jasa_rows'])->firstWhere('key', 'kebersamaan')['nominal']);
        $this->assertNull(collect($result['jasa_rows'])->firstWhere('key', 'kebersamaan')['jml']);
        $this->assertSame(390000, collect($result['action_rows'])->firstWhere('key', 'igd')['nominal']);
        $this->assertSame(13, collect($result['action_rows'])->firstWhere('key', 'igd')['jml']);
        $this->assertSame(40000, collect($result['action_rows'])->firstWhere('key', 'visite')['nominal']);
        $this->assertSame(2, collect($result['action_rows'])->firstWhere('key', 'visite')['jml']);
        $rawatJalan = collect($result['action_rows'])->firstWhere('key', 'rawat_jalan');
        $this->assertSame(80000, $rawatJalan['nominal']);
        $this->assertSame(10, $rawatJalan['jml']);
        $this->assertSame(30000, collect($rawatJalan['detail_rows'])->firstWhere('key', 'umum')['nominal']);
        $this->assertSame(3, collect($rawatJalan['detail_rows'])->firstWhere('key', 'umum')['jml']);
        $this->assertSame(50000, collect($rawatJalan['detail_rows'])->firstWhere('key', 'bpjs')['nominal']);
        $this->assertSame(7, collect($rawatJalan['detail_rows'])->firstWhere('key', 'bpjs')['jml']);
        $this->assertSame(
            'Periode sebelumnya - Desember 2025',
            collect($rawatJalan['detail_rows'])->firstWhere('key', 'bpjs')['source_period_text']
        );
        $this->assertEmpty($result['bpjs_rows']);
        $this->assertSame(2000, collect($result['deduction_rows'])->firstWhere('key', 'dana_sehat')['nominal']);
        $this->assertSame(50000, collect($result['deduction_rows'])->firstWhere('key', 'bpjs')['nominal']);
        $this->assertSame(2201000, $result['total_pendapatan']);
        $this->assertSame(52000, $result['total_potongan']);
        $this->assertSame(2149000, $result['total_bersih']);
        $this->assertSame(2149000, $result['total_diterima']);
    }

    public function test_slip_unit_kerja_uses_mapping_with_position_fallback(): void
    {
        $service = new penggajianService;
        $repository = new class
        {
            public function getUnitKerjaByNik(string $nik): ?string
            {
                return $nik === 'PG001' ? 'Farmasi' : null;
            }
        };
        $property = new ReflectionProperty($service, 'penggajianRepository');
        $property->setAccessible(true);
        $property->setValue($service, $repository);

        $method = new ReflectionMethod($service, 'unitKerjaLabelForNik');
        $method->setAccessible(true);

        $this->assertSame('Farmasi', $method->invoke($service, 'PG001', 'Apoteker'));
        $this->assertSame('Perawat', $method->invoke($service, 'PG002', 'Perawat'));
        $this->assertSame('-', $method->invoke($service, 'PG003', null));
    }

    public function test_doctor_slip_payload_hides_lain_lain_adjustment_rows(): void
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'doctorSlipPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'periode' => '2026-06',
            'nik' => 'DR001',
            'nama' => 'dr. Contoh',
            'jabatan' => 'Dokter Umum',
            'status' => 'T',
            'gaji_dibayar' => 100000,
            'total' => 125000,
            'total_slip' => 125000,
        ]);

        $this->assertSame(125000, $result['total_pendapatan']);
        $this->assertSame([], collect($result['other_income_rows'])->pluck('label')->all());
    }

    public function test_doctor_slip_payload_marks_current_bpjs_source_period(): void
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'doctorSlipPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'periode' => '2026-06',
            'nik' => 'DR001',
            'nama' => 'dr. Contoh',
            'jabatan' => 'Dokter Umum',
            'status' => 'T',
            'gaji_dibayar' => 0,
            'total' => 0,
            'total_slip' => 30000,
            'tahap2' => [
                'total_bruto' => 30000,
                'total_potongan' => 0,
                'total' => 30000,
                'premi_detail' => [
                    [
                        'source_key' => 'premi_dokter_jasa_rawat_jalan_bpjs',
                        'source_label' => 'Premi Dokter BPJS - jasa rawat jalan',
                        'source_table' => 'generate_premi_dokter_detail',
                        'source_periode' => '2026-06',
                        'source_period_mode' => 'current',
                        'source_period_label' => 'Juni 2026',
                        'source_jumlah_data' => 3,
                        'source_jumlah_pasien' => 3,
                        'nominal' => 30000,
                    ],
                ],
            ],
        ]);

        $bpjsRow = collect($result['action_rows'])
            ->firstWhere('key', 'rawat_jalan')['detail_rows'][0];

        $this->assertSame('bpjs', $bpjsRow['key']);
        $this->assertSame('Periode berjalan - Juni 2026', $bpjsRow['source_period_text']);
    }

    public function test_payroll_total_rounding_uses_excel_roundup_negative_three(): void
    {
        $service = new penggajianService;
        $normalize = new ReflectionMethod($service, 'normalizeRoundingConfig');
        $normalize->setAccessible(true);
        $round = new ReflectionMethod($service, 'applyRoundingConfig');
        $round->setAccessible(true);

        $config = $normalize->invoke($service, [
            'stage1_total_enabled' => false,
            'stage1_total_base' => 1,
            'stage1_total_mode' => 'down',
            'stage2_total_enabled' => false,
            'stage2_total_base' => 1,
            'stage2_total_mode' => 'nearest',
        ]);

        $this->assertTrue($config['stage1_total_enabled']);
        $this->assertSame(1000, $config['stage1_total_base']);
        $this->assertSame('up', $config['stage1_total_mode']);
        $this->assertTrue($config['stage2_total_enabled']);
        $this->assertSame(1000, $config['stage2_total_base']);
        $this->assertSame('up', $config['stage2_total_mode']);

        $this->assertSame(69000, $round->invoke($service, 68001, 'stage1_total', $config));
        $this->assertSame(69000, $round->invoke($service, 69000, 'stage1_total', $config));
        $this->assertSame(-69000, $round->invoke($service, -68001, 'stage1_total', $config));
    }

    private function calculateStage2Potongan(Collection $potonganList): Collection
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'calculateStage2Potongan');
        $method->setAccessible(true);

        return $method->invoke($service, $potonganList, 1000000, 2000000, 500000);
    }
}
