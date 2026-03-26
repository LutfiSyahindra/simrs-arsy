<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jabatanModel;
use App\Models\dbSimrs\profesiModel;

class profesiRepository
{
    protected $model;

    public function __construct(profesiModel $model)
    {
        $this->model = $model;
    }
    public function getProfesi()
    {
        return $this->model::select('id','nama','tunjangan','kode')->get();
    }

    public function getLast()
    {
        return $this->model::orderBy('id', 'desc')->first();
    }

    public function create(array $data)
    {
        return $this->model::insert($data);
    }

    public function findById($id)
    {
        return $this->model::find($id);
    }

    public function update($id, array $data)
    {
        $model = $this->model::findOrFail($id);

        $model->update([
            'nama' => $data['nama'],
            'tunjangan' => $data['tunjangan'],
        ]);

        return $model;
    }

    public function delete($id)
    {
        $jabatan = $this->model::findOrFail($id);

        $jabatan->delete();

        return true;
    }

}
