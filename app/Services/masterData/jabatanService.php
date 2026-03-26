<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\gapokExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Repositories\masterData\jabatanRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class jabatanService
{
    protected $jabatanRepository;

    public function __construct(jabatanRepository $jabatanRepository)
    {
        $this->jabatanRepository = $jabatanRepository;
    }

    public function jabatanTable()
    {
        $jabatan = $this->jabatanRepository->getJabatan();

        $dataJabatan = [];
        if ($jabatan) {
            foreach ($jabatan as $j) {
                $dataJabatan[] = [
                    'id' => $j->id,
                    'kode' => $j->kode,
                    'nama' => $j->nama,
                    'tunjangan' => (int) $j->tunjangan,
                ];
            }
        }

        return collect($dataJabatan);
    }

    public function generateKodeJabatan()
    {
        $last = $this->jabatanRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'JBT' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function create($request)
    {
        $data = [];

        $last = $this->jabatanRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($request->nama as $i => $nama) {
            $tunjangan = $request->tunjangan[$i] ?? 0;

            $kode = 'JBT' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'nama' => $nama,
                'tunjangan' => $tunjangan,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Log::info('Data yang akan disimpan:', $data);

        return $this->jabatanRepository->create($data);
    }

    public function findById($id)
    {
        return $this->jabatanRepository->findById($id);
    }

    public function update($id, array $data)
    {
        return $this->jabatanRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->jabatanRepository->delete($id);
    }
}
