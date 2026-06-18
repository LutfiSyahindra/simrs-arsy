<?php

namespace App\Repositories\mappingData;

use App\Models\dbKhanza\DetailTindakanLabModel;
use App\Models\dbKhanza\PenjaminModel;
use App\Models\dbKhanza\TindakanFarmasiModel;
use App\Models\dbKhanza\TindakanLabModel;
use App\Models\dbKhanza\TindakanOperasiModel;
use App\Models\dbKhanza\TindakanRadModel;
use App\Models\dbKhanza\TindakanRajalModel;
use App\Models\dbKhanza\TindakanRanapModel;
use App\Models\dbSimrs\jnsTindakanModel;
use App\Models\dbSimrs\mappingTindakanModel;
use Illuminate\Support\Facades\DB;

class tindakanMappingRepository
{
    public const SOURCE_LABELS = [
        'LEGACY' => 'Data Lama',
        'RAJAL' => 'Rawat Jalan',
        'RANAP' => 'Rawat Inap',
        'LAB' => 'Laboratorium',
        'LAB_DETAIL' => 'Detail Laboratorium',
        'RAD' => 'Radiologi',
        'OPERASI' => 'Operasi',
        'FARMASI' => 'Farmasi',
    ];

    public const SEARCHABLE_SOURCES = [
        'RAJAL',
        'RANAP',
        'LAB',
        'LAB_DETAIL',
        'RAD',
        'OPERASI',
        'FARMASI',
    ];

    protected $tindakanRajalModel, $tindakanRanapModel, $tindakanLabModel, $tindakanRadModel, $detailTindakanLabModel, $tindakanOperasiModel, $tindakanFarmasiModel, $penjaminModel, $mappingTindakanModel, $masterJnsTindakanModel;

    public function __construct(TindakanRajalModel $TindakanRajalModel, TindakanRanapModel $TindakanRanapModel, TindakanLabModel $TindakanLabModel, TindakanRadModel $TindakanRadModel, DetailTindakanLabModel $DetailTindakanLabModel, TindakanOperasiModel $TindakanOperasiModel, TindakanFarmasiModel $TindakanFarmasiModel, PenjaminModel $PenjaminModel, mappingTindakanModel $MappingTindakanModel, jnsTindakanModel $MasterJnsTindakanModel)

    {
        $this->tindakanRajalModel = $TindakanRajalModel;
        $this->tindakanRanapModel = $TindakanRanapModel;
        $this->tindakanLabModel = $TindakanLabModel;
        $this->tindakanRadModel = $TindakanRadModel;
        $this->detailTindakanLabModel = $DetailTindakanLabModel;
        $this->tindakanOperasiModel = $TindakanOperasiModel;
        $this->tindakanFarmasiModel = $TindakanFarmasiModel;
        $this->penjaminModel = $PenjaminModel;
        $this->mappingTindakanModel = $MappingTindakanModel;
        $this->masterJnsTindakanModel = $MasterJnsTindakanModel;
    }

    public function getTindakanRajal(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('RAJAL', $keyword, [], $limit);
    }

    public function getTindakanRanap(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('RANAP', $keyword, [], $limit);
    }

    public function getTindakanLab(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('LAB', $keyword, [], $limit);
    }

    public function getTindakanRad(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('RAD', $keyword, [], $limit);
    }

    public function getDetailTindakanLab(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('LAB_DETAIL', $keyword, [], $limit);
    }

    public function getTindakanOperasi(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('OPERASI', $keyword, [], $limit);
    }

    public function getTindakanFarmasi(string $keyword, int $limit = 15)
    {
        return $this->fetchSourceRows('FARMASI', $keyword, [], $limit);
    }

    public function getMasterJnsTindakan()
    {
        return $this->masterJnsTindakanModel::select('id', 'kode', 'jenis')
            ->orderBy('jenis')
            ->get();
    }

    public function getJenisTindakanWithCounts()
    {
        return $this->masterJnsTindakanModel::select('id', 'kode', 'jenis')
            ->withCount(['mappingTindakan as jumlah_tindakan'])
            ->orderBy('jenis')
            ->get();
    }

    public function findJenisById($id)
    {
        return $this->masterJnsTindakanModel::find($id);
    }

    public function getMappingTindakan()
    {
        return $this->mappingTindakanModel::select('id', 'jnsTindakan_id', 'sumber_tindakan', 'kd_tindakan', 'nm_tindakan', 'kd_pj', 'nm_pj', 'parent_kd_tindakan', 'parent_nm_tindakan')
            ->with(['jnsTindakan:id,kode,jenis'])
            ->get();
    }

    public function getMappingsByJenis($jenisId)
    {
        return $this->mappingTindakanModel::select('id', 'jnsTindakan_id', 'sumber_tindakan', 'kd_tindakan', 'nm_tindakan', 'kd_pj', 'nm_pj', 'parent_kd_tindakan', 'parent_nm_tindakan')
            ->where('jnsTindakan_id', $jenisId)
            ->orderBy('sumber_tindakan')
            ->orderBy('parent_nm_tindakan')
            ->orderBy('nm_tindakan')
            ->get();
    }

