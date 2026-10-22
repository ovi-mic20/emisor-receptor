<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaña orbital</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --game-panel: rgba(8, 16, 24, .76); --game-line: rgba(164, 218, 224, .22); --game-text: #eef5ef; --game-muted: #a1b7b8; --game-cyan: #69e3dc; --game-pink: #fa987f; }
        * { box-sizing: border-box; }
        body { margin: 0; overflow: hidden; background: #070b12; color: var(--game-text); font-family: 'Space Grotesk', ui-sans-serif, sans-serif; }
        canvas { display: block; width: 100vw; height: 100vh; }
        .game-hud { position: fixed; z-index: 2; inset: 20px 22px auto; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; pointer-events: none; }
        .game-title { margin: 0; font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
        .game-title span { display: block; margin-top: 5px; color: var(--game-muted); font: 10px 'DM Mono', monospace; letter-spacing: 0; }
        #info { padding: 10px 13px; border: 1px solid var(--game-line); border-radius: 4px; background: var(--game-panel); color: var(--game-cyan); font: 10px 'DM Mono', monospace; letter-spacing: .08em; text-transform: uppercase; }
        .game-actions { display: flex; gap: 8px; pointer-events: auto; }
        .game-button { display: inline-flex; align-items: center; padding: 10px 13px; border: 1px solid var(--game-line); border-radius: 4px; background: var(--game-panel); color: var(--game-text); cursor: pointer; font: 11px 'DM Mono', monospace; text-decoration: none; text-transform: uppercase; }
        .game-button:hover, .game-button:focus-visible { border-color: var(--game-cyan); color: var(--game-cyan); outline: none; }
        .game-hint { position: fixed; z-index: 1; bottom: 18px; left: 22px; color: var(--game-muted); font: 10px 'DM Mono', monospace; line-height: 1.8; pointer-events: none; }
        @media (max-width: 680px) {
            .game-hud { inset: 14px 12px auto; flex-wrap: wrap; }
            .game-title { flex: 1 0 calc(50% - 8px); }
            #info { order: 3; }
            .game-actions { margin-left: auto; }
            .game-button { padding: 9px 10px; }
            .game-hint { bottom: 14px; left: 14px; max-width: 55%; }
        }
    </style>
</head>
<body>
    <canvas id="game" aria-label="Vista de campaña en tiempo real de las naves de los jugadores"></canvas>
    <header class="game-hud">
        <h1 class="game-title">Campaña orbital<span>Sector 07 / Enfrentamiento en vivo</span></h1>
        <div id="info" aria-live="polite">Buscando jugadores</div>
        <nav class="game-actions" aria-label="Acciones de campaña">
            <a class="game-button" href="{{ route('inicio') }}">Inicio</a>
            <a class="game-button" href="{{ route('jugador') }}" target="_blank" rel="noopener">Abrir nave</a>
        </nav>
    </header>
    <p class="game-hint">ABRE TU NAVE PARA UNIRTE<br>Las naves conectadas aparecen aquí</p>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');
            const info = document.getElementById('info');
            const enemigos = {};
            let width = window.innerWidth;
            let height = window.innerHeight;
            let pixelRatio = 1;
            let stars = [];
            let lastFrameAt = performance.now();

            function resize() {
                width = window.innerWidth;
                height = window.innerHeight;
                pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.round(width * pixelRatio);
                canvas.height = Math.round(height * pixelRatio);
                ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
                stars = Array.from({ length: Math.ceil(width * height / 8500) }, () => ({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 1.7 + 0.4,
                    phase: Math.random() * Math.PI * 2,
                }));
            }

            window.Echo.channel('galaxia').listen('.nave.movida', (e) => {
                const enemigo = enemigos[e.jugadorId];
                const nuevaPosicion = {
                    x: Number(e.x),
                    y: Number(e.y),
                    viewportWidth: Number(e.viewportWidth) || 800,
                    viewportHeight: Number(e.viewportHeight) || 600,
                    receivedAt: performance.now(),
                    prevX: enemigo ? enemigo.x : Number(e.x),
                    prevY: enemigo ? enemigo.y : Number(e.y),
                };
                if (enemigo) {
                    Object.assign(enemigo, nuevaPosicion);
                } else {
                    enemigos[e.jugadorId] = {
                        ...nuevaPosicion,
                        drawX: nuevaPosicion.x / nuevaPosicion.viewportWidth * width,
                        drawY: nuevaPosicion.y / nuevaPosicion.viewportHeight * height,
                        rotacion: 0,
                    };
                }
                actualizarEstado();
            });

            function actualizarEstado() {
                const jugadores = Object.keys(enemigos).length;
                if (jugadores === 0) {
                    info.textContent = 'Buscando jugadores';
                } else if (jugadores === 1) {
                    info.textContent = '1 jugador / esperando rival';
                } else {
                    info.textContent = `${jugadores} jugadores / combate en curso`;
                }
            }

            function dibujar(now) {
                const delta = Math.min((now - lastFrameAt) / 1000, 0.05);
                lastFrameAt = now;
                const background = ctx.createLinearGradient(0, 0, width, height);
                background.addColorStop(0, '#101d2b');
                background.addColorStop(0.52, '#08111c');
                background.addColorStop(1, '#160e1b');
                ctx.fillStyle = background;
                ctx.fillRect(0, 0, width, height);

                for (const star of stars) {
                    ctx.globalAlpha = 0.24 + (Math.sin(now / 750 + star.phase) + 1) * 0.24;
                    ctx.fillStyle = '#d9f3ed';
                    ctx.fillRect(star.x, star.y, star.size, star.size);
                }
                ctx.globalAlpha = 1;

                let seDesconectoJugador = false;
                for (const [id, enemigo] of Object.entries(enemigos)) {
                    if (now - enemigo.receivedAt > 15000) {
                        delete enemigos[id];
                        seDesconectoJugador = true;
                        continue;
                    }
                    const targetX = enemigo.x / enemigo.viewportWidth * width;
                    const targetY = enemigo.y / enemigo.viewportHeight * height;
                    enemigo.drawX += (targetX - enemigo.drawX) * Math.min(1, delta * 14);
                    enemigo.drawY += (targetY - enemigo.drawY) * Math.min(1, delta * 14);

                    const deltaX = enemigo.x - enemigo.prevX;
                    const deltaY = enemigo.y - enemigo.prevY;
                    if (Math.abs(deltaX) + Math.abs(deltaY) > 0.5) {
                        enemigo.rotacion = Math.atan2(deltaY, deltaX) + Math.PI / 2;
                    }

                    ctx.save();
                    ctx.translate(enemigo.drawX, enemigo.drawY);
                    ctx.rotate(enemigo.rotacion);
                    ctx.shadowColor = '#ff2b2b';
                    ctx.shadowBlur = 22;
                    ctx.fillStyle = '#d92b2b';
                    ctx.strokeStyle = '#ff6b6b';
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(0, -22);
                    ctx.lineTo(16, 15);
                    ctx.lineTo(6, 12);
                    ctx.lineTo(0, 17);
                    ctx.lineTo(-6, 12);
                    ctx.lineTo(-16, 15);
                    ctx.closePath();
                    ctx.fill();
                    ctx.stroke();
                    ctx.shadowBlur = 0;
                    ctx.fillStyle = '#ff5a5a';
                    ctx.beginPath();
                    ctx.arc(0, 2, 3, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.shadowBlur = 0;
                    ctx.fillStyle = '#eef5ef';
                    ctx.font = '10px "DM Mono", monospace';
                    ctx.textAlign = 'center';
                    ctx.fillText(id.slice(-4).toUpperCase(), 0, 34);
                    ctx.restore();
                }
                if (seDesconectoJugador) actualizarEstado();

                requestAnimationFrame(dibujar);
            }

            window.addEventListener('resize', resize);
            resize();
            requestAnimationFrame(dibujar);
        });
    </script>
</body>
</html>
