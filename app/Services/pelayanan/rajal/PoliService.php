<?php

namespace App\Services\pelayanan\rajal;

use App\Repositories\pelayanan\rajal\PoliRepository;

class PoliService
{
    protected $poliRepository;

    public function __construct(PoliRepository $poliRepository)
    {
        $this->poliRepository = $poliRepository;
    }

    public function getPoliData()
    {
        return $this->poliRepository->getAllPoli();
    }
    public function getPasien($kd_poli, $kd_sps)
    {
        return $this->poliRepository->getPasien($kd_poli, $kd_sps);
    }

    public function getDokter($kd_poli)
    {
        return $this->poliRepository->getDokter($kd_poli);
    }

    public function getDataPasien()
    {
        return $this->poliRepository->getDataPasien();
    }

    public function getDataPasienRawatInap($tgl1, $tgl2)
    {
        return $this->poliRepository->getDataPasienRanap($tgl1, $tgl2);
    }

    public function getDataPasienIgd()
    {
        return $this->poliRepository->getDataPasienIgd();
    }

}
