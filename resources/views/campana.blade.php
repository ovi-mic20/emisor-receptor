<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Campaña orbital</title>
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/campana.css',
        'resources/js/campana.js',
    ])
</head>
<body>
    <canvas id="game" aria-label="Combate espacial cooperativo: destruye meteoritos y monstruos"></canvas>

    <header class="game-hud">
        <div class="game-heading">
            <span class="eyebrow">SECTOR 07 · OPERACIÓN DE SUPERVIVENCIA</span>
            <h1>Campaña orbital</h1>
            <p><span class="status-dot" id="connectionDot"></span><span id="connection" aria-live="polite">Conectando con la sala...</span></p>
        </div>
        <div class="hud-stats" aria-live="polite">
            <div class="hud-stat"><span>PUNTOS</span><strong id="score">0</strong></div>
            <div class="hud-stat"><span>VIDAS</span><strong id="lives">3</strong></div>
            <div class="hud-stat"><span>TIEMPO</span><strong id="survival">0:00</strong></div>
        </div>
        <nav class="game-actions" aria-label="Acciones de campaña">
            <a class="game-button" href="{{ route('inicio') }}">Inicio</a>
            <button class="game-button game-button-primary" id="restart" type="button">Reiniciar</button>
        </nav>
    </header>

    <aside class="mission-panel">
        <div class="panel-topline">
            <span class="panel-kicker">COMUNICACIONES</span>
            <span class="live-indicator"><i></i> EN VIVO</span>
        </div>
        <h2>Tripulación</h2>
        <p class="panel-caption" id="roomStatus">Esperando a otro piloto</p>
        <ul class="pilot-list" id="pilots" aria-label="Pilotos en la sala"></ul>
        <div class="panel-divider"></div>
        <div class="alliance-row">
            <div>
                <span class="panel-kicker">VÍNCULO</span>
                <strong id="allianceStatus">Sin compañero</strong>
            </div>
            <button class="game-button alliance-button" id="requestAlliance" type="button" disabled>Proponer alianza</button>
        </div>
        <p class="panel-caption alliance-message" id="allianceMessage" aria-live="polite">La alianza es opcional. Sin aceptar, los pilotos son enemigos.</p>
        <div class="panel-divider"></div>
        <div class="threat-row">
            <span class="threat-icon meteor-icon" aria-hidden="true"></span>
            <span>Meteoritos<small>+10 puntos</small></span>
        </div>
        <div class="threat-row">
            <span class="threat-icon monster-icon" aria-hidden="true"></span>
            <span>Monstruos<small>+25 puntos</small></span>
        </div>
    </aside>

    <div class="game-hint">
        <span class="panel-kicker">CONTROLES</span>
        <span><kbd>WASD</kbd> / <kbd>↑ ↓ ← →</kbd> · Mover</span>
        <span><kbd>ESPACIO</kbd> · Disparar a amenazas</span>
        <span><kbd>F</kbd> · Disparar al rival</span>
    </div>

    <div class="touch-controls" aria-label="Controles táctiles">
        <div class="move-controls">
            <button class="touch-button" type="button" data-control="ArrowLeft" aria-label="Mover a la izquierda">&#8592;</button>
            <button class="touch-button" type="button" data-control="ArrowRight" aria-label="Mover a la derecha">&#8594;</button>
        </div>
        <button class="touch-button touch-fire" type="button" data-control="Space">FUEGO</button>
    </div>

    <section class="alliance-dialog" id="allianceDialog" role="dialog" aria-modal="true" aria-labelledby="allianceTitle" hidden>
        <div class="dialog-card">
            <span class="panel-kicker">SOLICITUD DE VÍNCULO</span>
            <h2 id="allianceTitle">¿Unimos fuerzas?</h2>
            <p id="alliancePrompt">Otro piloto propone formar una alianza.</p>
            <div class="dialog-actions">
                <button class="game-button" id="declineAlliance" type="button">Seguir como enemigos</button>
                <button class="game-button game-button-primary" id="acceptAlliance" type="button">Aceptar alianza</button>
            </div>
        </div>
    </section>

    <section class="game-over" id="gameOver" role="dialog" aria-modal="true" aria-labelledby="gameOverTitle" hidden>
        <div class="dialog-card">
            <span class="panel-kicker">FIN DE LA MISIÓN</span>
            <h2 id="gameOverTitle">Nave fuera de combate</h2>
            <p id="finalScore"></p>
            <button class="game-button game-button-primary" id="playAgain" type="button">Volver a la campaña</button>
        </div>
    </section>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
