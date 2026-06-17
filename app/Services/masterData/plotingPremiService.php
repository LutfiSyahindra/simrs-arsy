<?php

namespace App\Services\masterData;

use App\Repositories\masterData\plotingPremiRepository;

class plotingPremiService
{
    protected $plotingPremiRepository;

    public function __construct(plotingPremiRepository $plotingPremiRepository)
    {
        $this->plotingPremiRepository = $plotingPremiRepository;
    }

    public function generateKodePlotingPremi()
    {
        $last = $this->plotingPremiRepository->getLast();
        $lastId = $last ? $last->id : 0;

        return [
            'kode' => 'PLT' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT),
        ];
    }

    public function plotingPremiTable()
    {
        $plotingPremi = $this->plotingPremiRepository->getModel();

        $dataPlotingPremi = [];
        if ($plotingPremi) {
            foreach ($plotingPremi as $item) {
                $dataPlotingPremi[] = [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'ploting' => $item->ploting,
                ];
            }
        }

        return collect($dataPlotingPremi);
    }

    public function create($plotingList)
    {
        $data = [];

        $last = $this->plotingPremiRepository->getLast();
        $lastId = $last ? $last->id : 0;

        foreach ($plotingList as $ploting) {
            $kode = 'PLT' . str_pad(++$lastId, 3, '0', STR_PAD_LEFT);

            $data[] = [
                'kode' => $kode,
                'ploting' => $ploting,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->plotingPremiRepository->create($data);
    }

    public function findById($id)
    {
        return $this->plotingPremiRepository->findById($id);
    }

    public function update($id, array $data)
    {
        return $this->plotingPremiRepository->update($id, [
            'ploting' => $data['ploting'],
        ]);
    }

    public function delete($id)
    {
        return $this->plotingPremiRepository->delete($id);
    }
}
