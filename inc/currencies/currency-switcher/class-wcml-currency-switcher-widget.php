<?php
  
class WCML_Currency_Switcher_Widget extends WP_Widget {

    function __construct() {
        parent::__construct( 'currency_sel_widget', __('Currency switcher', 'woocommerce-multilingual'), __('Currency switcher', 'woocommerce-multilingual'));
    }

    function widget($args, $instance) {

        echo $args['before_widget'];

        if( isset( $instance['settings']['widget_title'] ) && !empty( $instance['settings']['widget_title'] ) ){
            $widget_title = apply_filters( 'widget_title', $instance['settings']['widget_title'] );
            echo $args['before_title']. $widget_title . $args['after_title'];
        }

        do_action( 'wcml_currency_switcher', array( 'switcher_id' => $args[ 'id' ] ) );

        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {

        if ( ! $new_instance ) {
            $new_instance = array(
                'id' => $_POST['sidebar'],
                'settings' => WCML_Currency_Switcher::get_settings( $_POST['sidebar'] )
            );
        }

        return $new_instance;
    }

    function form( $instance ) {
        if( !isset( $instance[ 'id' ] ) )  $instance[ 'id' ] = 'product';

        printf('<p><a class="button button-secondary" href="%s"><span class="otgs-ico-edit"></span> %s</a></p>',admin_url('admin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/'.$instance[ 'id' ]),__('Customize the currency switcher', 'woocommerce-multilingual'));
        return;

    }
}