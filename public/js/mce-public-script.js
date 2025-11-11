/**
 * CoreAura: Conexión Externa - Frontend Script
 * 
 * Maneja la paginación AJAX, búsqueda y filtros dinámicos
 * 
 * @package CoreAura_Conexion_Externa
 * @version 1.1.0
 */

(function($) {
    'use strict';
    
    // Almacenar estado de cada instancia del shortcode
    const instanceStates = {};
    
    $(document).ready(function() {
        
        // Inicializar todas las instancias del shortcode
        $('.mce-tabla-wrapper').each(function() {
            const $wrapper = $(this);
            const instanceId = $wrapper.data('instance');
            
            if (instanceId && typeof window['mceShortcode_' + instanceId] !== 'undefined') {
                initInstance(instanceId, $wrapper);
            }
        });
        
    });
    
    /**
     * Inicializa una instancia del shortcode
     */
    function initInstance(instanceId, $wrapper) {
        const config = window['mceShortcode_' + instanceId];
        
        // Guardar estado inicial
        instanceStates[instanceId] = {
            paginaActual: 1,
            busqueda: '',
            filtros: {},
            config: config,
            originalHtml: $wrapper.find('.mce-contenido-ajax').html(), // Store original HTML
            originalPagination: $wrapper.find('.mce-paginacion-wrapper').html(), // Store original pagination
            originalInfo: $wrapper.find('.mce-info-resultados').html() // Store original info
        };
        
        // Solo inicializar funcionalidades de búsqueda si está habilitada
        if (config.mostrar_buscador !== false) {
            // Cargar opciones de filtros select
            loadFilterOptions(instanceId, $wrapper);
            
            // Bind eventos
            bindSearchEvents(instanceId, $wrapper);
            bindFilterEvents(instanceId, $wrapper);
        }
        
        bindPaginationEvents(instanceId, $wrapper);
    }
    
    /**
     * Carga las opciones para los filtros tipo select
     */
    function loadFilterOptions(instanceId, $wrapper) {
        const config = instanceStates[instanceId].config;
        
        $wrapper.find('.mce-filtro-select').each(function() {
            const $select = $(this);
            const columna = $select.data('columna');
            
            $.ajax({
                url: config.ajax_url,
                type: 'POST',
                data: {
                    action: 'mce_obtener_opciones_filtro',
                    nonce: config.nonce,
                    tabla: config.data.tabla,
                    columna: columna,
                    where: config.data.where
                },
                success: function(response) {
                    if (response.success && response.data.opciones) {
                        response.data.opciones.forEach(function(opcion) {
                            $select.append(
                                $('<option></option>')
                                    .attr('value', opcion)
                                    .text(opcion)
                            );
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar opciones de filtro:', error);
                }
            });
        });
    }
    
    /**
     * Bind eventos de búsqueda
     */
    function bindSearchEvents(instanceId, $wrapper) {
        const config = instanceStates[instanceId].config;
        
        // Búsqueda al hacer clic en "Buscar"
        $wrapper.find('.mce-btn-buscar').on('click', function(e) {
            e.preventDefault();
            ejecutarBusqueda(instanceId, $wrapper);
        });
        
        // Búsqueda al presionar Enter en el input
        $wrapper.find('.mce-input-busqueda').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                ejecutarBusqueda(instanceId, $wrapper);
            }
        });
        
        // Limpiar búsqueda y filtros
        $wrapper.find('.mce-btn-limpiar').on('click', function(e) {
            e.preventDefault();
            limpiarBusqueda(instanceId, $wrapper);
        });
    }
    
    /**
     * Bind eventos de filtros
     */
    function bindFilterEvents(instanceId, $wrapper) {
        // Los filtros también pueden ejecutarse al presionar Enter
        $wrapper.find('.mce-filtro-text, .mce-filtro-number, .mce-filtro-date').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                ejecutarBusqueda(instanceId, $wrapper);
            }
        });
    }
    
    /**
     * Bind eventos de paginación
     */
    function bindPaginationEvents(instanceId, $wrapper) {
        // Usar delegación de eventos en document para manejar contenido dinámico
        $(document).on('click', '#' + instanceId + ' .mce-pagination a.page-numbers', function(e) {
            e.preventDefault();
            
            const pagina = $(this).data('page');
            if (pagina) {
                cargarPagina(instanceId, $wrapper, pagina);
            }
        });
    }
    
    /**
     * Ejecuta la búsqueda/filtrado
     */
    function ejecutarBusqueda(instanceId, $wrapper) {
        const state = instanceStates[instanceId];
        const config = state.config;
        
        // Recopilar búsqueda universal
        const busqueda = $wrapper.find('.mce-input-busqueda').val() || '';
        
        // Recopilar filtros
        const filtros = {};
        
        $wrapper.find('.mce-filtro-select, .mce-filtro-text, .mce-filtro-number, .mce-filtro-date').each(function() {
            const $input = $(this);
            const columna = $input.data('columna');
            const valor = $input.val();
            
            if (valor && valor !== '') {
                filtros[columna] = valor;
            }
        });
        
        // Actualizar estado
        state.busqueda = busqueda;
        state.filtros = filtros;
        state.paginaActual = 1; // Resetear a página 1
        
        // Mostrar loading
        mostrarLoading($wrapper);
        
        // Ejecutar AJAX
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: {
                action: 'mce_buscar_filtrar',
                nonce: config.nonce,
                busqueda: busqueda,
                filtros: JSON.stringify(filtros),  // Enviar como JSON string
                tabla: config.data.tabla,
                columnas_sql: config.data.columnas_sql,
                limite: config.data.limite,
                orden: config.data.orden,
                direccion: config.data.direccion,
                where: config.data.where,
                mostrar_total: config.data.mostrar_total,
                texto_resultados: config.data.texto_resultados
            },
            success: function(response) {
                ocultarLoading($wrapper);
                
                if (response.success) {
                    // Actualizar contenido
                    $wrapper.find('.mce-contenido-ajax').html(response.data.html);
                    
                    // Actualizar paginación
                    if (response.data.paginacion) {
                        $wrapper.find('.mce-paginacion-wrapper').html(response.data.paginacion).show();
                    } else {
                        $wrapper.find('.mce-paginacion-wrapper').hide();
                    }
                    
                    // Actualizar info de resultados
                    if (response.data.info_resultados) {
                        $wrapper.find('.mce-info-resultados').html(response.data.info_resultados).show();
                    }
                    
                    // Scroll suave al inicio de la tabla
                    $('html, body').animate({
                        scrollTop: $wrapper.offset().top - 100
                    }, 400);
                    
                } else {
                    mostrarError($wrapper, response.data.message || 'Error al realizar la búsqueda');
                }
            },
            error: function(xhr, status, error) {
                ocultarLoading($wrapper);
                mostrarError($wrapper, 'Error de conexión. Por favor intenta de nuevo.');
                console.error('AJAX Error:', error);
            }
        });
    }
    
    /**
     * Limpia búsqueda y filtros
     */
    function limpiarBusqueda(instanceId, $wrapper) {
        const state = instanceStates[instanceId];
        
        // Limpiar inputs
        $wrapper.find('.mce-input-busqueda').val('');
        $wrapper.find('.mce-filtro-select').prop('selectedIndex', 0);
        $wrapper.find('.mce-filtro-text, .mce-filtro-number, .mce-filtro-date').val('');
        
        // Resetear estado
        state.busqueda = '';
        state.filtros = {};
        state.paginaActual = 1;
        
        // Restaurar vista original (exactamente como al inicio)
        $wrapper.find('.mce-contenido-ajax').html(state.originalHtml);
        $wrapper.find('.mce-paginacion-wrapper').html(state.originalPagination).show();
        $wrapper.find('.mce-info-resultados').html(state.originalInfo);
        
        // Scroll suave al inicio de la tabla
        $('html, body').animate({
            scrollTop: $wrapper.offset().top - 100
        }, 400);
    }
    
    /**
     * Carga una página específica
     */
    function cargarPagina(instanceId, $wrapper, pagina) {
        const state = instanceStates[instanceId];
        const config = state.config;
        
        // Actualizar estado
        state.paginaActual = pagina;
        
        // Mostrar loading
        mostrarLoading($wrapper);
        
        // Ejecutar AJAX
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: {
                action: 'mce_cargar_pagina',
                nonce: config.nonce,
                pagina: pagina,
                busqueda: state.busqueda,
                filtros: JSON.stringify(state.filtros),  // Enviar como JSON string
                tabla: config.data.tabla,
                columnas_sql: config.data.columnas_sql,
                limite: config.data.limite,
                orden: config.data.orden,
                direccion: config.data.direccion,
                where: config.data.where,
                mostrar_total: config.data.mostrar_total,
                texto_resultados: config.data.texto_resultados
            },
            success: function(response) {
                ocultarLoading($wrapper);
                
                if (response.success) {
                    // Actualizar contenido
                    $wrapper.find('.mce-contenido-ajax').html(response.data.html);
                    
                    // Actualizar paginación
                    if (response.data.paginacion) {
                        $wrapper.find('.mce-paginacion-wrapper').html(response.data.paginacion);
                    }
                    
                    // Actualizar info de resultados
                    if (response.data.info_resultados) {
                        $wrapper.find('.mce-info-resultados').html(response.data.info_resultados);
                    }
                    
                    // Scroll suave al inicio de la tabla
                    $('html, body').animate({
                        scrollTop: $wrapper.offset().top - 100
                    }, 400);
                    
                } else {
                    mostrarError($wrapper, response.data.message || 'Error al cargar la página');
                }
            },
            error: function(xhr, status, error) {
                ocultarLoading($wrapper);
                mostrarError($wrapper, 'Error de conexión. Por favor intenta de nuevo.');
                console.error('AJAX Error:', error);
            }
        });
    }
    
    /**
     * Muestra indicador de carga
     */
    function mostrarLoading($wrapper) {
        if ($wrapper.find('.mce-loading-overlay').length === 0) {
            $wrapper.find('.mce-contenido-ajax').append(
                '<div class="mce-loading-overlay">' +
                '<div class="mce-spinner"></div>' +
                '<p>Cargando...</p>' +
                '</div>'
            );
        }
        $wrapper.find('.mce-loading-overlay').fadeIn(200);
    }
    
    /**
     * Oculta indicador de carga
     */
    function ocultarLoading($wrapper) {
        $wrapper.find('.mce-loading-overlay').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Muestra mensaje de error
     */
    function mostrarError($wrapper, mensaje) {
        const $error = $('<div class="mce-error-message">' + mensaje + '</div>');
        $wrapper.find('.mce-contenido-ajax').prepend($error);
        
        setTimeout(function() {
            $error.fadeOut(400, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
})(jQuery);
