<?php

namespace App\Events\Pelayanan;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PanggilAdmisiEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $nomorAntrian;
    public $loket;
    public $status;

    public function __construct($nomorAntrian, $loket, $status)
    {
        $this->nomorAntrian = $nomorAntrian;
        $this->loket = $loket;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return new Channel('panggilan-admisi-V2');
    }

    public function broadcastAs()
    {
        return 'PanggilAdmisi-V2';
    }

    public function broadcastWith()
    {
        return [
            'nomorAntrian' => $this->nomorAntrian,
            'loket' => $this->loket,
            'status' => $this->status
        ];
    }
}
