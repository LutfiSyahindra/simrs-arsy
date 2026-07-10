<?php

namespace Tests\Unit;

use App\Services\keuangan\penggajian\penggajianService;
use Illuminate\Support\Collection;
use ReflectionMethod;
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

    private function calculateStage2Potongan(Collection $potonganList): Collection
    {
        $service = new penggajianService;
        $method = new ReflectionMethod($service, 'calculateStage2Potongan');
        $method->setAccessible(true);

        return $method->invoke($service, $potonganList, 1000000, 2000000, 500000);
    }
}
