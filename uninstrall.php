<?php
/**
 * Archivo de desinstalación para Mi Conexión Externa.
 * Elimina todas las opciones y transients del plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// 1. Eliminar la opción principal de conexión
delete_option( 'mce_db_settings' );

// 2. Eliminar transients generados por el plugin (añade aquí si usas transients personalizados)
// delete_transient( 'nombre_de_tu_transient' );

// 3. Eliminar meta de usuario/post si aplica (añade aquí si tu plugin guardó meta extra)
// delete_user_meta( $user_id, 'meta_key' );
// delete_post_meta_by_key( 'meta_key' );

// 4. Eliminar roles/capacidades personalizados (si aplica)
// $role = get_role( 'nombre_role' );
// if ( $role ) { remove_role( 'nombre_role' ); }
