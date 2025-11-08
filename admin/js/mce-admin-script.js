(function ($) {
    'use strict';

    $(document).ready(function () {
        var testButton = $('#mce-test-connection-btn');
        var noticeBox = $('#mce-test-connection-notice');
        var spinner = testButton.siblings('.spinner');

        testButton.on('click', function () {
            // Deshabilitar el botón y mostrar el spinner
            testButton.prop('disabled', true);
            spinner.addClass('is-active');

            // Limpiar mensajes anteriores y mostrar "Probando..."
            noticeBox
                .removeClass('notice-success notice-error notice-info')
                .addClass('notice-info')
                .html('<p>' + (mce_ajax_object.testing_text || 'Probando conexión...') + '</p>')
                .slideDown();

            // Preparar datos para AJAX (nota: usar "security" o "nonce" según tu handler PHP)
            var data = {
                action: 'mce_test_connection', // Asegura que tu handler PHP es para esta action
                security: mce_ajax_object.test_nonce // O nonce, depende del nombre en localize_script
            };

            // Realiza la llamada AJAX
            $.post(mce_ajax_object.ajax_url, data)
                .done(function (response) {
                    if (response.success === true && response.data && response.data.message) {
                        noticeBox
                            .removeClass('notice-info notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>');
                    } else if (response.data && response.data.message) {
                        noticeBox
                            .removeClass('notice-info notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>');
                    } else {
                        noticeBox
                            .removeClass('notice-info notice-success')
                            .addClass('notice-error')
                            .html('<p>Error inesperado al procesar la respuesta.</p>');
                    }
                })
                .fail(function () {
                    noticeBox
                        .removeClass('notice-info notice-success')
                        .addClass('notice-error')
                        .html('<p>' + (mce_ajax_object.error_text || 'Fallo de comunicación AJAX') + '</p>');
                })
                .always(function () {
                    testButton.prop('disabled', false);
                    spinner.removeClass('is-active');
                });
        });
    });
})(jQuery);
