<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class Nave implements ShouldBroadcast
{
    public string $jugadorId;
    public float $x;
    public float $y;
    public float $viewportWidth;
    public float $viewportHeight;

    public function __construct(string $jugadorId, float $x, float $y, float $viewportWidth, float $viewportHeight)
    {
        $this->jugadorId = $jugadorId;
        $this->x = $x;
        $this->y = $y;
        $this->viewportWidth = $viewportWidth;
        $this->viewportHeight = $viewportHeight;
    }

    public function broadcastOn(): array
    {
        return [new Channel('galaxia')];
    }

    public function broadcastAs(): string
    {
        return 'nave.movida';
    }
}