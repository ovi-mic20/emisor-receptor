<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Juega piedra, papel o tijera contra el bot o contra otra persona en línea.">
    <title>Piedra, papel o tijera</title>
    @vite(['resources/css/app.css', 'resources/css/ppt.css'])
</head>
<body class="ppt-page">
    <main class="ppt-shell">
        <header class="ppt-header">
            <span class="ppt-eyebrow">JUEGO CLÁSICO · DUELO EN LÍNEA</span>
            <h1>Piedra, papel<br>o tijera</h1>
            <p>Elige cómo quieres jugar. Gana quien llegue primero a tres puntos.</p>
        </header>

        <section class="mode-grid" aria-label="Modos de juego">
            <article class="mode-card">
                <span class="mode-number">01 · PRÁCTICA</span>
                <h2>En solitario</h2>
                <p>Juega cuando quieras contra el bot. La partida se juega en este dispositivo.</p>
                <a class="ppt-button" href="{{ route('jugador') }}">Jugar contra el bot <span aria-hidden="true">→</span></a>
            </article>
            <article class="mode-card mode-card-online">
                <span class="mode-number">02 · EN LÍNEA</span>
                <h2>Contra otra persona</h2>
                <p>Entra a la cola y te emparejaremos al azar con otra persona que esté buscando partida.</p>
                <a class="ppt-button" href="{{ route('campana') }}">Buscar rival <span aria-hidden="true">→</span></a>
            </article>
        </section>
        <footer class="ppt-footer">Gana quien llegue primero a 3 puntos · Las jugadas se revelan al mismo tiempo</footer>
    </main>
</body>
</html>
