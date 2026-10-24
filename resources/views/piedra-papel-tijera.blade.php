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
                    <div class="hand-stage" id="you-hand" data-revealed="false" aria-label="Tu jugada aún no revelada" role="img">
                        <svg class="hand-illustration" viewBox="0 0 180 160" aria-hidden="true">
                            <g class="hand-pose" data-pose="piedra">
                                <path class="hand-sleeve" d="M68 120h46l8 31q-31 12-62 0z"/>
                                <path class="hand-skin" d="M64 119c-10-4-15-11-15-21V77c0-9 7-14 15-12 4-11 17-13 24-6 9-9 23-6 27 3 9-5 20 1 19 11l-2 19c-1 13-8 22-21 28H77c-6 0-10-1-13-3z"/>
                                <path class="hand-thumb" d="M54 91c8-7 17-7 25-2l24 3c9 1 12 12 5 17l-18 11-25-4c-12-2-18-16-11-25z"/>
                                <path class="hand-crease" d="M62 71v15M83 63v19M107 65v17M65 105c15 5 29 6 42 2"/>
                            </g>
                            <g class="hand-pose" data-pose="papel">
                                <path class="hand-sleeve" d="M70 119h45l8 32q-31 12-61 0z"/>
                                <path class="hand-skin" d="M62 119 51 87c-3-9 2-16 10-18 7-2 13 2 16 9l3 8-7-42c-2-10 4-17 12-18 8-1 14 5 15 13l5 35-1-48c0-10 6-16 14-16s14 7 14 16l-1 50 7-37c2-9 9-14 17-12 8 2 12 9 10 18l-10 53c-3 16-13 28-29 32l-37 1c-14 0-23-5-27-19z"/>
                                <path class="hand-thumb" d="M64 89c-10-6-20 2-17 12l8 18 22 10 9-14-13-19z"/>
                                <path class="hand-crease" d="M85 57l4 31M111 48v40M138 61l-6 28M83 105h49"/>
                            </g>
                            <g class="hand-pose" data-pose="tijera">
                                <path class="hand-sleeve" d="M71 120h45l8 31q-31 12-61 0z"/>
                                <path class="hand-skin" d="M66 120c-8-5-12-14-11-25l4-20c1-8 8-12 15-10l-7-26c-2-9 3-16 11-18 8-2 15 3 17 11l10 38 12-43c3-9 11-13 19-10 8 3 11 11 8 19l-15 40 9-8c7-6 16-4 20 3 4 7 1 15-5 20l-16 14c-2 10-9 19-20 25l-31 1c-9 0-15-3-20-11z"/>
                                <path class="hand-thumb" d="M62 87c-9-4-17 4-13 13l10 17 21 9 8-14-15-19z"/>
                                <path class="hand-crease" d="M65 78l12 22M112 76l17 8M79 108h38"/>
                            </g>
                            <text class="hand-question" x="91" y="99">?</text>
                        </svg>
                        <span class="hand-caption" aria-hidden="true">?</span>
                    </div>
                </div>
                <span class="versus" aria-hidden="true">:</span>
                <div class="score-card score-card-opponent">
                    <span id="opponent-label">{{ $modo === 'solo' ? 'Bot' : 'Rival' }}</span>
                    <strong id="opponent-score">0</strong>
                    <div class="hand-stage" id="opponent-hand" data-revealed="false" aria-label="La jugada del rival aún no fue revelada" role="img">
                        <svg class="hand-illustration" viewBox="0 0 180 160" aria-hidden="true">
                            <g class="hand-pose" data-pose="piedra">
                                <path class="hand-sleeve" d="M68 120h46l8 31q-31 12-62 0z"/>
                                <path class="hand-skin" d="M64 119c-10-4-15-11-15-21V77c0-9 7-14 15-12 4-11 17-13 24-6 9-9 23-6 27 3 9-5 20 1 19 11l-2 19c-1 13-8 22-21 28H77c-6 0-10-1-13-3z"/>
                                <path class="hand-thumb" d="M54 91c8-7 17-7 25-2l24 3c9 1 12 12 5 17l-18 11-25-4c-12-2-18-16-11-25z"/>
                                <path class="hand-crease" d="M62 71v15M83 63v19M107 65v17M65 105c15 5 29 6 42 2"/>
                            </g>
                            <g class="hand-pose" data-pose="papel">
                                <path class="hand-sleeve" d="M70 119h45l8 32q-31 12-61 0z"/>
                                <path class="hand-skin" d="M62 119 51 87c-3-9 2-16 10-18 7-2 13 2 16 9l3 8-7-42c-2-10 4-17 12-18 8-1 14 5 15 13l5 35-1-48c0-10 6-16 14-16s14 7 14 16l-1 50 7-37c2-9 9-14 17-12 8 2 12 9 10 18l-10 53c-3 16-13 28-29 32l-37 1c-14 0-23-5-27-19z"/>
                                <path class="hand-thumb" d="M64 89c-10-6-20 2-17 12l8 18 22 10 9-14-13-19z"/>
                                <path class="hand-crease" d="M85 57l4 31M111 48v40M138 61l-6 28M83 105h49"/>
                            </g>
                            <g class="hand-pose" data-pose="tijera">
                                <path class="hand-sleeve" d="M71 120h45l8 31q-31 12-61 0z"/>
                                <path class="hand-skin" d="M66 120c-8-5-12-14-11-25l4-20c1-8 8-12 15-10l-7-26c-2-9 3-16 11-18 8-2 15 3 17 11l10 38 12-43c3-9 11-13 19-10 8 3 11 11 8 19l-15 40 9-8c7-6 16-4 20 3 4 7 1 15-5 20l-16 14c-2 10-9 19-20 25l-31 1c-9 0-15-3-20-11z"/>
                                <path class="hand-thumb" d="M62 87c-9-4-17 4-13 13l10 17 21 9 8-14-15-19z"/>
                                <path class="hand-crease" d="M65 78l12 22M112 76l17 8M79 108h38"/>
                            </g>
                            <text class="hand-question" x="91" y="99">?</text>
                        </svg>
                        <span class="hand-caption" aria-hidden="true">?</span>
                    </div>
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
