(function ($) {
    'use strict';

    // Delegación global para sobrevivir reemplazos completos de DOM
    $(function () {
        $(document).on('click', '.mce-pagination a.page-numbers', function (e) {
            e.preventDefault();

            var $link = $(this);
            var $wrapper = $link.closest('.mce-shortcode-wrapper');

            // No avanzar si ya estamos en la actual
            if ($link.hasClass('current')) {
                return;
            }

            var href = $link.attr('href');
            var page = 1;

            if (href.indexOf('pagina_mce=') > -1) {
                var pageNumStr = href.split('pagina_mce=')[1];
                page = parseInt(pageNumStr.split('&')[0]);
            } else {
                return;
            }

            if (isNaN(page)) {
                return;
            }

            $wrapper.css('opacity', 0.4);

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

            data.action = 'mce_get_page_content';
            data.nonce = mce_ajax_object.nonce;
            data.pagina = page;

            $.post(mce_ajax_object.ajax_url, data)
                .done(function (response) {
                    if (response.success) {
                        // Reemplaza el wrapper completo
                        $wrapper.replaceWith(response.data.html);
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
