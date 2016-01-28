<?php

if( file_exists( WCML_PLUGIN_PATH . '/inc/installer/loader.php' ) ){

    include WCML_PLUGIN_PATH . '/inc/installer/loader.php' ;
    $args = array();
    WP_Installer_Setup( $wp_installer_instance, $args );

}