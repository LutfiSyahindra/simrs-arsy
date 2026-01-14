<?php

namespace App\Repositories\Settings\Auth;

use App\Models\User;
use Spatie\Permission\Models\Role;

class UserRepository
{
    public function getData(){
        $dataUser = User::all();
        return $dataUser;
    }

    public function updateStatus($id, $status){
        $user = User::find($id);
        $user->status = $status;
        $user->save();
    }

    public function createUser($data){
        return User::create($data);
    }

    public function findById($id)
    {
        $data = User::find($id);
        return $data;
    }

    public function getRolesByIds(array $ids)
    {
        return Role::whereIn('id', $ids)->get();
    }

    public function syncRoles($user, array $roles)
    {
        return $user->syncRoles($roles);
    }

    public function getUsersRoles($userId)
    {
        $user = User::with('roles')->findOrFail($userId);
        return $user->roles;
    }

}
