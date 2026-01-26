<?php

namespace App\Services\keuangan\premi;

use App\Repositories\keuangan\premi\premiRepository;
use Illuminate\Support\Facades\Log;

class premiService
{
    protected $premiRepository;

    public function __construct(premiRepository $premiRepository)
    {
        $this->premiRepository = $premiRepository;
    }

    /**
     * ============================
     * PREMI DOKTER
     * ============================
     */
    public function getPremiDokter($tglAwal, $tglAkhir, array $filter = [])
    {
        return $this->premiRepository
            ->getPremiDokter($tglAwal, $tglAkhir, $filter);
    }

    /**
     * ============================
     * PREMI PERAWAT
     * (sementara TANPA filter piutang)
     * ============================
     */
    public function getPremiPerawat($tglAwal, $tglAkhir, array $filter = [])
    {
        return $this->premiRepository
            ->getPremiPerawat($tglAwal, $tglAkhir, $filter);
    }

    /**
     * ============================
     * DATA GABUNGAN (DATATABLE)
     * ============================
     */
    public function getPremiTable($tglAwal, $tglAkhir, array $filter = [])
    {
        Log::info('Filter Premi', $filter);

        $dataPremi = [];

        /**
         * ============================
         * TAB DOKTER
         * ============================
         */
        if (($filter['jenis'] ?? 'dokter') === 'dokter') {

            $premiDokter = $this->premiRepository
                ->getPremiDokter($tglAwal, $tglAkhir, $filter);

            foreach ($premiDokter as $d) {
                $dataPremi[] = [
                    'kode'  => $d->kd_dokter,
                    'nama'  => $d->nm_dokter,
                    'jenis' => 'Dokter',
                    'premi' => (int) $d->total_tindakan_dr,
                ];
            }
        }

        /**
         * ============================
         * TAB PARAMEDIS
         * ============================
         */
        if (($filter['jenis'] ?? null) === 'paramedis') {

            $premiPerawat = $this->premiRepository
                ->getPremiPerawat($tglAwal, $tglAkhir, $filter);

            foreach ($premiPerawat as $p) {
                $dataPremi[] = [
                    'kode'  => $p->nip,
                    'nama'  => $p->nama,
                    'jenis' => 'Paramedis',
                    'premi' => (int) $p->total_tindakan_pr,
                ];
            }
        }

        return collect($dataPremi);
    }

    public function getPremiDetail(string $tglAwal, string $tglAkhir, array $filter)
    {
        /* ================= VALIDASI DASAR ================= */
        if (empty($filter['jenis'])) {
            throw new \InvalidArgumentException('Filter jenis wajib diisi');
        }

        /* ================= DOKTER ================= */
        if ($filter['jenis'] === 'Dokter') {

            if (empty($filter['kd_dokter'])) {
                throw new \InvalidArgumentException('kd_dokter wajib diisi untuk detail dokter');
            }

            return $this->premiRepository->getDetailPremiDokter(
                $filter['kd_dokter'],
                $tglAwal,
                $tglAkhir,
                $filter
            );
        }

        /* ================= PERAWAT (SIAP DIPAKAI) ================= */
        if ($filter['jenis'] === 'Perawat') {

            if (empty($filter['nip'])) {
                throw new \InvalidArgumentException('nip wajib diisi untuk detail perawat');
            }

            return $this->premiRepository->getDetailPremiParamedis(
                $filter['nip'],
                $tglAwal,
                $tglAkhir,
                $filter
            );
        }

        throw new \InvalidArgumentException('Jenis premi tidak dikenali');
    }

    public function getPremiDetailTotal(
            string $tglAwal,
            string $tglAkhir,
            array $filter
        ): int {
            /* ================= VALIDASI DASAR ================= */
            if (empty($filter['jenis'])) {
                throw new \InvalidArgumentException('Filter jenis wajib diisi');
            }

            /* ================= DOKTER ================= */
            if ($filter['jenis'] === 'Dokter') {

                if (empty($filter['kd_dokter'])) {
                    throw new \InvalidArgumentException('kd_dokter wajib diisi untuk total dokter');
                }

                return $this->premiRepository->getTotalPremiDokter(
                    $filter['kd_dokter'],
                    $tglAwal,
                    $tglAkhir,
                    $filter
                );
            }

            /* ================= PERAWAT (SIAP DIPAKAI) ================= */
            if ($filter['jenis'] === 'Perawat') {
                if (empty($filter['nip'])) {
                    throw new \InvalidArgumentException('nip wajib diisi untuk total perawat');
                }

                return $this->premiRepository->getTotalPremiParamedis(
                    $filter['nip'],
                    $tglAwal,
                    $tglAkhir,
                    $filter
                );
            }

            throw new \InvalidArgumentException('Jenis premi tidak dikenali');
    }








}
