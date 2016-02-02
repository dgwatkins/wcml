<?php

class WCML_Autoloader {

    private $include_paths = array();

    public function __construct() {
        if ( function_exists( "__autoload" ) ) {
            spl_autoload_register( "__autoload" );
        }

        spl_autoload_register( array( $this, 'autoload' ) );

        $this->include_paths = array(
            WCML_PLUGIN_PATH . '/inc/',
            WCML_PLUGIN_PATH . '/inc/template-classes',
            WCML_PLUGIN_PATH . '/compatibility/'
        );
    }

    private function get_file_name_from_class( $class ) {
        return 'class-' . str_replace( '_', '-', $class ) . '.php';
    }

    private function load_file( $path ) {
        if ( $path && is_readable( $path ) ) {
            include_once( $path );
            return true;
        }
        return false;
    }

    public function autoload( $class ) {
        $class = strtolower( $class );
        $file  = $this->get_file_name_from_class( $class );
        $path  = '';

        foreach( $this->include_paths as $path ){
            if( $this->load_file( $path . '/' . $file) ){
                break;
            }
        }

    }
}

new WCML_Autoloader();
