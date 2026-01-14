<?php

namespace App\Http\Controllers\simrs\Settings\Auth;

use App\Http\Controllers\Controller;
use App\Services\Settings\Auth\RolesService;
use App\Services\Settings\Auth\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class UsersController extends Controller
{
    protected $UserService, $RolesService;
    public function __construct(UserService $UserService, RolesService $RolesService)
    {
        $this->UserService = $UserService;
        $this->RolesService = $RolesService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('simrs.settings.auth.users.users');
    }

    public function table()
    {
        $Users = $this->UserService->getData();
        $dataUsers = [];
        foreach ($Users as $r) {
            $dataUsers[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'branch_id' => $r['branch_id']??'',
                'status' => $r['status'],
            ];
        }

        return DataTables::of($dataUsers)
        ->addIndexColumn()
        ->addColumn('actions', function ($dataUsers) {
            return '
                <button class="btn btn-sm btn-success" onclick="editUsers(' . $dataUsers['id'] . ')"> 
                    <i class="mdi mdi-pencil"></i>
                </button> 
                <button class="btn btn-sm btn-danger" onclick="deleteUsers(' . $dataUsers['id'] . ')">  
                    <i class="mdi mdi-delete"></i>
                </button>
                <button class="btn btn-sm btn-warning" onclick="assignRoles(' . $dataUsers['id'] . ')">  
                    <i class="mdi mdi-eye"></i>
                </button>
            ';
        })

        ->rawColumns(['actions'])
        ->make(true);

    }

    public function updateStatus(Request $request){
        return $this->UserService->updateStatus($request->id, $request->status);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        Log::info($validated);

        $user = $this->UserService->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dibuat',
            'data' => $user
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
        $dataUsers = $this->UserService->findById($id);
        return response()->json($dataUsers);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
        ]);

        // Jika password tidak diisi, hapus dari array agar tidak overwrite
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Panggil service untuk update
        $user = $this->UserService->update($id, $validated);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diperbarui.',
            'data' => $user
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->UserService->destroy($id);
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            // Tangani jika terjadi kesalahan
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dataRoles(){
        $Roles = $this->RolesService->getRoles();
        return response()->json($Roles);
    }

    public function assignRoles(Request $request)
    {
        $validated = $request->validate([
            'userssId' => 'required|exists:users,id',
            'roles_id' => 'required|array',
            'roles_id.*' => 'exists:roles,id',
        ]);
        Log::info($validated);

        $this->UserService->assignRolesToUsers(
            $validated['userssId'],
            $validated['roles_id']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions berhasil di-assign ke role.'
        ]);
    }

    public function getUserRoles($userId)
    {
        $Roles = $this->UserService->getUsersRoles($userId);

        return response()->json([
            'status' => true,
            'data' => $Roles->pluck('id') // hanya kirim array ID
        ]);
    }
}
