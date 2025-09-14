$(document).ready(function() {
    // Анимация появления формы
    $('.box-form').hide().fadeIn(800);
    $('.title').hide().slideDown(1000);

    // Анимация для полей ввода
    $('.input-box input').on('focus', function() {
        $(this).parent().addClass('focused');
    });

    $('.input-box input').on('blur', function() {
        if ($(this).val() === '') {
            $(this).parent().removeClass('focused');
        }
    });

    // Проверка заполненности полей при загрузке
    $('.input-box input').each(function() {
        if ($(this).val() !== '') {
            $(this).parent().addClass('focused');
        }
    });

    $('#loginForm').submit(function(event) {
        event.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('.btn');
        const $message = $('#loginMessage');
        
        // Анимация кнопки
        $submitBtn.addClass('loading');
        $submitBtn.prop('disabled', true);
        
        var formData = $form.serialize();
        console.log(formData);
        
        $.ajax({
            type: 'POST',
            url: 'login.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log("Response from server:", response);
                
                if (response.success) {
                    $message.html('<div class="success-message">' + response.message + '</div>');
                    
                    // Анимация успешного входа
                    $form.fadeOut(500, function() {
                        setTimeout(() => {
                            window.location.href = response.redirect || "room.html";
                        }, 1000);
                    });
                } else {
                    $message.html('<div class="error-message">' + response.message + '</div>');
                    shakeForm($form);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                $message.html('<div class="error-message">Ошибка подключения к серверу</div>');
                shakeForm($form);
            },
            complete: function() {
                $submitBtn.removeClass('loading');
                $submitBtn.prop('disabled', false);
            }
        });
    });

    $('#registerForm').submit(function(event) {
        event.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('.btn');
        const $message = $('#registerMessage');
        
        // Анимация кнопки
        $submitBtn.addClass('loading');
        $submitBtn.prop('disabled', true);
        
        var formData = $form.serialize();
        console.log(formData);
        
        $.ajax({
            type: 'POST',
            url: 'reg.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log(response);
                
                if (response.success) {
                    $message.html('<div class="success-message">' + response.message + '</div>');
                    
                    // Анимация успешной регистрации
                    $form.fadeOut(500, function() {
                        setTimeout(() => {
                            window.location.href = response.redirect || "login.html";
                        }, 1000);
                    });
                } else {
                    $message.html('<div class="error-message">' + response.message + '</div>');
                    shakeForm($form);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                $message.html('<div class="error-message">Ошибка подключения к серверу</div>');
                shakeForm($form);
            },
            complete: function() {
                $submitBtn.removeClass('loading');
                $submitBtn.prop('disabled', false);
            }
        });
    });

    // Функция для анимации тряски формы при ошибке
    function shakeForm($form) {
        $form.addClass('shake');
        setTimeout(() => {
            $form.removeClass('shake');
        }, 500);
    }

    // Анимация для ссылок
    $('a').hover(
        function() {
            $(this).addClass('link-hover');
        },
        function() {
            $(this).removeClass('link-hover');
        }
    );
});