<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsTunjanganModel;

class jenisTunjanganRepository
{
    /**
     * Create a new class instance.
     */

    public function getJnsTunjangan()
    {
        return jnsTunjanganModel::select('id','kode','nama','persentase')
            ->get()
            ->map(function ($item) {

                // 🔥 hapus nol di belakang
                $item->persentase = rtrim(rtrim($item->persentase, '0'), '.');

                return $item;
            });
    }

    public function getLast()
    {
        return jnsTunjanganModel::orderBy('id', 'desc')->first();
    }

    public function create(array $data)
    {
        return jnsTunjanganModel::insert($data);
    }

    public function findById($id)
    {
        return jnsTunjanganModel::find($id);
    }

    public function update($id, array $data)
    {
        $model = jnsTunjanganModel::findOrFail($id);

        $model->update([
            'nama' => $data['nama'],
            'persentase' => $data['persentase'],
        ]);

        return $model;
    }

    public function delete($id)
    {
        $jnsTunjangan = jnsTunjanganModel::findOrFail($id);

        $jnsTunjangan->delete();

        return true;
    }
}
