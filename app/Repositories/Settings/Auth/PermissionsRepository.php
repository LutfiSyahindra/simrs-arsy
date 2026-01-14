<?php

namespace App\Repositories\Settings\Auth;

use Spatie\Permission\Models\Permission;

class PermissionsRepository
{
    /**
     * Create a new class instance.
     */
    public function getPermissions()
    {
        $dataPermissions = Permission::all();
        return $dataPermissions;
    }

    public function createPermissions($data){
        return Permission::create($data);
    }

    public function findPermissions($id){
        return Permission::find($id);
    }
}
