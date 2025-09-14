console.log('JavaScript file loaded');

const burgerMenu = document.getElementById('burgerMenu');
const menu = document.getElementById('menu');

burgerMenu.addEventListener('click', function() {
    burgerMenu.classList.toggle('active');
    menu.classList.toggle('active');
});

function toggleButton() {
    var button = document.getElementsByClassName('btn')[0];
    var room_id = new URLSearchParams(window.location.search).get('room_id');
    var ready_status = button.textContent === 'Готов' ? true : false;

    fetch('update_ready_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `room_id=${room_id}&ready_status=${ready_status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (button.textContent === 'Готов') {
                button.textContent = 'Не готов';
                button.style.setProperty('--btn-bg-color', '#E42828');
                button.style.setProperty('--btn-hover-bg-color', '#F19393');
            } else {
                button.textContent = 'Готов';
                button.style.setProperty('--btn-bg-color', '#51E03F');
                button.style.setProperty('--btn-hover-bg-color', '#8CEC7F');
            }
            loadRoomInfo(room_id);

            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            alert('Ошибка при обновлении статуса готовности: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const inviteButton = document.querySelector('.invite');
    const popup = document.getElementById('popup');
    const closeBtn = document.getElementById('close-btn');
    const sendButton = document.querySelector('.send');
    const nickInput = document.querySelector('.nick');
    const leaveButton = document.getElementById('leave-btn');

    inviteButton.addEventListener('click', function() {
        popup.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        popup.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == popup) {
            popup.style.display = 'none';
        }
    });

    sendButton.addEventListener('click', function() {
        const invitedLogin = nickInput.value;
        const room_id = new URLSearchParams(window.location.search).get('room_id');

        console.log('Sending invite:', { room_id, invitedLogin });

        fetch('send_invite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${room_id}&invited_login=${invitedLogin}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Приглашение отправлено!');
                popup.style.display = 'none';
            } else {
                alert('Ошибка при отправке приглашения: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    const urlParams = new URLSearchParams(window.location.search);
    const room_id = urlParams.get('room_id');

    if (room_id) {
        loadRoomInfo(room_id);
        setInterval(() => loadRoomInfo(room_id), 5000);
    }

    leaveButton.addEventListener('click', function() {
        if (confirm('Вы уверены, что хотите выйти из комнаты?')) {
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
                    window.location.href = 'room.html';
                } else {
                    alert('Ошибка при выходе из комнаты: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});

function loadRoomInfo(room_id) {
    fetch(`get_room_info.php?room_id=${room_id}`)
    .then(response => response.json())
    .then(data => {
        document.getElementById('roomNumber').textContent = `Комната ${data.room_id}`;
        const playerList = document.getElementById('playerList');
        playerList.innerHTML = '';
        data.players.forEach(player => {
            const li = document.createElement('li');
            li.textContent = player.login;
            if (player.ready) {
                li.classList.add('ready');
            }
            playerList.appendChild(li);
        });

      
        if (data.players.length >= 2 && data.players.every(player => player.ready)) {
            window.location.href = `game.html?room_id=${room_id}`;
        }
    })
    .catch(error => console.error('Error:', error));
}