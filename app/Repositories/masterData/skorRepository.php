<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\skorModel;

class skorRepository
{
    protected $model;

    public function __construct(skorModel $model)
    {
        $this->model = $model;
    }
    public function getSkor()
    {
        return $this->model::select('id','kd_skor','jenis','keterangan','bobot_skor')->get();
    }

    public function getLast()
    {
        return $this->model::orderBy('id', 'desc')->first();
    }

    public function create($data)
    {
        return $this->model::insert($data);
    }

    public function update($id, array $data)
    {
        return $this->model::where('id', $id)->update($data);
    }
}
