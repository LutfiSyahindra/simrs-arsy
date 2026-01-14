<?php

namespace App\Services\Settings\Auth;

use App\Repositories\Settings\Auth\UserRepository;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function getData(){
        return $this->userRepository->getData();
    }

    public function updateStatus($id, $status){
        return $this->userRepository->updateStatus($id, $status);
    }

    public function create($data)
    {
        return $this->userRepository->createUser($data);
    }

    public function findById($id){
        return $this->userRepository->findById($id);
    }
    
    public function update($id, $data)
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return null; // atau bisa lempar exception
        }

        $user->update($data);

        return $user;
    }

    public function destroy($id){
        $user = $this->userRepository->findById($id);
        $user->delete();
    }

    public function assignRolesToUsers($userId, array $roleIds)
    {
        $user = $this->userRepository->findById($userId);

        // Ambil role berdasarkan ID, lalu hanya ambil nama
        $roles = $this->userRepository->getRolesByIds($roleIds)->pluck('name')->toArray();

        return $this->userRepository->syncRoles($user, $roles);
    }

    public function getUsersRoles($userId)
    {
        return $this->userRepository->getUsersRoles($userId);
    }
}
