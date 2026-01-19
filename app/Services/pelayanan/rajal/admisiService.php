<?php

namespace App\Services\pelayanan\rajal;

use App\Repositories\pelayanan\rajal\AdmisiRepository;

class admisiService
{
    protected $admisiRepository;

    public function __construct(AdmisiRepository $admisiRepository)
    {
        $this->admisiRepository = $admisiRepository;
    }

    public function generateNomorAntrian()
    {
        return $this->admisiRepository->generateNomorAntrian();
    }

}
