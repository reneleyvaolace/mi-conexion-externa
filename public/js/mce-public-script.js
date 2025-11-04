(function ($) {
	'use strict';

	$(document).ready(function () {
		
		/**
		 * Esta es la función principal de la paginación AJAX.
		 *
		 * Usamos "delegación de eventos" (escuchando en 'body') porque
		 * los enlaces de paginación '.mce-pagination a' serán reemplazados
		 * con cada llamada AJAX, y un .click() simple dejaría de funcionar
		 * después de la primera carga.
		 */
		$('body').on('click', '.mce-pagination a.page-numbers', function (e) {
			
			// 1. ¡Prevenir la recarga de la página! (Tu requisito)
			e.preventDefault();

			// 2. Encontrar el contenedor principal (el .mce-shortcode-wrapper)
			var $wrapper = $(this).closest('.mce-shortcode-wrapper');

			// 3. Ignorar clics en la página actual
			if ( $(this).hasClass('current') ) {
				return;
			}

			// 4. Obtener la página a la que se hizo clic desde el enlace (href)
			var href = $(this).attr('href');
			var page = 1; // Por defecto
			
			try {
				// Usamos el parser de URL del navegador para encontrar "?pagina_mce=X"
				var url = new URL(href);
				page = url.searchParams.get('pagina_mce');
			} catch (error) {
				console.error('Error al parsear la URL de paginación:', error);
				return; // Salir si la URL está rota
			}

			// 5. Mostrar un estado de "cargando"
			$wrapper.css('opacity', 0.4);

			// 6. Recoger TODOS los atributos (data-tabla, data-color_titulo, etc.)
			// ¡del wrapper! Esto es crucial para que el PHP pueda
			// reconstruir el shortcode con los mismos atributos.
			var data = $wrapper.data();
			
			// 7. Añadir los datos de AJAX
			data.action = 'mce_get_page_content'; // El hook de PHP que escucha
			data.nonce  = mce_ajax_object.nonce; // El nonce de seguridad
			data.pagina = page; // La nueva página que queremos cargar

			// 8. Realizar la llamada AJAX
			$.post(mce_ajax_object.ajax_url, data)
				.done(function (response) {
					if (response.success) {
						// ¡Éxito! Reemplazar el contenido del wrapper
						// con el nuevo HTML (nuevas tarjetas + nuevos enlaces)
						// que nos envió el PHP.
						$wrapper.html(response.data.html);
						
						// Opcional: Hacer scroll suave de vuelta al inicio del wrapper
						/*
						$('html, body').animate({
							scrollTop: $wrapper.offset().top - 50 // 50px de espacio
						}, 500);
						*/
					} else {
						// El PHP nos dio un error (ej. tabla no encontrada)
						alert('Error: ' + response.data.message);
						$wrapper.css('opacity', 1); // Quitar el "cargando"
					}
				})
				.fail(function () {
					// El servidor falló (Error 500, etc.)
					alert('Error: No se pudo contactar al servidor.');
					$wrapper.css('opacity', 1); // Quitar el "cargando"
				});
		});

	});

})(jQuery);