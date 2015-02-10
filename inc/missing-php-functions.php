<?php
  
/* PHP 5.3 - start */

if(false === function_exists('lcfirst'))
{
    /**
     * Make a string's first character lowercase
     *
     * @param string $str
     * @return string the resulting string.
     */
    function lcfirst( $str ) {
        $str[0] = strtolower($str[0]);
        return (string)$str;
    }
}

/* PHP 5.3 - end */
  
//WPML
add_action('plugins_loaded', 'wcml_check_wpml_is_ajax');

function wcml_check_wpml_is_ajax(){
    if(defined('ICL_SITEPRESS_VERSION') && version_compare(preg_replace('#-(.+)$#', '', ICL_SITEPRESS_VERSION), '3.1.5', '<')){
        
        function wpml_is_ajax() {
            if ( defined( 'DOING_AJAX' ) ) {
                return true;
            }

            return ( isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest' ) ? true : false;
        }
        
    }
    
    
}

  
?>
