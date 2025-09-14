document.addEventListener('DOMContentLoaded', function() {
    const burgerMenu = document.getElementById('burgerMenu');
    const menu = document.getElementById('menu');
    const playButton = document.querySelector('.play');
    const timeElement = document.querySelector('.time');
    const effectElement = document.querySelector('.effect');
    const myCardsContainer = document.querySelector('.mycards');
    const livesElements = document.querySelectorAll('.lives-count');
    const tokensElements = document.querySelectorAll('.tokens-count');
    const cardsContainer = document.querySelector('.cards');
    const playersContainer = document.getElementById('playersContainer');
    const leaveButton = document.getElementById('leave-btn');

    let timer;
    let timerInterval;
    let selectedCards = { 1: null, 2: null, 3: null };
    let currentPlayerIndex = 0;
    let players = {};
    let room_id;
    let current_timee;
    let phase = 1;
    let cardQueue = [];
    let allPlayersReady = false;
    let allCardsSelected = false;
    let myLogin;
    let checkPlayersReady = true;

    let socket;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000;

    function connectWebSocket() {
        try {
            socket = new WebSocket('ws://localhost:8080');

            socket.onopen = function(event) {
                console.log('WebSocket is open now.');
                reconnectAttempts = 0;

                loadGameInfo();
                updateCardsInHand();
            };

            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                console.log('Received message:', data);
                if (data.type === 'timer') {
                    updateTimer(data.timeLeft);
                } else if (data.type === 'gameInfo') {
                    loadGameInfo(data);
                } else if (data.type === 'startPhase2') {
                    console.log('Received startPhase2 message');
                    setTimeout(() => {
                        startPhase2();
                    }, 2000);
                } else if (data.type === 'checkPlayersReady') {
                    allPlayersReady = data.allPlayersReady;
                    if (allPlayersReady && phase === 1) {
                        phase = 2;
                        startPhase2();
                    }
                } else if (data.type === 'checkPhase2') {
                    console.log('Received checkPhase2 message');
                    checkPhase2(data.room_id);
                } else if (data.type === 'playerReady') {
                    console.log(`Player ${data.login} is ready`);
                    checkPhase2(room_id);
                } else if (data.type === 'gameEnd') {
                    console.log('Game ended, showing winner window for all players');
                    showWinnerWindow(data.winner, data.winnerName);
                } else if (data.type === 'gameUpdate') {
                    console.log('Game update received:', data);
                    handleGameUpdate(data);
                } else if (data.type === 'playerAction') {
                    console.log('Player action received:', data);
                    handlePlayerAction(data);
                } else if (data.type === 'roomCleanup') {
                    console.log('Room cleanup message received:', data.message);
                    // Можно добавить дополнительную логику при получении сообщения об очистке комнаты
                }
            };

            socket.onclose = function(event) {
                console.log('WebSocket is closed now.');
                if (reconnectAttempts < maxReconnectAttempts) {
                    console.log(`Attempting to reconnect... (${reconnectAttempts + 1}/${maxReconnectAttempts})`);
                    setTimeout(connectWebSocket, reconnectDelay);
                    reconnectAttempts++;
                } else {
                    console.log('Max reconnection attempts reached. WebSocket connection failed.');
                }
            };

            socket.onerror = function(error) {
                console.log('WebSocket error: ', error);
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            if (reconnectAttempts < maxReconnectAttempts) {
                setTimeout(connectWebSocket, reconnectDelay);
                reconnectAttempts++;
            }
        }
    }

    // Инициализируем WebSocket подключение
    connectWebSocket();

    burgerMenu.addEventListener('click', function() {
        burgerMenu.classList.toggle('active');
        menu.classList.toggle('active');
    });

    leaveButton.addEventListener('click', function() {
        const urlParams = new URLSearchParams(window.location.search);
        room_id = urlParams.get('room_id');

        fetch('leave_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${room_id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'php.php';
            } else {
                console.error('Ошибка при выходе из комнаты:', data.message);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    });

    function updateCardsChosenStatus() {
        return fetch(`update_cards_chosen_status.php?room_id=${room_id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети: ' + response.statusText);
                }
                return response.text();
            })
            .then(text => {
                console.log('Server response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        console.log('Cards chosen status updated successfully');
                    } else {
                        console.error('Ошибка:', data.message);
                    }
                } catch (e) {
                    console.error('Ошибка парсинга JSON:', e);
                }
            })
            .catch(error => console.error('Ошибка:', error));
    }

    function startTimer(timeLeft) {
        clearInterval(timerInterval);
        timer = timeLeft;
        updateTimer(timer);
        timerInterval = setInterval(() => {
            timer--;
            updateTimer(timer);
            if (timer <= 0) {
                clearInterval(timerInterval);
                playButton.disabled = false;
                playButton.textContent = 'Сыграть';
                playButton.style.backgroundColor = '#51E03F';
                playButton.style.cursor = 'pointer';
                playCards();
            }
        }, 1000);
    }

    function loadGameInfo() {
        const urlParams = new URLSearchParams(window.location.search);
        room_id = urlParams.get('room_id');

        fetch(`get_game_info.php?room_id=${room_id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);

                if (data && data.success) {
                    players = {};
                    myLogin = data.my_login;
                    current_timee = data.current_timee;

                    console.log('Loaded players:', data.players);
                    console.log('Current time:', current_timee);

                    playersContainer.innerHTML = '';

                    data.players.forEach((player, index) => {
                        const playerIndex = index + 1;
                        players[playerIndex] = player;
                        if (player.login !== myLogin) {
                            let playerElement = document.createElement('div');
                            playerElement.classList.add('player', `enemy${playerIndex}`);
                            playerElement.dataset.id = player.id_player;
                            playersContainer.appendChild(playerElement);
                            console.log(`Created player element for id ${player.id_player}`);

                            playerElement.innerHTML = `
                                <div class="icon" style="background-image: url('${player.character}')">
                                <div class="enemy_stats">
                                <div class="tokens"><img src="char_icons/Ellipse1.svg"><span class="tokens-count">${player.tokens}</span></div>
                                <div class="lives"><img src="char_icons/Vector.svg"><span class="lives-count">${player.lives}</span></div>
                                </div>
                                </div>
                                <p class="nick">${player.login}</p>
                            `;
                        }
                    });

                    const myPlayer = Object.values(players).find(player => player.login === myLogin);
                    if (myPlayer) {
                        const myCharElement = document.querySelector('.mychar');
                        myCharElement.classList.add('player');
                        myCharElement.dataset.id = myPlayer.id_player;
                        myCharElement.querySelector('.myicon').style.backgroundImage = `url('${myPlayer.character}')`;
                        myCharElement.querySelector('.lives-count').textContent = myPlayer.lives;
                        myCharElement.querySelector('.tokens-count').textContent = myPlayer.tokens;
                    } else {
                        console.error('My player not found');
                    }

                    const myCards = data.my_cards;
                    console.log('My Cards:', myCards);
                    myCardsContainer.innerHTML = '';
                    if (myCards && myCards.length > 0) {
                        myCards.forEach(card => {
                            const cardElement = document.createElement('div');
                            cardElement.classList.add('mycard');
                            cardElement.style.backgroundImage = `url('${card.png}')`;
                            cardElement.dataset.cardtype = card.cardtype;
                            cardElement.dataset.descr = card.descr;
                            cardElement.addEventListener('click', () => selectCard(card.id_card, card.descr, card.cardtype));
                            cardElement.addEventListener('mouseenter', () => showCardEffect(card.descr, card.id_card));
                            cardElement.addEventListener('mouseleave', () => hideCardEffect(card.id_card));
                            cardElement.addEventListener('click', function() {
                                this.classList.toggle('glowing');
                                effectElement.innerHTML = '';
                            });
                            myCardsContainer.appendChild(cardElement);
                        });
                    } else {
                        console.error('No cards found for the player.');

                        updateCardsInHand();
                    }

                    allPlayersReady = data.all_players_ready;
                    console.log('All players ready:', allPlayersReady);

                    phase = data.phase;
                    if (phase === 2) {
                        console.log('Starting phase 2...');
                        startPhase2();
                    } else {
                        console.log('Phase 1 continues...');
                        fetchCurrentTime();
                    }
                } else {
                    console.error('Ошибка:', data ? data.message : 'Нет данных');
                }
            })
            .catch(error => console.error('Ошибка:', error));
    }

    function fetchCurrentTime() {
        if (!room_id) {
            console.error('Room ID is not set');
            return;
        }
        
        fetch(`get_current_timee.php?room_id=${room_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    startTimer(data.time_left);
                } else {
                    console.error('Ошибка:', data.message);
                }
            })
            .catch(error => console.error('Ошибка:', error));
    }

    function updateCurrentTime() {
        fetch(`update_current_timee.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${room_id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Time updated successfully');
            } else {
                console.error('Ошибка:', data.message);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function selectCard(cardId, cardDescr, cardType) {
        if (!selectedCards[cardType] && !Object.values(selectedCards).includes(cardType)) {
            selectedCards[cardType] = cardId;
            effectElement.innerHTML += `<p>${cardDescr}</p>`;
        } else {
            console.error('Card type already selected or invalid card type');
        }
    }

    function updateTimer(timeLeft) {
        timeElement.textContent = timeLeft;
    }

    function playCards() {
        console.log('Playing cards...');
        console.log('Selected Cards:', selectedCards);
        console.log('Room ID:', room_id);

        fetch('play_cards.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${room_id}&selected_cards=${encodeURIComponent(JSON.stringify(selectedCards))}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            console.log('Server response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    players = {};
                    data.players.forEach((player, index) => {
                        const playerIndex = index + 1;
                        players[playerIndex] = player;
                    });
                    console.log('Updated players:', players);

                    Object.values(players).forEach(player => {
                        let playerElement = document.querySelector(`.player[data-id="${player.id_player}"]`);
                        if (playerElement) {
                            console.log(`Updating player element for id ${player.id_player}: lives=${player.lives}, tokens=${player.tokens}`);
                            const livesElement = playerElement.querySelector('.lives-count');
                            const tokensElement = playerElement.querySelector('.tokens-count');
                            
                            if (livesElement) {
                                livesElement.textContent = player.lives;
                            }
                            if (tokensElement) {
                                tokensElement.textContent = player.tokens;
                            }
                        } else {
                            console.error(`Player element with id ${player.id_player} not found`);
                        }
                    });

                    selectedCards = { 1: null, 2: null, 3: null };
                    effectElement.innerHTML = '';

                    // Обновляем нашего игрока
                    const myPlayer = Object.values(players).find(player => player.login === myLogin);
                    if (myPlayer) {
                        console.log(`Updating my player after playCards: lives=${myPlayer.lives}, tokens=${myPlayer.tokens}`);
                        const myLivesElement = document.querySelector('.mychar .lives-count');
                        const myTokensElement = document.querySelector('.mychar .tokens-count');
                        
                        if (myLivesElement) {
                            myLivesElement.textContent = myPlayer.lives;
                        }
                        if (myTokensElement) {
                            myTokensElement.textContent = myPlayer.tokens;
                        }
                    }

                    currentPlayerIndex = (currentPlayerIndex + 1) % Object.keys(players).length;

                    // Отправляем обновление игры всем игрокам
                    sendGameUpdate('playerStats', {
                        players: players,
                        currentPlayerIndex: currentPlayerIndex
                    });

                    // Отправляем информацию о сыгранных картах
                    sendPlayerAction('cardPlayed', {
                        cards: selectedCards,
                        player: myLogin
                    });

                    // Отправляем обновление игрового поля всем игрокам
                    sendGameUpdate('gameFieldUpdate', {
                        players: players,
                        cardsPlayed: selectedCards,
                        player: myLogin,
                        timestamp: Date.now()
                    });

                    allPlayersReady = Object.values(players).every(player => player.cards_chosen);
                    allCardsSelected = Object.values(selectedCards).every(card => card !== null);
                    if (allPlayersReady && allCardsSelected && phase === 1) {
                        phase = 2;
                        console.log('Sending startPhase2 message via WebSocket');
                        socket.send(JSON.stringify({ type: 'startPhase2', room_id: room_id }));
                        
                        // Отправляем обновление фазы
                        sendGameUpdate('phaseChange', { phase: 2 });
                    } else {
                        // Активируем кнопку "Сыграть" для следующего хода
                        playButton.disabled = false;
                        playButton.textContent = 'Сыграть';
                        playButton.style.backgroundColor = '#51E03F';
                        playButton.style.cursor = 'pointer';
                        
                        loadGameInfo();
                    }
                    socket.send(JSON.stringify({ type: 'playerReady', room_id: room_id, login: myLogin }));
                } else {
                    alert('Ошибка при выполнении хода: ' + data.message);
                }
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function checkPhase2(room_id) {
        fetch(`get_game_info.php?room_id=${room_id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('Check Phase 2 response:', data);
            if (data.success && data.phase === 2) {
                phase = 2;
                startPhase2();
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function startPhase2() {
        console.log('Phase 2 started!');
        checkPlayersReady = false;

        updateCardsChosenStatus()
        .then(() => {
            fetch(`get_chosen_cards.php?room_id=${room_id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети: ' + response.statusText);
                }
                return response.text();
            })
            .then(text => {
                console.log('Server response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const groupedCards = {};
                        data.chosen_cards.forEach(card => {
                            if (!groupedCards[card.id_player]) {
                                groupedCards[card.id_player] = [];
                            }
                            groupedCards[card.id_player].push(card);
                        });

                        for (const playerId in groupedCards) {
                            groupedCards[playerId].sort((a, b) => a.card_position - b.card_position);
                        }

                        const playerLeadValues = {};
                        for (const playerId in groupedCards) {
                            const cardType3 = groupedCards[playerId].find(card => card.cardtype === 3);
                            playerLeadValues[playerId] = cardType3 ? cardType3.lead : 0;
                        }

                        const sortedPlayerIds = Object.keys(playerLeadValues).sort((a, b) => playerLeadValues[b] - playerLeadValues[a]);

                        cardQueue = [];
                        sortedPlayerIds.forEach(playerId => {
                            for (let i = 1; i <= 3; i++) {
                                const card = groupedCards[playerId].find(card => card.card_position === i);
                                if (card) {
                                    cardQueue.push({
                                        ...card,
                                        id_player: playerId
                                    });
                                } else {
                                    cardQueue.push({
                                        id_card: 1,
                                        cardtype: 0,
                                        lead: 0,
                                        heal: 0,
                                        damage: 0,
                                        descr: 'Пустая карта',
                                        png: 'images/card0.jpg',
                                        card_position: i,
                                        id_player: playerId
                                    });
                                }
                            }
                        });

                        console.log('Chosen cards:', cardQueue);
                        playNextCard(myLogin);
                    } else {
                        console.error('Ошибка:', data.message);
                    }
                } catch (e) {
                    console.error('Ошибка парсинга JSON:', e);
                }
            })
            .catch(error => console.error('Ошибка:', error));
        })
        .catch(error => console.error('Ошибка обновления состояния cards_chosen:', error));
    }

    function playNextCard(myLogin) {
        if (cardQueue.length > 0) {
            const currentPlayerId = cardQueue[0].id_player;
            const playerCards = cardQueue.filter(card => card.id_player === currentPlayerId);

            cardsContainer.innerHTML = '';

            for (let i = 1; i <= 3; i++) {
                const cardContainer = document.createElement('div');
                cardContainer.classList.add('cardtype_' + i);
                cardsContainer.appendChild(cardContainer);
            }

            playerCards.forEach((card, index) => {
                const cardElement = document.createElement('div');
                cardElement.classList.add('card', `cardtype_${card.cardtype}`);
                cardElement.style.backgroundImage = `url('${card.png}')`;

                const cardPosition = card.card_position;
                const cardContainer = document.querySelector(`.cardtype_${cardPosition}`);
                if (cardContainer) {
                    cardContainer.appendChild(cardElement);
                    
                    // Добавляем анимацию появления карты с задержкой
                    setTimeout(() => {
                        createCardRevealAnimation(cardElement);
                    }, index * 200); // Каждая карта появляется с интервалом 200мс
                }

                applyCardEffects(card, myLogin);
            });

            setTimeout(() => {
                document.querySelectorAll('.card').forEach(cardElement => cardElement.remove());

                setTimeout(() => {
                    cardQueue = cardQueue.filter(card => card.id_player !== currentPlayerId);

                    console.log('Removing cards for player ID:', currentPlayerId);
                    fetch(`remove_spells.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_player=${currentPlayerId}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Ошибка сети: ' + response.statusText);
                        }
                        return response.text();
                    })
                    .then(text => {
                        console.log('Server response:', text);
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                console.log('Cards removed from Spells table');
                            } else {
                                console.error('Ошибка:', data.message);
                            }
                        } catch (e) {
                            console.error('Ошибка парсинга JSON:', e);
                        }
                    })
                    .catch(error => console.error('Ошибка:', error));

                    playNextCard(myLogin);
                }, 1000);
            }, 1000);
        } else {
            fetch(`check_spells_empty.php?room_id=${room_id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети: ' + response.statusText);
                }
                return response.text();
            })
            .then(text => {
                console.log('Server response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success && data.is_empty) {
                        phase = 1;
                        loadGameInfo();
                        updateCardsInHand();
                        resetCardsChosen();
                        cardsContainer.innerHTML = '';
                        checkPlayersReady = true;
                        
                        // Активируем кнопку "Сыграть" для нового раунда
                        playButton.disabled = false;
                        playButton.textContent = 'Сыграть';
                        playButton.style.backgroundColor = '#51E03F';
                        playButton.style.cursor = 'pointer';
                        
                        checkWinner();

                        updateCurrentTime();
                    } else {
                        console.log('Waiting for more cards to be played...');
                    }
                } catch (e) {
                    console.error('Ошибка парсинга JSON:', e);
                }
            })
            .catch(error => console.error('Ошибка:', error));
        }
    }

    function checkWinner() {
        fetch(`check_winner.php?room_id=${room_id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            console.log('Server response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    if (data.winner) {
                        console.log('Winner found:', data.winner);
                        
                        // Получаем информацию о победителе
                        fetch(`get_winner_info.php?winner_id=${data.winner}`)
                        .then(response => response.json())
                        .then(winnerData => {
                            if (winnerData.success) {
                                // Отправляем сообщение всем игрокам в комнате через WebSocket
                                if (socket && socket.readyState === WebSocket.OPEN) {
                                    socket.send(JSON.stringify({
                                        type: 'gameEnd',
                                        room_id: room_id,
                                        winner: data.winner,
                                        winnerName: winnerData.winner_name
                                    }));
                                    console.log('Sent gameEnd message to all players via WebSocket');
                                }
                                
                                // Показываем окно победителя всем игрокам
                                showWinnerWindow(data.winner, winnerData.winner_name);
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка получения информации о победителе:', error);
                            // Отправляем сообщение даже при ошибке
                            if (socket && socket.readyState === WebSocket.OPEN) {
                                socket.send(JSON.stringify({
                                    type: 'gameEnd',
                                    room_id: room_id,
                                    winner: data.winner,
                                    winnerName: 'Неизвестный игрок'
                                }));
                                console.log('Sent gameEnd message with fallback winner name');
                            }
                            // Показываем окно победителя даже при ошибке
                            showWinnerWindow(data.winner, 'Неизвестный игрок');
                        });
                    } else {
                        console.log('No winner yet');
                    }
                } else {
                    console.error('Ошибка:', data.message);
                }
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function showWinnerWindow(winnerId, winnerName) {
        // Проверяем, не показано ли уже окно победителя
        if (document.getElementById('winnerModal')) {
            return;
        }

        // Создаем модальное окно победителя
        const winnerModal = document.createElement('div');
        winnerModal.id = 'winnerModal';
        winnerModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            animation: fadeIn 0.8s ease-in;
        `;

        const winnerContent = document.createElement('div');
        winnerContent.style.cssText = `
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.98), rgba(35, 35, 35, 0.98));
            border: 3px solid rgba(255, 230, 41, 0.8);
            border-radius: 25px;
            padding: 50px;
            text-align: center;
            color: white;
            font-family: 'Minecraft', sans-serif;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(15px);
            animation: winnerModalIn 1s ease-out;
            max-width: 500px;
            width: 90%;
        `;

        const isWinner = winnerId && Object.values(players).find(p => p.id_player == winnerId)?.login === myLogin;

        winnerContent.innerHTML = `
            <div style="margin-bottom: 30px;">
                <div style="font-size: 4em; margin-bottom: 20px;">
                    ${isWinner ? '🎉🏆🎉' : '🏆⚔️🏆'}
                </div>
                <h1 style="color: ${isWinner ? '#FFE629' : '#51E03F'}; font-size: 2.8em; margin-bottom: 20px; text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.9);">
                    ${isWinner ? 'ПОБЕДА!' : 'ИГРА ОКОНЧЕНА'}
                </h1>
            </div>
            
            <div style="margin: 40px 0; padding: 20px; background: rgba(255, 230, 41, 0.1); border-radius: 15px; border: 1px solid rgba(255, 230, 41, 0.3);">
                <h2 style="color: #51E03F; font-size: 1.5em; margin-bottom: 15px;">Победитель:</h2>
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                    <div style="font-size: 2em;">👑</div>
                    <p style="font-size: 1.8em; color: #FFE629; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8); font-weight: bold;">${winnerName}</p>
                    <div style="font-size: 2em;">👑</div>
                </div>
            </div>

            <div style="margin: 30px 0; padding: 15px; background: rgba(81, 224, 63, 0.1); border-radius: 10px; border: 1px solid rgba(81, 224, 63, 0.3);">
                <p style="color: #51E03F; font-size: 1.1em; margin: 0;">
                    🎮 Комната будет удалена через несколько секунд
                </p>
            </div>

            <div style="display: flex; justify-content: center; margin-top: 30px;">
                <button id="returnToMenuBtn" style="
                    background: linear-gradient(135deg, #51E03F, #8CEC7F);
                    border: 2px solid rgba(81, 224, 63, 0.6);
                    color: white;
                    padding: 20px 40px;
                    font-size: 1.4em;
                    border-radius: 15px;
                    cursor: pointer;
                    font-family: 'Minecraft', sans-serif;
                    transition: all 0.3s ease;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
                    box-shadow: 0 6px 20px rgba(81, 224, 63, 0.4);
                    min-width: 250px;
                ">🏠 Вернуться в меню</button>
            </div>

            <div style="margin-top: 25px; color: #888; font-size: 0.9em;">
                Автоматический возврат в меню через <span id="countdown">15</span> секунд
            </div>
        `;

        winnerModal.appendChild(winnerContent);
        document.body.appendChild(winnerModal);

        // Добавляем CSS анимации для модального окна
        const modalStyle = document.createElement('style');
        modalStyle.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes winnerModalIn {
                0% { 
                    transform: scale(0.5) translateY(-50px);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.05) translateY(0);
                    opacity: 0.8;
                }
                100% { 
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
            }
            #returnToMenuBtn:hover {
                background: linear-gradient(135deg, #8CEC7F, #51E03F);
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(81, 224, 63, 0.5);
            }
        `;
        document.head.appendChild(modalStyle);

        // Обработчик кнопки возврата в меню
        document.getElementById('returnToMenuBtn').addEventListener('click', function() {
            // Отправляем сообщение о выходе из комнаты через WebSocket
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({
                    type: 'leaveRoom',
                    room_id: room_id,
                    login: myLogin
                }));
            }
            
            // Выполняем выход из комнаты
            fetch('leave_room.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `room_id=${room_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Successfully left room');
                } else {
                    console.error('Ошибка при выходе из комнаты:', data.message);
                }
                // Все равно перенаправляем в меню
                window.location.href = 'php.php';
            })
            .catch(error => {
                console.error('Ошибка:', error);
                // Все равно перенаправляем в меню
                window.location.href = 'php.php';
            });
        });


        // Обратный отсчет
        let countdown = 15;
        const countdownElement = document.getElementById('countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'php.php';
            }
        }, 1000);

        // Очищаем игру через 10 секунд
        setTimeout(() => {
            fetch('cleanup_game.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `room_id=${room_id}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Game cleanup result:', data);
            })
            .catch(error => {
                console.error('Ошибка очистки игры:', error);
            });
        }, 10000);

        // Автоматический возврат в меню через 15 секунд
        setTimeout(() => {
            if (document.getElementById('winnerModal')) {
                window.location.href = 'php.php';
            }
        }, 15000);
    }

    function applyCardEffects(card, myLogin) {
        console.log(`applyCardEffects called with card: ${card.descr}`);
        console.log(`Applying effects for card: ${card.descr}`);
        console.log(`Card damage: ${card.damage}, Card heal: ${card.heal}`);

        // Определяем источник анимации (карта игрока)
        const cardElement = document.querySelector(`.card[style*="${card.png}"]`);
        const sourceElement = cardElement || document.querySelector('.cards');

        fetch('apply_card_effects.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${room_id}&card_id=${card.id_card}&card_position=${card.card_position}&player_id=${card.id_player}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            console.log('Server response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    console.log('Card effects applied successfully');

                    // Запускаем анимации в зависимости от эффектов карты
                    if (card.damage > 0) {
                        // Анимация урона
                        const targetPlayer = Object.values(players).find(p => p.id_player == card.id_player);
                        if (targetPlayer) {
                            const targetElement = document.querySelector(`.player[data-id="${targetPlayer.id_player}"]`);
                            if (targetElement && sourceElement) {
                                setTimeout(() => {
                                    createFireballAnimation(sourceElement, targetElement, card.damage);
                                }, 500); // Небольшая задержка для синхронизации
                            }
                        }
                    }

                    if (card.heal > 0) {
                        // Анимация лечения
                        const targetPlayer = Object.values(players).find(p => p.id_player == card.id_player);
                        if (targetPlayer) {
                            const targetElement = document.querySelector(`.player[data-id="${targetPlayer.id_player}"]`);
                            if (targetElement) {
                                setTimeout(() => {
                                    createHealAnimation(targetElement, card.heal);
                                }, 500); // Небольшая задержка для синхронизации
                            }
                        }
                    }

                    // Отправляем обновление статистики игроков всем в комнате
                    sendGameUpdate('playerStats', {
                        players: players,
                        updateType: 'livesUpdate'
                    });
                    
                    updatePlayerUI();
                } else {
                    console.error('Ошибка:', data.message);
                }
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function updatePlayerUI() {
        fetch(`get_game_info.php?room_id=${room_id}`)
        .then(response => response.text())
        .then(text => {
            console.log('Server response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    players = {};
                    data.players.forEach((player, index) => {
                        const playerIndex = index + 1;
                        players[playerIndex] = player;
                    });
                    console.log('Updated players:', players);

                    // Обновляем UI всех игроков
                    Object.values(players).forEach(player => {
                        let playerElement = document.querySelector(`.player[data-id="${player.id_player}"]`);
                        if (playerElement) {
                            console.log(`Updating player element for id ${player.id_player}: lives=${player.lives}, tokens=${player.tokens}`);
                            const livesElement = playerElement.querySelector('.lives-count');
                            const tokensElement = playerElement.querySelector('.tokens-count');
                            
                            if (livesElement) {
                                livesElement.textContent = player.lives;
                            }
                            if (tokensElement) {
                                tokensElement.textContent = player.tokens;
                            }
                        } else {
                            console.error(`Player element with id ${player.id_player} not found`);
                        }
                    });

                    // Обновляем нашего игрока
                    const myPlayer = Object.values(players).find(player => player.login === myLogin);
                    if (myPlayer) {
                        console.log(`Updating my player: lives=${myPlayer.lives}, tokens=${myPlayer.tokens}`);
                        const myLivesElement = document.querySelector('.mychar .lives-count');
                        const myTokensElement = document.querySelector('.mychar .tokens-count');
                        
                        if (myLivesElement) {
                            myLivesElement.textContent = myPlayer.lives;
                        }
                        if (myTokensElement) {
                            myTokensElement.textContent = myPlayer.tokens;
                        }
                    } else {
                        console.error('My player not found');
                    }
                    
                    // Отправляем обновление статистики игроков всем в комнате
                    sendGameUpdate('playerStats', {
                        players: players,
                        updateType: 'livesUpdate'
                    });
                } else {
                    console.error('Ошибка:', data.message);
                }
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function updateCardsInHand() {
        fetch(`update_cards_in_hand.php?room_id=${room_id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {

                    myCardsContainer.innerHTML = '';
                    if (data.cards && data.cards.length > 0) {
                        data.cards.forEach(card => {
                            const cardElement = document.createElement('div');
                            cardElement.classList.add('mycard');
                            cardElement.style.backgroundImage = `url('${card.png}')`;
                            cardElement.dataset.cardtype = card.cardtype;
                            cardElement.dataset.descr = card.descr;
                            cardElement.addEventListener('click', () => selectCard(card.id_card, card.descr, card.cardtype));
                            cardElement.addEventListener('mouseenter', () => showCardEffect(card.descr, card.id_card));
                            cardElement.addEventListener('mouseleave', () => hideCardEffect(card.id_card));
                            cardElement.addEventListener('click', function() {
                                this.classList.toggle('glowing');
                                effectElement.innerHTML = '';
                            });
                            myCardsContainer.appendChild(cardElement);
                        });
                    } else {
                        console.error('No cards found for the player.');
                    }
                } else {
                    console.error('Ошибка:', data.message);
                }
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function resetCardsChosen() {
        fetch(`reset_cards_chosen.php?room_id=${room_id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            console.log('Server response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    console.log('Cards chosen reset successfully');
                } else {
                    console.error('Ошибка:', data.message);
                }
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    }

    function showCardEffect(descr, cardId) {
        if (!selectedCards[cardId]) {
            effectElement.innerHTML += `<p>${descr}</p>`;
        }
    }

    function hideCardEffect(cardId) {
        if (!selectedCards[cardId]) {
            effectElement.innerHTML = '';
        }
    }

    playButton.addEventListener('click', function() {
        playButton.disabled = true;
        playButton.textContent = 'Играет...';
        playButton.style.backgroundColor = '#E42828';
        playButton.style.cursor = 'not-allowed';
        playCards();
    });

    window.addEventListener('load', function() {
        fetchCurrentTime();
    });

    socket.addEventListener('message', function(event) {
        const data = JSON.parse(event.data);
        if (data.type === 'startPhase2') {
            updateCurrentTime();
        }
    });

    // Функции анимаций

    /**
     * Создает анимацию файербола для нанесения урона
     * @param {HTMLElement} fromElement - элемент, откуда летит файербол
     * @param {HTMLElement} toElement - элемент, в который попадает файербол
     * @param {number} damage - количество урона
     */
    function createFireballAnimation(fromElement, toElement, damage) {
        const fromRect = fromElement.getBoundingClientRect();
        const toRect = toElement.getBoundingClientRect();
        
        // Создаем файербол
        const fireball = document.createElement('div');
        fireball.className = 'fireball';
        fireball.style.left = (fromRect.left + fromRect.width / 2) + 'px';
        fireball.style.top = (fromRect.top + fromRect.height / 2) + 'px';
        document.body.appendChild(fireball);
        
        // Вычисляем траекторию
        const deltaX = (toRect.left + toRect.width / 2) - (fromRect.left + fromRect.width / 2);
        const deltaY = (toRect.top + toRect.height / 2) - (fromRect.top + fromRect.height / 2);
        
        // Устанавливаем CSS переменные для анимации
        fireball.style.setProperty('--target-x', deltaX + 'px');
        fireball.style.setProperty('--target-y', deltaY + 'px');
        
        // Запускаем анимацию полета
        fireball.style.animation = 'fireballFly 0.8s ease-in-out forwards';
        
        // Создаем следы файербола
        createFireballTrails(fromRect, toRect);
        
        // Через 0.8 секунды создаем взрыв
        setTimeout(() => {
            createExplosionAnimation(toElement, damage);
            fireball.remove();
        }, 800);
    }

    /**
     * Создает следы файербола
     */
    function createFireballTrails(fromRect, toRect) {
        const trailCount = 8;
        const duration = 800;
        
        for (let i = 0; i < trailCount; i++) {
            setTimeout(() => {
                const trail = document.createElement('div');
                trail.className = 'fireball-trail';
                
                const progress = i / trailCount;
                const x = fromRect.left + fromRect.width / 2 + (toRect.left - fromRect.left) * progress;
                const y = fromRect.top + fromRect.height / 2 + (toRect.top - fromRect.top) * progress;
                
                trail.style.left = x + 'px';
                trail.style.top = y + 'px';
                
                document.body.appendChild(trail);
                
                setTimeout(() => trail.remove(), 100);
            }, i * (duration / trailCount));
        }
    }

    /**
     * Создает анимацию взрыва
     */
    function createExplosionAnimation(targetElement, damage) {
        const targetRect = targetElement.getBoundingClientRect();
        
        // Создаем взрыв
        const explosion = document.createElement('div');
        explosion.className = 'explosion';
        explosion.style.left = (targetRect.left + targetRect.width / 2 - 40) + 'px';
        explosion.style.top = (targetRect.top + targetRect.height / 2 - 40) + 'px';
        document.body.appendChild(explosion);
        
        // Добавляем эффект тряски для цели
        targetElement.classList.add('player-damage');
        
        // Показываем урон
        showDamageNumber(targetRect, damage);
        
        // Убираем эффекты через время
        setTimeout(() => {
            explosion.remove();
            targetElement.classList.remove('player-damage');
        }, 600);
    }

    /**
     * Создает анимацию лечения
     * @param {HTMLElement} targetElement - элемент, который лечится
     * @param {number} healAmount - количество лечения
     */
    function createHealAnimation(targetElement, healAmount) {
        const targetRect = targetElement.getBoundingClientRect();
        
        // Создаем эффект лечения
        const healEffect = document.createElement('div');
        healEffect.className = 'heal-effect';
        healEffect.style.left = (targetRect.left + targetRect.width / 2 - 30) + 'px';
        healEffect.style.top = (targetRect.top + targetRect.height / 2 - 30) + 'px';
        document.body.appendChild(healEffect);
        
        // Создаем частицы лечения
        createHealParticles(targetRect);
        
        // Добавляем эффект свечения для цели
        targetElement.classList.add('player-heal');
        
        // Показываем количество лечения
        showHealNumber(targetRect, healAmount);
        
        // Убираем эффекты через время
        setTimeout(() => {
            healEffect.remove();
            targetElement.classList.remove('player-heal');
        }, 1200);
    }

    /**
     * Создает частицы лечения
     */
    function createHealParticles(targetRect) {
        const particleCount = 12;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'heal-particles';
            
            const centerX = targetRect.left + targetRect.width / 2;
            const centerY = targetRect.top + targetRect.height / 2;
            
            // Случайное направление для частицы
            const angle = (i / particleCount) * 2 * Math.PI;
            const distance = 30 + Math.random() * 20;
            const x = Math.cos(angle) * distance;
            const y = Math.sin(angle) * distance;
            
            particle.style.left = centerX + 'px';
            particle.style.top = centerY + 'px';
            particle.style.setProperty('--particle-x', x + 'px');
            particle.style.setProperty('--particle-y', y + 'px');
            
            document.body.appendChild(particle);
            
            setTimeout(() => particle.remove(), 1200);
        }
    }

    /**
     * Показывает число урона
     */
    function showDamageNumber(targetRect, damage) {
        const damageText = document.createElement('div');
        damageText.textContent = '-' + damage;
        damageText.style.cssText = `
            position: absolute;
            left: ${targetRect.left + targetRect.width / 2}px;
            top: ${targetRect.top - 20}px;
            color: #ff4444;
            font-size: 24px;
            font-weight: bold;
            font-family: 'Minecraft', sans-serif;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            z-index: 1002;
            pointer-events: none;
            animation: damageNumberFloat 1s ease-out forwards;
        `;
        
        document.body.appendChild(damageText);
        
        setTimeout(() => damageText.remove(), 1000);
    }

    /**
     * Показывает число лечения
     */
    function showHealNumber(targetRect, healAmount) {
        const healText = document.createElement('div');
        healText.textContent = '+' + healAmount;
        healText.style.cssText = `
            position: absolute;
            left: ${targetRect.left + targetRect.width / 2}px;
            top: ${targetRect.top - 20}px;
            color: #4caf50;
            font-size: 24px;
            font-weight: bold;
            font-family: 'Minecraft', sans-serif;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            z-index: 1002;
            pointer-events: none;
            animation: healNumberFloat 1.2s ease-out forwards;
        `;
        
        document.body.appendChild(healText);
        
        setTimeout(() => healText.remove(), 1200);
    }

    /**
     * Создает анимацию появления карт противника
     */
    function createCardRevealAnimation(cardElement) {
        cardElement.classList.add('enemy-card-reveal');
        
        setTimeout(() => {
            cardElement.classList.remove('enemy-card-reveal');
        }, 800);
    }

    // Функции для обработки обновлений игры

    /**
     * Обрабатывает обновления игрового поля
     */
    function handleGameUpdate(data) {
        console.log('Handling game update:', data);
        
        switch (data.updateType) {
            case 'playerTurn':
                // Обновляем информацию о текущем игроке
                updateCurrentPlayerInfo(data.data);
                break;
            case 'cardsPlayed':
                // Обновляем информацию о сыгранных картах
                updateCardsInfo(data.data);
                break;
            case 'playerStats':
                // Обновляем статистику игроков
                updatePlayerStats(data.data);
                break;
            case 'phaseChange':
                // Обновляем фазу игры
                updateGamePhase(data.data);
                break;
            case 'gameFieldUpdate':
                // Обновляем игровое поле после завершения хода
                updateGameField(data.data);
                break;
            default:
                // Общее обновление - перезагружаем всю игровую информацию
                loadGameInfo();
                break;
        }
    }

    /**
     * Обрабатывает действия игроков
     */
    function handlePlayerAction(data) {
        console.log('Handling player action:', data);
        
        if (data.action === 'cardPlayed') {
            // Показываем анимацию сыгранной карты
            showCardPlayedAnimation(data.data);
        } else if (data.action === 'playerJoined') {
            // Обновляем список игроков
            loadGameInfo();
        } else if (data.action === 'playerLeft') {
            // Обновляем список игроков
            loadGameInfo();
        }
    }

    /**
     * Отправляет обновление игры через WebSocket
     */
    function sendGameUpdate(updateType, data) {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                type: 'gameUpdate',
                room_id: room_id,
                updateType: updateType,
                data: data
            }));
        }
    }

    /**
     * Отправляет информацию о действии игрока
     */
    function sendPlayerAction(action, data) {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                type: 'playerAction',
                room_id: room_id,
                player: myLogin,
                action: action,
                data: data
            }));
        }
    }

    /**
     * Обновляет информацию о текущем игроке
     */
    function updateCurrentPlayerInfo(data) {
        // Обновляем UI с информацией о текущем игроке
        console.log('Updating current player info:', data);
    }

    /**
     * Обновляет информацию о картах
     */
    function updateCardsInfo(data) {
        // Обновляем информацию о сыгранных картах
        console.log('Updating cards info:', data);
    }

    /**
     * Обновляет статистику игроков
     */
    function updatePlayerStats(data) {
        // Обновляем статистику всех игроков
        console.log('Updating player stats:', data);
        
        // Если есть данные о игроках, обновляем их напрямую
        if (data.players) {
            players = {};
            Object.values(data.players).forEach((player, index) => {
                const playerIndex = index + 1;
                players[playerIndex] = player;
            });
            
            // Обновляем UI всех игроков
            Object.values(players).forEach(player => {
                let playerElement = document.querySelector(`.player[data-id="${player.id_player}"]`);
                if (playerElement) {
                    console.log(`Updating player stats for id ${player.id_player}: lives=${player.lives}, tokens=${player.tokens}`);
                    const livesElement = playerElement.querySelector('.lives-count');
                    const tokensElement = playerElement.querySelector('.tokens-count');
                    
                    if (livesElement) {
                        livesElement.textContent = player.lives;
                    }
                    if (tokensElement) {
                        tokensElement.textContent = player.tokens;
                    }
                }
            });

            // Обновляем нашего игрока
            const myPlayer = Object.values(players).find(player => player.login === myLogin);
            if (myPlayer) {
                console.log(`Updating my player stats: lives=${myPlayer.lives}, tokens=${myPlayer.tokens}`);
                const myLivesElement = document.querySelector('.mychar .lives-count');
                const myTokensElement = document.querySelector('.mychar .tokens-count');
                
                if (myLivesElement) {
                    myLivesElement.textContent = myPlayer.lives;
                }
                if (myTokensElement) {
                    myTokensElement.textContent = myPlayer.tokens;
                }
            }
        } else {
            // Если нет данных о игроках, загружаем их с сервера
            updatePlayerUI();
        }
        
        // Активируем кнопку "Сыграть" для следующего хода
        playButton.disabled = false;
        playButton.textContent = 'Сыграть';
        playButton.style.backgroundColor = '#51E03F';
        playButton.style.cursor = 'pointer';
    }

    /**
     * Обновляет фазу игры
     */
    function updateGamePhase(data) {
        console.log('Updating game phase:', data);
        if (data.phase === 2) {
            startPhase2();
        }
    }

    /**
     * Обновляет игровое поле после завершения хода
     */
    function updateGameField(data) {
        console.log('Updating game field:', data);
        
        // Обновляем статистику игроков
        if (data.players) {
            players = {};
            Object.values(data.players).forEach((player, index) => {
                const playerIndex = index + 1;
                players[playerIndex] = player;
            });
            
            // Обновляем UI игроков
            Object.values(players).forEach(player => {
                let playerElement = document.querySelector(`.player[data-id="${player.id_player}"]`);
                if (playerElement) {
                    playerElement.querySelector('.lives-count').textContent = player.lives;
                    playerElement.querySelector('.tokens-count').textContent = player.tokens;
                }
            });

            // Обновляем нашего игрока
            const myPlayer = Object.values(players).find(player => player.login === myLogin);
            if (myPlayer) {
                document.querySelector('.mychar .lives-count').textContent = myPlayer.lives;
                document.querySelector('.mychar .tokens-count').textContent = myPlayer.tokens;
            }
        }

        // Показываем уведомление о сыгранных картах
        if (data.cardsPlayed && data.player && data.player !== myLogin) {
            showPlayerCardsNotification(data.player, data.cardsPlayed);
        }

        // Активируем кнопку "Сыграть" для следующего хода
        playButton.disabled = false;
        playButton.textContent = 'Сыграть';
        playButton.style.backgroundColor = '#51E03F';
        playButton.style.cursor = 'pointer';

        // Обновляем карты в руке если нужно
        updateCardsInHand();
    }

    /**
     * Показывает анимацию сыгранной карты
     */
    function showCardPlayedAnimation(data) {
        // Показываем анимацию сыгранной карты
        console.log('Showing card played animation:', data);
    }

    /**
     * Показывает уведомление о сыгранных картах другого игрока
     */
    function showPlayerCardsNotification(playerName, cardsPlayed) {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = 'player-cards-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.95), rgba(45, 45, 45, 0.95));
            border: 2px solid rgba(255, 230, 41, 0.5);
            border-radius: 10px;
            padding: 15px 20px;
            color: white;
            font-family: 'Minecraft', sans-serif;
            font-size: 14px;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            animation: slideInRight 0.5s ease-out;
        `;

        const cardsCount = Object.values(cardsPlayed).filter(card => card !== null).length;
        notification.innerHTML = `
            <div style="color: #FFE629; font-weight: bold; margin-bottom: 5px;">
                ${playerName} сыграл ${cardsCount} карт
            </div>
            <div style="color: #51E03F; font-size: 12px;">
                Игровое поле обновлено
            </div>
        `;

        document.body.appendChild(notification);

        // Убираем уведомление через 3 секунды
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.5s ease-in forwards';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    // Добавляем CSS для анимаций чисел и уведомлений
    const style = document.createElement('style');
    style.textContent = `
        @keyframes damageNumberFloat {
            0% { 
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            100% { 
                transform: translateY(-30px) scale(1.2);
                opacity: 0;
            }
        }
        
        @keyframes healNumberFloat {
            0% { 
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            100% { 
                transform: translateY(-40px) scale(1.3);
                opacity: 0;
            }
        }

        @keyframes slideInRight {
            0% { 
                transform: translateX(100%);
                opacity: 0;
            }
            100% { 
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            0% { 
                transform: translateX(0);
                opacity: 1;
            }
            100% { 
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
});
