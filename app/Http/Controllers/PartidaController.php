<?php

namespace App\Http\Controllers;

use App\Events\PartidaActualizada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PartidaController extends Controller
{
    private const WAITING_PLAYERS_KEY = 'piedra-papel-tijera:espera';

    private const CACHE_TTL_MINUTES = 15;

    public function buscar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'jugadorId' => ['required', 'uuid'],
        ]);

        $jugadorId = $data['jugadorId'];

        return Cache::lock('piedra-papel-tijera:emparejamiento', 10)->block(3, function () use ($jugadorId): JsonResponse {
            $jugador = Cache::get($this->playerKey($jugadorId));

            if (is_array($jugador) && isset($jugador['partidaId'])) {
                return response()->json([
                    'estado' => 'encontrado',
                    'partidaId' => $jugador['partidaId'],
                ]);
            }

            if (($jugador['estado'] ?? null) === 'esperando') {
                $jugador['actualizado'] = now()->timestamp;
                Cache::put($this->playerKey($jugadorId), $jugador, now()->addMinutes(self::CACHE_TTL_MINUTES));
            }

            $espera = Cache::get(self::WAITING_PLAYERS_KEY, []);
            $espera = array_values(array_filter(
                $espera,
                fn (string $id): bool => $id !== $jugadorId && $this->isRecentlyWaiting($id)
            ));

            if ($espera === []) {
                $espera[] = $jugadorId;
                Cache::put(self::WAITING_PLAYERS_KEY, $espera, now()->addMinutes(self::CACHE_TTL_MINUTES));
                Cache::put($this->playerKey($jugadorId), [
                    'estado' => 'esperando',
                    'actualizado' => now()->timestamp,
                ], now()->addMinutes(self::CACHE_TTL_MINUTES));

                return response()->json(['estado' => 'esperando']);
            }

            $oponenteId = $espera[array_rand($espera)];
            $espera = array_values(array_filter($espera, fn (string $id): bool => $id !== $oponenteId));
            Cache::put(self::WAITING_PLAYERS_KEY, $espera, now()->addMinutes(self::CACHE_TTL_MINUTES));

            $partidaId = (string) Str::uuid();
            $partida = [
                'id' => $partidaId,
                'jugadorUnoId' => $oponenteId,
                'jugadorDosId' => $jugadorId,
                'estado' => 'en_curso',
                'ronda' => 1,
                'puntos' => [
                    $oponenteId => 0,
                    $jugadorId => 0,
                ],
                'jugadas' => [],
                'ultimaRonda' => null,
                'ganadorId' => null,
            ];

            Cache::put($this->matchKey($partidaId), $partida, now()->addMinutes(self::CACHE_TTL_MINUTES));
            Cache::put($this->playerKey($oponenteId), ['partidaId' => $partidaId], now()->addMinutes(self::CACHE_TTL_MINUTES));
            Cache::put($this->playerKey($jugadorId), ['partidaId' => $partidaId], now()->addMinutes(self::CACHE_TTL_MINUTES));

            return response()->json([
                'estado' => 'encontrado',
                'partidaId' => $partidaId,
            ]);
        });
    }

    public function cancelarEspera(string $jugadorId): JsonResponse
    {
        abort_unless (Str::isUuid($jugadorId), 404);

        return Cache::lock('piedra-papel-tijera:emparejamiento', 10)->block(3, function () use ($jugadorId): JsonResponse {
            $jugador = Cache::get($this->playerKey($jugadorId));

            if (($jugador['estado'] ?? null) === 'esperando') {
                $espera = Cache::get(self::WAITING_PLAYERS_KEY, []);
                $espera = array_values(array_filter($espera, fn (string $id): bool => $id !== $jugadorId));
                Cache::put(self::WAITING_PLAYERS_KEY, $espera, now()->addMinutes(self::CACHE_TTL_MINUTES));
                Cache::forget($this->playerKey($jugadorId));
            }

            return response()->json(['ok' => true]);
        });
    }

    public function estado(Request $request, string $partidaId): JsonResponse
    {
        abort_unless (Str::isUuid($partidaId), 404);

        $data = $request->validate([
            'jugadorId' => ['required', 'uuid'],
        ]);
        $partida = Cache::get($this->matchKey($partidaId));

        abort_unless (is_array($partida) && $this->isPlayer($partida, $data['jugadorId']), 404);

        return response()->json($this->estadoParaJugador($partida, $data['jugadorId']));
    }

    public function jugar(Request $request, string $partidaId): JsonResponse
    {
        abort_unless (Str::isUuid($partidaId), 404);

        $data = $request->validate([
            'jugadorId' => ['required', 'uuid'],
            'jugada' => ['required', 'in:piedra,papel,tijera'],
        ]);

        return Cache::lock($this->matchLockKey($partidaId), 10)->block(3, function () use ($data, $partidaId): JsonResponse {
            $partida = Cache::get($this->matchKey($partidaId));

            abort_unless (is_array($partida) && $this->isPlayer($partida, $data['jugadorId']), 404);
            abort_if ($partida['estado'] !== 'en_curso', 409, 'La partida ya terminó.');

            if (! array_key_exists($data['jugadorId'], $partida['jugadas'])) {
                $partida['jugadas'][$data['jugadorId']] = $data['jugada'];

                if (count($partida['jugadas']) === 2) {
                    $partida = $this->resolverRonda($partida);
                }

                Cache::put($this->matchKey($partidaId), $partida, now()->addMinutes(self::CACHE_TTL_MINUTES));
                defer(fn () => broadcast(new PartidaActualizada($partidaId)));
            }

            return response()->json($this->estadoParaJugador($partida, $data['jugadorId']));
        });
    }

    private function resolverRonda(array $partida): array
    {
        [$jugadorUnoId, $jugadorDosId] = [$partida['jugadorUnoId'], $partida['jugadorDosId']];
        $jugadaUno = $partida['jugadas'][$jugadorUnoId];
        $jugadaDos = $partida['jugadas'][$jugadorDosId];
        $ganadorId = $this->ganadorDeRonda($jugadorUnoId, $jugadaUno, $jugadorDosId, $jugadaDos);

        if ($ganadorId !== null) {
            $partida['puntos'][$ganadorId]++;
        }

        $partida['ultimaRonda'] = [
            'numero' => $partida['ronda'],
            'jugadas' => [
                $jugadorUnoId => $jugadaUno,
                $jugadorDosId => $jugadaDos,
            ],
            'ganadorId' => $ganadorId,
        ];
        $partida['jugadas'] = [];
        $partida['ronda']++;

        if ($ganadorId !== null && $partida['puntos'][$ganadorId] === 3) {
            $partida['estado'] = 'finalizada';
            $partida['ganadorId'] = $ganadorId;
        }

        return $partida;
    }

    private function ganadorDeRonda(string $jugadorUnoId, string $jugadaUno, string $jugadorDosId, string $jugadaDos): ?string
    {
        if ($jugadaUno === $jugadaDos) {
            return null;
        }

        $jugadasGanadoras = [
            'piedra' => 'tijera',
            'papel' => 'piedra',
            'tijera' => 'papel',
        ];

        return $jugadasGanadoras[$jugadaUno] === $jugadaDos ? $jugadorUnoId : $jugadorDosId;
    }

    private function estadoParaJugador(array $partida, string $jugadorId): array
    {
        $oponenteId = $partida['jugadorUnoId'] === $jugadorId
            ? $partida['jugadorDosId']
            : $partida['jugadorUnoId'];
        $ultimaRonda = $partida['ultimaRonda'];

        return [
            'partidaId' => $partida['id'] ?? null,
            'estado' => $partida['estado'],
            'ronda' => $partida['ronda'],
            'puntos' => [
                'tu' => $partida['puntos'][$jugadorId],
                'oponente' => $partida['puntos'][$oponenteId],
            ],
            'jugaste' => array_key_exists($jugadorId, $partida['jugadas']),
            'oponenteJugo' => array_key_exists($oponenteId, $partida['jugadas']),
            'tuJugadaActual' => $partida['jugadas'][$jugadorId] ?? null,
            'ultimaRonda' => $ultimaRonda === null ? null : [
                'numero' => $ultimaRonda['numero'],
                'jugadas' => [
                    'tu' => $ultimaRonda['jugadas'][$jugadorId],
                    'oponente' => $ultimaRonda['jugadas'][$oponenteId],
                ],
                'ganador' => $ultimaRonda['ganadorId'] === null
                    ? 'empate'
                    : ($ultimaRonda['ganadorId'] === $jugadorId ? 'tu' : 'oponente'),
            ],
            'ganador' => $partida['ganadorId'] === null
                ? null
                : ($partida['ganadorId'] === $jugadorId ? 'tu' : 'oponente'),
        ];
    }

    private function isPlayer(array $partida, string $jugadorId): bool
    {
        return $partida['jugadorUnoId'] === $jugadorId || $partida['jugadorDosId'] === $jugadorId;
    }

    private function isRecentlyWaiting(string $jugadorId): bool
    {
        $jugador = Cache::get($this->playerKey($jugadorId));

        return ($jugador['estado'] ?? null) === 'esperando'
            && ($jugador['actualizado'] ?? 0) >= now()->subSeconds(30)->timestamp;
    }

    private function playerKey(string $jugadorId): string
    {
        return 'piedra-papel-tijera:jugador:'.$jugadorId;
    }

    private function matchKey(string $partidaId): string
    {
        return 'piedra-papel-tijera:partida:'.$partidaId;
    }

    private function matchLockKey(string $partidaId): string
    {
        return 'piedra-papel-tijera:partida-lock:'.$partidaId;
    }
}
