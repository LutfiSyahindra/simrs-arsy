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

    // /**
    //  * ============================
    //  * PREMI DOKTER
    //  * ============================
    //  */
    // public function getPremiDokter($tglAwal, $tglAkhir, array $filter = [])
    // {
    //     return $this->premiRepository
    //         ->getPremiDokter($tglAwal, $tglAkhir, $filter);
    // }

    // /**
    //  * ============================
    //  * PREMI PERAWAT
    //  * (sementara TANPA filter piutang)
    //  * ============================
    //  */
    // public function getPremiPerawat($tglAwal, $tglAkhir, array $filter = [])
    // {
    //     return $this->premiRepository
    //         ->getPremiPerawat($tglAwal, $tglAkhir, $filter);
    // }

    /**
     * ============================
     * DATA GABUNGAN (DATATABLE)
     * ============================
     */
    public function getPremiTable($tglAwal, $tglAkhir, array $filter = [])
    {
        $dataPremi = [];
        $mode = $filter['view_mode'] ?? 'grouped';

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

        /**
         * ============================
         * TAB PREMI KAMAR
         * ============================
         */
        if (($filter['jenis'] ?? null) === 'kamar_inap') {

            $premiKamar = $this->premiRepository
                ->getPremiKamar($tglAwal, $tglAkhir, $filter, $mode);

            if ($mode === 'grouped') {

                foreach ($premiKamar as $p) {
                    $dataPremi[] = [
                        'kode'  => $p->kd_kamar,
                        'nama'  => $p->nama ?? 'Kamar ' . $p->kd_kamar,
                        'jenis' => 'Kamar',
                        'premi' => (int) $p->total_kamar,
                        'group_by' => 'kamar_inap',
                    ];
                }

            } else {

                foreach ($premiKamar as $p) {
                    $dataPremi[] = [
                        'no_rawat' => $p->no_rawat,
                        'nama'     => $p->nm_pasien,
                        'kode'     => $p->kd_kamar,
                        'tgl_masuk'=> $p->tgl_masuk,
                        'lama'     => $p->lama,
                        'premi'    => (int) $p->premi,
                        'jenis' => 'Kamar',
                    ];
                }

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

        /* ================= KAMAR INAP ================= */
        if ($filter['jenis'] === 'Kamar') {

            if (empty($filter['kd_kamar'])) {
                throw new \InvalidArgumentException('kd_kamar wajib diisi untuk detail kamar');
            }

            return $this->premiRepository->getDetailPremiKamar(
                $filter['kd_kamar'],
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

            /* ================= KAMAR ================= */
            if ($filter['jenis'] === 'Kamar') {
                if (empty($filter['kd_kamar'])) {
                    throw new \InvalidArgumentException('kd_kamar wajib diisi untuk total kamar');
                }

                return $this->premiRepository->getTotalPremiKamar(
                    $filter['kd_kamar'],
                    $tglAwal,
                    $tglAkhir,
                    $filter
                );
            }

            throw new \InvalidArgumentException('Jenis premi tidak dikenali');
    }

    public function getPremiDokterChart($tglAwal, $tglAkhir, array $filter = [])
    {
        return $this->premiRepository->getPremiDokterChart($tglAwal, $tglAkhir, $filter);
    }

    public function getPremiParamedisChart($tglAwal, $tglAkhir, array $filter = [])
    {
        return $this->premiRepository->getPremiParamedisChart($tglAwal, $tglAkhir, $filter);
    }

    public function getPremiKamarChart($tglAwal, $tglAkhir, array $filter = [])
    {
        return $this->premiRepository->getPremiKamarChart($tglAwal, $tglAkhir, $filter);
    }

}
