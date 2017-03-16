<?php

class Test_WCML_Troubleshooting extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_element_translations' ) )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();

	}

	/**
	 * @return WCML_Troubleshooting
	 */
	private function get_subject(){
		return new WCML_Troubleshooting( $this->woocommerce_wpml, $this->sitepress, $this->wpdb  );
	}

	/**
	 * @test
	 */
	public function trbl_sync_stock(){

		$_POST[ 'wcml_nonce' ] = rand_str();

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST[ 'wcml_nonce' ], 'trbl_sync_stock' ),
			'return' => true,
			'times'  => 1,
		));
		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );

		$min_stock = 2;
		$product_obj 					= new stdClass();
		$product_obj->ID   				= mt_rand( 100, 200 );
		$product_obj->trid   			= mt_rand( 100, 200 );
		$product_obj->element_type   	= 'product';

		$products = array( $product_obj );

		$this->wpdb->method( 'get_results' )->willReturn( $products );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_obj->ID, '_manage_stock', true ),
			'return' => 'yes'
		));

		$en_translation = new stdClass();
		$en_translation->language_code = 'en';
		$en_translation->element_id = $product_obj->ID;
		$translations['en'] = $en_translation;

		$fr_translation = new stdClass();
		$fr_translation->language_code = 'fr';
		$fr_translation->element_id = mt_rand( 101, 200 );
		$translations['fr'] = $fr_translation;

		$this->sitepress->method( 'get_element_translations' )->willReturn( $translations );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_obj->ID, '_stock', true ),
			'return' => $min_stock
		));

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_obj->ID, '_stock_status', true ),
			'return' => 'instock'
		));

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $fr_translation->element_id, '_stock', true ),
			'return' => mt_rand( 100, 200 )
		));

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_obj->ID, '_stock_status', true ),
			'return' => 'instock'
		));


		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $product_obj->ID, '_stock', $min_stock ),
			'return' => true
		));


		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $product_obj->ID, '_stock_status', 'instock' ),
			'return' => true
		));

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $fr_translation->element_id, '_stock', $min_stock ),
			'return' => true
		));

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $fr_translation->element_id, '_stock_status', 'instock' ),
			'return' => true
		));

		\WP_Mock::wpFunction( 'wp_send_json_success', array(
			'return' => true,
			'times'  => 1,
		));

		$subject = $this->get_subject();
		$subject->trbl_sync_stock();
	}
}
