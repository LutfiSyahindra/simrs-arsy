<?php

namespace App\Http\Controllers\simrs\Settings\Auth;

use App\Http\Controllers\Controller;
use App\Services\Settings\Auth\PermissionsService;
use App\Services\Settings\Auth\RolesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    protected $RolesService, $PermissionsService;
    public function __construct(RolesService $RolesService, PermissionsService $PermissionsService)
    {
        $this->PermissionsService = $PermissionsService;
        $this->RolesService = $RolesService;
    }
    /**
     * Display a listing of the resource.
     */
    public function Role()
    {
        return view('simrs.settings.auth.roles.roles');
    }

    public function table()
    {
        $Roles = $this->RolesService->getRoles();

        return DataTables::of($Roles)
        ->addIndexColumn()
        ->addColumn('actions', function ($dataRoles) {
            return '
                <button class="btn btn-sm btn-success" onclick="editRoles(' . $dataRoles['id'] . ')"> 
                    <i class="mdi mdi-pencil"></i>
                </button> 
                <button class="btn btn-sm btn-danger" onclick="deleteRoles(' . $dataRoles['id'] . ')">  
                    <i class="mdi mdi-delete"></i>
                </button>
                <button class="btn btn-sm btn-warning" onclick="assignPermissions(' . $dataRoles['id'] . ')">  
                    <i class="mdi mdi-shield-key"></i>
                </button>
            ';
        })

        ->rawColumns(['actions'])
        ->make(true);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
        ]);

        Log::info($validated);

        $roles = $this->RolesService->createRoles($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dibuat',
            'data' => $roles
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $dataRoles = $this->RolesService->findRole($id);
        return response()->json($dataRoles);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ]);

        // Panggil service untuk update
        $Roles = $this->RolesService->update($id, $validated);

        if (!$Roles) {
            return response()->json([
                'message' => 'Roles tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Roles berhasil diperbarui.',
            'data' => $Roles
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->RolesService->destroy($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Roles berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            // Tangani jika terjadi kesalahan
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dataPermissions(){
        $Permissions = $this->PermissionsService->getPermissions();
        return response()->json($Permissions);
    }

    public function assignPermissions(Request $request){
        $validated = $request->validate([
            'rolessId' => 'required|exists:roles,id',
            'permissions_id' => 'required|array',
            'permissions_id.*' => 'exists:permissions,id',
        ]);
        Log::info($validated);

        $this->RolesService->assignPermissionsToRole(
            $validated['rolessId'],
            $validated['permissions_id']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions berhasil di-assign ke role.'
        ]);
    }   

    public function getRolePermissions($roleId)
    {
        $permissions = $this->RolesService->getPermissionsByRole($roleId);

        return response()->json([
            'status' => true,
            'data' => $permissions->pluck('id') // hanya kirim array ID
        ]);
    }

}
