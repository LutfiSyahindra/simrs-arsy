<?php

namespace App\Services\masterData;

use App\Repositories\masterData\unitRepository;

class unitService
{
    protected $unitRepository;

    public function __construct(unitRepository $unitRepository)
    {
        $this->unitRepository = $unitRepository;
    }

    public function generateKodeUnit()
    {
        $last = $this->unitRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'UNT' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function unitTable()
    {
        $units = $this->unitRepository->getModel();

        $dataUnits = [];
        if ($units) {
            foreach ($units as $u) {
                $dataUnits[] = [
                    'id' => $u->id,
                    'kode' => $u->kode,
                    'jenis' => $u->jenis,
                    'keterangan' => $u->keterangan,
                ];
            }
        }

        return collect($dataUnits);
    }

    public function create($request)
    {
        $data = [];

        $last = $this->unitRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($request->nama as $i => $nama) {
            $bobot = $request->bobot[$i] ?? 0;

            $kode = 'UNT' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'jenis' => $request->jenis[$i],
                'keterangan' => $nama,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        return $this->unitRepository->create($data);
    }

    public function findById($id)
    {
        return $this->unitRepository->getModel()->where('id', $id)->first();
    }

    public function update($id, array $data)
    {
        return $this->unitRepository->update($id, [
            'jenis' => $data['jenis'],
            'keterangan' => $data['keterangan'] ?? ($data['nama'] ?? null),
        ]);
    }

    public function delete($id)
    {
        $unit = $this->unitRepository->getModel()->where('id', $id)->first();

        if (!$unit) {
            return false;
        }

        return $unit->delete();
    }
}
