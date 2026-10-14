<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080b12">
    <title>Receptor | Orbital</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="space-page">
    <main class="space-shell">
        <header class="space-topbar">
            <a class="space-brand" href="{{ route('emisor') }}">
                <span class="brand-mark" aria-hidden="true"></span>
                <span>ORBITAL <span style="color:var(--space-muted)">/ RECEPTOR</span></span>
            </a>
            <a class="space-nav" href="{{ route('emisor') }}">
                Volver al emisor <span class="nav-arrow" aria-hidden="true">&#8599;</span>
            </a>
        </header>

        <section class="space-grid">
            <div class="space-copy receiver-copy">
                <p class="eyebrow">Sistema solar / Canal 01</p>
                <h1 class="space-title">Estacion de <span>escucha</span></h1>
                <p class="space-description">Las señales recibidas desde el emisor aparecen aqui al instante.</p>

                <section class="receiver-screen" aria-label="Mensaje recibido">
                    <div class="receiver-screen-header">
                        <span class="system-label">Transmision entrante</span>
                        <span class="signal-status"><span class="signal-dot" aria-hidden="true"></span>Escuchando</span>
                    </div>
                    <div id="target-div" class="message-orbit-stage" aria-hidden="true"></div>
                    <div id="spoken-message" class="visually-hidden" aria-live="polite">Esperando señal...</div>
                </section>
                <p class="receiver-footnote">FRECUENCIA 01.04 / ENLACE EN TIEMPO REAL</p>
            </div>

            <div class="orbit-scene" role="img" aria-label="Planetas girando alrededor de una estrella">
                <div class="orbit orbit-one"><span class="planet planet-one"></span></div>
                <div class="orbit orbit-two"><span class="planet planet-two"></span></div>
                <div class="orbit orbit-three"><span class="planet planet-three"></span></div>
                <div class="star-core"><span class="core-glint"></span></div>
                <div class="scene-caption"><span class="signal-dot" aria-hidden="true"></span>SEÑAL ESTABLE</div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const targetDiv = document.getElementById('target-div');
            const spokenMessage = document.getElementById('spoken-message');

            function renderMessage(message) {
                const text = String(message ?? '').trim() || 'Sin mensaje';
                const words = text.split(/\s+/);

                targetDiv.replaceChildren();
                spokenMessage.textContent = text;

                const star = document.createElement('span');
                star.className = 'message-star';
                targetDiv.append(star);

                const maximumOrbit = Math.min(targetDiv.clientWidth, targetDiv.clientHeight) * 0.88;
                const minimumOrbit = words.length > 1 ? Math.min(112, maximumOrbit * 0.45) : maximumOrbit;

                words.forEach((word, index) => {
                    const orbit = document.createElement('span');
                    const duration = 12 + (index % 5) * 2;
                    const delay = -(index * duration / words.length);
                    const orbitSize = words.length === 1
                        ? maximumOrbit
                        : minimumOrbit + (maximumOrbit - minimumOrbit) * index / (words.length - 1);

                    orbit.className = index % 2 === 0 ? 'message-orbit' : 'message-orbit message-orbit-reverse';
                    orbit.style.setProperty('--orbit-size', `${orbitSize}px`);
                    orbit.style.setProperty('--orbit-duration', `${duration}s`);
                    orbit.style.setProperty('--orbit-delay', `${delay}s`);

                    const wordElement = document.createElement('span');
                    wordElement.className = 'message-word';
                    wordElement.textContent = word;
                    orbit.append(wordElement);
                    targetDiv.append(orbit);
                });
            }

            renderMessage('Esperando señal...');

            // Suscripción al canal público y escucha del evento 
            window.Echo.channel('style-channel')
                .listen('.RemoteStyleUpdate', (e) => {
                    // El nombre con punto (.) escucha usando broadcastAs() 
                    renderMessage(e.newText);
                });
        });
    </script>
</body>

</html>