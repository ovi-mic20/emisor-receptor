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
    public int $vidas;
    public int $puntos;
    public bool $escudo;
    public ?array $disparo = null;
    public ?array $impacto = null;
    public ?array $relacion = null;
    public bool $campana;

    public function __construct(
        string $jugadorId,
        float $x,
        float $y,
        float $viewportWidth,
        float $viewportHeight,
        int $vidas = 3,
        int $puntos = 0,
        bool $escudo = false,
        ?array $disparo = null,
        ?array $impacto = null,
        bool $campana = false,
        ?array $relacion = null
    ) {
        $this->jugadorId = $jugadorId;
        $this->x = $x;
        $this->y = $y;
        $this->viewportWidth = $viewportWidth;
        $this->viewportHeight = $viewportHeight;
        $this->vidas = $vidas;
        $this->puntos = $puntos;
        $this->escudo = $escudo;
        $this->disparo = $disparo;
        $this->impacto = $impacto;
        $this->campana = $campana;
        $this->relacion = $relacion;
    }

    public function broadcastOn(): array
    {
        return [new Channel($this->campana ? 'campana' : 'galaxia')];
    }

    public function broadcastAs(): string
    {
        return 'nave.movida';
    }
}
