<?php

class Test_WCML_Downloadable_Products extends OTGS_TestCase
{

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp()
	{
		parent::setUp();

		\WP_Mock::userFunction( 'wp_register_script', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wp_register_style', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wp_enqueue_style', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'return' => true ) );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_wp_api' ) )
		                        ->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'constant' ) )
		                     ->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

	}

	/**
	 * @return WCML_Downloadable_Products
	 */
	private function get_subject()
	{
		return new WCML_Downloadable_Products( $this->woocommerce_wpml, $this->sitepress );
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_product_options_downloads', array( $subject, 'product_options_downloads_custom_option' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_variation_options_download', array( $subject, 'product_options_downloads_custom_option' ), 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function new_original_product_edit_page( ){

		$product_id = rand( 1, 100 );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_original_product' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		$_POST['product_id'] = $product_id;

		\WP_Mock::userFunction( 'get_post_type', array(
				'args' => $product_id,
				'return' => 'product'
			)
		);

		$custom_files_ui = \Mockery::mock( 'overload:WCML_Custom_Files_UI' );
		$custom_files_ui->shouldReceive( 'show' )->andReturn( '' );

		$subject = $this->get_subject();

		$this->expectOutputString( '' );
		$subject->product_options_downloads_custom_option();
	}


	/**
	 * @test
	 */
	public function not_original_product_edit_page(  ){

		$product_id = mt_rand( 1, 100 );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_original_product' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		$_POST['product_id'] = $product_id;

		\WP_Mock::userFunction( 'get_post_type', array(
				'args' => $product_id,
				'return' => 'product'
			)
		);

		$subject = $this->get_subject();

		$this->assertFalse( $subject->product_options_downloads_custom_option() );

	}

	/**
	 * @test
	 */
	public function not_product_edit_page(  ){

		$subject = $this->get_subject();

		$product_id = rand( 1, 100 );

		$_POST['product_id'] = $product_id;

		\WP_Mock::userFunction( 'get_post_type', array(
				'args' => $product_id,
				'return' => rand_str()
			)
		);

		$this->assertFalse( $subject->product_options_downloads_custom_option() );

		global $pagenow;
		$pagenow = 'post.php';

		$product_id = rand( 1, 100 );

		$_GET['post'] = $product_id;

		\WP_Mock::userFunction( 'get_post_type', array(
				'args' => $product_id,
				'return' => rand_str()
			)
		);

		$this->assertFalse( $subject->product_options_downloads_custom_option() );
	}

	/**
	 * @test
	 */
	public function not_original_product_native_edit_page( ){

		global $pagenow;
		$pagenow = 'post.php';

		$product_id = rand( 1, 100 );

		$_GET['post'] = $product_id;
		$_GET['source_lang'] = rand_str();
		$_GET['post_type']  = 'product';

		\WP_Mock::userFunction( 'get_post_type', array(
				'args' => $product_id,
				'return' => rand_str()
			)
		);

		$subject = $this->get_subject();

		$this->assertFalse( $subject->product_options_downloads_custom_option() );

	}

}

if ( ! class_exists( 'WPML_Templates_Factory' ) ) {

	/**
	 * Class WPML_Templates_Factory
	 * Stub for Test_WCML_Downloadable_Products
	 */
	abstract class WPML_Templates_Factory {

		public function __construct() { /*silence is golden*/ }

		public function show( ) { /*silence is golden*/  }

	}
}


if ( ! class_exists( 'WP_Widget' ) ) {
	/**
	 * Class WP_Widget
	 * Stub for Test_WCML_Downloadable_Products
	 */
	abstract class WP_Widget {

		public function __construct() { /*silence is golden*/
		}

	}
}
