<?php

namespace App\Repositories\keuangan\premi;

use Illuminate\Support\Facades\DB;

class premiRepository
{

    private function validPerson($q, $field)
    {
        return $q->whereNotNull($field)
                ->where($field, '!=', '-')
                ->where($field, '!=', '');
    }

    private function filterPiutang($q, string $alias)
    {
        return $q->whereExists(function ($sub) use ($alias) {
            $sub->select(DB::raw(1))
                ->from('reg_periksa as rp')
                ->join('piutang_pasien as pp', 'pp.no_rawat', '=', 'rp.no_rawat')
                ->whereColumn('rp.no_rawat', "$alias.no_rawat")
                ->where('rp.status_bayar', 'Sudah Bayar')
                ->where('pp.status', 'Belum Lunas');
        });
    }

    private function filterLunasNonPiutang($q, string $alias)
    {
        return $q->whereExists(function ($sub) use ($alias) {
                $sub->select(DB::raw(1))
                    ->from('reg_periksa as rp')
                    ->whereColumn('rp.no_rawat', "$alias.no_rawat")
                    ->where('rp.status_bayar', 'Sudah Bayar');
            })
            ->whereNotExists(function ($sub) use ($alias) {
                $sub->select(DB::raw(1))
                    ->from('piutang_pasien as pp')
                    ->whereColumn('pp.no_rawat', "$alias.no_rawat");
            });
    }

    private function filterBelumClosingKasir($q, string $alias)
    {
        return $q->whereExists(function ($sub) use ($alias) {
            $sub->select(DB::raw(1))
                ->from('reg_periksa as rp')
                ->whereColumn('rp.no_rawat', "$alias.no_rawat")
                ->where('rp.status_bayar', 'Belum Bayar');
        });
    }

    private function filterStatusRawat($q, string $alias, string $statusRawat)
    {
        return $q->whereExists(function ($sub) use ($alias, $statusRawat) {
            $sub->select(DB::raw(1))
                ->from('reg_periksa as rp')
                ->whereColumn('rp.no_rawat', "$alias.no_rawat")
                ->when(
                    $statusRawat === 'rj',
                    fn ($w) => $w->where('rp.status_lanjut', 'Ralan')
                )
                ->when(
                    $statusRawat === 'ri',
                    fn ($w) => $w->where('rp.status_lanjut', 'Ranap')
                );
        });
    }

