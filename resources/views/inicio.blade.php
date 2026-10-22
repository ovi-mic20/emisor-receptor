<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elige tu camino en el espacio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            color-scheme: dark;
            --game-ink: #070b12;
            --game-panel: rgba(11, 21, 31, 0.84);
            --game-line: rgba(164, 218, 224, 0.22);
            --game-text: #eef5ef;
            --game-muted: #a1b7b8;
            --game-cyan: #69e3dc;
            --game-pink: #fa987f;
        }

        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 32px 20px;
            place-items: center;
            background:
                radial-gradient(ellipse at 15% 20%, rgba(42, 108, 115, .18), transparent 42%),
                radial-gradient(ellipse at 85% 78%, rgba(120, 55, 68, .16), transparent 40%),
                var(--game-ink);
            color: var(--game-text);
            font-family: 'Space Grotesk', ui-sans-serif, sans-serif;
        }
        .menu { width: min(100%, 920px); }
        .eyebrow { margin: 0 0 12px; color: var(--game-cyan); font: 11px 'DM Mono', monospace; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(32px, 6vw, 58px); letter-spacing: -.04em; line-height: 1.05; }
        .intro { max-width: 560px; margin: 16px 0 34px; color: var(--game-muted); font-size: 15px; line-height: 1.7; }
        .modes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .mode {
            display: flex;
            min-height: 255px;
            flex-direction: column;
            align-items: flex-start;
            padding: 25px;
            border: 1px solid var(--game-line);
            border-radius: 8px;
            background: var(--game-panel);
            box-shadow: 0 20px 70px rgba(0, 0, 0, .18);
            backdrop-filter: blur(12px);
        }
        .mode-campaign { border-color: rgba(250, 152, 127, .34); }
        .mode-index { color: var(--game-muted); font: 10px 'DM Mono', monospace; letter-spacing: .1em; }
        .mode h2 { margin: 23px 0 8px; font-size: 23px; }
        .mode p { margin: 0 0 24px; color: var(--game-muted); font-size: 13px; line-height: 1.65; }
        .mode-link {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            margin-top: auto;
            padding: 0 15px;
            border: 1px solid rgba(105, 227, 220, .5);
            border-radius: 4px;
            color: var(--game-cyan);
            font: 11px 'DM Mono', monospace;
            text-decoration: none;
            text-transform: uppercase;
            transition: background .18s ease, border-color .18s ease;
        }
        .mode-campaign .mode-link { border-color: rgba(250, 152, 127, .55); color: var(--game-pink); }
        .mode-link:hover, .mode-link:focus-visible { background: rgba(105, 227, 220, .1); outline: none; }
        .mode-campaign .mode-link:hover, .mode-campaign .mode-link:focus-visible { background: rgba(250, 152, 127, .1); }
        .footnote { margin: 20px 0 0; color: var(--game-muted); font: 10px 'DM Mono', monospace; line-height: 1.7; }
        @media (max-width: 620px) {
            body { padding: 28px 16px; }
            .intro { margin-bottom: 24px; }
            .modes { grid-template-columns: 1fr; }
            .mode { min-height: 220px; padding: 21px; }
            .mode h2 { margin-top: 17px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <main class="menu">
        <p class="eyebrow">Emitiendo en el Sector 07</p>
        <h1>Elige tu camino en el espacio</h1>
        <p class="intro">Prepárate, piloto. Entra a una partida individual o abre la sala de campaña para ver a los demás jugadores en acción.</p>

        <section class="modes" aria-label="Modos de juego">
            <article class="mode">
                <span class="mode-index">SUPERVIVENCIA</span>
                <h2>Modo individual</h2>
                <p>Controla tu nave, esquiva los meteoritos y dispara para sobrevivir todo lo posible.</p>
                <a class="mode-link" href="{{ route('jugador') }}">Jugar individual</a>
            </article>
            <article class="mode mode-campaign">
                <span class="mode-index">MULTIJUGADOR</span>
                <h2>Modo campaña</h2>
                <p>Enfréntate en tiempo real a otro piloto, ataca su nave y esquiva los meteoritos de la zona.</p>
                <a class="mode-link" href="{{ route('campana') }}">Abrir campaña</a>
            </article>
        </section>
</body>
</html>
