<?php

namespace Tests\Unit;

use App\Repositories\keuangan\premi\generatePelayananNonMedisRepository;
use App\Services\keuangan\premi\generatePelayananNonMedisService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GeneratePelayananNonMedisServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_summary_is_ready_when_bhp_and_kamar_are_locked(): void
    {
        $repository = Mockery::mock(generatePelayananNonMedisRepository::class);
        $repository->shouldReceive('getDependencies')
            ->once()
            ->with('2026-06', 'umum')
            ->andReturn([
                'bhp' => $this->dependency(1, 10000, true),
                'kamar' => $this->dependency(2, 20000, true),
            ]);
        $repository->shouldReceive('findByPeriodAndType')
            ->once()
            ->with('2026-06', 'umum')
            ->andReturnNull();
        $repository->shouldReceive('calculate')
            ->once()
            ->with('2026-06', 'umum')
            ->andReturn($this->calculation());

        $summary = (new generatePelayananNonMedisService($repository))
            ->getSummary('2026-06', 'umum');

        $this->assertTrue($summary['ready']);
        $this->assertSame(
            'Data BHP dan Kamar Inap sudah tersedia dan terkunci.',
            $summary['readiness_message']
        );
        $this->assertSame(35000.0, $summary['total_final']);
    }

    public function test_summary_is_not_ready_when_kamar_is_not_locked(): void
    {
        $repository = Mockery::mock(generatePelayananNonMedisRepository::class);
        $repository->shouldReceive('getDependencies')
            ->once()
            ->with('2026-06', 'bpjs')
            ->andReturn([
                'bhp' => $this->dependency(1, 10000, true),
                'kamar' => $this->dependency(2, 20000, false),
            ]);
        $repository->shouldReceive('findByPeriodAndType')
            ->once()
            ->with('2026-06', 'bpjs')
            ->andReturnNull();
        $repository->shouldNotReceive('calculate');

        $summary = (new generatePelayananNonMedisService($repository))
            ->getSummary('2026-06', 'bpjs');

        $this->assertFalse($summary['ready']);
        $this->assertSame(
            'Kunci terlebih dahulu: Kamar Inap.',
            $summary['readiness_message']
        );
        $this->assertFalse($summary['dependency_kamar']['is_locked']);
    }

    private function dependency(int $id, int $total, bool $isLocked): object
    {
        return (object) [
            'id' => $id,
            'total_bhp' => $total,
            'total_lama_inap' => $total,
            'is_locked' => $isLocked,
            'updated_at' => null,
        ];
    }

    private function calculation(): array
    {
        return [
            'jumlah_transaksi' => 10,
            'jumlah_jenis_tindakan' => 2,
            'jumlah_mapping_premi' => 2,
            'total_biaya_rawat' => 100000,
            'total_mapping_premi' => 5000,
        ];
    }
}
