<?php

namespace App\Services\masterData;

use App\Repositories\masterData\jenisTindakanRepository;
use Illuminate\Support\Facades\Log;

class jnsTindakanService
{
    protected $jnsTindakanRepository;

    public function __construct(jenisTindakanRepository $jnsTindakanRepository)
    {
        $this->jnsTindakanRepository = $jnsTindakanRepository;
    }

    public function generateKodeJnsTindakan()
    {
        $last = $this->jnsTindakanRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'JTN' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function jnsTindakanTable()
    {
        $jnsTindakan = $this->jnsTindakanRepository->getModel();

        $datajnsTindakan = [];
        if ($jnsTindakan) {
            foreach ($jnsTindakan as $u) {
                $datajnsTindakan[] = [
                    'id' => $u->id,
                    'kode' => $u->kode,
                    'jenis' => $u->jenis,
                ];
            }
        }

        return collect($datajnsTindakan);
    }

    public function create($jenisList)
    {
        $data = [];

        $last = $this->jnsTindakanRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($jenisList as $jenis) {
            $kode = 'JTN' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'jenis' => $jenis,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        return $this->jnsTindakanRepository->create($data);
    }

    public function findById($id)
    {
        return $this->jnsTindakanRepository->getModel()->where('id', $id)->first();
    }

    public function update($id, array $data)
    {
        return $this->jnsTindakanRepository->update($id, [
            'jenis' => $data['jenis'],
        ]);
    }

    public function delete($id)
    {
        $jnsTindakan = $this->jnsTindakanRepository->getModel()->where('id', $id)->first();

        if (!$jnsTindakan) {
            return false;
        }

        return $jnsTindakan->delete();
    }
}
