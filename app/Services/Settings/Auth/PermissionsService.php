<?php

namespace App\Services\Settings\Auth;

use App\Repositories\Settings\Auth\PermissionsRepository;

class PermissionsService
{
    /**
     * Create a new class instance.
     */
    protected $PermissionsRepository;

    public function __construct(PermissionsRepository $PermissionsRepository)
    {
        $this->PermissionsRepository = $PermissionsRepository;
    }
    /**
     * Create a new class instance.
     */
    public function getPermissions()
    {
        $Permissions = $this->PermissionsRepository->getPermissions();

        $dataPermissions = [];
        foreach ($Permissions as $r) {
            $dataPermissions[] = [
                'id'        => $r->id,
                'name'      => $r->name,
            ];
        }

        return $dataPermissions;
    }

    public function createPermissions($data){
        return $this->PermissionsRepository->createPermissions($data);
    }

    public function findPermissions($id){
        return $this->PermissionsRepository->findPermissions($id);
    }

    public function update($id, $data)
    {
        $Permissions = $this->PermissionsRepository->findPermissions($id);

        if (!$Permissions) {
            return null; // atau bisa lempar exception
        }

        $Permissions->update($data);

        return $Permissions;
    }

    public function destroy($id){
        $Permissions = $this->PermissionsRepository->findPermissions($id);
        $Permissions->delete();
    }
}
