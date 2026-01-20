<?php

namespace App\Services\pelayanan\rajal;

use App\Repositories\pelayanan\rajal\LoketAdmisiRepository;

class LoketAdmisiService
{
    protected $LoketAdmisiRepository;

    public function __construct(LoketAdmisiRepository $LoketAdmisiRepository)
    {
        $this->LoketAdmisiRepository = $LoketAdmisiRepository;
    }

    public function getAllLoket()
    {
        return $this->LoketAdmisiRepository->getAllLoket();
    }

    public function lockLoket($id, $petugas = null)
    {
        return $this->LoketAdmisiRepository->lockLoket($id, $petugas);
    }

    public function unlockLoket($id)
    {
        return $this->LoketAdmisiRepository->unlockLoket($id);
    }

}
