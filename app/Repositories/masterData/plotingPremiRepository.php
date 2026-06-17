<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\plotingPremiModel;

class plotingPremiRepository
{
    protected $model;

    public function __construct(plotingPremiModel $model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model::select('id', 'kode', 'ploting')->get();
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

    public function findById($id)
    {
        return $this->model::select('id', 'kode', 'ploting')->where('id', $id)->first();
    }

    public function delete($id)
    {
        return $this->model::where('id', $id)->delete();
    }
}
