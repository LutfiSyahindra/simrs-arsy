<?php


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\simrs\Settings\Auth\PermissionsController;
use App\Http\Controllers\simrs\Settings\Auth\RoleController;
use App\Http\Controllers\simrs\Settings\Auth\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('simrs/dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('simrs/settings')->group(function () {
        // Users
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::get('/users/tableUsers', [UsersController::class, 'table'])->name('users.table');
        Route::put('/users/updateStatus', [UsersController::class, 'updateStatus'])->name('users.updateStatus');
        Route::get('/users/getBranches', [UsersController::class, 'getBranches'])->name('users.getBranches');
        Route::post('/users/store', [UsersController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}/update', [UsersController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}/delete', [UsersController::class, 'destroy'])->name('users.delete');
        Route::get('/users/dataRoles', [UsersController::class, 'dataRoles'])->name('users.dataRoles');
        Route::post('/users/assignRoles', [UsersController::class, 'assignRoles'])->name('users.assignRoles');
        Route::get('/users/{id}/getUserRoles', [UsersController::class, 'getUserRoles'])->name('users.getUserRoles');

        // Role
        Route::get('/roles', [RoleController::class, 'Role'])->name('roles.role');
        Route::get('/roles/tableRoles', [RoleController::class, 'table'])->name('roles.table');
        Route::post('/roles/store', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}/update', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}/delete', [RoleController::class, 'destroy'])->name('roles.delete');
        Route::get('/roles/dataPermissions', [RoleController::class, 'dataPermissions'])->name('roles.dataPermissions');
        Route::post('/roles/assignPermissions', [RoleController::class, 'assignPermissions'])->name('roles.assignPermissions');
        Route::get('/roles/{id}/getRolePermissions', [RoleController::class, 'getRolePermissions'])->name('roles.getRolePermissions');

        // Permission
        Route::get('/permissions', [PermissionsController::class, 'Permissions'])->name('permissions.permissions');
        Route::get('/permissions/tablePermissions', [PermissionsController::class, 'table'])->name('permissions.table');
        Route::post('/permissions/store', [PermissionsController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{id}/edit', [PermissionsController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{id}/update', [PermissionsController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{id}/delete', [PermissionsController::class, 'destroy'])->name('permissions.delete');
    });

require __DIR__.'/auth.php';
