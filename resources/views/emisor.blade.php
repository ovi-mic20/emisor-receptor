<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080b12">
    <title>Emisor | Orbital</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="space-page">
    <main class="space-shell">
        <header class="space-topbar">
            <a class="space-brand" href="{{ route('emisor') }}">
                <span class="brand-mark" aria-hidden="true"></span>
                <span>ORBITAL <span style="color:var(--space-muted)">/ CONTROL</span></span>
            </a>
            <a class="space-nav" href="{{ route('receptor') }}">
                Abrir receptor <span class="nav-arrow" aria-hidden="true">&#8599;</span>
            </a>
        </header>

        <section class="space-grid">
            <div class="space-copy">
                <p class="eyebrow">Sistema solar / Canal 01</p>
                <h1 class="space-title">Centro de <span>transmision</span></h1>
                <p class="space-description">Envía una señal a través del espacio. El receptor la verá aparecer en tiempo real.</p>

                @if (session('ok'))
                    <div class="form-notice form-notice-success" role="status">{{ session('ok') }}</div>
                @endif
                @if ($errors->any())
                    <div class="form-notice form-notice-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form class="transmission-form" method="POST" action="{{ route('emitir') }}">
                    @csrf
                    <div class="form-fields">
                        <div>
                            <label class="field-label" for="newText">Mensaje de la señal</label>
                            <input class="space-input" id="newText" type="text" name="newText" placeholder="Ej: Ahora atendiendo: A-037" required>
                        </div>
                        <div>
                            <label class="field-label" for="newStyle">Estilo CSS (opcional)</label>
                            <textarea class="space-textarea" id="newStyle" name="newStyle" rows="4" placeholder="color:#a8f0d5;font-size:28px;text-align:center;"></textarea>
                        </div>
                    </div>
                    <button class="transmit-button" type="submit">
                        <span class="button-orbit" aria-hidden="true"></span>
                        <span>Emitir transmision</span>
                        <span class="button-arrow" aria-hidden="true">&#8594;</span>
                    </button>
                </form>
                <div class="space-meta"><span class="signal-dot" aria-hidden="true"></span>Enlace de datos activo</div>
            </div>

            <div class="orbit-scene" role="img" aria-label="Planetas girando alrededor de una estrella">
                <div class="orbit orbit-one"><span class="planet planet-one"></span></div>
                <div class="orbit orbit-two"><span class="planet planet-two"></span></div>
                <div class="orbit orbit-three"><span class="planet planet-three"></span></div>
                <div class="star-core"><span class="core-glint"></span></div>
                <div class="scene-caption"><span class="signal-dot" aria-hidden="true"></span>ORBITA DE TRANSMISION</div>
            </div>
        </section>
    </main>
</body>

</html>