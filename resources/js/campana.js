document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('game');
    const ctx = canvas.getContext('2d');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const connection = document.getElementById('connection');
    const connectionDot = document.getElementById('connectionDot');
    const roomStatus = document.getElementById('roomStatus');
    const pilotsElement = document.getElementById('pilots');
    const scoreElement = document.getElementById('score');
    const livesElement = document.getElementById('lives');
    const survivalElement = document.getElementById('survival');
    const allianceStatus = document.getElementById('allianceStatus');
    const allianceMessage = document.getElementById('allianceMessage');
    const allianceDialog = document.getElementById('allianceDialog');
    const gameOverDialog = document.getElementById('gameOver');
    const toastElement = document.getElementById('toast');
    const playerId = 'camp-' + (window.crypto && crypto.randomUUID
        ? crypto.randomUUID()
        : Math.random().toString(36).slice(2) + Date.now().toString(36));
    const keys = new Set();
    const players = new Map();
    const bullets = [];
    const destroyed = new Set();
    const processedShots = new Set();
    const processedRelations = new Set();
    const stars = [];
    const pendingImpacts = [];
    let pendingRelation = null;
    let incomingRequest = null;
    let allianceAwaitingResponse = false;
    let relation = 'enemy';
    let width = window.innerWidth;
    let height = window.innerHeight;
    let pixelRatio = 1;
    let score = 0;
    let lives = 3;
    let gameOver = false;
    let roomFull = false;
    let lastFrameAt = performance.now();
    let lastSyncAt = 0;
    let syncRetryAt = 0;
    let lastSyncErrorLogAt = 0;
    let lastShotAt = 0;
    let startedAt = Date.now();
    let displayedSurvival = -1;
    let syncing = false;
    let shotSequence = 0;
    let toastTimer = null;

    const player = { x: width / 2, y: height * .72, angle: -Math.PI / 2, speed: 250 };

    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(width * pixelRatio);
        canvas.height = Math.round(height * pixelRatio);
        ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
        player.x = Math.max(24, Math.min(width - 24, player.x || width / 2));
        player.y = Math.max(54, Math.min(height - 35, player.y || height * .72));
        stars.length = 0;
        for (let index = 0; index < Math.ceil(width * height / 7000); index++) {
            stars.push({
                x: Math.random() * width,
                y: Math.random() * height,
                size: Math.random() * 1.5 + .35,
                depth: Math.random() * .8 + .2,
                phase: Math.random() * Math.PI * 2,
            });
        }
    }

    function setConnection(message, state) {
        connection.textContent = message;
        connectionDot.classList.toggle('connected', state === 'connected');
        connectionDot.classList.toggle('error', state === 'error');
    }

    function showToast(message) {
        toastElement.textContent = message;
        toastElement.classList.add('visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toastElement.classList.remove('visible'), 2600);
    }

    function currentRivals() {
        const now = Date.now();
        for (const [id, rival] of players) {
            if (now - rival.receivedAt > 15000) players.delete(id);
        }
        return [...players.entries()];
    }

    function updateRoom() {
        const rivals = currentRivals();
        roomFull = rivals.length > 1;
        roomStatus.textContent = roomFull
            ? 'Sala llena · máximo 2 pilotos'
            : rivals.length === 1
                ? 'Dos pilotos en combate'
                : 'Esperando a otro piloto';
        document.getElementById('requestAlliance').disabled = rivals.length !== 1
            || relation === 'ally' || allianceAwaitingResponse || Boolean(pendingRelation) || Boolean(incomingRequest) || roomFull;
        allianceStatus.textContent = relation === 'ally'
            ? 'Aliados'
            : rivals.length === 0 ? 'Sin compañero' : 'Enemigos';
        if (relation === 'ally') {
            allianceMessage.textContent = 'Fuego amigo desactivado. Sobrevivan juntos.';
        } else if (allianceAwaitingResponse) {
            allianceMessage.textContent = 'Solicitud enviada · esperando respuesta.';
        } else if (incomingRequest) {
            allianceMessage.textContent = 'Tienes una solicitud de alianza pendiente.';
        } else {
            allianceMessage.textContent = 'Sin aceptar una solicitud, los pilotos son enemigos.';
        }

        pilotsElement.replaceChildren();
        const pilots = [{ id: playerId, score, lives, self: true }, ...rivals.map(([id, rival]) => ({ ...rival, id }))];
        for (const pilot of pilots) {
            const item = document.createElement('li');
            item.className = 'pilot' + (pilot.self ? '' : ' rival');
            const name = document.createElement('span');
            name.className = 'pilot-name';
            const mark = document.createElement('i');
            mark.className = 'pilot-mark';
            const label = document.createElement('span');
            label.textContent = pilot.self ? 'Tu nave' : `Piloto ${pilot.id.slice(-4).toUpperCase()}`;
            name.append(mark, label);
            const stats = document.createElement('span');
            stats.className = 'pilot-meta';
            stats.textContent = `${pilot.score ?? 0} pts · ${pilot.lives ?? 3} v`;
            item.append(name, stats);
            pilotsElement.append(item);
        }
    }

    function updateHud() {
        scoreElement.textContent = String(score);
        livesElement.textContent = String(lives);
        updateSurvival();
        updateRoom();
    }

    function updateSurvival() {
        const seconds = Math.floor((Date.now() - startedAt) / 1000);
        if (seconds === displayedSurvival) return;
        displayedSurvival = seconds;
        survivalElement.textContent = formatTime(seconds);
    }

    function formatTime(seconds) {
        return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
    }

    function handleKeyDown(event) {
        const key = normalizeKey(event);
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Space'].includes(key)) event.preventDefault();
        keys.add(key);
    }

    function normalizeKey(event) {
        if (event.code === 'Space') return 'Space';
        const key = event.key.toLowerCase();
        if (key === 'w') return 'ArrowUp';
        if (key === 's') return 'ArrowDown';
        if (key === 'a') return 'ArrowLeft';
        if (key === 'd') return 'ArrowRight';
        return event.key;
    }

    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', (event) => keys.delete(normalizeKey(event)));
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

    function nearestEnemy() {
        let nearest = null;
        let distance = Infinity;
        for (const [id, rival] of currentRivals()) {
            if (relation === 'ally') continue;
            const x = rival.x / rival.viewportWidth * width;
            const y = rival.y / rival.viewportHeight * height;
            const currentDistance = Math.hypot(x - player.x, y - player.y);
            if (currentDistance < distance) {
                nearest = { id, x, y };
                distance = currentDistance;
            }
        }
        return nearest;
    }

    function fire(targetPlayer = false) {
        const now = performance.now();
        if (gameOver || roomFull || now - lastShotAt < 330) return;
        const target = targetPlayer ? nearestEnemy() : null;
        if (targetPlayer && !target) return;
        lastShotAt = now;
        const angle = target ? Math.atan2(target.y - player.y, target.x - player.x) : player.angle;
        player.angle = angle;
        const shot = {
            id: `${playerId}-${++shotSequence}`,
            x: player.x + Math.cos(angle) * 20,
            y: player.y + Math.sin(angle) * 20,
            angle,
            viewportWidth: width,
            viewportHeight: height,
            targetId: target ? target.id : null,
        };
        bullets.push({ ...shot, ownerId: playerId, speed: 500 });
        shot.targetId = target ? target.id : null;
        pendingShot = shot;
    }

    function sendAlliance(action, targetId, requestId = null) {
        pendingRelation = {
            id: requestId || `${playerId}-relation-${Date.now()}`,
            emisorId: playerId,
            destinatarioId: targetId,
            accion: action,
        };
        updateHud();
    }

    document.getElementById('requestAlliance').addEventListener('click', () => {
        const rivals = currentRivals();
        if (rivals.length !== 1 || roomFull || relation === 'ally') return;
        allianceAwaitingResponse = true;
        sendAlliance('request', rivals[0][0]);
        allianceMessage.textContent = 'Solicitud enviada · esperando respuesta.';
    });

    document.getElementById('acceptAlliance').addEventListener('click', () => {
        if (!incomingRequest) return;
        const request = incomingRequest;
        incomingRequest = null;
        relation = 'ally';
        sendAlliance('accept', request.emisorId, request.id);
        allianceDialog.hidden = true;
        addLog('Alianza aceptada · fuego amigo desactivado');
    });

    document.getElementById('declineAlliance').addEventListener('click', () => {
        if (!incomingRequest) return;
        const request = incomingRequest;
        incomingRequest = null;
        relation = 'enemy';
        sendAlliance('reject', request.emisorId, request.id);
        allianceDialog.hidden = true;
        addLog('Alianza rechazada · pilotos enemigos');
    });

    function addLog(message) {
        showToast(message);
    }

    function receiveRelation(relacion) {
        if (!relacion || !relacion.id || processedRelations.has(relacion.id)) return;
        if (relacion.destinatarioId !== playerId) return;
        processedRelations.add(relacion.id);

        if (relacion.accion === 'request') {
            incomingRequest = { ...relacion };
            document.getElementById('alliancePrompt').textContent =
                `El piloto ${relacion.emisorId.slice(-4).toUpperCase()} propone luchar en equipo.`;
            allianceDialog.hidden = false;
        } else if (relacion.accion === 'accept') {
            relation = 'ally';
            allianceAwaitingResponse = false;
            pendingRelation = null;
            addLog('Alianza aceptada · fuego amigo desactivado');
        } else if (relacion.accion === 'reject') {
            relation = 'enemy';
            allianceAwaitingResponse = false;
            pendingRelation = null;
            addLog('Solicitud rechazada · pilotos enemigos');
        }
        updateHud();
    }

    function receiveNave(event) {
        if (!event.jugadorId || event.jugadorId === playerId) return;
        const rival = {
            x: Number(event.x),
            y: Number(event.y),
            viewportWidth: Number(event.viewportWidth) || 800,
            viewportHeight: Number(event.viewportHeight) || 600,
            score: Number(event.puntos) || 0,
            lives: Number.isFinite(Number(event.vidas)) ? Number(event.vidas) : 3,
            receivedAt: Date.now(),
        };
        if (!Number.isFinite(rival.x) || !Number.isFinite(rival.y)) return;
        const existing = players.get(event.jugadorId);
        if (existing) Object.assign(existing, rival);
        else players.set(event.jugadorId, rival);
        updateRoom();

        const shot = event.disparo;
        if (shot && shot.targetId === playerId && !processedShots.has(shot.id)) {
            processedShots.add(shot.id);
            bullets.push({
                ...shot,
                x: Number(shot.x) / Number(shot.viewportWidth) * width,
                y: Number(shot.y) / Number(shot.viewportHeight) * height,
                ownerId: event.jugadorId,
                speed: 500,
            });
        }

        if (event.impacto) {
            const impact = event.impacto;
            if (impact.tipo === 'meteorito' || impact.tipo === 'monstruo') destroyed.add(impact.id);
            if (impact.atacanteId === playerId && impact.tipo === 'rival') {
                score += 50;
                updateHud();
            }
        }
        receiveRelation(event.relacion);
    }

    const channel = window.Echo.channel('campana');
    channel.listen('.nave.movida', receiveNave);
    channel.subscribed(() => setConnection('Conectado · sala orbital', 'connected'));
    channel.error((error) => {
        console.error('No se pudo suscribir al canal de campaña.', error);
        setConnection('No se pudo conectar a la sala', 'error');
    });

    const pusherConnection = window.Echo.connector.pusher.connection;
    pusherConnection.bind('state_change', ({ current }) => {
        if (current === 'connected') setConnection('Conectado · sala orbital', 'connected');
        else if (current === 'connecting' || current === 'initialized') setConnection('Conectando con la sala...', 'pending');
        else setConnection(`WebSocket: ${current}`, 'error');
    });

    function syncState(now) {
        if (now - lastSyncAt < 100 || now < syncRetryAt || syncing || roomFull) return;
        lastSyncAt = now;
        syncing = true;
        const shot = pendingShot;
        const impact = pendingImpacts[0] || null;
        const relationChange = pendingRelation;
        fetch('/mover', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                jugadorId: playerId,
                x: player.x,
                y: player.y,
                viewportWidth: width,
                viewportHeight: height,
                modo: 'campana',
                vidas: lives,
                puntos: score,
                disparo: shot || undefined,
                impacto: impact || undefined,
                relacion: relationChange || undefined,
            }),
        }).then((response) => {
            if (!response.ok) throw new Error(`La sincronización respondió ${response.status}.`);
            if (shot && pendingShot && pendingShot.id === shot.id) pendingShot = null;
            if (impact && pendingImpacts[0] && pendingImpacts[0].id === impact.id) pendingImpacts.shift();
            if (relationChange && pendingRelation && pendingRelation.id === relationChange.id) pendingRelation = null;
            syncRetryAt = 0;
        }).catch((error) => {
            syncRetryAt = performance.now() + 1500;
            if (performance.now() - lastSyncErrorLogAt >= 5000) {
                console.error('No se pudo sincronizar la nave; se reintentará automáticamente.', error);
                lastSyncErrorLogAt = performance.now();
            }
            setConnection('Error de red · reintentando', 'error');
        }).finally(() => {
            syncing = false;
        });
    }

    function seededRandom(seed) {
        let value = seed | 0;
        return () => {
            value = (value * 1664525 + 1013904223) | 0;
            return (value >>> 0) / 4294967296;
        };
    }

    function getThreats(now) {
        const period = 2500;
        const slot = Math.floor(now / period);
        const threats = [];
        for (let index = Math.max(0, slot - 10); index <= slot; index++) {
            const random = seededRandom(index + 881);
            const monster = index % 4 === 3;
            const radius = monster ? 17 + random() * 8 : 14 + random() * 17;
            const x = random();
            const speed = monster ? 72 + random() * 38 : 88 + random() * 75;
            const elapsed = (now - index * period) / 1000;
            const y = -radius + elapsed * speed;
            if (y - radius < height && y + radius > 0) {
                threats.push({
                    id: `${monster ? 'monstruo' : 'meteorito'}-${index}`,
                    type: monster ? 'monstruo' : 'meteorito',
                    x: x * width,
                    y,
                    radius,
                    speed,
                    angle: index * .83 + now * .00025,
                });
            }
        }
        return threats;
    }

    function loseLife() {
        lives = Math.max(0, lives - 1);
        if (lives === 0) {
            gameOver = true;
            document.getElementById('finalScore').textContent =
                `Puntuación: ${score} · Tiempo: ${formatTime(Math.floor((Date.now() - startedAt) / 1000))}`;
            gameOverDialog.hidden = false;
            keys.clear();
        }
        updateHud();
    }

    function registerHit(threat, attackerId = playerId) {
        if (destroyed.has(threat.id)) return;
        destroyed.add(threat.id);
        if (attackerId === playerId) {
            const points = threat.type === 'monstruo' ? 25 : 10;
            score += points;
            addLog(`${threat.type === 'monstruo' ? 'Monstruo' : 'Meteorito'} destruido · +${points}`);
            updateHud();
        }
        if (attackerId === playerId) {
            pendingImpacts.push({ id: threat.id, atacanteId: playerId, tipo: threat.type });
        }
    }

    function update(delta, now) {
        for (const [id, rival] of currentRivals()) {
            if (Date.now() - rival.receivedAt > 15000) players.delete(id);
        }
        if (roomFull) {
            return;
        }
        if (!gameOver) {
            const dx = Number(keys.has('ArrowRight')) - Number(keys.has('ArrowLeft'));
            const dy = Number(keys.has('ArrowDown')) - Number(keys.has('ArrowUp'));
            const length = Math.hypot(dx, dy) || 1;
            player.x = Math.max(20, Math.min(width - 20, player.x + dx / length * player.speed * delta));
            player.y = Math.max(54, Math.min(height - 22, player.y + dy / length * player.speed * delta));
            if (keys.has('Space')) fire();
            if (keys.has('f') || keys.has('F')) fire(true);

            const threats = getThreats(Date.now()).filter((threat) => !destroyed.has(threat.id));
            for (let index = bullets.length - 1; index >= 0; index--) {
                const bullet = bullets[index];
                bullet.x += Math.cos(bullet.angle) * bullet.speed * delta;
                bullet.y += Math.sin(bullet.angle) * bullet.speed * delta;
                if (bullet.x < -30 || bullet.x > width + 30 || bullet.y < -30 || bullet.y > height + 30) {
                    bullets.splice(index, 1);
                    continue;
                }
                if (bullet.ownerId === playerId) {
                    const target = threats.find((threat) => Math.hypot(threat.x - bullet.x, threat.y - bullet.y) < threat.radius + 4);
                    if (target) {
                        registerHit(target);
                        bullets.splice(index, 1);
                    }
                } else if (bullet.targetId === playerId && relation !== 'ally'
                    && Math.hypot(bullet.x - player.x, bullet.y - player.y) < 20) {
                    bullets.splice(index, 1);
                    loseLife();
                    pendingImpacts.push({ id: bullet.id, atacanteId: bullet.ownerId, tipo: 'rival' });
                }
            }

            for (const threat of threats) {
                if (Math.hypot(threat.x - player.x, threat.y - player.y) < threat.radius + 15) {
                    destroyed.add(threat.id);
                    loseLife();
                }
            }
            syncState(now);
        }
        updateSurvival();
    }

    function drawBackground(now, delta) {
        const gradient = ctx.createLinearGradient(0, 0, width, height);
        gradient.addColorStop(0, '#101a29');
        gradient.addColorStop(.52, '#070d17');
        gradient.addColorStop(1, '#170f1a');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);
        const haze = ctx.createRadialGradient(width * .2, height * .38, 0, width * .2, height * .38, width * .56);
        haze.addColorStop(0, 'rgba(40, 112, 137, .2)');
        haze.addColorStop(1, 'rgba(8, 13, 26, 0)');
        ctx.fillStyle = haze;
        ctx.fillRect(0, 0, width, height);
        for (const star of stars) {
            if (!gameOver) {
                star.y += (5 + star.depth * 15) * delta;
                if (star.y > height) {
                    star.y = 0;
                    star.x = Math.random() * width;
                }
            }
            ctx.globalAlpha = (.28 + (Math.sin(now / 650 + star.phase) + 1) * .2) * star.depth;
            ctx.fillStyle = '#d9f3f2';
            ctx.fillRect(star.x, star.y, star.size * star.depth, star.size * star.depth);
        }
        ctx.globalAlpha = 1;
    }

    function drawShip(x, y, angle, color, now, label = '') {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle + Math.PI / 2);
        const pulse = 10 + Math.sin(now / 55 + x * .01) * 4;
        ctx.fillStyle = 'rgba(250, 152, 127, .7)';
        ctx.beginPath();
        ctx.moveTo(-5, 11);
        ctx.lineTo(0, 11 + pulse);
        ctx.lineTo(5, 11);
        ctx.closePath();
        ctx.fill();
        ctx.shadowColor = color;
        ctx.shadowBlur = 18;
        ctx.fillStyle = color === '#79e4dc' ? '#91ece3' : '#e0a08c';
        ctx.strokeStyle = '#e9f4ef';
        ctx.lineWidth = 1.1;
        ctx.beginPath();
        ctx.moveTo(0, -22);
        ctx.lineTo(15, 14);
        ctx.lineTo(5, 10);
        ctx.lineTo(0, 16);
        ctx.lineTo(-5, 10);
        ctx.lineTo(-15, 14);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
        ctx.restore();
        if (label) {
            ctx.fillStyle = '#e8efed';
            ctx.font = '9px "DM Mono", monospace';
            ctx.textAlign = 'center';
            ctx.fillText(label, x, y + 31);
        }
    }

    function drawMeteor(threat) {
        ctx.save();
        ctx.translate(threat.x, threat.y);
        ctx.rotate(threat.angle);
        const r = threat.radius;
        const shape = threat.type === 'monstruo'
            ? Array.from({ length: 10 }, (_, index) => .78 + Math.sin(index * 2.4 + threat.id.length) * .2)
            : Array.from({ length: 9 }, (_, index) => .78 + Math.sin(index * 4.1 + threat.id.length) * .18);
        const fill = ctx.createRadialGradient(-r * .3, -r * .35, 1, 0, 0, r * 1.2);
        if (threat.type === 'monstruo') {
            fill.addColorStop(0, '#9fbe8a');
            fill.addColorStop(.52, '#526d61');
            fill.addColorStop(1, '#253b3b');
            ctx.shadowColor = '#79e4dc';
        } else {
            fill.addColorStop(0, '#d0a07f');
            fill.addColorStop(.52, '#805b50');
            fill.addColorStop(1, '#382c34');
            ctx.shadowColor = '#fa987f';
        }
        ctx.shadowBlur = 13;
        ctx.fillStyle = fill;
        ctx.strokeStyle = threat.type === 'monstruo' ? 'rgba(185, 221, 170, .7)' : 'rgba(241, 177, 139, .65)';
        ctx.lineWidth = 1.1;
        ctx.beginPath();
        shape.forEach((scale, index) => {
            const angle = index / shape.length * Math.PI * 2;
            const x = Math.cos(angle) * r * scale;
            const y = Math.sin(angle) * r * scale;
            if (index === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
        ctx.shadowBlur = 0;
        ctx.fillStyle = 'rgba(19, 25, 27, .48)';
        if (threat.type === 'monstruo') {
            ctx.beginPath();
            ctx.arc(-r * .25, -r * .08, r * .12, 0, Math.PI * 2);
            ctx.arc(r * .25, -r * .08, r * .12, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = 'rgba(250, 152, 127, .75)';
            ctx.beginPath();
            ctx.moveTo(-r * .2, r * .28);
            ctx.quadraticCurveTo(0, r * .45, r * .2, r * .28);
            ctx.stroke();
        } else {
            ctx.beginPath();
            ctx.arc(-r * .22, -r * .1, r * .17, 0, Math.PI * 2);
            ctx.arc(r * .3, r * .2, r * .1, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.restore();
    }

    function draw(now) {
        const delta = Math.min((now - lastFrameAt) / 1000, .05);
        lastFrameAt = now;
        update(delta, now);
        drawBackground(now, delta);
        for (const threat of getThreats(Date.now())) {
            if (!destroyed.has(threat.id)) drawMeteor(threat);
        }
        for (const bullet of bullets) {
            ctx.save();
            const color = bullet.ownerId === playerId ? '#79e4dc' : '#fa987f';
            ctx.shadowColor = color;
            ctx.shadowBlur = 12;
            ctx.strokeStyle = color;
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            ctx.moveTo(bullet.x - Math.cos(bullet.angle) * 17, bullet.y - Math.sin(bullet.angle) * 17);
            ctx.lineTo(bullet.x, bullet.y);
            ctx.stroke();
            ctx.restore();
        }
        for (const [, rival] of currentRivals()) {
            const x = rival.x / rival.viewportWidth * width;
            const y = rival.y / rival.viewportHeight * height;
            drawShip(x, y, player.angle, relation === 'ally' ? '#79e4dc' : '#fa987f', now, relation === 'ally' ? 'ALIADO' : 'RIVAL');
        }
        drawShip(player.x, player.y, player.angle, '#79e4dc', now);
        requestAnimationFrame(draw);
    }

    function restartGame() {
        player.x = width / 2;
        player.y = height * .72;
        player.angle = -Math.PI / 2;
        lives = 3;
        score = 0;
        startedAt = Date.now();
        gameOver = false;
        destroyed.clear();
        bullets.length = 0;
        pendingImpacts.length = 0;
        gameOverDialog.hidden = true;
        updateHud();
        addLog('Nueva salida de campaña');
    }

    document.getElementById('restart').addEventListener('click', restartGame);
    document.getElementById('playAgain').addEventListener('click', restartGame);

    resize();
    updateHud();
    requestAnimationFrame(draw);
});
