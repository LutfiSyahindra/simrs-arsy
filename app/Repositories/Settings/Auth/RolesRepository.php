<?php

namespace App\Repositories\Settings\Auth;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesRepository
{
    /**
     * Create a new class instance.
     */
    public function getRoles()
    {
        $dataRole = Role::all();
        return $dataRole;
    }

    public function createRoles($data){
        return Role::create($data);
    }

    public function findRole($id){
        return Role::find($id);
    }

    public function getPermissionsByIds(array $ids)
    {
        return Permission::whereIn('id', $ids)->get();
    }

    public function syncPermissions($role, $permissions)
    {
        return $role->syncPermissions($permissions);
    }

    public function getRolePermissions($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        return $role->permissions;
    }

    
}
