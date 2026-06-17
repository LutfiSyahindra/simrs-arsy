<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\plotingPremiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class plotingPremiController extends Controller
{
    protected $plotingPremiService;

    public function __construct(plotingPremiService $plotingPremiService)
    {
        $this->plotingPremiService = $plotingPremiService;
    }

    public function plotingPremi()
    {
        return view('simrs.masterData.Keuangan.plotingPremi.plotingPremi');
    }

    public function generateKodePlotingPremi()
    {
        return response()->json($this->plotingPremiService->generateKodePlotingPremi());
    }

    public function plotingPremiTable()
    {
        $data = $this->plotingPremiService->plotingPremiTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editPlotingPremi('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deletePlotingPremi('.$row['id'].')">
                        <i class="mdi mdi-trash-can"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'ploting' => 'required|array|min:1',
                'ploting.*' => [
                    'required',
                    'string',
                    'max:100',
                    'distinct',
                    Rule::unique('master_ploting_premi', 'ploting'),
                ],
            ], [
                'ploting.required' => 'Ploting Premi wajib ada',
                'ploting.*.required' => 'Ploting Premi tidak boleh kosong',
                'ploting.*.unique' => 'Ploting Premi sudah digunakan',
                'ploting.*.distinct' => 'Ploting Premi tidak boleh duplikat',
            ]);

            $result = $this->plotingPremiService->create($validated['ploting']);

            return response()->json([
                'status' => true,
                'message' => 'Ploting Premi berhasil disimpan',
                'data' => $result
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
    }

    public function edit(string $id)
    {
        $dataPlotingPremi = $this->plotingPremiService->findById($id);
        return response()->json($dataPlotingPremi);
    }

    public function update(Request $request, string $id)
    {
        try {
            $ploting = $request->input('ploting');
            $ploting = is_array($ploting) ? ($ploting[0] ?? null) : $ploting;

            $validated = validator([
                'ploting' => $ploting,
            ], [
                'ploting' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('master_ploting_premi', 'ploting')->ignore($id),
                ],
            ], [
                'ploting.required' => 'Ploting Premi wajib diisi',
                'ploting.string' => 'Ploting Premi harus berupa teks',
                'ploting.max' => 'Ploting Premi tidak boleh lebih dari 100 karakter',
                'ploting.unique' => 'Ploting Premi sudah digunakan',
            ])->validate();

            $result = $this->plotingPremiService->update($id, [
                'ploting' => $validated['ploting'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Ploting Premi berhasil diperbarui',
                'data' => $result
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {

            $this->plotingPremiService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data Ploting Premi berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
