<?php

namespace App\Services\masterData;

use App\Repositories\masterData\skorRepository;
use Illuminate\Support\Facades\Log;

class skorService
{
    protected $skorRepository;

    public function __construct(skorRepository $skorRepository)
    {
        $this->skorRepository = $skorRepository;
    }

    public function generateKodeSkor()
    {
        $last = $this->skorRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'SKR' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function skorTable()
    {
        $skor = $this->skorRepository->getSkor();

        $dataSkor = [];
        if ($skor) {
            foreach ($skor as $s) {
                $dataSkor[] = [
                    'id' => $s->id,
                    'kd_skor' => $s->kd_skor,
                    'jenis' => $s->jenis,
                    'keterangan' => $s->keterangan,
                    'bobot_skor' => (int) $s->bobot_skor,
                ];
            }
        }

        return collect($dataSkor);
    }

    public function create($request)
    {
        $data = [];

        $last = $this->skorRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($request->nama as $i => $nama) {
            $bobot = $request->bobot[$i] ?? 0;

            $kode = 'SKR' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kd_skor' => $kode,
                'jenis' => $request->jenis[$i],
                'keterangan' => $nama,
                'bobot_skor' => $bobot,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Log::info('Data yang akan disimpan:', $data);

        return $this->skorRepository->create($data);
    }

    public function delete($id)
    {
        $skor = $this->skorRepository->getSkor()->where('id', $id)->first();

        if (!$skor) {
            return false;
        }

        return $skor->delete();
    }

    public function findById($id)
    {
        return $this->skorRepository->getSkor()->where('id', $id)->first();
    }

    public function update($id, array $data)
    {
        return $this->skorRepository->update($id, [
            'jenis' => $data['jenis'],
            'keterangan' => $data['keterangan'] ?? ($data['nama'] ?? null),
            'bobot_skor' => $data['bobot_skor'] ?? ($data['bobot'] ?? 0),
        ]);
    }

}
