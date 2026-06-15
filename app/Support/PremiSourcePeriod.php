<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class PremiSourcePeriod
{
    public static function resolve(string $periode, string $jenis): string
    {
        $source = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        if ($jenis === 'bpjs') {
            $source->subMonth();
        }

        return $source->format('Y-m');
    }

    public static function range(string $periode, string $jenis): array
    {
        $start = Carbon::createFromFormat(
            'Y-m',
            self::resolve($periode, $jenis)
        )->startOfMonth();

        return [
            'start' => $start,
            'end' => $start->copy()->addMonth(),
        ];
    }
}
