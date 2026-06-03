<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\unitModel;

class unitRepository
{
    protected $model;

    public function __construct(unitModel $model)
    {
        $this->model = $model;
    }
    public function getModel()
    {
        return $this->model::select('id','kode','jenis','keterangan')->get();
    }

    public function getLast()
    {
        return $this->model::orderBy('id', 'desc')->first();
    }

    public function create($data)
    {
        return $this->model::insert($data);
    }

    public function update($id, $data)
    {
        return $this->model::where('id', $id)->update($data);
    }
}
