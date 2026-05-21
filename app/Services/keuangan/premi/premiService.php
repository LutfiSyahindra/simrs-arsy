<?php

namespace App\Services\keuangan\premi;

use App\Repositories\keuangan\premi\premiRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class premiService
{
    protected $premiRepository;

    public function __construct(premiRepository $premiRepository)
    {
        $this->premiRepository = $premiRepository;
    }

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
                        'kode'     => $p->kd_kamar,
                        'nama'     => $p->nama ?? 'Kamar ' . $p->kd_kamar,
                        'jenis'    => 'Kamar',
                        'premi'    => (int) $p->total_kamar,
                        'group_by' => 'kamar_inap',
                    ];
                }

            } else {

                foreach ($premiKamar as $p) {
                    $dataPremi[] = [
                        'no_rawat'  => $p->no_rawat,
                        'nama'      => $p->nm_pasien,
                        'kode'      => $p->kd_kamar,
                        'tgl_masuk' => $p->tgl_masuk,
                        'lama'      => $p->lama,
                        'premi'     => (int) $p->premi,
                        'jenis'     => 'Kamar',
                    ];
                }

            }
        }

        /**
         * ============================
         * TAB RUMAH SAKIT
         * ============================
         */
        if (in_array(($filter['jenis'] ?? null), ['rs', 'rumah_sakit'])) {

            $premiRs = $this->premiRepository
                ->getPremiRs($tglAwal, $tglAkhir, $filter);

            foreach ($premiRs as $p) {
                $formatTgl = Carbon::parse($p->tanggal)->translatedFormat('d-M-Y');

                $dataPremi[] = [
                    'kode'     => $p->tanggal,   // tetap raw untuk detail/export
                    'nama'     => 'Rumah Sakit',
                    'tanggal'  => $formatTgl,    // tampil cantik di tabel
                    'jenis'    => 'Rumah Sakit',
                    'premi'    => (int) $p->total_rs,
                    'group_by' => 'tanggal',
                ];
            }
        }

        return collect($dataPremi);
    }

    public function getPremiDetail(string $tglAwal, string $tglAkhir, array $filter)
    {
        Log::info('Memulai getPremiDetail dengan filter', $filter);
        if (empty($filter['jenis'])) {
            throw new \InvalidArgumentException('Filter jenis wajib diisi');
        }

        $jenis = strtolower(trim($filter['jenis']));

        if ($jenis === 'dokter') {
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

        if (in_array($jenis, ['perawat', 'paramedis'])) {
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

        if (in_array($jenis, ['kamar', 'kamar_inap'])) {
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

        if (in_array($jenis, ['rs', 'rumah_sakit', 'rumah sakit', 'Rumah Sakit'])) {
            $tanggal = $filter['tanggal'] ?? $filter['kode'] ?? null;

            if (empty($tanggal)) {
                throw new \InvalidArgumentException('tanggal wajib diisi untuk detail rumah sakit');
            }

            return $this->premiRepository->getDetailPremiRs(
                $tanggal,
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

        $jenis = strtolower(trim(str_replace('_', ' ', $filter['jenis'])));

        /* ================= DOKTER ================= */
        if ($jenis === 'dokter') {

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

        /* ================= PERAWAT ================= */
        if (in_array($jenis, ['perawat', 'paramedis'])) {

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
        if (in_array($jenis, ['kamar', 'kamar inap'])) {

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

        /* ================= RUMAH SAKIT ================= */
        if (in_array($jenis, ['rs', 'rumah sakit'])) {

            $tanggal = $filter['tanggal'] ?? $filter['kode'] ?? null;

            if (empty($tanggal)) {
                throw new \InvalidArgumentException('tanggal wajib diisi untuk total rumah sakit');
            }

            return $this->premiRepository->getTotalPremiRs(
                $tanggal,
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

    public function getPremiRsChart($tglAwal, $tglAkhir, array $filter = [])
    {
        return $this->premiRepository->getPremiRsChart($tglAwal, $tglAkhir, $filter);
    }

}
