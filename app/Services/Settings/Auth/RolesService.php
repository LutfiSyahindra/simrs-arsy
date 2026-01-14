<?php

namespace App\Services\Settings\Auth;

use App\Repositories\Settings\Auth\RolesRepository;
use Spatie\Permission\Models\Permission;

class RolesService
{
    protected $RolesRepository;

    public function __construct(RolesRepository $RolesRepository)
    {
        $this->RolesRepository = $RolesRepository;
    }
    /**
     * Create a new class instance.
     */
    public function getRoles()
    {
        $Roles = $this->RolesRepository->getRoles();

        $dataRoles = [];
        foreach ($Roles as $r) {
            $dataRoles[] = [
                'id'        => $r->id,
                'name'      => $r->name,
            ];
        }

        return $dataRoles;
    }

    public function createRoles($data){
        return $this->RolesRepository->createRoles($data);
    }

    public function findRole($id){
        return $this->RolesRepository->findRole($id);
    }

    public function update($id, $data)
    {
        $Roles = $this->RolesRepository->findRole($id);

        if (!$Roles) {
            return null; // atau bisa lempar exception
        }

        $Roles->update($data);

        return $Roles;
    }

    public function destroy($id)
    {
        $Roles = $this->RolesRepository->findRole($id);
        $Roles->delete();
    }

    public function assignPermissionsToRole($roleId, array $permissionIds)
    {
        $role = $this->RolesRepository->findRole($roleId);
        $permissions = $this->RolesRepository->getPermissionsByIds($permissionIds);

        return $this->RolesRepository->syncPermissions($role, $permissions);
    }

    public function getPermissionsByRole($roleId)
    {
        return $this->RolesRepository->getRolePermissions($roleId);
    }

}
