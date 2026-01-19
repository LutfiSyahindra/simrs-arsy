<?php

namespace App\Events\Pelayanan;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AdmisiAnjunganEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $antrian;

    public function __construct($antrian)
    {
        $this->antrian = $antrian;
    }

    public function broadcastOn()
    {
        return new Channel('antrian-admisi-V2');
    }

    public function broadcastAs()
    {
        // Nama event yang akan ditangkap di sisi client
        return 'antrianAdmisi.created-V2';
    }

    public function broadcastWith()
    {
        // Data yang dikirim ke client
        return [
            'antrian' => [
                'no_antrian' => $this->antrian->no_antrian,
                'tanggal'    => $this->antrian->tanggal,
                'status_panggil' => $this->antrian->status_panggil,
                'loket'      => $this->antrian->loket,
                'id'         => $this->antrian->id,
            ]
        ];
    }
}
