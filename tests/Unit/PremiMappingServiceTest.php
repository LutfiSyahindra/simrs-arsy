<?php

namespace Tests\Unit;

use App\Repositories\mappingData\premiMappingRepository;
use App\Services\mappingData\premiMappingService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PremiMappingServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_guide_pegawai_formats_active_employee_data(): void
    {
        $repository = Mockery::mock(premiMappingRepository::class);
        $repository->shouldReceive('getPegawai')
            ->once()
            ->andReturn(collect([
                (object) [
                    'nik' => 'PGW001',
                    'nama' => 'Budi',
                    'jbtn' => 'Perawat',
                    'stts_kerja' => 'Tetap',
                ],
            ]));

        $result = (new premiMappingService($repository))->guidePegawai();

        $this->assertSame([
            [
                'nik' => 'PGW001',
                'nama' => 'Budi',
                'jbtn' => 'Perawat',
                'stts_kerja' => 'Tetap',
                'text' => 'PGW001 - Budi',
            ],
        ], $result->all());
    }

    public function test_sync_pegawai_normalizes_nik_before_persisting(): void
    {
        $repository = Mockery::mock(premiMappingRepository::class);
        $repository->shouldReceive('syncPegawai')
            ->once()
            ->with(7, ['PGW001', 'PGW002'])
            ->andReturnTrue();

        $result = (new premiMappingService($repository))->syncPegawai(
            7,
            ['PGW001', 'PGW001', ' PGW002 ', '']
        );

        $this->assertTrue($result);
    }

    public function test_update_persists_umum_and_bpjs_values(): void
    {
        $repository = Mockery::mock(premiMappingRepository::class);
        $repository->shouldReceive('update')
            ->once()
            ->with(5, [
                'jnsTindakan_id' => 2,
                'jenis' => 'nominal',
                'jenis_umum' => 'nominal',
                'jenis_bpjs' => 'persen',
                'nilai' => 2500,
                'nilai_umum' => 2500,
                'nilai_bpjs' => 1750,
                'nilai_bersama_umum' => 500,
                'nilai_bersama_bpjs' => 10,
            ])
            ->andReturnTrue();

        $result = (new premiMappingService($repository))->update(
            5,
            2,
            'nominal',
            'persen',
            2500,
            1750,
            500,
            10
        );

        $this->assertTrue($result);
    }
}
