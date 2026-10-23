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
            --game-panel: rgba(9, 17, 27, .84);
            --game-line: rgba(166, 208, 219, .2);
            --game-text: #eef5f3;
            --game-muted: #9aaeb5;
            --game-cyan: #79e4dc;
            --game-pink: #fa987f;
        }

        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100svh;
            margin: 0;
            padding: clamp(22px, 5vw, 64px) 20px;
            place-items: center;
            background:
                radial-gradient(ellipse at 18% 20%, rgba(35, 88, 112, .2), transparent 42%),
                radial-gradient(ellipse at 85% 80%, rgba(105, 45, 60, .16), transparent 38%),
                var(--game-ink);
            color: var(--game-text);
            font-family: 'Space Grotesk', ui-sans-serif, sans-serif;
        }
        .menu { width: min(100%, 960px); }
        .menu-header { max-width: 690px; margin-bottom: 38px; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 17px;
            color: var(--game-cyan);
            font: 9px 'DM Mono', monospace;
            letter-spacing: .15em;
            text-transform: uppercase;
        }
        .eyebrow::before {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--game-cyan);
            box-shadow: 0 0 12px rgba(121, 228, 220, .7);
            content: "";
        }
        h1 { margin: 0; font-size: clamp(36px, 7vw, 68px); letter-spacing: -.055em; line-height: .99; }
        .intro { max-width: 570px; margin: 19px 0 0; color: var(--game-muted); font-size: 14px; line-height: 1.8; }
        .section-label { display: flex; align-items: center; gap: 12px; margin: 0 0 13px; color: var(--game-muted); font: 9px 'DM Mono', monospace; letter-spacing: .14em; text-transform: uppercase; }
        .section-label::after { height: 1px; flex: 1; background: linear-gradient(90deg, var(--game-line), transparent); content: ""; }
        .modes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .mode {
            position: relative;
            display: flex;
            min-height: 270px;
            flex-direction: column;
            align-items: flex-start;
            overflow: hidden;
            padding: 25px 26px;
            border: 1px solid var(--game-line);
            border-radius: 14px;
            background: linear-gradient(145deg, rgba(18, 32, 45, .92), rgba(7, 14, 23, .9));
            box-shadow: 0 16px 44px rgba(0, 0, 0, .3), inset 0 1px rgba(255, 255, 255, .035);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .mode::after {
            position: absolute;
            top: -90px;
            right: -70px;
            width: 210px;
            height: 210px;
            border: 1px solid rgba(121, 228, 220, .08);
            border-radius: 50%;
            box-shadow: 0 0 0 24px rgba(121, 228, 220, .025), 0 0 0 48px rgba(121, 228, 220, .018);
            content: "";
            pointer-events: none;
        }
        .mode:hover { transform: translateY(-3px); border-color: rgba(121, 228, 220, .38); box-shadow: 0 22px 56px rgba(0, 0, 0, .36), inset 0 1px rgba(255, 255, 255, .05); }
        .mode-campaign { border-color: rgba(250, 152, 127, .3); }
        .mode-campaign::after { border-color: rgba(250, 152, 127, .09); box-shadow: 0 0 0 24px rgba(250, 152, 127, .025), 0 0 0 48px rgba(250, 152, 127, .018); }
        .mode-index { color: var(--game-cyan); font: 9px 'DM Mono', monospace; letter-spacing: .14em; }
        .mode-campaign .mode-index { color: var(--game-pink); }
        .mode h2 { margin: 25px 0 8px; font-size: 24px; letter-spacing: -.035em; }
        .mode p { max-width: 370px; margin: 0 0 24px; color: var(--game-muted); font-size: 12px; line-height: 1.8; }
        .mode-link {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            margin-top: auto;
            padding: 0 13px;
            border: 1px solid rgba(121, 228, 220, .42);
            border-radius: 8px;
            color: var(--game-cyan);
            font: 9px 'DM Mono', monospace;
            letter-spacing: .1em;
            text-decoration: none;
            text-transform: uppercase;
            transition: background .18s ease, border-color .18s ease, transform .18s ease;
        }
        .mode-link:hover, .mode-link:focus-visible { transform: translateY(-1px); background: rgba(121, 228, 220, .1); outline: none; }
        .mode-campaign .mode-link { border-color: rgba(250, 152, 127, .42); color: var(--game-pink); }
        .mode-campaign .mode-link:hover, .mode-campaign .mode-link:focus-visible { background: rgba(250, 152, 127, .1); }
        .footnote { display: flex; align-items: center; gap: 9px; margin: 19px 0 0; color: var(--game-muted); font: 9px 'DM Mono', monospace; line-height: 1.7; }
        .footnote::before { width: 5px; height: 5px; flex: 0 0 auto; border-radius: 50%; background: var(--game-cyan); content: ""; }
        @media (max-width: 620px) {
            body { padding: 28px 16px; }
            .menu-header { margin-bottom: 27px; }
            h1 { font-size: clamp(36px, 11vw, 52px); }
            .intro { margin-top: 14px; font-size: 13px; }
            .modes { grid-template-columns: 1fr; }
            .mode { min-height: 220px; padding: 21px; border-radius: 12px; }
            .mode h2 { margin-top: 18px; font-size: 21px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <main class="menu">
        <header class="menu-header">
            <p class="eyebrow">Transmisión activa · Sector 07</p>
            <h1>Elige tu camino<br>en el espacio</h1>
            <p class="intro">Prepárate, piloto. Entra a una partida individual o abre la sala de campaña para ver a los demás jugadores en acción.</p>
        </header>

        <p class="section-label">Selecciona tu misión</p>
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
        <p class="footnote">Campaña orbital · Hasta dos pilotos por sala</p>
    </main>
</body>
</html>
