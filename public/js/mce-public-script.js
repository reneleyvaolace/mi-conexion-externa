(function ($) {
	'use strict';

	$(document).ready(function () {
		
		$('body').on('click', '.mce-pagination a.page-numbers', function (e) {
			
			// 1. ¡Prevenir la recarga de la página!
			e.preventDefault();

			// 2. Encontrar el contenedor principal
			var $wrapper = $(this).closest('.mce-shortcode-wrapper');

			// 3. Ignorar clics en la página actual
			if ( $(this).hasClass('current') ) {
				return;
			}

			// 4. *** ¡LÓGICA CORREGIDA! ***
			// Obtener la página a la que se hizo clic
			// de una forma robusta que no falle con URLs relativas.
			var href = $(this).attr('href');
			var page = 1;

			if (href.indexOf('pagina_mce=') > -1) {
				// 1. Corta el string en "pagina_mce="
				var pageNumStr = href.split('pagina_mce=')[1];
				// 2. Si hay más parámetros (ej. &foo=bar), los quita
				page = parseInt(pageNumStr.split('&')[0]);
			} else {
				console.error('Error: No se pudo encontrar "pagina_mce=" en el href del enlace.');
				return;
			}

			if ( isNaN(page) ) {
				console.error('Error: El número de página no es válido.');
				return;
			}

			// 5. Mostrar un estado de "cargando"
			$wrapper.css('opacity', 0.4);

			// 6. Recoger TODOS los atributos manualmente (snake_case)
			var data = {
				'tabla': $wrapper.data('tabla'),
				'columnas': $wrapper.data('columnas'),
				'paginacion': $wrapper.data('paginacion'),
				'columnas_mostrar': $wrapper.data('columnas_mostrar'),
				'llave_titulo': $wrapper.data('llave_titulo'),
				'ocultar_etiquetas': $wrapper.data('ocultar_etiquetas'),
				'color_titulo': $wrapper.data('color_titulo'),
				'tamano_titulo': $wrapper.data('tamano_titulo'),
				'color_etiqueta': $wrapper.data('color_etiqueta'),
				'color_valor': $wrapper.data('color_valor'),
				'color_enlace': $wrapper.data('color_enlace')
			};
			
			// 7. Añadir los datos de AJAX
			data.action = 'mce_get_page_content'; // El hook de PHP
			data.nonce  = mce_ajax_object.nonce; // El nonce de seguridad
			data.pagina = page; // La nueva página

			// 8. Realizar la llamada AJAX
			$.post(mce_ajax_object.ajax_url, data)
				.done(function (response) {
					if (response.success) {
						// ¡Éxito! Reemplazar el contenido del wrapper
						$wrapper.html(response.data.html);
						$wrapper.css('opacity', 1);
					} else {
						alert('Error: ' + response.data.message);
						$wrapper.css('opacity', 1);
					}
				})
				.fail(function () {
					alert('Error: No se pudo contactar al servidor.');
					$wrapper.css('opacity', 1);
				});
		});

	});

})(jQuery);