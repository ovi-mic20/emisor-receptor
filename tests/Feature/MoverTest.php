<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoverTest extends TestCase
{
    public function test_mover_responde_ok(): void
    {
        $response = $this->postJson('/mover', [
            'jugadorId' => 'camp-test-123',
            'x' => 100,
            'y' => 200,
            'viewportWidth' => 800,
            'viewportHeight' => 600,
            'modo' => 'campana',
            'vidas' => 3,
            'puntos' => 0,
            'escudo' => false,
        ]);

        // Si esto falla, el mensaje de la excepción aparece en la salida
        $response->assertOk();
    }
}
