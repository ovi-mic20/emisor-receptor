<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $modo === 'solo' ? 'Partida contra el bot' : 'Partida en línea' }} · Piedra, papel o tijera</title>
    @vite(['resources/css/app.css', 'resources/css/ppt.css', 'resources/js/ppt.js'])
</head>
<body
    class="ppt-page"
    data-mode="{{ $modo }}"
    data-search-url="{{ route('partidas.buscar') }}"
    data-state-url-template="{{ route('partidas.estado', ['partidaId' => 'PARTIDA_ID']) }}"
    data-move-url-template="{{ route('partidas.jugadas', ['partidaId' => 'PARTIDA_ID']) }}"
    data-cancel-url-template="{{ route('partidas.cancelar-espera', ['jugadorId' => 'JUGADOR_ID']) }}"
    data-home-url="{{ route('inicio') }}"
>
    <main class="ppt-shell game-shell">
        <nav class="game-nav" aria-label="Navegación">
            <a class="back-link" href="{{ route('inicio') }}">← <span>Volver al inicio</span></a>
            <span class="ppt-eyebrow">{{ $modo === 'solo' ? 'MODO SOLITARIO' : 'MODO EN LÍNEA' }}</span>
        </nav>

        <section class="game-card" aria-labelledby="game-title">
            <header class="game-heading">
                <div>
                    <span class="mode-number">PIEDRA · PAPEL · TIJERA</span>
                    <h1 id="game-title">{{ $modo === 'solo' ? 'Contra el bot' : 'Duelo al azar' }}</h1>
                </div>
                <span class="round-pill" id="round-label">Ronda 1</span>
            </header>

            <p class="game-status" id="game-status" role="status" aria-live="polite">
                {{ $modo === 'solo' ? 'Elige tu jugada para empezar.' : 'Buscando un rival...' }}
            </p>

            <div class="scoreboard" aria-label="Marcador">
                <div class="score-card">
                    <span id="you-label">Tú</span>
                    <strong id="you-score">0</strong>
                    <span class="hand" id="you-hand" aria-label="Tu jugada">?</span>
                </div>
                <span class="versus" aria-hidden="true">:</span>
                <div class="score-card score-card-opponent">
                    <span id="opponent-label">{{ $modo === 'solo' ? 'Bot' : 'Rival' }}</span>
                    <strong id="opponent-score">0</strong>
                    <span class="hand" id="opponent-hand" aria-label="Jugada del rival">?</span>
                </div>
            </div>

            <p class="round-result" id="round-result" aria-live="polite"></p>

            <fieldset class="move-picker" id="move-picker" {{ $modo === 'online' ? 'disabled' : '' }}>
                <legend>Elige tu jugada</legend>
                <div class="move-options">
                    <button class="move-button" type="button" data-choice="piedra">
                        <span class="move-symbol" aria-hidden="true">✊</span>
                        <span>Piedra</span>
                    </button>
                    <button class="move-button" type="button" data-choice="papel">
                        <span class="move-symbol" aria-hidden="true">✋</span>
                        <span>Papel</span>
                    </button>
                    <button class="move-button" type="button" data-choice="tijera">
                        <span class="move-symbol" aria-hidden="true">✌️</span>
                        <span>Tijera</span>
                    </button>
                </div>
            </fieldset>

            <div class="game-actions">
                <button class="ppt-button ppt-button-secondary" id="cancel-search" type="button" hidden>Cancelar búsqueda</button>
                <button class="ppt-button" id="play-again" type="button" hidden>Jugar otra vez</button>
            </div>
            <p class="game-note">La partida termina cuando alguien gana 3 rondas. Los empates no suman puntos.</p>
        </section>
    </main>
</body>
</html>
