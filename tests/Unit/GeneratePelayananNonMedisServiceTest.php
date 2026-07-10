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
        $repository->shouldReceive('getConfig')
            ->once()
            ->andReturn($this->config(7, 8));
        $repository->shouldReceive('findPremi')
            ->twice()
            ->with(7)
            ->andReturn((object) [
                'id' => 7,
                'kode' => 'PNM',
                'jenis' => 'Pelayanan Non Medis UMUM',
            ]);
        $repository->shouldReceive('findByPeriodAndType')
            ->once()
            ->with('2026-06', 'umum', 7)
            ->andReturnNull();
        $repository->shouldReceive('getDependencyOptions')
            ->once()
            ->with('2026-06', 'umum')
            ->andReturn([
                'bhp' => collect(),
                'kamar' => collect(),
            ]);
        $repository->shouldReceive('getDependencies')
            ->once()
            ->with('2026-06', 'umum', false, null, null)
            ->andReturn([
                'bhp' => $this->dependency(1, 10000, true),
                'kamar' => $this->dependency(2, 20000, true),
            ]);
        $repository->shouldReceive('calculate')
            ->once()
            ->with('2026-06', 'umum', 7)
            ->andReturn($this->calculation());
        $repository->shouldReceive('getMappedPegawai')
            ->once()
            ->with(7)
            ->andReturn($this->pegawai());
        $repository->shouldReceive('getKarcisTindakanIds')
            ->twice()
            ->andReturn(collect());

        $summary = (new generatePelayananNonMedisService($repository))
            ->getSummary('2026-06', 'umum');

        $this->assertTrue($summary['ready']);
        $this->assertSame(7, $summary['jnsPremi_id']);
        $this->assertSame(
            'Data BHP dan Kamar Inap sudah tersedia dan terkunci.',
            $summary['readiness_message']
        );
        $this->assertSame('2026-06', $summary['periode_sumber']);
        $this->assertSame(35000.0, $summary['total_final']);
    }

    public function test_summary_is_not_ready_when_kamar_is_not_locked(): void
    {
        $repository = Mockery::mock(generatePelayananNonMedisRepository::class);
        $repository->shouldReceive('getConfig')
            ->once()
            ->andReturn($this->config(7, 8));
        $repository->shouldReceive('findPremi')
            ->twice()
            ->with(8)
            ->andReturn((object) [
                'id' => 8,
                'kode' => 'PNB',
                'jenis' => 'Pelayanan Non Medis BPJS',
            ]);
        $repository->shouldReceive('findByPeriodAndType')
            ->once()
            ->with('2026-06', 'bpjs', 8)
            ->andReturnNull();
        $repository->shouldReceive('getDependencyOptions')
            ->once()
            ->with('2026-06', 'bpjs')
            ->andReturn([
                'bhp' => collect(),
                'kamar' => collect(),
            ]);
        $repository->shouldReceive('getDependencies')
            ->once()
            ->with('2026-06', 'bpjs', false, null, null)
            ->andReturn([
                'bhp' => $this->dependency(1, 10000, true),
                'kamar' => $this->dependency(2, 20000, false),
            ]);
        $repository->shouldNotReceive('calculate');
        $repository->shouldReceive('getMappedPegawai')
            ->once()
            ->with(8)
            ->andReturn($this->pegawai());
        $repository->shouldReceive('getKarcisTindakanIds')
            ->twice()
            ->andReturn(collect());

        $summary = (new generatePelayananNonMedisService($repository))
            ->getSummary('2026-06', 'bpjs');

        $this->assertFalse($summary['ready']);
        $this->assertSame(8, $summary['jnsPremi_id']);
        $this->assertSame(
            'Kunci terlebih dahulu: Kamar Inap.',
            $summary['readiness_message']
        );
        $this->assertSame('2026-05', $summary['periode_sumber']);
        $this->assertFalse($summary['dependency_kamar']['is_locked']);
    }

    private function dependency(int $id, int $total, bool $isLocked): object
    {
        return (object) [
            'id' => $id,
            'plotingPremi_id' => $id,
            'kode_ploting' => 'NON',
            'nama_ploting' => 'Non Medis',
            'total_bhp' => $total,
            'total_lama_inap' => $total,
            'nominal_hitung' => $total,
            'is_locked' => $isLocked,
            'updated_at' => null,
        ];
    }

    private function config(int $umumId, int $bpjsId): object
    {
        return (object) [
            'id' => 1,
            'jnsPremi_id' => $umumId,
            'jnsPremi_umum_id' => $umumId,
            'jnsPremi_bpjs_id' => $bpjsId,
            'distribution_mode' => 'split_evenly',
        ];
    }

    private function pegawai()
    {
        return collect([
            (object) [
                'nik' => 'EMP001',
                'pegawai_name' => 'Pegawai Satu',
                'pegawai_position' => 'Staff',
            ],
        ]);
    }

    private function calculation(): array
    {
        return [
            'jumlah_transaksi' => 10,
            'jumlah_jenis_tindakan' => 2,
            'jumlah_mapping_premi' => 2,
            'total_biaya_rawat' => 100000,
            'total_mapping_premi' => 5000,
            'kode_premi' => 'PNM',
            'nama_premi' => 'Pelayanan Non Medis',
            'details' => collect(),
        ];
    }
}
