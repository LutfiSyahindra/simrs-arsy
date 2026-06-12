<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generateKamarDetailModel;
use App\Models\dbSimrs\generateKamarModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateKamarRepository
{
    public function getEligibleByType(string $periode, string $jenisKamar): Collection
    {
        return $jenisKamar === 'bpjs'
            ? $this->getEligibleBpjsByPeriod($periode)
            : $this->getEligibleUmumByPeriod($periode);
    }

    public function getEligibleUmumByPeriod(string $periode): Collection
    {
        return $this->baseEligibleQuery($periode)
            ->whereNotIn('rp.kd_pj', ['BPJ', '-', ''])
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
                            ->where('rp.kd_pj', 'A09')
                            ->where('rp.status_bayar', 'Sudah Bayar');
                    });
            })
            ->orderBy('ki.tgl_masuk')
            ->orderBy('ki.jam_masuk')
            ->orderBy('ki.no_rawat')
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
            ->orderBy('ki.tgl_masuk')
            ->orderBy('ki.jam_masuk')
            ->orderBy('ki.no_rawat')
            ->get();
    }

    private function baseEligibleQuery(string $periode)
    {
        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $end = $start->copy()->addMonth();

        return DB::connection('mysql_khanza')
            ->table('kamar_inap as ki')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'ki.no_rawat')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->select([
                'ki.no_rawat',
                'ki.tgl_masuk',
                'ki.jam_masuk',
                'ki.kd_kamar',
                'ki.lama',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
            ])
            ->where('ki.tgl_masuk', '>=', $start->toDateString())
            ->where('ki.tgl_masuk', '<', $end->toDateString());
    }

    public function getResults(?string $periode = null, ?string $jenisKamar = null): Collection
    {
        return generateKamarModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisKamar, fn ($query) => $query->where('jenis_kamar', $jenisKamar))
            ->orderByDesc('periode')
            ->orderBy('jenis_kamar')
            ->get();
    }

    public function findByPeriodAndType(string $periode, string $jenisKamar): ?generateKamarModel
    {
        return generateKamarModel::query()
            ->with('lockedBy:id,name')
            ->where('periode', $periode)
            ->where('jenis_kamar', $jenisKamar)
            ->first();
    }

    public function findByPeriodAndTypeForUpdate(
        string $periode,
        string $jenisKamar
    ): ?generateKamarModel {
        return generateKamarModel::query()
            ->where('periode', $periode)
            ->where('jenis_kamar', $jenisKamar)
            ->lockForUpdate()
            ->first();
    }

    public function findForUpdate(int $id): ?generateKamarModel
    {
        return generateKamarModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function saveResult(
        string $periode,
        string $jenisKamar,
        int $jumlahKamar,
        int $jumlahLamaInap,
        int $nominal
    ): generateKamarModel {
        return generateKamarModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_kamar' => $jenisKamar,
            ],
            [
                'jumlah_kamar' => $jumlahKamar,
                'jumlah_lama_inap' => $jumlahLamaInap,
                'nominal_hitung' => $nominal,
                'total_lama_inap' => $jumlahLamaInap * $nominal,
                'generate_by' => Auth::id(),
            ]
        );
    }

    public function replaceDetails(generateKamarModel $generateKamar, Collection $details): void
    {
        $generateKamar->details()->delete();
        $now = now();

        $details
            ->map(fn ($detail) => [
                'generate_kamar_id' => $generateKamar->id,
                'no_rawat' => $detail->no_rawat,
                'tgl_masuk' => $detail->tgl_masuk,
                'jam_masuk' => $detail->jam_masuk,
                'kd_pj' => $detail->kd_pj,
                'nama_penjamin' => $detail->nama_penjamin,
                'kd_kamar' => $detail->kd_kamar,
                'lama' => (int) $detail->lama,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(500)
            ->each(fn ($chunk) => generateKamarDetailModel::query()->insert($chunk->all()));
    }

    public function findWithDetails(int $id): ?generateKamarModel
    {
        return generateKamarModel::query()
            ->with('lockedBy:id,name')
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('tgl_masuk')
                    ->orderBy('jam_masuk')
                    ->orderBy('no_rawat'),
            ])
            ->find($id);
    }

    public function updateLock(
        generateKamarModel $generateKamar,
        bool $isLocked,
        ?int $userId = null
    ): generateKamarModel {
        DB::table('generate_kamar_inap')
            ->where('id', $generateKamar->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateKamarModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($generateKamar->id);
    }

    public function getPenjaminNames(Collection $codes): Collection
    {
        return DB::connection('mysql_khanza')
            ->table('penjab')
            ->whereIn('kd_pj', $codes->filter()->unique()->values())
            ->pluck('png_jawab', 'kd_pj');
    }
}
