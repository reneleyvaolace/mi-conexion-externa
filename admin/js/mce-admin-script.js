(function ($) {
	'use strict';

	/**
	 * Todo este código se ejecutará una vez que el DOM esté listo.
	 */
	$(document).ready(function () {
		// Seleccionamos nuestros elementos una sola vez para mejor rendimiento.
		var testButton = $('#mce-test-connection-btn');
		var noticeBox = $('#mce-test-connection-notice');
		var spinner = testButton.siblings('.spinner');

		/**
		 * Manejador del clic en el botón de prueba.
		 */
		testButton.on('click', function () {
			// 1. Deshabilitar el botón y mostrar el spinner.
			testButton.prop('disabled', true);
			spinner.addClass('is-active');

			// 2. Limpiar mensajes anteriores y mostrar "Probando...".
			noticeBox
				.removeClass('notice-success notice-error')
				.addClass('notice-info')
				.html('<p>' + mce_ajax_object.testing_text + '</p>')
				.slideDown();

			// 3. Preparar los datos para enviar (Regla 1: Nonce y Action).
			var data = {
				action: 'mce_test_connection', // El hook 'wp_ajax_' que escucha nuestro PHP.
				security: mce_ajax_object.test_nonce, // El nonce que pasamos con wp_localize_script.
			};

			// 4. Realizar la llamada AJAX.
			$.post(mce_ajax_object.ajax_url, data)
				.done(function (response) {
					// 5a. Éxito (PHP devolvió wp_send_json_success).
					if (response.success === true) {
						noticeBox
							.removeClass('notice-info notice-error')
							.addClass('notice-success')
							// *** LÍNEA CORREGIDA ***
							.html('<p>' + response.data.message + '</p>');
					} else {
						// 5b. Error controlado (PHP devolvió wp_send_json_error).
						noticeBox
							.removeClass('notice-info notice-success')
							.addClass('notice-error')
							.html('<p>' + response.data.message + '</p>');
					}
				})
				.fail(function () {
					// 5c. Error catastrófico (Error 500, red caída, etc.).
					noticeBox
						.removeClass('notice-info notice-success')
						.addClass('notice-error')
						.html('<p>' + mce_ajax_object.error_text + '</p>');
				})
				.always(function () {
					// 6. Al finalizar, reactivar el botón y ocultar el spinner.
					testButton.prop('disabled', false);
					spinner.removeClass('is-active');
				});
		});
	});
})(jQuery);