<?php

namespace App\Jobs;

use App\Services\keuangan\penggajian\penggajianService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class KirimSlipGajiEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public int $uniqueFor = 3600;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $gajiId,
        public string $periode,
        public int $tahap = 1
    ) {}

    public function handle(penggajianService $penggajianService): void
    {
        $tahap = $this->normalizeTahap();
        $result = $penggajianService->sendSingleSlipEmail(
            $this->gajiId,
            $this->periode,
            $tahap
        );

        Log::info('Slip gaji Email berhasil dikirim melalui queue.', [
            'gaji_id' => $this->gajiId,
            'nik' => $result['nik'] ?? null,
            'email' => $result['email'] ?? null,
            'periode' => $this->periode,
            'tahap' => $tahap,
        ]);

        try {
            $penggajianService->recordSlipDeliveryLog(
                'email',
                'success',
                $this->gajiId,
                $this->periode,
                $tahap,
                $result,
                'Slip gaji Email berhasil terkirim.'
            );
        } catch (Throwable $exception) {
            Log::error('Gagal mencatat log slip gaji Email berhasil.', [
                'gaji_id' => $this->gajiId,
                'periode' => $this->periode,
                'tahap' => $tahap,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function uniqueId(): string
    {
        return 'email:'.$this->periode.':'.$this->normalizeTahap().':'.$this->gajiId;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queue slip gaji Email gagal.', [
            'gaji_id' => $this->gajiId,
            'periode' => $this->periode,
            'tahap' => $this->normalizeTahap(),
            'message' => $exception?->getMessage(),
        ]);

        try {
            app(penggajianService::class)->recordSlipDeliveryLog(
                'email',
                'failed',
                $this->gajiId,
                $this->periode,
                $this->normalizeTahap(),
                null,
                $exception?->getMessage() ?: 'Queue slip gaji Email gagal.'
            );
        } catch (Throwable $logException) {
            Log::error('Gagal mencatat log slip gaji Email gagal.', [
                'gaji_id' => $this->gajiId,
                'periode' => $this->periode,
                'tahap' => $this->normalizeTahap(),
                'message' => $logException->getMessage(),
            ]);
        }
    }

    private function normalizeTahap(): int
    {
        return (int) ($this->tahap ?? 1) === 2 ? 2 : 1;
    }
}
