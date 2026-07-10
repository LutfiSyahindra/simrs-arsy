<?php

namespace App\Services\masterData;

use App\Repositories\masterData\jenisPotonganRepository;

class jnsPotonganService
{
    protected $jnsPotonganRepository;

    public function __construct(jenisPotonganRepository $jnsPotonganRepository)
    {
        $this->jnsPotonganRepository = $jnsPotonganRepository;
    }

    public function generateKodePotongan()
    {
        $last = $this->jnsPotonganRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'POT'.str_pad($lastId + 1, 3, '0', STR_PAD_LEFT),
        ];
    }

    public function create($request)
    {
        $data = [];
        $last = $this->jnsPotonganRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($request->nama as $i => $nama) {
            $tipe = $request->tipe[$i] ?? 'manual';

            $data[] = [
                'kode' => 'POT'.str_pad(++$lastId, 3, '0', STR_PAD_LEFT),
                'nama' => $nama,
                'tipe' => $tipe,
                'nilai' => $tipe === 'manual' ? null : ($request->nilai[$i] ?? 0),
                'keterangan' => $request->keterangan[$i] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->jnsPotonganRepository->create($data);
    }

    public function getJnsPotonganTable()
    {
        $jnsPotonganTable = $this->jnsPotonganRepository->getJnsPotongan();

        $dataJnsPotongan = [];
        foreach ($jnsPotonganTable as $j) {
            $dataJnsPotongan[] = [
                'id' => $j->id,
                'kode' => $j->kode,
                'nama' => $j->nama,
                'tipe' => $j->tipe,
                'nilai' => $j->nilai,
                'keterangan' => $j->keterangan,
            ];
        }

        return collect($dataJnsPotongan);
    }

    public function findById($id)
    {
        return $this->jnsPotonganRepository->findById($id);
    }

    public function update($id, array $data)
    {
        if (($data['tipe'] ?? null) === 'manual') {
            $data['nilai'] = null;
        }

        return $this->jnsPotonganRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->jnsPotonganRepository->delete($id);
    }
}