    private function filterPenjamin($q, string $alias, string $penjamin)
    {
        return $q->whereExists(function ($sub) use ($alias, $penjamin) {
            $sub->select(DB::raw(1))
                ->from('reg_periksa as rp')
                ->join('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
                ->whereColumn('rp.no_rawat', "$alias.no_rawat")
                ->when(
                    $penjamin === 'umum',
                    fn ($w) => $w->where('pj.png_jawab', 'like', '%UMUM%')
                )
                ->when(
                    $penjamin === 'bpjs',
                    fn ($w) => $w->where('pj.png_jawab', 'like', '%BPJS%')
                )
                ->when(
                    $penjamin === 'asuransi',
                    fn ($w) => $w
                        ->where('pj.png_jawab', 'not like', '%BPJS%')
                        ->where('pj.png_jawab', 'not like', '%UMUM%')
                );
        });
    }

    private function applyAllFilter($q, string $alias, array $filter)
    {
        $q->when(
            ($filter['status_bayar'] ?? null) === 'piutang',
            fn ($w) => $this->filterPiutang($w, $alias)
        )
        ->when(
            ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
            fn ($w) => $this->filterLunasNonPiutang($w, $alias)
        )
        ->when(
            ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
            fn ($w) => $this->filterBelumClosingKasir($w, $alias)
        )
        ->when(
            in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
            fn ($w) => $this->filterStatusRawat($w, $alias, $filter['status_rawat'])
        )
        ->when(
            in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
            fn ($w) => $this->filterPenjamin($w, $alias, $filter['penjamin'])
        );
    }

    private function buildDetailPremiDokterQuery(
            string $kdDokter,
            string $tglAwal,
            string $tglAkhir,
            array $filter = []
        ) {
            $db = DB::connection('mysql_khanza');
            $sources = [];

            /* =====================================================
            * RAWAT JALAN
            * ===================================================== */
            $sources[] = $db->table('rawat_jl_dr as r')
                ->join('jns_perawatan as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.kd_dokter',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakandr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Jalan' as layanan"),
                ])
                ->where('r.kd_dokter', $kdDokter)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));

            /* =====================================================
            * RAWAT INAP  (❗ MASTER BERBEDA)
            * ===================================================== */
            $sources[] = $db->table('rawat_inap_dr as r')
                ->join('jns_perawatan_inap as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.kd_dokter',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakandr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Inap' as layanan"),
                ])
                ->where('r.kd_dokter', $kdDokter)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));
            
            /* =====================================================
            * RAWAT JALAN – DRPR
            * ===================================================== */
            $sources[] = $db->table('rawat_jl_drpr as r')
                ->join('jns_perawatan as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.kd_dokter',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakandr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Jalan dr dan Paramedis' as layanan"),
                ])
                ->where('r.kd_dokter', $kdDokter)
                ->where('r.tarif_tindakandr', '>', 0)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));
            
            /* =====================================================
            * RAWAT INAP – DRPR
            * ===================================================== */
            $sources[] = $db->table('rawat_inap_drpr as r')
                ->join('jns_perawatan_inap as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.kd_dokter',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakandr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Inap dr dan Paramedis' as layanan"),
                ])
                ->where('r.kd_dokter', $kdDokter)
                ->where('r.tarif_tindakandr', '>', 0)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));



            /* =====================================================
            * OPERASI
            * ===================================================== */
            foreach ([
                ['operator1', 'biayaoperator1'],
                ['operator2', 'biayaoperator2'],
                ['operator3', 'biayaoperator3'],
                ['dokter_anak', 'biayadokter_anak'],
                ['dokter_anestesi', 'biayadokter_anestesi'],
            ] as [$dokter, $biaya]) {

                $sources[] = $db->table('operasi as o')
                    ->join('paket_operasi as kp', 'kp.kode_paket', '=', 'o.kode_paket')
                    ->select([
                        "o.$dokter as kd_dokter",
                        'o.no_rawat',
                        'o.tgl_operasi as tanggal',
                        'kp.nm_perawatan as tindakan',
                        "o.$biaya as nilai",
                        DB::raw("'OPERASI' as sumber"),
                        DB::raw("'Operasi' as layanan"),
                    ])
                    ->where("o.$dokter", $kdDokter)
                    ->where("$biaya", '>', 0)
                    ->whereBetween('o.tgl_operasi', [$tglAwal, $tglAkhir])
                    ->tap(fn ($q) => $this->applyAllFilter($q, 'o', $filter));
            }

        /* =====================================================
        * RADIOLOGI – DOKTER PELAKSANA
        * ===================================================== */
        $sources[] = $db->table('periksa_radiologi as pr')
            ->join('jns_perawatan_radiologi as jr', 'jr.kd_jenis_prw', '=', 'pr.kd_jenis_prw')
            ->select([
                'pr.kd_dokter as kd_dokter',
                'pr.no_rawat',
                'pr.tgl_periksa as tanggal',
                'jr.nm_perawatan as tindakan',
                'pr.tarif_tindakan_dokter as nilai',
                DB::raw("'RADIOLOGI' as sumber"),
                DB::raw("'Radiologi (Pelaksana)' as layanan"),
            ])
            ->where('pr.kd_dokter', $kdDokter)
            ->where('pr.tarif_tindakan_dokter', '>', 0)
            ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
            ->tap(fn ($q) => $this->applyAllFilter($q, 'pr', $filter));

            /* =====================================================
            * RADIOLOGI – DOKTER PERUJUK
            * ===================================================== */
            $sources[] = $db->table('periksa_radiologi as pr')
                ->join('jns_perawatan_radiologi as jr', 'jr.kd_jenis_prw', '=', 'pr.kd_jenis_prw')
                ->select([
                    'pr.dokter_perujuk as kd_dokter',
                    'pr.no_rawat',
                    'pr.tgl_periksa as tanggal',
                    'jr.nm_perawatan as tindakan',
                    'pr.tarif_perujuk as nilai',
                    DB::raw("'RADIOLOGI' as sumber"),
                    DB::raw("'Radiologi (Perujuk)' as layanan"),
                ])
                ->where('pr.dokter_perujuk', $kdDokter)
                ->where('pr.tarif_perujuk', '>', 0)
                ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'pr', $filter));


            /* =====================================================
            * LAB – DOKTER PELAKSANA
            * ===================================================== */
            $sources[] = $db->table('detail_periksa_lab as dpl')
                ->join('periksa_lab as pl', function ($join) {
                    $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                        ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                        ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                        ->on('pl.jam', '=', 'dpl.jam');
                })
                ->join('jns_perawatan_lab as jl', 'jl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                ->select([
                    'pl.kd_dokter as kd_dokter',
                    'pl.no_rawat',
                    'dpl.tgl_periksa as tanggal',
                    'jl.nm_perawatan as tindakan',
                    'dpl.bagian_dokter as nilai',
                    DB::raw("'LAB' as sumber"),
                    DB::raw("'Laboratorium (Pelaksana)' as layanan"),
                ])
                ->where('pl.kd_dokter', $kdDokter)
                ->where('dpl.bagian_dokter', '>', 0)
                ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'pl', $filter));

                /* =====================================================
                * LAB – DOKTER PERUJUK
                * ===================================================== */
                $sources[] = $db->table('detail_periksa_lab as dpl')
                    ->join('periksa_lab as pl', function ($join) {
                        $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                            ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                            ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                            ->on('pl.jam', '=', 'dpl.jam');
                    })
                    ->join('jns_perawatan_lab as jl', 'jl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                    ->select([
                        'pl.dokter_perujuk as kd_dokter',
                        'pl.no_rawat',
                        'dpl.tgl_periksa as tanggal',
                        'jl.nm_perawatan as tindakan',
                        'dpl.bagian_perujuk as nilai',
                        DB::raw("'LAB' as sumber"),
                        DB::raw("'Laboratorium (Perujuk)' as layanan"),
                    ])
                    ->where('pl.dokter_perujuk', $kdDokter)
                    ->where('dpl.bagian_perujuk', '>', 0)
                    ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
                    ->tap(fn ($q) => $this->applyAllFilter($q, 'pl', $filter));

            $query = array_shift($sources);
            foreach ($sources as $q) {
                $query->unionAll($q);
            }

            return $db->query()
                ->fromSub($query, 'x')
                ->when(
                    !empty($filter['sumber']),
                    fn ($q) => $q->where('x.sumber', $filter['sumber'])
                )
                ->when(
                    !empty($filter['layanan']),
                    fn ($q) => $q->where('x.layanan', $filter['layanan'])
                );
    }

    private function buildDetailPremiParamedisQuery(
            string $nip,
            string $tglAwal,
            string $tglAkhir,
            array $filter = []
        ) {
            $db = DB::connection('mysql_khanza');
            $sources = [];

            /* =====================================================
            * RAWAT JALAN
            * ===================================================== */
            $sources[] = $db->table('rawat_jl_pr as r')
                ->join('jns_perawatan as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.nip',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakanpr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Jalan' as layanan"),
                ])
                ->where('r.nip', $nip)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));

            /* =====================================================
            * RAWAT INAP  (❗ MASTER BERBEDA)
            * ===================================================== */
            $sources[] = $db->table('rawat_inap_pr as r')
                ->join('jns_perawatan_inap as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.nip',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakanpr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Inap' as layanan"),
                ])
                ->where('r.nip', $nip)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));
            
            /* =====================================================
            * RAWAT JALAN – DRPR
            * ===================================================== */
            $sources[] = $db->table('rawat_jl_drpr as r')
                ->join('jns_perawatan as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.nip',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakanpr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Jalan dr dan Paramedis' as layanan"),
                ])
                ->where('r.nip', $nip)
                ->where('r.tarif_tindakanpr', '>', 0)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));
            
            /* =====================================================
            * RAWAT INAP – DRPR
            * ===================================================== */
            $sources[] = $db->table('rawat_inap_drpr as r')
                ->join('jns_perawatan_inap as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
                ->select([
                    'r.nip',
                    'r.no_rawat',
                    'r.tgl_perawatan as tanggal',
                    'jp.nm_perawatan as tindakan',
                    'r.tarif_tindakanpr as nilai',
                    DB::raw("'RAWAT' as sumber"),
                    DB::raw("'Rawat Inap dr dan Paramedis' as layanan"),
                ])
                ->where('r.nip', $nip)
                ->where('r.tarif_tindakanpr', '>', 0)
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'r', $filter));



            /* =====================================================
            * OPERASI
            * ===================================================== */
            foreach ([
                ['asisten_operator1', 'biayaasisten_operator1'],
                ['asisten_operator2', 'biayaasisten_operator2'],
                ['asisten_operator3', 'biayaasisten_operator3'],
                ['instrumen', 'biayainstrumen'],
                ['perawaat_resusitas', 'biayaperawaat_resusitas'],
                ['asisten_anestesi', 'biayaasisten_anestesi'],
                ['asisten_anestesi2', 'biayaasisten_anestesi2'],
            ] as [$kolomNip, $biaya]) {

                $sources[] = $db->table('operasi as o')
                    ->select([
                        "o.$kolomNip as nip",
                        'o.no_rawat',
                        'o.tgl_operasi as tanggal',
                        'o.kode_paket as tindakan',
                        "o.$biaya as nilai",
                        DB::raw("'OPERASI' as sumber"),
                        DB::raw("'Operasi' as layanan"),
                    ])
                    ->where("o.$kolomNip", $nip) // 🔥 $nip ASLI
                    ->where("$biaya", '>', 0)
                    ->whereBetween('o.tgl_operasi', [$tglAwal, $tglAkhir])
                    ->tap(fn ($q) => $this->applyAllFilter($q, 'o', $filter, true));
            }


        /* =====================================================
        * RADIOLOGI – PETUGAS
        * ===================================================== */
        $sources[] = $db->table('periksa_radiologi as pr')
            ->join('jns_perawatan_radiologi as jr', 'jr.kd_jenis_prw', '=', 'pr.kd_jenis_prw')
            ->select([
                'pr.nip as nip',
                'pr.no_rawat',
                'pr.tgl_periksa as tanggal',
                'jr.nm_perawatan as tindakan',
                'pr.tarif_tindakan_petugas as nilai',
                DB::raw("'RADIOLOGI' as sumber"),
                DB::raw("'Radiologi (Pelaksana)' as layanan"),
            ])
            ->where('pr.nip', $nip)
            ->where('pr.tarif_tindakan_petugas', '>', 0)
            ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
            ->tap(fn ($q) => $this->applyAllFilter($q, 'pr', $filter));

            /* =====================================================
            * LAB – DOKTER PELAKSANA
            * ===================================================== */
            $sources[] = $db->table('detail_periksa_lab as dpl')
                ->join('periksa_lab as pl', function ($join) {
                    $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                        ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                        ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                        ->on('pl.jam', '=', 'dpl.jam');
                })
                ->join('jns_perawatan_lab as jl', 'jl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                ->select([
                    'pl.nip as nip',
                    'pl.no_rawat',
                    'dpl.tgl_periksa as tanggal',
                    'jl.nm_perawatan as tindakan',
                    'dpl.bagian_laborat as nilai',
                    DB::raw("'LAB' as sumber"),
                    DB::raw("'Laboratorium (Pelaksana)' as layanan"),
                ])
                ->where('pl.nip', $nip)
                ->where('dpl.bagian_laborat', '>', 0)
                ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
                ->tap(fn ($q) => $this->applyAllFilter($q, 'pl', $filter));

            $query = array_shift($sources);
            foreach ($sources as $q) {
                $query->unionAll($q);
            }

            return $db->query()
                ->fromSub($query, 'x')
                ->when(
                    !empty($filter['sumber']),
                    fn ($q) => $q->where('x.sumber', $filter['sumber'])
                )
                ->when(
                    !empty($filter['layanan']),
                    fn ($q) => $q->where('x.layanan', $filter['layanan'])
                );
    }

    public function getPremiDokter($tglAwal, $tglAkhir, array $filter = [])
    {
        $db = DB::connection('mysql_khanza');
        $sources = [];

        // ================= RAWAT =================
        foreach ([
            'rawat_jl_dr',
            'rawat_inap_dr',
            'rawat_jl_drpr',
            'rawat_inap_drpr'
        ] as $table) {
            $alias = 'r'; // alias dinamis
            $sources[] = $db->table("$table as $alias")
                ->select("$alias.kd_dokter", "$alias.tarif_tindakandr as nilai_dr")
                ->whereBetween("$alias.tgl_perawatan", [$tglAwal, $tglAkhir])
                ->where(fn ($q) => $this->validPerson($q, "$alias.kd_dokter"))
                ->when(
                    ($filter['status_bayar'] ?? null) === 'piutang',
                    fn ($q) => $this->filterPiutang($q, $alias)
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                    fn ($q) => $this->filterLunasNonPiutang($q, $alias)
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                    fn ($q) => $this->filterBelumClosingKasir($q, $alias)
                )
                ->when(
                    in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                    fn ($q) => $this->filterStatusRawat($q, $alias, $filter['status_rawat'])
                )
                ->when(
                    in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                    fn ($q) => $this->filterPenjamin($q, $alias, $filter['penjamin'])
                );
        }

        // ================= OPERASI =================
        foreach ([
            ['operator1', 'biayaoperator1'],
            ['operator2', 'biayaoperator2'],
            ['operator3', 'biayaoperator3'],
            ['dokter_anak', 'biayadokter_anak'],
            ['dokter_anestesi', 'biayadokter_anestesi'],
        ] as [$dokter, $biaya]) {
            $sources[] = $db->table('operasi as o')
                ->select("o.$dokter as kd_dokter", "o.$biaya as nilai_dr")
                ->whereBetween('o.tgl_operasi', [$tglAwal, $tglAkhir])
                ->where(fn ($q) => $this->validPerson($q, "o.$dokter"))
                ->when(
                    ($filter['status_bayar'] ?? null) === 'piutang',
                    fn ($q) => $this->filterPiutang($q, 'o')
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                    fn ($q) => $this->filterLunasNonPiutang($q, 'o')
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                    fn ($q) => $this->filterBelumClosingKasir($q, 'o')
                )
                ->when(
                    in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                    fn ($q) => $this->filterStatusRawat($q, 'o', $filter['status_rawat'])
                )
                ->when(
                    in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                    fn ($q) => $this->filterPenjamin($q, 'o', $filter['penjamin'])
                );
                
        }

        // ================= RADIOLOGI =================
        $sources[] = $db->table('periksa_radiologi as pr')
            ->select('pr.dokter_perujuk as kd_dokter', 'pr.tarif_perujuk as nilai_dr')
            ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
            ->where(fn ($q) => $this->validPerson($q, 'pr.dokter_perujuk'))
            ->when(
                ($filter['status_bayar'] ?? null) === 'piutang',
                fn ($q) => $this->filterPiutang($q, 'pr')
            )
            ->when(
                    ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                    fn ($q) => $this->filterLunasNonPiutang($q, 'pr')
            )
            ->when(
                    ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                    fn ($q) => $this->filterBelumClosingKasir($q, 'pr')
            )
            ->when(
                    in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                    fn ($q) => $this->filterStatusRawat($q, 'pr', $filter['status_rawat'])
            )
            ->when(
                    in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                    fn ($q) => $this->filterPenjamin($q, 'pr', $filter['penjamin'])
            );

        $sources[] = $db->table('periksa_radiologi as pr')
            ->select('pr.kd_dokter as kd_dokter', 'pr.tarif_tindakan_dokter as nilai_dr')
            ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
            ->where(fn ($q) => $this->validPerson($q, 'pr.kd_dokter'))
            ->when(
                ($filter['status_bayar'] ?? null) === 'piutang',
                fn ($q) => $this->filterPiutang($q, 'pr')
            )
            ->when(
                    ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                    fn ($q) => $this->filterLunasNonPiutang($q, 'pr')
            )
            ->when(
                    ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                    fn ($q) => $this->filterBelumClosingKasir($q, 'pr')
            )
            ->when(
                    in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                    fn ($q) => $this->filterStatusRawat($q, 'pr', $filter['status_rawat'])
            )
            ->when(
                    in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                    fn ($q) => $this->filterPenjamin($q, 'pr', $filter['penjamin'])
            );


        // ================= LAB =================
        $labJoin = function ($q) {
            $q->join('periksa_lab as pl', function ($join) {
                $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                    ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                    ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                    ->on('pl.jam', '=', 'dpl.jam');
            });
        };

        $sources[] = $db->table('detail_periksa_lab as dpl')
        ->join('periksa_lab as pl', function ($join) {
            $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                ->on('pl.jam', '=', 'dpl.jam');
        })
        ->select('pl.dokter_perujuk as kd_dokter', 'dpl.bagian_perujuk as nilai_dr')
        ->where('dpl.bagian_perujuk', '>', 0)
        ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
        ->where(fn ($q) => $this->validPerson($q, 'pl.dokter_perujuk'))
        ->when(
            ($filter['status_bayar'] ?? null) === 'piutang',
            fn ($q) => $this->filterPiutang($q, 'pl')
        )
        ->when(
            ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
            fn ($q) => $this->filterLunasNonPiutang($q, 'pl')
        )
        ->when(
            ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
            fn ($q) => $this->filterBelumClosingKasir($q, 'pl')
        )
        ->when(
            in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
            fn ($q) => $this->filterStatusRawat($q, 'pl', $filter['status_rawat'])
        )
        ->when(
            in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
            fn ($q) => $this->filterPenjamin($q, 'pl', $filter['penjamin'])
        );

        $sources[] = $db->table('detail_periksa_lab as dpl')
            ->join('periksa_lab as pl', function ($join) {
                $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                    ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                    ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                    ->on('pl.jam', '=', 'dpl.jam');
            })
            ->select('pl.kd_dokter as kd_dokter', 'dpl.bagian_dokter as nilai_dr')
            ->where('dpl.bagian_dokter', '>', 0)
            ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
            ->where(fn ($q) => $this->validPerson($q, 'pl.kd_dokter'))
            ->when(
                ($filter['status_bayar'] ?? null) === 'piutang',
                fn ($q) => $this->filterPiutang($q, 'pl')
            )
            ->when(
                ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                fn ($q) => $this->filterLunasNonPiutang($q, 'pl')
            )

            ->when(
                ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                fn ($q) => $this->filterBelumClosingKasir($q, 'pl')
            )
            ->when(
                in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                fn ($q) => $this->filterStatusRawat($q, 'pl', $filter['status_rawat'])
            )
            ->when(
                in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                fn ($q) => $this->filterPenjamin($q, 'pl', $filter['penjamin'])
            );

        // ================= UNION =================
        $subQuery = array_shift($sources);
        foreach ($sources as $q) {
            $subQuery->unionAll($q);
        }

        return $db->table('dokter as d')
            ->leftJoinSub($subQuery, 'x', 'x.kd_dokter', '=', 'd.kd_dokter')
            ->select(
                'd.kd_dokter',
                'd.nm_dokter',
                DB::raw('COALESCE(SUM(x.nilai_dr),0) as total_tindakan_dr')
            )
            ->where(fn ($q) => $this->validPerson($q, 'd.kd_dokter'))
            ->groupBy('d.kd_dokter', 'd.nm_dokter')
            ->get();
    }

    public function getPremiPerawat($tglAwal, $tglAkhir, array $filter = [])
    {
        $db = DB::connection('mysql_khanza');
        $sources = [];

        // ================= RAWAT =================
        foreach ([
            'rawat_jl_pr',
            'rawat_inap_pr',
            'rawat_jl_drpr',
            'rawat_inap_drpr'
        ] as $table) {

            $sources[] = $db->table("$table as r")
                ->select('r.nip', 'r.tarif_tindakanpr as nilai_pr')
                ->whereBetween('r.tgl_perawatan', [$tglAwal, $tglAkhir])
                ->where(fn ($q) => $this->validPerson($q, 'r.nip'))
                ->when(
                    ($filter['status_bayar'] ?? null) === 'piutang',
                    fn ($q) => $this->filterPiutang($q, 'r')
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                    fn ($q) => $this->filterLunasNonPiutang($q, 'r')
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                    fn ($q) => $this->filterBelumClosingKasir($q, 'r')
                )
                ->when(
                    in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                    fn ($q) => $this->filterStatusRawat($q, 'r', $filter['status_rawat'])
                )
                ->when(
                    in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                    fn ($q) => $this->filterPenjamin($q, 'r', $filter['penjamin'])
                );
        }

        // ================= OPERASI =================
        foreach ([
            ['asisten_operator1', 'biayaasisten_operator1'],
            ['asisten_operator2', 'biayaasisten_operator2'],
            ['asisten_operator3', 'biayaasisten_operator3'],
            ['instrumen', 'biayainstrumen'],
            ['perawaat_resusitas', 'biayaperawaat_resusitas'],
            ['asisten_anestesi', 'biayaasisten_anestesi'],
            ['asisten_anestesi2', 'biayaasisten_anestesi2'],
        ] as [$nip, $biaya]) {

            $sources[] = $db->table('operasi as o')
                ->select("o.$nip as nip", "o.$biaya as nilai_pr")
                ->whereBetween('o.tgl_operasi', [$tglAwal, $tglAkhir])
                ->where(fn ($q) => $this->validPerson($q, "o.$nip"))
                ->when(
                    ($filter['status_bayar'] ?? null) === 'piutang',
                    fn ($q) => $this->filterPiutang($q, 'o')
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                    fn ($q) => $this->filterLunasNonPiutang($q, 'o')
                )
                ->when(
                    ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                    fn ($q) => $this->filterBelumClosingKasir($q, 'o')
                )
                ->when(
                    in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                    fn ($q) => $this->filterStatusRawat($q, 'o', $filter['status_rawat'])
                )
                ->when(
                    in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                    fn ($q) => $this->filterPenjamin($q, 'o', $filter['penjamin'])
                );
        }

        // ================= LAB =================
        $sources[] = $db->table('detail_periksa_lab as dpl')
            ->join('periksa_lab as pl', function ($join) {
                $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                    ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                    ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                    ->on('pl.jam', '=', 'dpl.jam');
            })
            ->select('pl.nip', 'dpl.bagian_laborat as nilai_pr')
            ->where('dpl.bagian_laborat', '>', 0)
            ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
            ->where(fn ($q) => $this->validPerson($q, 'pl.nip'))
            ->when(
                ($filter['status_bayar'] ?? null) === 'piutang',
                fn ($q) => $this->filterPiutang($q, 'pl')
            )
            ->when(
                ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                fn ($q) => $this->filterLunasNonPiutang($q, 'pl')
            )
            ->when(
                ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                fn ($q) => $this->filterBelumClosingKasir($q, 'pl')
            )
            ->when(
                in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                fn ($q) => $this->filterStatusRawat($q, 'pl', $filter['status_rawat'])
            )
            ->when(
                in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                fn ($q) => $this->filterPenjamin($q, 'pl', $filter['penjamin'])
            );

        // ================= RADIOLOGI =================
        $sources[] = $db->table('periksa_radiologi as pr')
            ->select('pr.nip', 'pr.tarif_tindakan_petugas as nilai_pr')
            ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
            ->where(fn ($q) => $this->validPerson($q, 'pr.nip'))
            ->when(
                ($filter['status_bayar'] ?? null) === 'piutang',
                fn ($q) => $this->filterPiutang($q, 'pr')
            )
            ->when(
                ($filter['status_bayar'] ?? null) === 'lunas_non_piutang',
                fn ($q) => $this->filterLunasNonPiutang($q, 'pr')
            )
            ->when(
                ($filter['status_bayar'] ?? null) === 'belum_closing_kasir',
                fn ($q) => $this->filterBelumClosingKasir($q, 'pr')
            )
            ->when(
                in_array(($filter['status_rawat'] ?? null), ['rj', 'ri']),
                fn ($q) => $this->filterStatusRawat($q, 'pr', $filter['status_rawat'])
            )
            ->when(
                in_array(($filter['penjamin'] ?? null), ['umum', 'bpjs', 'asuransi']),
                fn ($q) => $this->filterPenjamin($q, 'pr', $filter['penjamin'])
            );

        // ================= UNION =================
        $subQuery = array_shift($sources);
        foreach ($sources as $q) {
            $subQuery->unionAll($q);
        }

        return $db->table('petugas as p')
            ->leftJoinSub($subQuery, 'x', 'x.nip', '=', 'p.nip')
            ->select(
                'p.nip',
                'p.nama',
                DB::raw('COALESCE(SUM(x.nilai_pr),0) as total_tindakan_pr')
            )
            ->where(fn ($q) => $this->validPerson($q, 'p.nip'))
            ->groupBy('p.nip', 'p.nama')
            ->get();
    }

    public function getDetailPremiDokter(
            string $kdDokter,
            string $tglAwal,
            string $tglAkhir,
            array $filter = []
        ) {
            return $this->buildDetailPremiDokterQuery(
                $kdDokter,
                $tglAwal,
                $tglAkhir,
                $filter
            )
            ->orderBy('tanggal')
            ->get();
    }

    public function getTotalPremiDokter(
            string $kdDokter,
            string $tglAwal,
            string $tglAkhir,
            array $filter = []
        ): int {
            return (int) $this->buildDetailPremiDokterQuery(
                $kdDokter,
                $tglAwal,
                $tglAkhir,
                $filter
            )->sum('nilai');
    }

    public function getDetailPremiParamedis(
            string $nip,
            string $tglAwal,
            string $tglAkhir,
            array $filter = []
        ) {
            return $this->buildDetailPremiParamedisQuery(
                $nip,
                $tglAwal,
                $tglAkhir,
                $filter
            )
            ->orderBy('tanggal')
            ->get();
    }

    public function getTotalPremiParamedis(
            string $nip,
            string $tglAwal,
            string $tglAkhir,
            array $filter = []
        ): int {
            return (int) $this->buildDetailPremiParamedisQuery(
                $nip,
                $tglAwal,
                $tglAkhir,
                $filter
            )->sum('nilai');
    }



}