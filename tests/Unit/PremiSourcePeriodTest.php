<?php

namespace Tests\Unit;

use App\Support\PremiSourcePeriod;
use PHPUnit\Framework\TestCase;

class PremiSourcePeriodTest extends TestCase
{
    public function test_umum_uses_selected_period(): void
    {
        $this->assertSame(
            '2026-06',
            PremiSourcePeriod::resolve('2026-06', 'umum')
        );
    }

    public function test_bpjs_uses_previous_month(): void
    {
        $this->assertSame(
            '2026-05',
            PremiSourcePeriod::resolve('2026-06', 'bpjs')
        );
    }

    public function test_bpjs_previous_month_crosses_year_boundary(): void
    {
        $this->assertSame(
            '2025-12',
            PremiSourcePeriod::resolve('2026-01', 'bpjs')
        );
    }

    public function test_range_covers_source_month(): void
    {
        $range = PremiSourcePeriod::range('2026-01', 'bpjs');

        $this->assertSame('2025-12-01', $range['start']->toDateString());
        $this->assertSame('2026-01-01', $range['end']->toDateString());
    }
}
