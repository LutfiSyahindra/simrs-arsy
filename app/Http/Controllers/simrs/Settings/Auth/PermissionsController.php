<?php

namespace App\Http\Controllers\simrs\Settings\Auth;

use App\Http\Controllers\Controller;
use App\Services\Settings\Auth\PermissionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PermissionsController extends Controller
{
    protected $PermissionsService;
    public function __construct(PermissionsService $PermissionsService)
    {
        $this->PermissionsService = $PermissionsService;
    }
    /**
     * Display a listing of the resource.
     */
    public function permissions()
    {
        return view('simrs.settings.auth.permission.permission');
    }

    public function table()
    {
        $Permissions = $this->PermissionsService->getPermissions();

        return DataTables::of($Permissions)
        ->addIndexColumn()
        ->addColumn('actions', function ($dataPermissions) {
            return '
                <button class="btn btn-sm btn-success" onclick="editPermissions(' . $dataPermissions['id'] . ')"> 
                    <i class="mdi mdi-pencil"></i>
                </button> 
                <button class="btn btn-sm btn-danger" onclick="deletePermissions(' . $dataPermissions['id'] . ')">  
                    <i class="mdi mdi-delete"></i>
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
            'name' => 'required|string|max:100|unique:permissions,name',
        ]);

        Log::info($validated);

        $Permissions = $this->PermissionsService->createPermissions($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dibuat',
            'data' => $Permissions
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
        $dataPermissions = $this->PermissionsService->findPermissions($id);
        return response()->json($dataPermissions);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
        ]);

        // Panggil service untuk update
        $Permissions = $this->PermissionsService->update($id, $validated);

        if (!$Permissions) {
            return response()->json([
                'message' => 'Permissions tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions berhasil diperbarui.',
            'data' => $Permissions
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->PermissionsService->destroy($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Permissions berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            // Tangani jika terjadi kesalahan
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
