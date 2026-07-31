<?php

namespace Tests\Unit;

use App\Repositories\keuangan\premi\generatePremiBersamaRepository;
use App\Services\keuangan\premi\generatePremiBersamaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class GeneratePremiBersamaServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_generate_is_rejected_when_a_generator_source_has_not_been_generated(): void
    {
        $this->assertGenerateRejected([
            'source_label' => 'Laboratorium',
            'raw_snapshot' => [
                'generated_count' => 0,
                'locked_count' => 0,
            ],
        ]);
    }

    public function test_generate_is_rejected_when_a_generated_source_has_not_been_locked(): void
    {
        $this->assertGenerateRejected([
            'source_label' => 'Laboratorium',
            'raw_snapshot' => [
                'generated_count' => 1,
                'locked_count' => 0,
            ],
        ]);
    }

    private function assertGenerateRejected(array $source): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $repository = Mockery::mock(generatePremiBersamaRepository::class);
        $repository->shouldReceive('getConfig')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'jnsPremi_umum_id' => 7,
            ]);
        $repository->shouldReceive('findPremi')
            ->once()
            ->with(7)
            ->andReturn((object) [
                'id' => 7,
                'kode' => 'PRB',
                'jenis' => 'Premi Bersama',
            ]);
        $repository->shouldReceive('getConfigSourceMappings')
            ->once()
            ->with(1)
            ->andReturn(collect());
        $repository->shouldReceive('findByPeriodAndTypeForUpdate')
            ->once()
            ->with('2026-06', 'umum', 7)
            ->andReturnNull();
        $repository->shouldReceive('calculate')
            ->once()
            ->with('2026-06', 'umum', 7, Mockery::type('object'), Mockery::type('object'), true)
            ->andReturn([
                'generator_sources' => collect([$source]),
            ]);
        $repository->shouldNotReceive('saveResult');

        try {
            (new generatePremiBersamaService($repository))
                ->generate('2026-06', 'umum');

            $this->fail('Generate Premi Bersama seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Generate dan kunci sumber generator terlebih dahulu: Laboratorium.'],
                $exception->errors()['sources']
            );
        }
    }
}
