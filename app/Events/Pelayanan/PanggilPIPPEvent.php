<?php

namespace App\Events\Pelayanan;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PanggilPIPPEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $nama;
    public $poli;
    public $dokter;
    public $nomorAntrian;
    public $noRawat;

    public function __construct($nama, $poli, $dokter, $nomorAntrian, $noRawat)
    {
        $this->nama = $nama;
        $this->poli = $poli;
        $this->dokter = $dokter;
        $this->nomorAntrian = $nomorAntrian;
        $this->noRawat = $noRawat;
    }

    public function broadcastOn()
    {
        return new Channel('panggilan-pipp-V2');
    }

    public function broadcastAs()
    {
        return 'PanggilPIPP-V2';
    }

    public function broadcastWith()
    {
        return [
            'nama' => $this->nama,
            'poli' => $this->poli,
            'dokter' => $this->dokter,
            'nomorAntrian' => $this->nomorAntrian,
            'noRawat' => $this->noRawat,
        ];
    }
}
