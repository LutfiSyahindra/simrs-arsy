<?php

namespace App\Import\Keuangan\master;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class potonganPegawaiImport implements ToCollection
{
    protected $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function collection(Collection $rows)
    {
        $headings = $rows->shift();

        if (! $headings instanceof Collection) {
            return;
        }

        $indexes = $this->headingIndexes($headings);

        $nikIndex = $indexes['nik'] ?? 0;
        $kodePotonganIndex = $indexes['kode potongan'] ?? $indexes['potongan_id'] ?? 5;
        $nominalIndex = $indexes['nominal'] ?? 6;

        foreach ($rows as $row) {
            $nominal = $row[$nominalIndex] ?? null;

            if ($this->isBlank($nominal)) {
                continue;
            }

            $data = [
                'nik' => $row[$nikIndex] ?? null,
                'potongan_id' => $row[$kodePotonganIndex] ?? null,
                'nominal' => $nominal,
            ];

            $this->service->prosesImportPotonganPegawai($data);
        }
    }

    private function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function headingIndexes(Collection $headings): array
    {
        return $headings
            ->mapWithKeys(fn ($heading, $index) => [
                strtolower(trim((string) $heading)) => $index,
            ])
            ->all();
    }
}
