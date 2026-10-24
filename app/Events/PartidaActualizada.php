<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartidaActualizada implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $partidaId) {}

    public function broadcastOn(): array
    {
        return [new Channel('partida.'.$this->partidaId)];
    }

    public function broadcastAs(): string
    {
        return 'partida.actualizada';
    }
}
