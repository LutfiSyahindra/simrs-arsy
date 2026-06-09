<?php

namespace App\Services\masterData;

use App\Repositories\masterData\jenisPremiRepository;
use Illuminate\Support\Facades\Log;

class jnsPremiService
{
    protected $jnsPremiRepository;

    public function __construct(jenisPremiRepository $jnsPremiRepository)
    {
        $this->jnsPremiRepository = $jnsPremiRepository;
    }

    public function generateKodeJnsPremi()
    {
        $last = $this->jnsPremiRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'PRM' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function jnsPremiTable()
    {
        $jnsPremi = $this->jnsPremiRepository->getModel();

        $datajnsPremi = [];
        if ($jnsPremi) {
            foreach ($jnsPremi as $u) {
                $datajnsPremi[] = [
                    'id' => $u->id,
                    'kode' => $u->kode,
                    'jenis' => $u->jenis,
                ];
            }
        }

        return collect($datajnsPremi);
    }

    public function create($jenisList)
    {
        $data = [];

        $last = $this->jnsPremiRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($jenisList as $jenis) {
            $kode = 'PRM' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'jenis' => $jenis,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        return $this->jnsPremiRepository->create($data);
    }

    public function findById($id)
    {
        return $this->jnsPremiRepository->getModel()->where('id', $id)->first();
    }

    public function update($id, array $data)
    {
        return $this->jnsPremiRepository->update($id, [
            'jenis' => $data['jenis'],
        ]);
    }

    public function delete($id)
    {
        $jnsPremi = $this->jnsPremiRepository->getModel()->where('id', $id)->first();

        if (!$jnsPremi) {
            return false;
        }

        return $jnsPremi->delete();
    }
}
