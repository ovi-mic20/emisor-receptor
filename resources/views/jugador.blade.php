<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jugador</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            color-scheme: dark;
            --game-ink: #070b12;
            --game-panel: rgba(8, 16, 24, 0.76);
            --game-line: rgba(164, 218, 224, 0.22);
            --game-text: #eef5ef;
            --game-muted: #a1b7b8;
            --game-cyan: #69e3dc;
            --game-pink: #fa987f;
        }

        * { box-sizing: border-box; }
        body { margin: 0; overflow: hidden; background: var(--game-ink); color: var(--game-text); font-family: 'Space Grotesk', ui-sans-serif, sans-serif; }
        #game { display: block; width: 100vw; height: 100vh; touch-action: none; }
        .game-hud { position: fixed; z-index: 2; inset: 20px 22px auto; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; pointer-events: none; }
        .game-title { margin: 0; font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }
        .game-title span { display: block; margin-top: 5px; color: var(--game-muted); font: 10px 'DM Mono', monospace; letter-spacing: 0; }
        .hud-stats { display: flex; gap: 8px; }
        .hud-stat { min-width: 88px; padding: 9px 12px; border: 1px solid var(--game-line); border-radius: 5px; background: var(--game-panel); backdrop-filter: blur(12px); font: 10px 'DM Mono', monospace; text-transform: uppercase; }
        .hud-stat strong { display: block; margin-top: 4px; color: var(--game-cyan); font: 600 18px 'Space Grotesk', sans-serif; }
        .game-button { border: 1px solid var(--game-line); border-radius: 4px; padding: 10px 13px; background: var(--game-panel); color: var(--game-text); cursor: pointer; font: 11px 'DM Mono', monospace; text-transform: uppercase; pointer-events: auto; }
        .game-button:hover { border-color: var(--game-cyan); color: var(--game-cyan); }
        .game-hint { position: fixed; z-index: 1; bottom: 18px; left: 22px; color: var(--game-muted); font: 10px 'DM Mono', monospace; line-height: 1.8; pointer-events: none; }
        .touch-controls { position: fixed; z-index: 3; right: 16px; bottom: max(16px, env(safe-area-inset-bottom)); left: 16px; display: flex; justify-content: space-between; align-items: flex-end; pointer-events: none; }
        .move-controls { display: flex; gap: 8px; }
        .touch-button { width: 54px; height: 54px; border: 1px solid var(--game-line); border-radius: 50%; background: var(--game-panel); color: var(--game-text); font: 18px 'DM Mono', monospace; touch-action: none; user-select: none; }
        .touch-fire { width: 76px; height: 76px; border-color: rgba(105, 227, 220, 0.55); color: var(--game-cyan); font-size: 10px; }
        .touch-button:active { background: rgba(105, 227, 220, 0.2); }
        @media (min-width: 761px) { .touch-controls { display: none; } }
        @media (max-width: 560px) {
            .game-hud { inset: 14px 12px auto; flex-wrap: wrap; }
            .game-title { flex: 1 0 100%; }
            .hud-stats { gap: 6px; }
            .hud-stat { min-width: 75px; padding: 8px 9px; }
            .game-button { padding: 9px 10px; }
            .game-hint { bottom: 98px; left: 14px; }
        }
    </style>
