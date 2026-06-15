<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generateBhpDetailModel;
use App\Models\dbSimrs\generateBhpModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateBhpRepository
{
    public function getEligibleByType(string $periode, string $jenisBhp): Collection
    {
        $sourcePeriod = PremiSourcePeriod::resolve($periode, $jenisBhp);

        return $jenisBhp === 'bpjs'
            ? $this->getEligibleBpjsByPeriod($sourcePeriod)
            : $this->getEligibleUmumByPeriod($sourcePeriod);
    }

    public function getEligibleUmumByPeriod(string $periode): Collection
    {
        return $this->baseEligibleQuery($periode)
            ->whereNotIn('rp.kd_pj', ['BPJ', '-'])
            ->where(function ($query) {
                $query
                    ->where(function ($nonA09) {
                        $nonA09
                            ->where('rp.kd_pj', '!=', 'A09')
                            ->whereExists(function ($piutang) {
                                $piutang
                                    ->selectRaw('1')
                                    ->from('piutang_pasien as pp')
                                    ->whereColumn('pp.no_rawat', 'rp.no_rawat')
                                    ->where('pp.status', 'Belum Lunas');
                            });
                    })
                    ->orWhere(function ($a09) {
                        $a09
                            ->where('rp.kd_pj', 'A09');
                    });
            })
            ->orderBy('rp.tgl_registrasi')
            ->orderBy('rp.no_rawat')
            ->get();
    }

    public function getEligibleBpjsByPeriod(string $periode): Collection
    {
        return $this->baseEligibleQuery($periode)
            ->where('rp.kd_pj', 'BPJ')
            ->whereExists(function ($piutang) {
                $piutang
                    ->selectRaw('1')
                    ->from('piutang_pasien as pp')
                    ->whereColumn('pp.no_rawat', 'rp.no_rawat')
                    ->where('pp.status', 'Belum Lunas');
            })
            ->orderBy('rp.tgl_registrasi')
            ->orderBy('rp.no_rawat')
            ->get();
    }

    private function baseEligibleQuery(string $periode)
    {
        $range = PremiSourcePeriod::range($periode, 'umum');

        return DB::connection('mysql_khanza')
            ->table('reg_periksa as rp')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->select([
                'rp.no_rawat',
                'rp.tgl_registrasi',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
            ])
            ->where('rp.status_lanjut', 'Ranap')
            ->where('rp.tgl_registrasi', '>=', $range['start']->toDateString())
            ->where('rp.tgl_registrasi', '<', $range['end']->toDateString());
    }

    public function getResults(?string $periode = null, ?string $jenisBhp = null): Collection
    {
        return generateBhpModel::query()
            ->with('lockedBy:id,name')
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisBhp, fn ($query) => $query->where('jenis_bhp', $jenisBhp))
            ->orderByDesc('periode')
            ->orderBy('jenis_bhp')
            ->get();
    }

    public function findByPeriodAndType(string $periode, string $jenisBhp): ?generateBhpModel
    {
        return generateBhpModel::query()
            ->where('periode', $periode)
            ->where('jenis_bhp', $jenisBhp)
            ->first();
    }

    public function findByPeriodAndTypeForUpdate(
        string $periode,
        string $jenisBhp
    ): ?generateBhpModel {
        return generateBhpModel::query()
            ->where('periode', $periode)
            ->where('jenis_bhp', $jenisBhp)
            ->lockForUpdate()
            ->first();
    }

    public function findForUpdate(int $id): ?generateBhpModel
    {
        return generateBhpModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function saveResult(
        string $periode,
        string $jenisBhp,
        int $jumlah,
        int $nominal
    ): generateBhpModel {
        return generateBhpModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_bhp' => $jenisBhp,
            ],
            [
                'jumlah_bhp' => $jumlah,
                'nominal_hitung' => $nominal,
                'total_bhp' => $jumlah * $nominal,
                'generate_by' => Auth::user()->id,
            ]
        );
    }

    public function replaceDetails(generateBhpModel $generateBhp, Collection $details): void
    {
        $generateBhp->details()->delete();
        $now = now();

        $details
            ->map(fn ($detail) => [
                'generate_bhp_id' => $generateBhp->id,
                'no_rawat' => $detail->no_rawat,
                'tgl_registrasi' => $detail->tgl_registrasi,
                'kd_pj' => $detail->kd_pj,
                'nama_penjamin' => $detail->nama_penjamin,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(500)
            ->each(fn ($chunk) => generateBhpDetailModel::query()->insert($chunk->all()));
    }

    public function findWithDetails(int $id): ?generateBhpModel
    {
        return generateBhpModel::query()
            ->with('lockedBy:id,name')
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('tgl_registrasi')
                    ->orderBy('no_rawat'),
            ])
            ->find($id);
    }

    public function updateLock(
        generateBhpModel $generateBhp,
        bool $isLocked,
        ?int $userId = null
    ): generateBhpModel {
        DB::table('generate_bhp')
            ->where('id', $generateBhp->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateBhpModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($generateBhp->id);
    }

    public function getPenjaminNames(Collection $codes): Collection
    {
        return DB::connection('mysql_khanza')
            ->table('penjab')
            ->whereIn('kd_pj', $codes->filter()->unique()->values())
            ->pluck('png_jawab', 'kd_pj');
    }
}
