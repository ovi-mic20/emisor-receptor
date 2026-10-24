<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PartidaTest extends TestCase
{
    public function test_dos_jugadores_en_espera_se_emparejan_en_una_partida(): void
    {
        Cache::flush();
        $jugadorUnoId = '10000000-0000-4000-8000-000000000001';
        $jugadorDosId = '10000000-0000-4000-8000-000000000002';

        $this->postJson('/partidas/buscar', ['jugadorId' => $jugadorUnoId])
            ->assertOk()
            ->assertJsonPath('estado', 'esperando');

        $partidaId = $this->postJson('/partidas/buscar', ['jugadorId' => $jugadorDosId])
            ->assertOk()
            ->assertJsonPath('estado', 'encontrado')
            ->json('partidaId');

        $this->getJson("/partidas/{$partidaId}?jugadorId={$jugadorUnoId}")
            ->assertOk()
            ->assertJsonPath('puntos.tu', 0)
            ->assertJsonPath('puntos.oponente', 0);
    }

    public function test_cancelar_la_busqueda_quita_al_jugador_de_la_cola(): void
    {
        Cache::flush();
        $jugadorUnoId = '15000000-0000-4000-8000-000000000001';
        $jugadorDosId = '15000000-0000-4000-8000-000000000002';

        $this->postJson('/partidas/buscar', ['jugadorId' => $jugadorUnoId])
            ->assertJsonPath('estado', 'esperando');

        $this->deleteJson("/partidas/espera/{$jugadorUnoId}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->postJson('/partidas/buscar', ['jugadorId' => $jugadorDosId])
            ->assertOk()
            ->assertJsonPath('estado', 'esperando');
    }

    public function test_las_jugadas_se_revelan_juntas_y_la_partida_termina_al_llegar_a_tres_puntos(): void
    {
        Cache::flush();
        $jugadorUnoId = '20000000-0000-4000-8000-000000000001';
        $jugadorDosId = '20000000-0000-4000-8000-000000000002';
        $partidaId = $this->crearPartida($jugadorUnoId, $jugadorDosId);

        $this->postJson("/partidas/{$partidaId}/jugadas", [
            'jugadorId' => $jugadorUnoId,
            'jugada' => 'piedra',
        ])->assertOk();

        $this->getJson("/partidas/{$partidaId}?jugadorId={$jugadorDosId}")
            ->assertOk()
            ->assertJsonPath('jugaste', false)
            ->assertJsonPath('oponenteJugo', true)
            ->assertJsonPath('tuJugadaActual', null);

        $this->postJson("/partidas/{$partidaId}/jugadas", [
            'jugadorId' => $jugadorDosId,
            'jugada' => 'piedra',
        ])->assertOk()
            ->assertJsonPath('ultimaRonda.ganador', 'empate');

        foreach (range(1, 3) as $round) {
            $this->postJson("/partidas/{$partidaId}/jugadas", [
                'jugadorId' => $jugadorUnoId,
                'jugada' => 'piedra',
            ])->assertOk();

            $result = $this->postJson("/partidas/{$partidaId}/jugadas", [
                'jugadorId' => $jugadorDosId,
                'jugada' => 'tijera',
            ])->assertOk();

            if ($round < 3) {
                $result->assertJsonPath('estado', 'en_curso');
            }
        }

        $this->getJson("/partidas/{$partidaId}?jugadorId={$jugadorUnoId}")
            ->assertOk()
            ->assertJsonPath('estado', 'finalizada')
            ->assertJsonPath('ganador', 'tu')
            ->assertJsonPath('puntos.tu', 3);
    }

    public function test_no_se_puede_jugar_en_una_partida_de_otra_persona(): void
    {
        Cache::flush();
        $jugadorUnoId = '30000000-0000-4000-8000-000000000001';
        $jugadorDosId = '30000000-0000-4000-8000-000000000002';
        $personaAjenaId = '30000000-0000-4000-8000-000000000003';
        $partidaId = $this->crearPartida($jugadorUnoId, $jugadorDosId);

        $this->postJson("/partidas/{$partidaId}/jugadas", [
            'jugadorId' => $personaAjenaId,
            'jugada' => 'piedra',
        ])->assertNotFound();
    }

    public function test_rechaza_una_jugada_que_no_es_piedra_papel_o_tijera(): void
    {
        Cache::flush();
        $jugadorUnoId = '40000000-0000-4000-8000-000000000001';
        $jugadorDosId = '40000000-0000-4000-8000-000000000002';
        $partidaId = $this->crearPartida($jugadorUnoId, $jugadorDosId);

        $this->postJson("/partidas/{$partidaId}/jugadas", [
            'jugadorId' => $jugadorUnoId,
            'jugada' => 'lagarto',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('jugada');
    }

    private function crearPartida(string $jugadorUnoId, string $jugadorDosId): string
    {
        $this->postJson('/partidas/buscar', ['jugadorId' => $jugadorUnoId]);

        return $this->postJson('/partidas/buscar', ['jugadorId' => $jugadorDosId])
            ->json('partidaId');
    }
}
