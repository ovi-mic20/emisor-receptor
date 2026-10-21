<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espectador</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; overflow: hidden; background: #070b12; color: #eef5ef; font-family: 'Space Grotesk', ui-sans-serif, sans-serif; }
        canvas { display: block; width: 100vw; height: 100vh; }
        #info { position: fixed; top: 18px; left: 20px; padding: 10px 13px; border: 1px solid rgba(164, 218, 224, .22); border-radius: 4px; background: rgba(8, 16, 24, .76); color: #a1b7b8; font: 10px 'DM Mono', monospace; letter-spacing: .08em; text-transform: uppercase; }
    </style>
</head>
<body>
    <canvas id="game" aria-label="Vista en tiempo real de las naves de los jugadores"></canvas>
    <div id="info" aria-live="polite">ESPECTADOR / CONECTANDO</div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');
            const info = document.getElementById('info');
            const naves = {};
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
                const nave = naves[e.jugadorId];
                const nuevaPosicion = {
                    x: Number(e.x),
                    y: Number(e.y),
                    viewportWidth: Number(e.viewportWidth) || 800,
                    viewportHeight: Number(e.viewportHeight) || 600,
                    receivedAt: performance.now(),
                };
                if (nave) {
                    Object.assign(nave, nuevaPosicion);
                } else {
                    naves[e.jugadorId] = {
                        ...nuevaPosicion,
                        drawX: nuevaPosicion.x / nuevaPosicion.viewportWidth * width,
                        drawY: nuevaPosicion.y / nuevaPosicion.viewportHeight * height,
                    };
                }
                info.textContent = `ESPECTADOR / ${Object.keys(naves).length} JUGADOR${Object.keys(naves).length === 1 ? '' : 'ES'}`;
            });

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

                for (const [id, nave] of Object.entries(naves)) {
                    if (now - nave.receivedAt > 15000) {
                        delete naves[id];
                        continue;
                    }
                    const targetX = nave.x / nave.viewportWidth * width;
                    const targetY = nave.y / nave.viewportHeight * height;
                    nave.drawX += (targetX - nave.drawX) * Math.min(1, delta * 14);
                    nave.drawY += (targetY - nave.drawY) * Math.min(1, delta * 14);
                    ctx.save();
                    ctx.translate(nave.drawX, nave.drawY);
                    ctx.shadowColor = '#69e3dc';
                    ctx.shadowBlur = 16;
                    ctx.fillStyle = '#9af2e7';
                    ctx.strokeStyle = '#e1fff5';
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(0, -22);
                    ctx.lineTo(16, 15);
                    ctx.lineTo(5, 11);
                    ctx.lineTo(0, 17);
                    ctx.lineTo(-5, 11);
                    ctx.lineTo(-16, 15);
                    ctx.closePath();
                    ctx.fill();
                    ctx.stroke();
                    ctx.restore();
                }

                requestAnimationFrame(dibujar);
            }

            window.addEventListener('resize', resize);
            resize();
            requestAnimationFrame(dibujar);
        });
    </script>
</body>
</html>
