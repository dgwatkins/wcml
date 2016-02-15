<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Custom_Prices_UI extends WPML_Templates_Factory {

	private $woocommerce_wpml;
	private $product_id;
	private $custom_prices;
	private $is_variation;


	function __construct( &$woocommerce_wpml, $product_id ){
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->product_id = $product_id;
		$this->is_variation = get_post_type( $product_id) == 'product_variation' ? true : false;
		$this->custom_prices = get_post_custom( $product_id );

	}

	public function get_model() {

		$model = array(
			'custom_prices' => $this->custom_prices,
			'product_id' => $this->product_id,
			'currencies' => $this->get_currencies_info( ),
			'checked_calc_auto' => !isset($this->custom_prices['_wcml_custom_prices_status']) || (isset($this->custom_prices['_wcml_custom_prices_status']) && $this->custom_prices['_wcml_custom_prices_status'][0] == 0)? 'checked="checked"' : ' ' ,
			'checked_calc_manually' => isset($this->custom_prices['_wcml_custom_prices_status']) && $this->custom_prices['_wcml_custom_prices_status'][0] == 1?'checked="checked"':' ',
			'wc_currencies' => get_woocommerce_currencies(),
			'is_variation' => $this->is_variation,
			'html_id' => $this->is_variation ? '['.$this->product_id.']' : '',
			'strings' => array(
				'not_set' => __( 'Multi-currency is enabled but no secondary currencies have been set', 'woocommerce-multilingual' ),
				'calc_auto' => __( 'Calculate prices in other currencies automatically', 'woocommerce-multilingual' ),
				'see_prices' => __( 'Click to see the prices in the other currencies as they are currently shown on the front end.', 'woocommerce-multilingual' ),
				'show' => __( 'Show', 'woocommerce-multilingual' ),
				'hide' => __( 'Hide', 'woocommerce-multilingual' ),
				'set_manually' => __( 'Set prices in other currencies manually', 'woocommerce-multilingual' ),
				'enter_prices' => __( 'Enter prices in other currencies', 'woocommerce-multilingual' ),
				'hide_prices' => __( 'Hide prices in other currencies', 'woocommerce-multilingual' ),
				'det_auto' => __( 'Determined automatically based on exchange rate', 'woocommerce-multilingual' ),
				'regular_price' => __( 'Regular Price', 'woocommerce-multilingual' ),
				'sale_price' => __( 'Sale Price', 'woocommerce-multilingual' ),
				'schedule' => __( 'Schedule', 'woocommerce-multilingual' ),
				'same_as_def' => __( 'Same as default currency', 'woocommerce-multilingual' ),
				'set_dates' => __( 'Set dates', 'woocommerce-multilingual' ),
				'collapse' => __( 'Collapse', 'woocommerce-multilingual' ),
				'from' => __( 'From&hellip;', 'woocommerce-multilingual' ),
				'to' => __( 'To&hellip;', 'woocommerce-multilingual' ),
				'enter_price' => __( 'Please enter in a value less than the regular price', 'woocommerce-multilingual' )
			)
		);

		return $model;
	}


	public function get_currencies_info( ){

		$currencies = $this->woocommerce_wpml->multi_currency_support->get_currencies();
		$wc_currencies = get_woocommerce_currencies();

		foreach( $currencies as $key => $currency ){

			$currencies[ $key ][ 'currency_code' ] = $key;

			$currencies[ $key ][ 'regular_price' ] = '';
			$currencies[ $key ][ 'sale_price' ] = '';

			if( $this->product_id ){

				$currencies[ $key ][ 'regular_price' ] = get_post_meta( $this->product_id,'_regular_price',true);
				if( $currencies[ $key ][ 'regular_price' ] ){
					$currencies[ $key ][ 'regular_price' ] = $currencies[ $key ][ 'regular_price' ]*$currency['rate'];
				}

				$currencies[ $key ][ 'sale_price' ] = get_post_meta( $this->product_id,'_sale_price',true);
				if($currencies[ $key ][ 'sale_price' ]){
					$currencies[ $key ][ 'sale_price' ]  = $currencies[ $key ][ 'sale_price' ]*$currency['rate'];
				}
			}

			$currencies[ $key ][ 'custom_regular_price' ] = '';
			$currencies[ $key ][ 'custom_sale_price' ] = '';

			if( isset( $this->custom_prices[ '_wcml_custom_prices_status' ] ) ){

				if(isset($this->custom_prices['_regular_price_'.$key][0])){
					$currencies[ $key ][ 'custom_regular_price' ] = $this->custom_prices['_regular_price_'.$key][0];
				}

				if(isset($this->custom_prices['_sale_price_'.$key][0])){
					$currencies[ $key ][ 'custom_sale_price' ]    = $this->custom_prices['_sale_price_'.$key][0];
				}
			}


			$currencies[ $key ][ 'currency_format' ] = $wc_currencies[ $key ].' ( '.get_woocommerce_currency_symbol( $key ).' )';
			$currencies[ $key ][ 'currency_symbol' ] = get_woocommerce_currency_symbol( $key );

			if( $this->is_variation ){
				$currencies[ $key ][ 'custom_id' ] = '['.$key.']['.$this->product_id.']';

				if(version_compare(preg_replace( '#-(.+)$#', '', WC_VERSION ), '2.1', '<' ) ){
					$currencies[ $key ][ 'wc_input_type' ] = 'number';
				}else{
					$currencies[ $key ][ 'wc_input_type' ] = 'text';
				}

			}else{

				$wc_input = array();

				if(version_compare(preg_replace('#-(.+)$#', '', WC_VERSION ), '2.1', '<')){
					$wc_input['custom_attributes'] = array('step' 	=> 'any','min'	=> '0') ;
					$wc_input['type_name'] = 'type';
					$wc_input['type_val'] = 'number';
				}else{
					$wc_input['custom_attributes'] = array() ;
					$wc_input['type_name'] = 'data_type';
					$wc_input['type_val'] = 'price';
				}
				ob_start();
				woocommerce_wp_text_input(
					array(
						'id' => '_custom_regular_price'.'['.$key.']',
						'value'=> $currencies[ $key ][ 'custom_regular_price' ] ,
						'class' => 'wc_input_price wcml_input_price short wcml_regular_price',
						'label' => __( 'Regular Price', 'woocommerce-multilingual' ) . ' ('. $currencies[ $key ][ 'currency_symbol' ] .')',
						$wc_input['type_name'] => $wc_input['type_val'],
						'custom_attributes' => $wc_input['custom_attributes']
					)
				);
				$currencies[ $key ][ 'custom_regular_price_html' ] = ob_get_contents();
				ob_end_clean();

				ob_start();
				woocommerce_wp_text_input(
					array(
						'id' => '_custom_sale_price'.'['.$key.']',
						'value'=> $currencies[ $key ][ 'custom_sale_price' ] ,
						'class' => 'wc_input_price wcml_input_price short wcml_sale_price',
						'label' => __( 'Sale Price', 'woocommerce-multilingual' ) . ' ('. $currencies[ $key ][ 'currency_symbol' ].')',
						$wc_input['type_name'] => $wc_input['type_val'],
						'custom_attributes' => $wc_input['custom_attributes']
					)
				);
				$currencies[ $key ][ 'custom_sale_price_html' ] = ob_get_contents();
				ob_end_clean();

				$wc_input['custom_attributes'] = array( 'readonly' => 'readonly', 'rel'=> $currency['rate'] ) ;

				ob_start();
				woocommerce_wp_text_input(
					array(
						'id' => '_readonly_regular_price',
						'value'=>$currencies[ $key ][ 'regular_price' ],
						'class' => 'wc_input_price short',
						'label' => __( 'Regular Price', 'woocommerce-multilingual' ) . ' ('. $currencies[ $key ][ 'currency_symbol' ] .')',
						$wc_input['type_name'] => $wc_input['type_val'],
						'custom_attributes' => $wc_input['custom_attributes']
					)
				);
				$currencies[ $key ][ 'regular_price_html' ] = ob_get_contents();
				ob_end_clean();

				ob_start();
				woocommerce_wp_text_input(
					array(
						'id' => '_readonly_sale_price',
						'value'=> $currencies[ $key ][ 'sale_price' ],
						'class' => 'wc_input_price short',
						'label' => __( 'Sale Price', 'woocommerce-multilingual' ) . ' ('. $currencies[ $key ][ 'currency_symbol' ] .')',
						$wc_input['type_name'] => $wc_input['type_val'],
						'custom_attributes' => $wc_input['custom_attributes']
					)
				);
				$currencies[ $key ][ 'sale_price_html' ] = ob_get_contents();
				ob_end_clean();


			}

			$currencies[ $key ][ 'schedule_auto_checked' ] = (!isset($this->custom_prices['_wcml_schedule_'.$key]) || (isset($this->custom_prices['_wcml_schedule_'.$key]) && $this->custom_prices['_wcml_schedule_'.$key][0] == 0))?'checked="checked"':' ';
			$currencies[ $key ][ 'schedule_man_checked' ] =  isset($this->custom_prices['_wcml_schedule_'.$key]) && $this->custom_prices['_wcml_schedule_'.$key][0] == 1?'checked="checked"':' ';


			$currencies[ $key ][ 'sale_price_dates_from' ] 	= (isset($this->custom_prices['_sale_price_dates_from_'.$key]) && $this->custom_prices['_sale_price_dates_from_'.$key][0] != '') ? date_i18n( 'Y-m-d', $this->custom_prices['_sale_price_dates_from_'.$key][0] ) : '';
			$currencies[ $key ][ 'sale_price_dates_to' ] 	= (isset($this->custom_prices['_sale_price_dates_to_'.$key])  && $this->custom_prices['_sale_price_dates_to_'.$key][0] != '') ? date_i18n( 'Y-m-d', $this->custom_prices['_sale_price_dates_to_'.$key][0] ) : '';

		}

		return $currencies;

	}


	public function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/',
		);
	}

	public function get_template() {
		return 'custom-prices.twig';
	}
}