document.addEventListener('DOMContentLoaded', function() {
    const charIcons = document.querySelectorAll('.char-icon');
    const pickedCharIcon = document.querySelector('.picked-char-icon');
    const charName = document.querySelector('.name');
    const charInfo = document.querySelector('.info');
    const analogInfo = document.querySelector('.analog-info');
    const roomsContainer = document.getElementById('roomsContainer');
    const inviteBox = document.getElementById('invite-box');
    const inviteOverlay = document.getElementById('invite-overlay');
    const yesBtn = document.querySelector('.yes');
    const noBtn = document.querySelector('.no');
    const inviterElement = document.querySelector('.inviter');
    const userNameElement = document.getElementById('userName');
    const logoutBtn = document.getElementById('logoutBtn');

    // Получаем имя пользователя
    loadUserInfo();

    // Обработчик кнопки выхода
    logoutBtn.addEventListener('click', function() {
        if (confirm('Вы уверены, что хотите выйти?')) {
            logout();
        }
    });
  
    inviteBox.style.display = 'none';
    inviteOverlay.style.display = 'none';

    charIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            // Убираем выделение с предыдущего персонажа
            charIcons.forEach(i => i.classList.remove('selected'));
            
            // Добавляем выделение к выбранному персонажу
            this.classList.add('selected');
            
            const name = this.getAttribute('data-name');
            const info = this.getAttribute('data-info');
            const image = this.getAttribute('data-image');
            
            // Анимация смены персонажа
            pickedCharIcon.style.opacity = '0.5';
            setTimeout(() => {
                charName.textContent = name;
                charInfo.textContent = info;
                analogInfo.textContent = info;
                pickedCharIcon.style.backgroundImage = `url('${image}')`;
                pickedCharIcon.style.opacity = '1';
            }, 150);

            fetch('update_character.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `character=${image}`
            })
            .then(response => response.text())
            .then(data => console.log(data))
            .catch(error => console.error('Error:', error));
        });
    });

    document.getElementById('createRoomButton').addEventListener('click', function() {
        // Анимация нажатия кнопки
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 100);
        
        // Показываем индикатор загрузки
        const originalText = this.textContent;
        this.textContent = 'Создание...';
        this.disabled = true;
        
        fetch('create_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Анимация успешного создания
                this.style.background = 'linear-gradient(135deg, #8CEC7F, #51E03F)';
                this.textContent = 'Успешно!';
                setTimeout(() => {
                    window.location.href = `wait.html?room_id=${data.room_id}`;
                }, 500);
            } else {
                this.textContent = originalText;
                this.disabled = false;
                alert('Ошибка при создании комнаты: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.textContent = originalText;
            this.disabled = false;
        });
    });

    function loadRooms() {
        fetch('get_rooms.php')
        .then(response => response.json())
        .then(data => {
            const existingRooms = new Set();
            roomsContainer.innerHTML = '';
            
            if (data.rooms.length === 0) {
                const noRoomsDiv = document.createElement('div');
                noRoomsDiv.classList.add('no-rooms');
                noRoomsDiv.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #888;">
                        <h3>Нет доступных комнат</h3>
                        <p>Создайте новую комнату для игры!</p>
                    </div>
                `;
                roomsContainer.appendChild(noRoomsDiv);
                return;
            }
            
            data.rooms.forEach((room, index) => {
                if (!existingRooms.has(room.room_id)) {
                    existingRooms.add(room.room_id);
                    const roomDiv = document.createElement('div');
                    roomDiv.classList.add('room');
                    
                    const playerStatus = room.player_count === 2 ? 'Полная' : 'Свободно';
                    const statusColor = room.player_count === 2 ? '#E42828' : '#51E03F';
                    
                    // Проверяем, является ли текущий пользователь создателем комнаты
                    const currentUser = getCookie('login');
                    const isCreator = room.creator === currentUser;
                    
                    roomDiv.innerHTML = `
                        <div class="room-info">
                            <p class="room-creator">👤 ${room.creator}</p>
                            <p class="room-id">🏠 Комната ${room.room_id}</p>
                            <p class="room-players">👥 ${room.player_count}/2 игроков</p>
                            <p style="color: ${statusColor}; font-size: 1vw;">${playerStatus}</p>
                        </div>
                        <div class="room-buttons">
                            <button type="button" class="come_in" data-room-id="${room.room_id}" ${room.player_count === 2 ? 'disabled' : ''}>
                                ${room.player_count === 2 ? 'Полная' : 'Войти'}
                            </button>
                            ${isCreator ? `<button type="button" class="delete-room" data-room-id="${room.room_id}" title="Удалить комнату">🗑️</button>` : ''}
                        </div>
                    `;
                    roomsContainer.appendChild(roomDiv);
                }
            });

            document.querySelectorAll('.come_in').forEach(button => {
                button.addEventListener('click', function() {
                    const roomId = this.getAttribute('data-room-id');
                    fetch(`check_player_in_room.php?room_id=${roomId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.player_count < 2 && !data.is_player_in_room) {
                            fetch('join_room.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `room_id=${roomId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href = `wait.html?room_id=${roomId}`;
                                } else {
                                    alert('Ошибка при входе в комнату: ' + data.message);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                        } else if (data.is_player_in_room) {
                            alert('Вы уже находитесь в этой комнате.');
                        } else {
                            alert('Комната уже заполнена.');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            });

            // Обработчик кнопки удаления комнаты
            document.querySelectorAll('.delete-room').forEach(button => {
                button.addEventListener('click', function() {
                    const roomId = this.getAttribute('data-room-id');
                    
                    if (confirm('Вы уверены, что хотите удалить эту комнату? Это действие нельзя отменить.')) {
                        // Показываем индикатор загрузки
                        this.textContent = '⏳';
                        this.disabled = true;
                        
                        fetch('delete_room.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `room_id=${roomId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Анимация успешного удаления
                                this.textContent = '✅';
                                this.style.background = 'linear-gradient(135deg, #8CEC7F, #51E03F)';
                                
                                setTimeout(() => {
                                    // Обновляем список комнат
                                    loadRooms();
                                }, 1000);
                            } else {
                                alert('Ошибка при удалении комнаты: ' + data.message);
                                this.textContent = '🗑️';
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Ошибка при удалении комнаты');
                            this.textContent = '🗑️';
                            this.disabled = false;
                        });
                    }
                });
            });
        })
        .catch(error => console.error('Error:', error));
    }

    function checkInvitations() {
        fetch('check_invitations.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.inviter_login && data.room_id) {
                console.log('Приглашение получено:', data);
                inviterElement.textContent = data.inviter_login;
                inviteBox.setAttribute('data-room-id', data.room_id);
                
                // Показываем приглашение с анимацией
                inviteOverlay.style.display = 'block';
                inviteBox.style.display = 'block';
                
                // Небольшая задержка для плавной анимации
                setTimeout(() => {
                    inviteOverlay.classList.add('show');
                    inviteBox.classList.add('show');
                }, 50);
            } else {
                // Скрываем приглашение только если оно было показано
                if (inviteBox.classList.contains('show')) {
                    inviteBox.classList.remove('show');
                    inviteOverlay.classList.remove('show');
                    setTimeout(() => {
                        inviteOverlay.style.display = 'none';
                    }, 300);
                }
            }
        })
        .catch(error => {
            console.error('Error checking invitations:', error);
            // Скрываем приглашение при ошибке
            if (inviteBox.classList.contains('show')) {
                inviteBox.classList.remove('show');
                inviteOverlay.classList.remove('show');
                setTimeout(() => {
                    inviteOverlay.style.display = 'none';
                }, 300);
            }
        });
    }

    // Обработчик крестика убран

    // Закрытие по клику на overlay
    inviteOverlay.addEventListener('click', function() {
        inviteBox.classList.remove('show');
        inviteOverlay.classList.remove('show');
        setTimeout(() => {
            inviteBox.style.display = 'none';
            inviteOverlay.style.display = 'none';
        }, 300);
    });

    yesBtn.addEventListener('click', function() {
        const roomId = inviteBox.getAttribute('data-room-id');
        
        // Анимация нажатия кнопки
        yesBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            yesBtn.style.transform = 'scale(1)';
        }, 150);
        
        // Сначала удаляем приглашение из БД
        fetch('delete_invitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${roomId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Затем входим в комнату
                return fetch('join_room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `room_id=${roomId}`
                });
            } else {
                throw new Error('Ошибка при удалении приглашения: ' + data.message);
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                inviteBox.classList.remove('show');
                inviteOverlay.classList.remove('show');
                setTimeout(() => {
                    window.location.href = `wait.html?room_id=${roomId}`;
                }, 300);
            } else {
                alert('Ошибка при входе в комнату: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при обработке приглашения');
        });
    });

    noBtn.addEventListener('click', function() {
        const roomId = inviteBox.getAttribute('data-room-id');
        
        // Анимация нажатия кнопки
        noBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            noBtn.style.transform = 'scale(1)';
        }, 150);
        
        // Удаляем приглашение из БД
        fetch('delete_invitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${roomId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Приглашение отклонено и удалено из БД');
            } else {
                console.error('Ошибка при удалении приглашения:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
        
        // Скрываем приглашение
        inviteBox.classList.remove('show');
        inviteOverlay.classList.remove('show');
        setTimeout(() => {
            inviteBox.style.display = 'none';
            inviteOverlay.style.display = 'none';
        }, 300);
    });

    loadRooms();
    setInterval(loadRooms, 5000);
    setInterval(checkInvitations, 5000);

    // Функция для загрузки информации о пользователе
    function loadUserInfo() {
        // Пытаемся получить имя из cookie
        const loginCookie = getCookie('login');
        if (loginCookie) {
            userNameElement.textContent = loginCookie;
            userNameElement.style.opacity = '0';
            setTimeout(() => {
                userNameElement.style.opacity = '1';
            }, 200);
        } else {
            // Если нет cookie, запрашиваем с сервера
            fetch('get_user_info.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.login) {
                    userNameElement.textContent = data.login;
                    userNameElement.style.opacity = '0';
                    setTimeout(() => {
                        userNameElement.style.opacity = '1';
                    }, 200);
                } else {
                    // Если пользователь не авторизован, перенаправляем на страницу входа
                    window.location.href = 'login.html';
                }
            })
            .catch(error => {
                console.error('Error loading user info:', error);
                window.location.href = 'login.html';
            });
        }
    }

    // Функция для выхода
    function logout() {
        // Анимация кнопки
        logoutBtn.style.transform = 'scale(0.95)';
        logoutBtn.textContent = 'Выход...';
        logoutBtn.disabled = true;

        fetch('logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Анимация успешного выхода
                logoutBtn.style.background = 'linear-gradient(135deg, #8CEC7F, #51E03F)';
                logoutBtn.textContent = 'Успешно!';
                
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 1000);
            } else {
                logoutBtn.textContent = 'Выйти';
                logoutBtn.disabled = false;
                logoutBtn.style.transform = 'scale(1)';
                alert('Ошибка при выходе: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            logoutBtn.textContent = 'Выйти';
            logoutBtn.disabled = false;
            logoutBtn.style.transform = 'scale(1)';
            alert('Ошибка подключения к серверу');
        });
    }

    // Функция для получения cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
});