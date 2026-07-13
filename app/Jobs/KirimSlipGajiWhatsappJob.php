<?php

namespace App\Jobs;

use App\Services\keuangan\penggajian\penggajianService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class KirimSlipGajiWhatsappJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 180;
    public int $uniqueFor = 3600;
    public bool $failOnTimeout = true;

    public function __construct(
        public int $gajiId,
        public string $periode,
        public int $tahap = 1
    ) {
    }

    public function handle(penggajianService $penggajianService): void
    {
        $delaySeconds = max(1, (int) config('services.go_wa.queue_delay_seconds', 8));
        $tahap = $this->normalizeTahap();

        $result = Cache::lock('queue:slip-gaji-whatsapp', 180)
            ->block(90, function () use ($penggajianService, $delaySeconds, $tahap) {
                try {
                    return $penggajianService->sendSingleSlipWhatsapp(
                        $this->gajiId,
                        $this->periode,
                        $tahap
                    );
                } finally {
                    sleep($delaySeconds);
                }
            });

        Log::info('Slip gaji Whatsapp berhasil dikirim melalui queue.', [
            'gaji_id' => $this->gajiId,
            'nik' => $result['nik'] ?? null,
            'periode' => $this->periode,
            'tahap' => $tahap,
        ]);
    }

    public function uniqueId(): string
    {
        return $this->periode.':'.$this->normalizeTahap().':'.$this->gajiId;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queue slip gaji Whatsapp gagal.', [
            'gaji_id' => $this->gajiId,
            'periode' => $this->periode,
            'tahap' => $this->normalizeTahap(),
            'message' => $exception?->getMessage(),
        ]);
    }

    private function normalizeTahap(): int
    {
        return (int) ($this->tahap ?? 1) === 2 ? 2 : 1;
    }
}
