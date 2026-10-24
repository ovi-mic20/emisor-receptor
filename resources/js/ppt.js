const page = document.body;
const mode = page.dataset.mode;

if (mode) {
    const choices = ['piedra', 'papel', 'tijera'];
    const labels = {
        piedra: 'Piedra',
        papel: 'Papel',
        tijera: 'Tijera',
    };
    const playerHand = document.querySelector('#you-hand');
    const opponentHand = document.querySelector('#opponent-hand');
    const playerScore = document.querySelector('#you-score');
    const opponentScore = document.querySelector('#opponent-score');
    const roundLabel = document.querySelector('#round-label');
    const status = document.querySelector('#game-status');
    const result = document.querySelector('#round-result');
    const picker = document.querySelector('#move-picker');
    const playAgain = document.querySelector('#play-again');
    const choiceButtons = [...document.querySelectorAll('[data-choice]')];

    function setHand(element, choice, animate = false) {
        const caption = element.querySelector('.hand-caption');

        if (choice && element.dataset.choice === choice && element.dataset.revealed === 'true') {
            return;
        }

        if (choice) {
            element.dataset.choice = choice;
            element.dataset.revealed = 'true';
            element.setAttribute('aria-label', `Jugada: ${labels[choice]}`);
            caption.textContent = labels[choice];

            if (animate) {
                element.classList.remove('hand-reveal');
                void element.offsetWidth;
                element.classList.add('hand-reveal');
            }
        } else {
            delete element.dataset.choice;
            element.dataset.revealed = 'false';
            element.classList.remove('hand-reveal');
            element.setAttribute('aria-label', 'Todavía no hay jugada');
            caption.textContent = 'Esperando';
        }
    }

    function prepareHands(...hands) {
        hands.forEach((hand) => {
            hand.dataset.shaking = 'true';
            setHand(hand, null);
        });
    }

    function revealHands(...hands) {
        hands.forEach((hand) => {
            delete hand.dataset.shaking;
        });
    }

    function winnerOf(playerChoice, opponentChoice) {
        if (playerChoice === opponentChoice) {
            return 'empate';
        }

        const beats = {
            piedra: 'tijera',
            papel: 'piedra',
            tijera: 'papel',
        };

        return beats[playerChoice] === opponentChoice ? 'tu' : 'oponente';
    }

    if (mode === 'solo') {
        let playerPoints = 0;
        let botPoints = 0;
        let round = 1;
        let finished = false;
        let roundResolving = false;

        function playAgainstBot(playerChoice) {
            if (finished || roundResolving) {
                return;
            }

            roundResolving = true;
            picker.disabled = true;
            result.textContent = '';
            status.textContent = '¡Listos... piedra, papel o tijera!';
            prepareHands(playerHand, opponentHand);
            const botChoice = choices[Math.floor(Math.random() * choices.length)];

            window.setTimeout(() => {
                revealHands(playerHand, opponentHand);
                setHand(playerHand, playerChoice, true);
                setHand(opponentHand, botChoice, true);
                const winner = winnerOf(playerChoice, botChoice);

                if (winner === 'tu') {
                    playerPoints++;
                    status.textContent = 'Ganaste esta ronda.';
                    result.textContent = `${labels[playerChoice]} vence a ${labels[botChoice]}.`;
                } else if (winner === 'oponente') {
                    botPoints++;
                    status.textContent = 'El bot ganó esta ronda.';
                    result.textContent = `${labels[botChoice]} vence a ${labels[playerChoice]}.`;
                } else {
                    status.textContent = 'Empate. Esta ronda no suma puntos.';
                    result.textContent = `Ambos eligieron ${labels[playerChoice]}.`;
                }

                playerScore.textContent = String(playerPoints);
                opponentScore.textContent = String(botPoints);
                round++;
                roundLabel.textContent = `Ronda ${round}`;

                if (playerPoints === 3 || botPoints === 3) {
                    finished = true;
                    status.textContent = playerPoints === 3 ? '¡Ganaste la partida!' : 'El bot ganó la partida.';
                    roundLabel.textContent = 'Partida finalizada';
                    playAgain.hidden = false;
                } else {
                    picker.disabled = false;
                }

                roundResolving = false;
            }, 650);
        }

        choiceButtons.forEach((button) => {
            button.addEventListener('click', () => playAgainstBot(button.dataset.choice));
        });
    }

    if (mode === 'online') {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const playerId = getPlayerId();
        const cancelSearch = document.querySelector('#cancel-search');
        const homeUrl = page.dataset.homeUrl;
        let matchId = null;
        let matchmakingTimer = null;
        let stateTimer = null;
        let matchmakingRequestPending = false;
        let moveRequestPending = false;
        let echoChannel = null;
        let lastRevealedRound = 0;
        let isRevealingRound = false;
        let latestState = null;

        function getPlayerId() {
            let id = sessionStorage.getItem('ppt-player-id');

            if (!id) {
                id = crypto.randomUUID();
                sessionStorage.setItem('ppt-player-id', id);
            }

            return id;
        }

        async function requestJson(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    Accept: 'application/json',
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrf } : {}),
                    ...options.headers,
                },
            });
            const body = await response.json();

            if (!response.ok) {
                throw new Error(body.message || `La solicitud respondió ${response.status}.`);
            }

            return body;
        }

        function gameUrl(template, id, marker) {
            return template.replace(marker, encodeURIComponent(id));
        }

        function renderState(state) {
            latestState = state;

            if (isRevealingRound) {
                return;
            }

            playerScore.textContent = String(state.puntos.tu);
            opponentScore.textContent = String(state.puntos.oponente);
            roundLabel.textContent = state.estado === 'finalizada' ? 'Partida finalizada' : `Ronda ${state.ronda}`;
            picker.disabled = state.estado === 'finalizada' || state.jugaste || moveRequestPending;

            if (state.ultimaRonda && state.ultimaRonda.numero > lastRevealedRound) {
                lastRevealedRound = state.ultimaRonda.numero;
                isRevealingRound = true;
                picker.disabled = true;
                result.textContent = '';
                status.textContent = '¡Listos... piedra, papel o tijera!';
                prepareHands(playerHand, opponentHand);

                window.setTimeout(() => {
                    revealHands(playerHand, opponentHand);
                    setHand(playerHand, state.ultimaRonda.jugadas.tu, true);
                    setHand(opponentHand, state.ultimaRonda.jugadas.oponente, true);
                    isRevealingRound = false;
                    renderState(latestState);
                }, 650);

                return;
            }

            if (state.jugaste || state.oponenteJugo) {
                setHand(playerHand, state.tuJugadaActual);
                setHand(opponentHand, null);
                opponentHand.setAttribute('aria-label', state.oponenteJugo ? 'El rival ya eligió' : 'Esperando al rival');

                if (state.jugaste && state.oponenteJugo) {
                    status.textContent = 'Ambos eligieron. Revelando la ronda...';
                } else if (state.jugaste) {
                    status.textContent = 'Tu jugada está lista. Esperando al rival...';
                } else {
                    status.textContent = 'Tu rival ya eligió. Haz tu jugada.';
                }
            } else if (state.ultimaRonda) {
                setHand(playerHand, state.ultimaRonda.jugadas.tu);
                setHand(opponentHand, state.ultimaRonda.jugadas.oponente);

                if (state.ultimaRonda.ganador === 'empate') {
                    result.textContent = `Ronda ${state.ultimaRonda.numero}: empate.`;
                } else {
                    result.textContent = `Ronda ${state.ultimaRonda.numero}: ${state.ultimaRonda.ganador === 'tu' ? 'ganaste tú' : 'ganó tu rival'}.`;
                }
                status.textContent = state.estado === 'finalizada'
                    ? (state.ganador === 'tu' ? '¡Ganaste la partida!' : 'Tu rival ganó la partida.')
                    : 'Elige tu siguiente jugada.';
            } else {
                status.textContent = 'Elige tu jugada. Tu rival no verá tu elección hasta que ambos elijan.';
            }

            if (state.estado === 'finalizada') {
                picker.disabled = true;
                playAgain.hidden = false;
                if (state.ganador === 'tu') {
                    status.textContent = '¡Ganaste la partida!';
                } else {
                    status.textContent = 'Tu rival ganó la partida.';
                }
            }
        }

        async function loadState() {
            if (!matchId) {
                return;
            }

            const url = gameUrl(page.dataset.stateUrlTemplate, matchId, 'PARTIDA_ID');
            const stateUrl = `${url}?jugadorId=${encodeURIComponent(playerId)}`;

            try {
                const state = await requestJson(stateUrl);
                renderState(state);
            } catch (error) {
                console.error('No se pudo actualizar la partida en línea.', error);
                status.textContent = 'No se pudo actualizar la partida. Vuelve al inicio e inténtalo otra vez.';
                picker.disabled = true;
                window.clearInterval(stateTimer);
            }
        }

        async function connectToMatch(id) {
            matchId = id;
            window.clearInterval(matchmakingTimer);
            cancelSearch.hidden = true;
            status.textContent = 'Rival encontrado. Preparando la partida...';
            picker.disabled = false;

            try {
                await import('./echo.js');
                echoChannel = window.Echo.channel(`partida.${matchId}`);
                echoChannel.listen('.partida.actualizada', loadState);
            } catch (error) {
                console.error('No se pudo iniciar la conexión en tiempo real.', error);
                status.textContent = 'Partida encontrada. Sincronizando por conexión alternativa...';
            }

            await loadState();
            stateTimer = window.setInterval(loadState, 2500);
        }

        async function searchForOpponent() {
            if (matchmakingRequestPending) {
                return;
            }

            matchmakingRequestPending = true;

            try {
                const match = await requestJson(page.dataset.searchUrl, {
                    method: 'POST',
                    body: JSON.stringify({ jugadorId: playerId }),
                });

                if (match.estado === 'encontrado') {
                    await connectToMatch(match.partidaId);
                    return;
                }

                status.textContent = 'Buscando rival al azar... Deja esta pantalla abierta.';
                cancelSearch.hidden = false;
            } catch (error) {
                console.error('No se pudo buscar una partida.', error);
                status.textContent = `${error.message} Reintentando...`;
            } finally {
                matchmakingRequestPending = false;
            }
        }

        async function submitMove(choice) {
            if (!matchId || moveRequestPending) {
                return;
            }

            moveRequestPending = true;
            picker.disabled = true;

            try {
                const url = gameUrl(page.dataset.moveUrlTemplate, matchId, 'PARTIDA_ID');
                const state = await requestJson(url, {
                    method: 'POST',
                    body: JSON.stringify({ jugadorId: playerId, jugada: choice }),
                });
                renderState(state);
            } catch (error) {
                console.error('No se pudo enviar la jugada.', error);
                status.textContent = `${error.message} Intenta elegir de nuevo.`;
                picker.disabled = false;
            } finally {
                moveRequestPending = false;
            }
        }

        cancelSearch.addEventListener('click', async () => {
            window.clearInterval(matchmakingTimer);

            try {
                const url = gameUrl(page.dataset.cancelUrlTemplate, playerId, 'JUGADOR_ID');
                await requestJson(url, { method: 'DELETE' });
                window.location.assign(homeUrl);
            } catch (error) {
                console.error('No se pudo cancelar la búsqueda.', error);
                status.textContent = `${error.message} No se canceló la búsqueda.`;
            }
        });

        choiceButtons.forEach((button) => {
            button.addEventListener('click', () => submitMove(button.dataset.choice));
        });

        matchmakingTimer = window.setInterval(searchForOpponent, 4000);
        searchForOpponent();
    }

    playAgain.addEventListener('click', () => {
        if (mode === 'online') {
            sessionStorage.removeItem('ppt-player-id');
        }

        window.location.reload();
    });
}
