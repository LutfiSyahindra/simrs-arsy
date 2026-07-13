<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class hitungPremiController extends Controller
{
    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.hitungPremi');
    }

    public function generatorStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $periode = $validated['periode'];
        $status = [];

        foreach ($this->generatorStatusSources() as $key => $source) {
            $status[$key] = isset($source['type_column'])
                ? $this->typedStatus($source['table'], $source['type_column'], $periode)
                : $this->singleStatus($source['table'], $periode, $source['label'] ?? 'Mandiri');
        }

        return response()->json([
            'periode' => $periode,
            'data' => $status,
        ]);
    }

    private function typedStatus(string $table, string $typeColumn, string $periode): array
    {
        if (! $this->statusTableReady($table, $typeColumn)) {
            return [
                'supports_split' => true,
                'available' => false,
                'umum' => $this->emptyTypeStatus(),
                'bpjs' => $this->emptyTypeStatus(),
            ];
        }

        return [
            'supports_split' => true,
            'available' => true,
            'umum' => $this->periodTypeStatus($table, $typeColumn, $periode, 'umum'),
            'bpjs' => $this->periodTypeStatus($table, $typeColumn, $periode, 'bpjs'),
        ];
    }

    private function singleStatus(string $table, string $periode, string $label): array
    {
        if (! $this->statusTableReady($table)) {
            return [
                'supports_split' => false,
                'available' => false,
                'label' => $label,
                'generated' => false,
                'count' => 0,
                'locked_count' => 0,
            ];
        }

        $query = DB::table($table)->where('periode', $periode);

        return [
            'supports_split' => false,
            'available' => true,
            'label' => $label,
            'generated' => (clone $query)->exists(),
            'count' => (clone $query)->count(),
            'locked_count' => $this->lockedCount($query, $table),
        ];
    }

    private function periodTypeStatus(string $table, string $typeColumn, string $periode, string $type): array
    {
        $query = DB::table($table)
            ->where('periode', $periode)
            ->where($typeColumn, $type);

        return [
            'generated' => (clone $query)->exists(),
            'count' => (clone $query)->count(),
            'locked_count' => $this->lockedCount($query, $table),
        ];
    }

    private function lockedCount($query, string $table): int
    {
        if (! Schema::hasColumn($table, 'is_locked')) {
            return 0;
        }

        return (clone $query)->where('is_locked', true)->count();
    }

    private function statusTableReady(string $table, ?string $typeColumn = null): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'periode')
            && ($typeColumn === null || Schema::hasColumn($table, $typeColumn));
    }

    private function emptyTypeStatus(): array
    {
        return [
            'generated' => false,
            'count' => 0,
            'locked_count' => 0,
        ];
    }

    private function generatorStatusSources(): array
    {
        return [
            'ugd' => ['table' => 'generate_ugd', 'type_column' => 'jenis_ugd'],
            'vk' => ['table' => 'generate_vk', 'type_column' => 'jenis_vk'],
            'kamar' => ['table' => 'generate_kamar_inap', 'type_column' => 'jenis_kamar'],
            'bhp' => ['table' => 'generate_bhp', 'type_column' => 'jenis_bhp'],
            'laboratorium' => ['table' => 'generate_laboratorium', 'type_column' => 'jenis_laboratorium'],
            'radiologi' => ['table' => 'generate_radiologi', 'type_column' => 'jenis_radiologi'],
            'apotek' => ['table' => 'generate_apotek', 'type_column' => 'jenis_apotek'],
            'operasi' => ['table' => 'generate_operasi', 'type_column' => 'jenis_operasi'],
            'gizi' => ['table' => 'generate_gizi', 'type_column' => 'jenis_gizi'],
            'nicu' => ['table' => 'generate_nicu', 'type_column' => 'jenis_nicu'],
            'tindakan-medis' => ['table' => 'generate_tindakan_medis', 'type_column' => 'jenis_pelayanan'],
            'pelayanan-non-medis' => ['table' => 'premi_pelayanan_non_medis', 'type_column' => 'jenis_pelayanan'],
            'premi-bersama' => ['table' => 'generate_premi_bersama', 'type_column' => 'jenis_pelayanan'],
            'dokter' => ['table' => 'generate_premi_dokter', 'type_column' => 'jenis_pelayanan'],
            'icu' => ['table' => 'generate_icu', 'type_column' => 'jenis_icu'],
            'fisio' => ['table' => 'generate_premi_fisio', 'type_column' => 'jenis_fisio'],
            'driver' => ['table' => 'generate_premi_driver', 'label' => 'Mandiri'],
            'casemix' => ['table' => 'generate_casemix', 'label' => 'BPJS'],
        ];
    }
}
