<?php

namespace App\Events\Pelayanan;

// use Illuminate\Foundation\Events\Dispatchable;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\Channel;
// use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
// use Illuminate\Broadcasting\WithReverb;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PanggilPasienEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $nama;
    public $poli;
    public $dokter;
    public $nomorAntrian;

    public function __construct($nama, $poli, $dokter, $nomorAntrian)
    {
        $this->nama = $nama;
        $this->poli = $poli;
        $this->dokter = $dokter;
        $this->nomorAntrian = $nomorAntrian;
    }

    // Channel tempat event akan dikirim
    public function broadcastOn()
    {
        return new Channel('panggilan-pasien-V2');
    }

    // Nama event yang diterima di frontend
    public function broadcastAs()
    {
        return 'PanggilPasien-V2';
    }

    // Data yang dikirim ke frontend
    public function broadcastWith()
    {
        return [
            'nama' => $this->nama,
            'poli' => $this->poli,
            'dokter' => $this->dokter,
            'nomorAntrian' => $this->nomorAntrian,
        ];
    }
}
