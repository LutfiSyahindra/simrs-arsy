<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\gapokExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Repositories\masterData\jabatanRepository;
use App\Repositories\masterData\profesiRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class profesiService
{
    protected $profesiRepository;

    public function __construct(profesiRepository $profesiRepository)
    {
        $this->profesiRepository = $profesiRepository;
    }

    public function profesiTable()
    {
        $profesi = $this->profesiRepository->getProfesi();

        $dataProfesi = [];
        if ($profesi) {
            foreach ($profesi as $p) {
                $dataProfesi[] = [
                    'id' => $p->id,
                    'kode' => $p->kode,
                    'nama' => $p->nama,
                    'tunjangan' => (int) $p->tunjangan,
                ];
            }
        }

        return collect($dataProfesi);
    }

    public function generateKodeProfesi()
    {
        $last = $this->profesiRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'PRF' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function create($request)
    {
        $data = [];

        $last = $this->profesiRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($request->nama as $i => $nama) {
            $tunjangan = $request->tunjangan[$i] ?? 0;

            $kode = 'PRF' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'nama' => $nama,
                'tunjangan' => $tunjangan,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Log::info('Data yang akan disimpan:', $data);

        return $this->profesiRepository->create($data);
    }

    public function findById($id)
    {
        return $this->profesiRepository->findById($id);
    }

    public function update($id, array $data)
    {
        return $this->profesiRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->profesiRepository->delete($id);
    }
}
