<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class PremiSourcePeriod
{
    public const MODE_CURRENT = 'current';

    public const MODE_PREVIOUS = 'previous';

    public static function normalizeMode(?string $mode, string $jenis): string
    {
        if ($jenis !== 'bpjs') {
            return self::MODE_CURRENT;
        }

        return $mode === self::MODE_CURRENT ? self::MODE_CURRENT : self::MODE_PREVIOUS;
    }

    public static function modeLabel(?string $mode, string $jenis): string
    {
        return self::normalizeMode($mode, $jenis) === self::MODE_CURRENT
            ? 'Periode Generate'
            : 'Bulan Sebelumnya';
    }

    public static function resolve(string $periode, string $jenis, ?string $mode = null): string
    {
        $source = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        if ($jenis === 'bpjs' && self::normalizeMode($mode, $jenis) === self::MODE_PREVIOUS) {
            $source->subMonth();
        }

        return $source->format('Y-m');
    }

    public static function range(string $periode, string $jenis, ?string $mode = null): array
    {
        $start = Carbon::createFromFormat(
            'Y-m',
            self::resolve($periode, $jenis, $mode)
        )->startOfMonth();

        return [
            'start' => $start,
            'end' => $start->copy()->addMonth(),
        ];
    }
}