    public function findById($id)
    {
        return $this->mappingTindakanModel::with('jnsTindakan:id,kode,jenis')->find($id);
    }

    public function existsMapping(int $jenisId, string $source, string $kodeTindakan, $exceptId = null)
    {
        return $this->mappingTindakanModel::where('jnsTindakan_id', $jenisId)
            ->where('sumber_tindakan', $source)
            ->where('kd_tindakan', $kodeTindakan)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    public function insert(array $data)
    {
        return $this->mappingTindakanModel::insert($data);
    }

    public function update($id, array $data)
    {
        $row = $this->mappingTindakanModel::findOrFail($id);
        $row->update($data);

        return $row->fresh('jnsTindakan:id,kode,jenis');
    }

    public function delete($id)
    {
        return $this->mappingTindakanModel::where('id', $id)->delete();
    }

    public function searchTindakan(string $keyword, ?string $source = null, int $limit = 500)
    {
        $keyword = trim($keyword);

        if (mb_strlen($keyword) < 2) {
            return collect();
        }

        $sources = $this->normalizeSearchSources($source);
        $perSourceLimit = $source
            ? $limit
            : max(500, (int) ceil($limit / count($sources)) + 2);
        $rows = collect();

        foreach ($sources as $sourceName) {
            $rows = $rows->merge($this->fetchSourceRows($sourceName, $keyword, [], $perSourceLimit));
        }

        return $rows
            ->sortBy(fn ($item) => strtolower($item['nm_tindakan']))
            ->take($limit)
            ->values();
    }

    public function findTindakanByKeys(array $sourceKeys)
    {
        $groups = collect($sourceKeys)
            ->map(function ($sourceKey) {
                $parts = explode(':', (string) $sourceKey, 2);

                if (count($parts) !== 2 || !isset(self::SOURCE_LABELS[$parts[0]]) || trim($parts[1]) === '') {
                    return null;
                }

                return [
                    'source' => $parts[0],
                    'kode' => $parts[1],
                    'source_key' => $parts[0] . ':' . $parts[1],
                ];
            })
            ->filter()
            ->unique('source_key')
            ->groupBy('source');

        $rows = collect();

        foreach ($groups as $source => $items) {
            $rows = $rows->merge(
                $this->fetchSourceRows($source, null, $items->pluck('kode')->values()->toArray())
            );
        }

        return $rows->keyBy('source_key');
    }

    public function sourceLabel(string $source)
    {
        return self::SOURCE_LABELS[$source] ?? $source;
    }

    protected function normalizeSearchSources(?string $source): array
    {
        $source = strtoupper(trim((string) $source));

        if ($source !== '' && in_array($source, self::SEARCHABLE_SOURCES, true)) {
            return [$source];
        }

        return self::SEARCHABLE_SOURCES;
    }

    protected function fetchSourceRows(string $source, ?string $keyword = null, array $codes = [], int $limit = 15)
    {
        $query = $this->sourceQuery($source);

        if (!$query) {
            return collect();
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $this->applySearch($query, $this->sourceSearchColumns($source), $keyword);
        }

        if (!empty($codes)) {
            $query->whereIn($this->sourceCodeColumn($source), $codes);
        }

        if (empty($codes)) {
            $query->limit($limit);
        }

        return $this->normalizeTindakanRows($query->get());
    }

    protected function khanza()
    {
        return DB::connection($this->tindakanRajalModel->getConnectionName());
    }

    protected function sourceQuery(string $source)
    {
        $db = $this->khanza();
        $penjabTable = $this->penjaminModel->getTable();

        return match ($source) {
            'RAJAL' => $db->table($this->tindakanRajalModel->getTable() . ' as t')
                ->leftJoin($penjabTable . ' as pj', 'pj.kd_pj', '=', 't.kd_pj')
                ->where('t.status', '1')
                ->select([
                    DB::raw("'RAJAL' as sumber_tindakan"),
                    't.kd_jenis_prw as kd_tindakan',
                    't.nm_perawatan as nm_tindakan',
                    't.kd_pj',
                    'pj.png_jawab as nm_pj',
                    DB::raw('null as parent_kd_tindakan'),
                    DB::raw('null as parent_nm_tindakan'),
                ]),
            'RANAP' => $db->table($this->tindakanRanapModel->getTable() . ' as t')
                ->leftJoin($penjabTable . ' as pj', 'pj.kd_pj', '=', 't.kd_pj')
                ->where('t.status', '1')
                ->select([
                    DB::raw("'RANAP' as sumber_tindakan"),
                    't.kd_jenis_prw as kd_tindakan',
                    't.nm_perawatan as nm_tindakan',
                    't.kd_pj',
                    'pj.png_jawab as nm_pj',
                    DB::raw('null as parent_kd_tindakan'),
                    DB::raw('null as parent_nm_tindakan'),
                ]),
            'LAB' => $db->table($this->tindakanLabModel->getTable() . ' as t')
                ->leftJoin($penjabTable . ' as pj', 'pj.kd_pj', '=', 't.kd_pj')
                ->where('t.status', '1')
                ->select([
                    DB::raw("'LAB' as sumber_tindakan"),
                    't.kd_jenis_prw as kd_tindakan',
                    't.nm_perawatan as nm_tindakan',
                    't.kd_pj',
                    'pj.png_jawab as nm_pj',
                    DB::raw('null as parent_kd_tindakan'),
                    DB::raw('null as parent_nm_tindakan'),
                ]),
            'LAB_DETAIL' => $db->table($this->detailTindakanLabModel->getTable() . ' as d')
                ->join($this->tindakanLabModel->getTable() . ' as l', 'l.kd_jenis_prw', '=', 'd.kd_jenis_prw')
                ->leftJoin($penjabTable . ' as pj', 'pj.kd_pj', '=', 'l.kd_pj')
                ->where('l.status', '1')
                ->select([
                    DB::raw("'LAB_DETAIL' as sumber_tindakan"),
                    'd.id_template as kd_tindakan',
                    'd.Pemeriksaan as nm_tindakan',
                    'l.kd_pj',
                    'pj.png_jawab as nm_pj',
                    'l.kd_jenis_prw as parent_kd_tindakan',
                    'l.nm_perawatan as parent_nm_tindakan',
                ]),
            'RAD' => $db->table($this->tindakanRadModel->getTable() . ' as t')
                ->leftJoin($penjabTable . ' as pj', 'pj.kd_pj', '=', 't.kd_pj')
                ->where('t.status', '1')
                ->select([
                    DB::raw("'RAD' as sumber_tindakan"),
                    't.kd_jenis_prw as kd_tindakan',
                    't.nm_perawatan as nm_tindakan',
                    't.kd_pj',
                    'pj.png_jawab as nm_pj',
                    DB::raw('null as parent_kd_tindakan'),
                    DB::raw('null as parent_nm_tindakan'),
                ]),
            'OPERASI' => $db->table($this->tindakanOperasiModel->getTable() . ' as t')
                ->where('t.status', '1')
                ->select([
                    DB::raw("'OPERASI' as sumber_tindakan"),
                    't.kode_paket as kd_tindakan',
                    't.nm_perawatan as nm_tindakan',
                    DB::raw('null as kd_pj'),
                    DB::raw('null as nm_pj'),
                    DB::raw('null as parent_kd_tindakan'),
                    't.kategori as parent_nm_tindakan',
                ]),
            'FARMASI' => $db->table($this->tindakanFarmasiModel->getTable() . ' as t')
                ->where('t.status', '1')
                ->select([
                    DB::raw("'FARMASI' as sumber_tindakan"),
                    't.kode_brng as kd_tindakan',
                    't.nama_brng as nm_tindakan',
                    DB::raw('null as kd_pj'),
                    DB::raw('null as nm_pj'),
                    DB::raw('null as parent_kd_tindakan'),
                    DB::raw('null as parent_nm_tindakan'),
                ]),
            default => null,
        };
    }

    protected function sourceSearchColumns(string $source)
    {
        return match ($source) {
            'LAB_DETAIL' => ['d.id_template', 'd.Pemeriksaan', 'l.kd_jenis_prw', 'l.nm_perawatan', 'pj.png_jawab'],
            'OPERASI' => ['t.kode_paket', 't.nm_perawatan', 't.kategori'],
            'FARMASI' => ['t.kode_brng', 't.nama_brng'],
            default => ['t.kd_jenis_prw', 't.nm_perawatan', 'pj.png_jawab'],
        };
    }

    protected function sourceCodeColumn(string $source)
    {
        return match ($source) {
            'LAB_DETAIL' => 'd.id_template',
            'OPERASI' => 't.kode_paket',
            'FARMASI' => 't.kode_brng',
            default => 't.kd_jenis_prw',
        };
    }

    protected function applySearch($query, array $columns, string $keyword)
    {
        $like = '%' . trim($keyword) . '%';

        return $query->where(function ($where) use ($columns, $like) {
            foreach ($columns as $column) {
                $where->orWhere($column, 'like', $like);
            }
        });
    }

    protected function normalizeTindakanRows($rows)
    {
        return collect($rows)->map(function ($row) {
            $source = (string) $row->sumber_tindakan;
            $kode = (string) $row->kd_tindakan;
            $parentKode = $row->parent_kd_tindakan ? (string) $row->parent_kd_tindakan : null;
            $parentNama = $row->parent_nm_tindakan ? (string) $row->parent_nm_tindakan : null;

            return [
                'source_key' => $source . ':' . $kode,
                'sumber_tindakan' => $source,
                'sumber_label' => $this->sourceLabel($source),
                'kd_tindakan' => $kode,
                'nm_tindakan' => (string) $row->nm_tindakan,
                'kd_pj' => $row->kd_pj ? (string) $row->kd_pj : null,
                'nm_pj' => $row->nm_pj ? (string) $row->nm_pj : null,
                'parent_kd_tindakan' => $parentKode,
                'parent_nm_tindakan' => $parentNama,
            ];
        });
    }
}
