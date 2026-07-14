<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class keuanganController extends Controller
{
    public function index(Request $request)
    {
        $periode = $this->normalizePeriod($request->query('periode'));
        $periodDate = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfMonth();
        $previousPeriod = $periodDate->copy()->subMonth()->format('Y-m');

        $summary = $this->payrollSummary($periode);
        $previousSummary = $this->payrollSummary($previousPeriod);
        $trend = $this->monthlyTrend($periodDate);
        $premium = $this->premiumAnalytics($periode, $summary);
        $generator = $this->generatorAnalytics($periode);
        $insights = $this->buildInsights($summary, $previousSummary, $premium, $generator);

        return view('simrs.backOffice.keuangan.dashboard.dashboard', [
            'periode' => $periode,
            'periodeLabel' => $this->periodLabel($periodDate),
            'previousPeriodLabel' => $this->periodLabel($periodDate->copy()->subMonth()),
            'summary' => $summary,
            'previousSummary' => $previousSummary,
            'trend' => $trend,
            'premium' => $premium,
            'generator' => $generator,
            'insights' => $insights,
        ]);
    }

    private function normalizePeriod(?string $periode): string
    {
        if (is_string($periode) && preg_match('/^\d{4}-\d{2}$/', $periode) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $periode.'-01')->format('Y-m');
            } catch (\Throwable $exception) {
                return now()->format('Y-m');
            }
        }

        return now()->format('Y-m');
    }

    private function payrollSummary(string $periode): array
    {
        $stage1 = $this->stage1Summary($periode);
        $stage2 = $this->stage2Summary($periode);
        $niks = collect($stage1['niks'])
            ->merge($stage2['niks'])
            ->filter()
            ->map(fn ($nik) => (string) $nik)
            ->unique()
            ->values();

        $totalPayroll = $stage1['total'] + $stage2['total'];
        $totalPremi = $stage1['premi'] + $stage2['premi'];
        $totalGapok = $stage1['gaji_dibayar'] + $stage2['gaji_dibayar'];
        $totalTunjangan = $stage1['tunjangan'];
        $totalPotongan = $stage2['potongan'];
        $totalPembulatan = $stage1['pembulatan'] + $stage2['pembulatan'];

        return [
            'periode' => $periode,
            'total_payroll' => $totalPayroll,
            'total_premi' => $totalPremi,
            'total_gapok' => $totalGapok,
            'total_tunjangan' => $totalTunjangan,
            'total_potongan' => $totalPotongan,
            'total_pembulatan' => $totalPembulatan,
            'pegawai_unique' => $niks->count(),
            'stage1' => $stage1,
            'stage2' => $stage2,
            'component' => [
                ['label' => 'Gaji Dibayarkan', 'value' => $totalGapok],
                ['label' => 'Tunjangan', 'value' => $totalTunjangan],
                ['label' => 'Premi', 'value' => $totalPremi],
                ['label' => 'Potongan', 'value' => $totalPotongan],
                ['label' => 'Pembulatan', 'value' => $totalPembulatan],
            ],
        ];
    }

    private function stage1Summary(string $periode): array
    {
        $table = 'gaji_tahap1';

        if (! $this->periodTableReady($table)) {
            return $this->emptyPayrollStage();
        }

        $query = DB::table($table)->where('periode', $periode);
        $premiExpr = $this->columnAmount($table, 'premi');
        $gajiExpr = $this->columnAmount($table, 'gaji_dibayar');
        $tunjanganExpr = $this->columnAmount($table, 'tunjangan');
        $pembulatanExpr = $this->columnAmount($table, 'pembulatan');
        $totalExpr = $this->hasColumn($table, 'total')
            ? $this->columnAmount($table, 'total')
            : $gajiExpr.' + '.$tunjanganExpr.' + '.$premiExpr;

        $row = (clone $query)
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw("SUM($totalExpr) as total")
            ->selectRaw("SUM($gajiExpr) as gaji_dibayar")
            ->selectRaw("SUM($tunjanganExpr) as tunjangan")
            ->selectRaw("SUM($premiExpr) as premi")
            ->selectRaw("SUM($pembulatanExpr) as pembulatan")
            ->selectRaw("SUM(CASE WHEN UPPER(TRIM(status)) = 'T' THEN 1 ELSE 0 END) as tetap")
            ->selectRaw("SUM(CASE WHEN UPPER(TRIM(status)) IN ('FT', 'KONTRAK', 'PEGAWAI KONTRAK', 'FT>1') THEN 1 ELSE 0 END) as kontrak")
            ->first();

        return [
            'jumlah' => (int) ($row->jumlah ?? 0),
            'tetap' => (int) ($row->tetap ?? 0),
            'kontrak' => (int) ($row->kontrak ?? 0),
            'lainnya' => max(0, (int) ($row->jumlah ?? 0) - (int) ($row->tetap ?? 0) - (int) ($row->kontrak ?? 0)),
            'total' => (int) ($row->total ?? 0),
            'gaji_dibayar' => (int) ($row->gaji_dibayar ?? 0),
            'tunjangan' => (int) ($row->tunjangan ?? 0),
            'premi' => (int) ($row->premi ?? 0),
            'potongan' => 0,
            'pembulatan' => (int) ($row->pembulatan ?? 0),
            'sumber_premi' => 0,
            'niks' => (clone $query)->pluck('nik')->all(),
        ];
    }

    private function stage2Summary(string $periode): array
    {
        $table = 'gaji_tahap2';

        if (! $this->periodTableReady($table)) {
            return $this->emptyPayrollStage();
        }

        $query = DB::table($table)->where('periode', $periode);
        $totalExpr = $this->columnAmount($table, 'total');
        $gajiExpr = $this->columnAmount($table, 'gaji_dibayar');
        $premiExpr = $this->columnAmount($table, 'total_premi');
        $potonganExpr = $this->columnAmount($table, 'total_potongan');
        $pembulatanExpr = $this->columnAmount($table, 'pembulatan');
        $sourceExpr = $this->columnAmount($table, 'jumlah_sumber_premi');

        $row = (clone $query)
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw("SUM($totalExpr) as total")
            ->selectRaw("SUM($gajiExpr) as gaji_dibayar")
            ->selectRaw("SUM($premiExpr) as premi")
            ->selectRaw("SUM($potonganExpr) as potongan")
            ->selectRaw("SUM($pembulatanExpr) as pembulatan")
            ->selectRaw("SUM($sourceExpr) as sumber_premi")
            ->selectRaw("SUM(CASE WHEN UPPER(TRIM(status)) = 'T' THEN 1 ELSE 0 END) as tetap")
            ->selectRaw("SUM(CASE WHEN UPPER(TRIM(status)) IN ('FT', 'KONTRAK', 'PEGAWAI KONTRAK', 'FT>1') THEN 1 ELSE 0 END) as kontrak")
            ->first();

        return [
            'jumlah' => (int) ($row->jumlah ?? 0),
            'tetap' => (int) ($row->tetap ?? 0),
            'kontrak' => (int) ($row->kontrak ?? 0),
            'lainnya' => max(0, (int) ($row->jumlah ?? 0) - (int) ($row->tetap ?? 0) - (int) ($row->kontrak ?? 0)),
            'total' => (int) ($row->total ?? 0),
            'gaji_dibayar' => (int) ($row->gaji_dibayar ?? 0),
            'tunjangan' => 0,
            'premi' => (int) ($row->premi ?? 0),
            'potongan' => (int) ($row->potongan ?? 0),
            'pembulatan' => (int) ($row->pembulatan ?? 0),
            'sumber_premi' => (int) ($row->sumber_premi ?? 0),
            'niks' => (clone $query)->pluck('nik')->all(),
        ];
    }

    private function emptyPayrollStage(): array
    {
        return [
            'jumlah' => 0,
            'tetap' => 0,
            'kontrak' => 0,
            'lainnya' => 0,
            'total' => 0,
            'gaji_dibayar' => 0,
            'tunjangan' => 0,
            'premi' => 0,
            'potongan' => 0,
            'pembulatan' => 0,
            'sumber_premi' => 0,
            'niks' => [],
        ];
    }

    private function monthlyTrend(Carbon $periodDate): array
    {
        $periods = collect(range(5, 0))
            ->map(fn ($monthBack) => $periodDate->copy()->subMonths($monthBack));

        $rows = $periods->map(function (Carbon $date) {
            $summary = $this->payrollSummary($date->format('Y-m'));

            return [
                'periode' => $date->format('Y-m'),
                'label' => $this->shortPeriodLabel($date),
                'payroll' => $summary['total_payroll'],
                'premi' => $summary['total_premi'],
                'potongan' => $summary['total_potongan'],
                'pegawai' => $summary['pegawai_unique'],
            ];
        })->values();

        return [
            'labels' => $rows->pluck('label')->all(),
            'payroll' => $rows->pluck('payroll')->all(),
            'premi' => $rows->pluck('premi')->all(),
            'potongan' => $rows->pluck('potongan')->all(),
            'pegawai' => $rows->pluck('pegawai')->all(),
        ];
    }

    private function premiumAnalytics(string $periode, array $summary): array
    {
        $sources = collect();

        if ($this->periodTableReady('gaji_tahap1') && $this->hasColumn('gaji_tahap1', 'premi')) {
            $stage1Premium = (int) DB::table('gaji_tahap1')
                ->where('periode', $periode)
                ->sum('premi');

            if ($stage1Premium > 0) {
                $sources->push([
                    'label' => 'Premi Gaji Tahap 1',
                    'total' => $stage1Premium,
                    'jumlah' => (int) DB::table('gaji_tahap1')
                        ->where('periode', $periode)
                        ->where('premi', '>', 0)
                        ->count(),
                    'pegawai' => (int) DB::table('gaji_tahap1')
                        ->where('periode', $periode)
                        ->where('premi', '>', 0)
                        ->distinct()
                        ->count('nik'),
                ]);
            }
        }

        if ($this->periodTableReady('gaji_tahap2')
            && Schema::hasTable('gaji_tahap2_detail')
            && $this->hasColumn('gaji_tahap2_detail', 'gaji_tahap2_id')
            && $this->hasColumn('gaji_tahap2_detail', 'nominal')) {
            $labelParts = [];

            if ($this->hasColumn('gaji_tahap2_detail', 'source_label')) {
                $labelParts[] = "NULLIF(d.source_label, '')";
            }

            if ($this->hasColumn('gaji_tahap2_detail', 'source_key')) {
                $labelParts[] = "NULLIF(d.source_key, '')";
            }

            $labelParts[] = "'Premi'";
            $labelExpression = 'COALESCE('.implode(', ', $labelParts).')';

            $detailQuery = DB::table('gaji_tahap2_detail as d')
                ->join('gaji_tahap2 as g', 'g.id', '=', 'd.gaji_tahap2_id')
                ->where('g.periode', $periode)
                ->selectRaw($labelExpression.' as label')
                ->selectRaw('SUM(COALESCE(d.nominal, 0)) as total')
                ->selectRaw('COUNT(*) as jumlah')
                ->selectRaw('COUNT(DISTINCT g.nik) as pegawai');

            if ($this->hasColumn('gaji_tahap2_detail', 'source_label')) {
                $detailQuery->groupBy('d.source_label');
            }

            if ($this->hasColumn('gaji_tahap2_detail', 'source_key')) {
                $detailQuery->groupBy('d.source_key');
            }

            $detailQuery
                ->orderByDesc('total')
                ->limit(12)
                ->get()
                ->each(function ($row) use ($sources) {
                    $sources->push([
                        'label' => (string) ($row->label ?: 'Premi'),
                        'total' => (int) ($row->total ?? 0),
                        'jumlah' => (int) ($row->jumlah ?? 0),
                        'pegawai' => (int) ($row->pegawai ?? 0),
                    ]);
                });
        }

        $sources = $sources
            ->groupBy('label')
            ->map(function (Collection $rows, string $label) {
                return [
                    'label' => $label,
                    'total' => (int) $rows->sum('total'),
                    'jumlah' => (int) $rows->sum('jumlah'),
                    'pegawai' => (int) $rows->sum('pegawai'),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(8);

        return [
            'sources' => $sources,
            'recipients' => $this->topPremiumRecipients($periode),
            'stage_split' => [
                ['label' => 'Tahap 1', 'value' => $summary['stage1']['premi']],
                ['label' => 'Tahap 2', 'value' => $summary['stage2']['premi']],
            ],
        ];
    }

    private function topPremiumRecipients(string $periode): Collection
    {
        $rows = collect();

        if ($this->periodTableReady('gaji_tahap1') && $this->hasColumn('gaji_tahap1', 'premi')) {
            DB::table('gaji_tahap1')
                ->where('periode', $periode)
                ->where('premi', '>', 0)
                ->select(['nik', 'nama', 'jabatan'])
                ->selectRaw('premi as total_premi')
                ->selectRaw("'Tahap 1' as tahap")
                ->get()
                ->each(fn ($row) => $rows->push($row));
        }

        if ($this->periodTableReady('gaji_tahap2') && $this->hasColumn('gaji_tahap2', 'total_premi')) {
            DB::table('gaji_tahap2')
                ->where('periode', $periode)
                ->where('total_premi', '>', 0)
                ->select(['nik', 'nama', 'jabatan'])
                ->selectRaw('total_premi')
                ->selectRaw("'Tahap 2' as tahap")
                ->get()
                ->each(fn ($row) => $rows->push($row));
        }

        return $rows
            ->groupBy(fn ($row) => (string) $row->nik)
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'nik' => (string) ($first->nik ?? '-'),
                    'nama' => (string) ($first->nama ?? '-'),
                    'jabatan' => (string) ($first->jabatan ?? '-'),
                    'total_premi' => (int) $items->sum('total_premi'),
                    'tahap' => $items->pluck('tahap')->unique()->implode(', '),
                ];
            })
            ->sortByDesc('total_premi')
            ->values()
            ->take(8);
    }

    private function generatorAnalytics(string $periode): array
    {
        $items = collect($this->generatorSources())
            ->map(function (array $source, string $key) use ($periode) {
                $table = $source['table'];

                if (! $this->periodTableReady($table)) {
                    return [
                        'key' => $key,
                        'label' => $source['label'],
                        'table' => $table,
                        'available' => false,
                        'count' => 0,
                        'locked_count' => 0,
                        'amount' => 0,
                        'actions' => 0,
                        'locked_rate' => 0,
                        'types' => collect(),
                    ];
                }

                $query = DB::table($table)->where('periode', $periode);
                $count = (int) (clone $query)->count();
                $amountExpr = $this->generatorAmountExpression($table);
                $actionExpr = $this->generatorActionExpression($table);
                $amount = (int) ((clone $query)->selectRaw("SUM($amountExpr) as total")->value('total') ?? 0);
                $actions = (int) ((clone $query)->selectRaw("SUM($actionExpr) as total")->value('total') ?? 0);
                $lockedCount = $this->hasColumn($table, 'is_locked')
                    ? (int) (clone $query)->where('is_locked', true)->count()
                    : 0;

                return [
                    'key' => $key,
                    'label' => $source['label'],
                    'table' => $table,
                    'available' => true,
                    'count' => $count,
                    'locked_count' => $lockedCount,
                    'amount' => $amount,
                    'actions' => $actions,
                    'locked_rate' => $count > 0 ? round(($lockedCount / $count) * 100, 1) : 0,
                    'types' => $this->generatorTypes($table, $source['type_column'] ?? null, $periode),
                ];
            })
            ->values();

        $generated = $items->where('count', '>', 0);
        $totalRows = (int) $items->sum('count');
        $lockedRows = (int) $items->sum('locked_count');

        return [
            'items' => $items,
            'top' => $items
                ->sortByDesc(fn ($item) => $item['amount'] > 0 ? $item['amount'] : $item['count'])
                ->values()
                ->take(6),
            'summary' => [
                'available_modules' => $items->where('available', true)->count(),
                'generated_modules' => $generated->count(),
                'total_modules' => $items->count(),
                'rows' => $totalRows,
                'locked_rows' => $lockedRows,
                'locked_rate' => $totalRows > 0 ? round(($lockedRows / $totalRows) * 100, 1) : 0,
                'amount' => (int) $items->sum('amount'),
                'actions' => (int) $items->sum('actions'),
            ],
        ];
    }

    private function generatorTypes(string $table, ?string $typeColumn, string $periode): Collection
    {
        if (! $typeColumn || ! $this->hasColumn($table, $typeColumn)) {
            return collect();
        }

        return DB::table($table)
            ->where('periode', $periode)
            ->selectRaw("COALESCE(NULLIF($typeColumn, ''), '-') as type")
            ->selectRaw('COUNT(*) as count')
            ->groupBy($typeColumn)
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'type' => strtoupper((string) ($row->type ?? '-')),
                'count' => (int) ($row->count ?? 0),
            ]);
    }

    private function generatorAmountExpression(string $table): string
    {
        $priorityColumns = [
            'total_premi',
            'total',
            'grand_total',
            'total_nominal',
            'nominal_premi',
            'nominal',
            'total_hitung',
            'premi',
            'jasa',
            'nilai',
        ];

        foreach ($priorityColumns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $this->columnAmount($table, $column);
            }
        }

        $columns = Schema::getColumnListing($table);
        $componentColumns = collect($columns)
            ->filter(function (string $column) {
                $lower = strtolower($column);

                if (str_contains($lower, 'percent')
                    || str_contains($lower, 'pembagi')
                    || str_contains($lower, 'divider')
                    || str_contains($lower, 'mode')
                    || str_contains($lower, 'enabled')) {
                    return false;
                }

                return str_starts_with($lower, 'total_premi')
                    || str_starts_with($lower, 'premi_')
                    || str_starts_with($lower, 'jasa_');
            })
            ->values();

        if ($componentColumns->isEmpty()) {
            return '0';
        }

        return $componentColumns
            ->map(fn ($column) => $this->columnAmount($table, $column))
            ->implode(' + ');
    }

    private function generatorActionExpression(string $table): string
    {
        $priorityColumns = [
            'jumlah_tindakan',
            'total_jumlah_tindakan',
            'jumlah_pasien',
            'jumlah_kunjungan',
            'jumlah',
            'qty',
            'lama',
        ];

        foreach ($priorityColumns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $this->columnAmount($table, $column);
            }
        }

        return '1';
    }

    private function buildInsights(array $summary, array $previousSummary, array $premium, array $generator): Collection
    {
        $payrollGrowth = $this->changePayload($summary['total_payroll'], $previousSummary['total_payroll']);
        $premiumShare = $this->percent($summary['total_premi'], max(1, $summary['total_payroll']));
        $topPremium = $premium['sources']->first();
        $generatorSummary = $generator['summary'];

        return collect([
            [
                'label' => 'Perubahan Payroll',
                'value' => $payrollGrowth['percent_label'],
                'text' => $payrollGrowth['description'].' dibanding '.$previousSummary['periode'].'.',
                'tone' => $payrollGrowth['direction'] === 'down' ? 'danger' : ($payrollGrowth['direction'] === 'up' ? 'success' : 'muted'),
                'icon' => $payrollGrowth['direction'] === 'down' ? 'mdi-trending-down' : 'mdi-trending-up',
            ],
            [
                'label' => 'Rasio Premi',
                'value' => $premiumShare.'%',
                'text' => 'Premi terhadap total payroll periode ini.',
                'tone' => 'info',
                'icon' => 'mdi-chart-donut',
            ],
            [
                'label' => 'Kunci Generator',
                'value' => $generatorSummary['locked_rate'].'%',
                'text' => $generatorSummary['locked_rows'].' dari '.$generatorSummary['rows'].' baris generator sudah terkunci.',
                'tone' => $generatorSummary['locked_rate'] >= 90 ? 'success' : 'warning',
                'icon' => 'mdi-lock-check-outline',
            ],
            [
                'label' => 'Sumber Premi Terbesar',
                'value' => $topPremium['label'] ?? '-',
                'text' => $topPremium ? $this->rupiah($topPremium['total']).' dari '.$topPremium['pegawai'].' pegawai.' : 'Belum ada rincian sumber premi.',
                'tone' => 'primary',
                'icon' => 'mdi-account-cash-outline',
            ],
        ]);
    }

    private function changePayload(int $current, int $previous): array
    {
        $amount = $current - $previous;
        $percent = $previous > 0
            ? round(($amount / $previous) * 100, 1)
            : ($current > 0 ? 100 : 0);

        if ($amount > 0) {
            $direction = 'up';
            $description = 'Naik '.$this->rupiah(abs($amount));
        } elseif ($amount < 0) {
            $direction = 'down';
            $description = 'Turun '.$this->rupiah(abs($amount));
        } else {
            $direction = 'flat';
            $description = 'Stabil';
        }

        return [
            'amount' => $amount,
            'percent' => $percent,
            'percent_label' => ($amount > 0 ? '+' : '').$percent.'%',
            'direction' => $direction,
            'description' => $description,
        ];
    }

    private function percent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 1);
    }

    private function rupiah(int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function periodTableReady(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'periode');
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function columnAmount(string $table, string $column): string
    {
        if (! $this->hasColumn($table, $column)) {
            return '0';
        }

        return 'COALESCE('.$this->quoteIdentifier($column).', 0)';
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function periodLabel(Carbon $date): string
    {
        return $this->monthName((int) $date->format('n')).' '.$date->format('Y');
    }

    private function shortPeriodLabel(Carbon $date): string
    {
        return substr($this->monthName((int) $date->format('n')), 0, 3).' '.$date->format('y');
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month] ?? '';
    }

    private function generatorSources(): array
    {
        return [
            'ugd' => ['label' => 'UGD', 'table' => 'generate_ugd', 'type_column' => 'jenis_ugd'],
            'vk' => ['label' => 'VK', 'table' => 'generate_vk', 'type_column' => 'jenis_vk'],
            'kamar' => ['label' => 'Kamar', 'table' => 'generate_kamar_inap', 'type_column' => 'jenis_kamar'],
            'bhp' => ['label' => 'BHP', 'table' => 'generate_bhp', 'type_column' => 'jenis_bhp'],
            'laboratorium' => ['label' => 'Laboratorium', 'table' => 'generate_laboratorium', 'type_column' => 'jenis_laboratorium'],
            'radiologi' => ['label' => 'Radiologi', 'table' => 'generate_radiologi', 'type_column' => 'jenis_radiologi'],
            'apotek' => ['label' => 'Apotek', 'table' => 'generate_apotek', 'type_column' => 'jenis_apotek'],
            'operasi' => ['label' => 'Operasi', 'table' => 'generate_operasi', 'type_column' => 'jenis_operasi'],
            'gizi' => ['label' => 'Gizi', 'table' => 'generate_gizi', 'type_column' => 'jenis_gizi'],
            'nicu' => ['label' => 'NICU', 'table' => 'generate_nicu', 'type_column' => 'jenis_nicu'],
            'tindakan-medis' => ['label' => 'Tindakan Medis', 'table' => 'generate_tindakan_medis', 'type_column' => 'jenis_pelayanan'],
            'pelayanan-non-medis' => ['label' => 'Non Medis', 'table' => 'premi_pelayanan_non_medis', 'type_column' => 'jenis_pelayanan'],
            'premi-bersama' => ['label' => 'Premi Bersama', 'table' => 'generate_premi_bersama', 'type_column' => 'jenis_pelayanan'],
            'dokter' => ['label' => 'Premi Dokter', 'table' => 'generate_premi_dokter', 'type_column' => 'jenis_pelayanan'],
            'icu' => ['label' => 'ICU', 'table' => 'generate_icu', 'type_column' => 'jenis_icu'],
            'fisio' => ['label' => 'Fisio', 'table' => 'generate_premi_fisio', 'type_column' => 'jenis_fisio'],
            'driver' => ['label' => 'Driver', 'table' => 'generate_premi_driver'],
            'casemix' => ['label' => 'Casemix', 'table' => 'generate_casemix'],
        ];
    }
}
