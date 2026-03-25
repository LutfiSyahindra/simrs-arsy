<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\gapokExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Repositories\masterData\jenisTunjanganRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class jnsTunjanganService
{
    protected $jnsTunjanganRepository;

    public function __construct(jenisTunjanganRepository $jnsTunjanganRepository)
    {
        $this->jnsTunjanganRepository = $jnsTunjanganRepository;
    }

    public function generateKodeTunjangan()
    {
        $last = $this->jnsTunjanganRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'TJ' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT)
        ];
    }

    public function create($request)
    {
        $data = [];

        $last = $this->jnsTunjanganRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($request->nama as $i => $nama) {
            $persentase = $request->persentase[$i] ?? 0;

            $kode = 'TJ' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'nama' => $nama,
                'persentase' => $persentase,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Log::info('Data yang akan disimpan:', $data);

        return $this->jnsTunjanganRepository->create($data);
    }

    public function getJnsTunjanganTable()
    {
        $jnsTunjanganTable = $this->jnsTunjanganRepository->getJnsTunjangan();

        $dataJnsTunjangan = [];
        if ($jnsTunjanganTable) {
            foreach ($jnsTunjanganTable as $j) {
                $dataJnsTunjangan[] = [
                    'id' => $j->id,
                    'kode' => $j->kode,
                    'nama' => $j->nama,
                    'persentase' => $j->persentase,
                ];
            }
        }

        return collect($dataJnsTunjangan);
    }

    public function findById($id)
    {
        return $this->jnsTunjanganRepository->findById($id);
    }

    public function update($id, array $data)
    {
        return $this->jnsTunjanganRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->jnsTunjanganRepository->delete($id);
    }
}