</head>
<body>
    <canvas id="game" aria-label="Juego de nave espacial: destruye los meteoritos antes de que alcancen la nave"></canvas>
    <header class="game-hud">
        <h1 class="game-title">Defensa orbital<span>Sector 07 / Supervivencia</span></h1>
        <div class="hud-stats" aria-live="polite">
            <div class="hud-stat">Puntos<strong id="score">0</strong></div>
            <div class="hud-stat">Vidas<strong id="lives">3</strong></div>
        </div>
        <button class="game-button" id="restart" type="button">Reiniciar</button>
    </header>
    <div class="game-hint">MOVER: FLECHAS / WASD<br>DISPARAR: ESPACIO</div>
    <div class="touch-controls" aria-label="Controles táctiles">
        <div class="move-controls">
            <button class="touch-button" type="button" data-control="ArrowLeft" aria-label="Mover a la izquierda">&#8592;</button>
            <button class="touch-button" type="button" data-control="ArrowRight" aria-label="Mover a la derecha">&#8594;</button>
        </div>
        <button class="touch-button touch-fire" type="button" data-control="Space">FUEGO</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const jugadorId = 'jug-' + Math.random().toString(36).slice(2, 8);
            const scoreElement = document.getElementById('score');
            const livesElement = document.getElementById('lives');
            const keys = new Set();
            const bullets = [];
            const meteors = [];
            let width = window.innerWidth;
            let height = window.innerHeight;
            let pixelRatio = 1;
            let stars = [];
            let score = 0;
            let lives = 3;
            let gameOver = false;
            let lastShotAt = 0;
            let lastMeteorAt = 0;
            let lastFrameAt = performance.now();
            let lastPositionAt = 0;
            let lastPosition = null;
            const player = { x: width / 2, y: height - 85, radius: 17, speed: 340 };

            function resize() {
                width = window.innerWidth;
                height = window.innerHeight;
                pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.round(width * pixelRatio);
                canvas.height = Math.round(height * pixelRatio);
                ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
                player.x = Math.max(24, Math.min(width - 24, player.x || width / 2));
                player.y = Math.max(52, Math.min(height - 55, player.y || height - 85));
                stars = Array.from({ length: Math.ceil(width * height / 8500) }, () => ({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 1.7 + 0.4,
                    phase: Math.random() * Math.PI * 2,
                }));
            }

            function onKeyDown(event) {
                const key = normalizeKey(event);
                if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Space'].includes(key)) event.preventDefault();
                keys.add(key);
            }

            function onKeyUp(event) {
                keys.delete(normalizeKey(event));
            }

            function normalizeKey(event) {
                if (event.code === 'Space') return 'Space';
                if (event.key.toLowerCase() === 'w') return 'ArrowUp';
                if (event.key.toLowerCase() === 's') return 'ArrowDown';
                if (event.key.toLowerCase() === 'a') return 'ArrowLeft';
                if (event.key.toLowerCase() === 'd') return 'ArrowRight';
                return event.key;
            }

            document.addEventListener('keydown', onKeyDown);
            document.addEventListener('keyup', onKeyUp);
            window.addEventListener('blur', () => keys.clear());
            window.addEventListener('resize', resize);

            document.querySelectorAll('[data-control]').forEach((button) => {
                const key = button.dataset.control;
                button.addEventListener('pointerdown', (event) => {
                    event.preventDefault();
                    button.setPointerCapture(event.pointerId);
                    keys.add(key);
                });
                const release = () => keys.delete(key);
                button.addEventListener('pointerup', release);
                button.addEventListener('pointercancel', release);
                button.addEventListener('lostpointercapture', release);
            });

            document.getElementById('restart').addEventListener('click', resetGame);

            function resetGame() {
                player.x = width / 2;
                player.y = height - 85;
                bullets.length = 0;
                meteors.length = 0;
                score = 0;
                lives = 3;
                gameOver = false;
                lastShotAt = 0;
                lastMeteorAt = 0;
                scoreElement.textContent = score;
                livesElement.textContent = lives;
            }

            function spawnMeteor(now) {
                const radius = 17 + Math.random() * 20;
                const vertices = Array.from({ length: 9 }, () => 0.72 + Math.random() * 0.48);
                meteors.push({
                    x: radius + Math.random() * Math.max(1, width - radius * 2),
                    y: -radius,
                    radius,
                    speed: 95 + Math.random() * 95 + Math.min(score, 250) * 0.28,
                    spin: (Math.random() - 0.5) * 2.4,
                    angle: Math.random() * Math.PI * 2,
                    vertices,
                });
                lastMeteorAt = now;
            }

            function update(delta, now) {
                if (gameOver) return;

                let directionX = Number(keys.has('ArrowRight')) - Number(keys.has('ArrowLeft'));
                let directionY = Number(keys.has('ArrowDown')) - Number(keys.has('ArrowUp'));
                const directionLength = Math.hypot(directionX, directionY) || 1;
                player.x += directionX / directionLength * player.speed * delta;
                player.y += directionY / directionLength * player.speed * delta;
                player.x = Math.max(player.radius, Math.min(width - player.radius, player.x));
                player.y = Math.max(54, Math.min(height - player.radius, player.y));

                if (keys.has('Space') && now - lastShotAt >= 220) {
                    bullets.push({ x: player.x, y: player.y - 22, speed: 570 });
                    lastShotAt = now;
                }

                if (now - lastMeteorAt >= Math.max(330, 900 - score * 2)) spawnMeteor(now);

                for (let index = bullets.length - 1; index >= 0; index--) {
                    bullets[index].y -= bullets[index].speed * delta;
                    if (bullets[index].y < -10) bullets.splice(index, 1);
                }

                for (let index = meteors.length - 1; index >= 0; index--) {
                    const meteor = meteors[index];
                    meteor.y += meteor.speed * delta;
                    meteor.angle += meteor.spin * delta;

                    if (meteor.y - meteor.radius > height) {
                        meteors.splice(index, 1);
                        loseLife();
                        continue;
                    }

                    if (meteorTouchesShip(meteor)) {
                        meteors.splice(index, 1);
                        loseLife();
                    }
                }

                for (let bulletIndex = bullets.length - 1; bulletIndex >= 0; bulletIndex--) {
                    const bullet = bullets[bulletIndex];
                    const meteorIndex = meteors.findIndex((meteor) => Math.hypot(meteor.x - bullet.x, meteor.y - bullet.y) < meteor.radius + 4);
                    if (meteorIndex !== -1) {
                        bullets.splice(bulletIndex, 1);
                        meteors.splice(meteorIndex, 1);
                        score += 10;
                        scoreElement.textContent = score;
                    }
                }
            }

            function loseLife() {
                lives = Math.max(0, lives - 1);
                livesElement.textContent = lives;
                if (lives === 0) gameOver = true;
            }

            function meteorTouchesShip(meteor) {
                const ship = [
                    { x: 0, y: -22 },
                    { x: 16, y: 15 },
                    { x: 5, y: 11 },
                    { x: 0, y: 17 },
                    { x: -5, y: 11 },
                    { x: -16, y: 15 },
                ].map((point) => ({ x: point.x + player.x, y: point.y + player.y }));
                const rock = meteor.vertices.map((scale, index) => {
                    const angle = meteor.angle + index / meteor.vertices.length * Math.PI * 2;
                    const radius = meteor.radius * scale;
                    return {
                        x: meteor.x + Math.cos(angle) * radius,
                        y: meteor.y + Math.sin(angle) * radius,
                    };
                });

                function contains(polygon, point) {
                    let inside = false;
                    for (let index = 0, previous = polygon.length - 1; index < polygon.length; previous = index++) {
                        const currentPoint = polygon[index];
                        const previousPoint = polygon[previous];
                        if ((currentPoint.y > point.y) !== (previousPoint.y > point.y)
                            && point.x < (previousPoint.x - currentPoint.x) * (point.y - currentPoint.y) / (previousPoint.y - currentPoint.y) + currentPoint.x) {
                            inside = !inside;
                        }
                    }
                    return inside;
                }

                function edgesCross(first, second, third, fourth) {
                    const cross = (start, end, point) => (end.x - start.x) * (point.y - start.y) - (end.y - start.y) * (point.x - start.x);
                    const firstSide = cross(first, second, third);
                    const secondSide = cross(first, second, fourth);
                    const thirdSide = cross(third, fourth, first);
                    const fourthSide = cross(third, fourth, second);
                    return firstSide * secondSide <= 0 && thirdSide * fourthSide <= 0;
                }

                if (ship.some((point) => contains(rock, point)) || rock.some((point) => contains(ship, point))) return true;

                return ship.some((point, index) => {
                    const nextShipPoint = ship[(index + 1) % ship.length];
                    return rock.some((rockPoint, rockIndex) => edgesCross(
                        point,
                        nextShipPoint,
                        rockPoint,
                        rock[(rockIndex + 1) % rock.length]
                    ));
                });
            }

            function draw(now) {
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

                for (const bullet of bullets) drawBullet(bullet);
                for (const meteor of meteors) drawMeteor(meteor);
                drawShip(now);

                if (gameOver) drawGameOver();
            }

            function drawShip(now) {
                ctx.save();
                ctx.translate(player.x, player.y);
                const flameLength = 10 + Math.sin(now / 48) * 4;
                ctx.fillStyle = '#fa987f';
                ctx.beginPath();
                ctx.moveTo(-6, 12);
                ctx.lineTo(0, 12 + flameLength);
                ctx.lineTo(6, 12);
                ctx.closePath();
                ctx.fill();

                ctx.shadowColor = '#69e3dc';
                ctx.shadowBlur = 18;
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
                ctx.shadowBlur = 0;
                ctx.fillStyle = '#286778';
                ctx.beginPath();
                ctx.ellipse(0, 0, 4, 8, 0, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }

            function drawBullet(bullet) {
                ctx.save();
                ctx.shadowColor = '#69e3dc';
                ctx.shadowBlur = 14;
                ctx.fillStyle = '#d8fff4';
                ctx.beginPath();
                ctx.roundRect(bullet.x - 2, bullet.y - 8, 4, 16, 2);
                ctx.fill();
                ctx.restore();
            }

            function drawMeteor(meteor) {
                ctx.save();
                ctx.translate(meteor.x, meteor.y);
                ctx.rotate(meteor.angle);
                ctx.fillStyle = '#a96758';
                ctx.strokeStyle = '#f1ad8d';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                meteor.vertices.forEach((scale, index) => {
                    const angle = index / meteor.vertices.length * Math.PI * 2;
                    const x = Math.cos(angle) * meteor.radius * scale;
                    const y = Math.sin(angle) * meteor.radius * scale;
                    if (index === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                });
                ctx.closePath();
                ctx.fill();
                ctx.stroke();
                ctx.fillStyle = 'rgba(49, 32, 35, 0.48)';
                ctx.beginPath();
                ctx.arc(-meteor.radius * 0.2, -meteor.radius * 0.12, meteor.radius * 0.18, 0, Math.PI * 2);
                ctx.arc(meteor.radius * 0.34, meteor.radius * 0.22, meteor.radius * 0.11, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }

            function drawGameOver() {
                ctx.fillStyle = 'rgba(3, 7, 12, 0.72)';
                ctx.fillRect(0, 0, width, height);
                ctx.textAlign = 'center';
                ctx.fillStyle = '#eef5ef';
                ctx.font = '600 32px Space Grotesk, sans-serif';
                ctx.fillText('NAVE DESTRUIDA', width / 2, height / 2 - 12);
                ctx.fillStyle = '#a1b7b8';
                ctx.font = '14px DM Mono, monospace';
                ctx.fillText(`PUNTOS: ${score}  /  PRESIONA REINICIAR`, width / 2, height / 2 + 22);
            }

            function syncPosition(now) {
                if (now - lastPositionAt < 100 || (lastPosition && lastPosition.x === player.x && lastPosition.y === player.y)) return;
                lastPositionAt = now;
                lastPosition = { x: player.x, y: player.y };
                fetch('/mover', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        jugadorId,
                        x: player.x,
                        y: player.y,
                        viewportWidth: width,
                        viewportHeight: height,
                    }),
                }).catch(() => {});
            }

            function loop(now) {
                const delta = Math.min((now - lastFrameAt) / 1000, 0.05);
                lastFrameAt = now;
                update(delta, now);
                draw(now);
                syncPosition(now);
                requestAnimationFrame(loop);
            }

            resize();
            requestAnimationFrame(loop);
        });
    </script>
</body>
</html>
