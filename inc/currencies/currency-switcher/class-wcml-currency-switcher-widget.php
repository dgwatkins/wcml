<?php
  
class WCML_Currency_Switcher_Widget extends WP_Widget {

    function __construct() {

        parent::__construct( 'currency_sel_widget', __('Currency switcher', 'woocommerce-multilingual'), __('Currency switcher', 'woocommerce-multilingual'));
    }

    function widget($args, $instance) {

        echo $args['before_widget'];

        do_action( 'wcml_currency_switcher', array( 'switcher_id' => $args[ 'id' ] ) );

        echo $args['after_widget'];
    }

    function form( $instance ) {
        printf('<p><a class="button button-secondary" href="%s"><span class="otgs-ico-edit"></span> %s</a></p>',admin_url('admin.php?page=wpml-wcml&tab=multi-currency#currency-switcher'),__('Customize the currency switcher', 'woocommerce-multilingual'));
        return;

    }
}