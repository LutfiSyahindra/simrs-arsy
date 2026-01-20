<?php

namespace App\Services\pelayanan\rajal;

use App\Repositories\pelayanan\rajal\AdmisiPanggilRepository;

class AdmisiPanggilService
{
    protected $AdmisiPanggilRepository;

    public function __construct(AdmisiPanggilRepository $AdmisiPanggilRepository)
    {
        $this->AdmisiPanggilRepository = $AdmisiPanggilRepository;
    }

    public function getAllAdmisi()
    {
        return $this->AdmisiPanggilRepository->getAllAdmisi();
    }

    public function updateStatusPanggil($id)
    {
        return $this->AdmisiPanggilRepository->updateStatusPanggil($id);
    }

    public function updateLoket($id, $loket)
    {
        return $this->AdmisiPanggilRepository->updateLoket($id, $loket);
    }
        
}
