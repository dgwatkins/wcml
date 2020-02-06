<?php

/**
 * Class Test_WCML_Translation_Editor
 */
class Test_WCML_Translation_Editor extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder('SitePress')
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_wp_api' ) )
		                         ->getMock();


		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant', 'version_compare' ) )
		               ->getMock();


		$this->sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();

	}

	/**
	 * @return WCML_Translation_Editor
	 */
	private function get_subject(){
		return new WCML_Translation_Editor( $this->woocommerce_wpml, $this->sitepress, $this->wpdb  );
	}

	/**
	 * @test
	 */
	public function add_admin_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wpml_tm_show_page_builders_translation_editor_warning', array( $subject, 'show_page_builders_translation_editor_warning' ), 10 ,2 );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function show_page_builders_translation_editor_warning(){

		$subject = $this->get_subject();

		$post_id = mt_rand();

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'product'
		));

		$this->assertFalse( $subject->show_page_builders_translation_editor_warning( true, $post_id) );
	}

	/**
	 * @test
	 */
	public function it_locks_variable_fields_for_variable_with_auto_sync_download_files(){

		$post_id = mt_rand( 1, 10 );
		$_GET['post'] = $post_id;

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'product'
		));

		\WP_Mock::wpFunction( 'get_post_status', array(
			'args'   => array( $post_id ),
			'return' => 'publish'
		));

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'wcml_products' )
		                                         ->disableOriginalConstructor()
												->setMethods( array( 'is_original_product', 'get_original_product_id'))
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'is_original_product' )->with( $post_id )->willReturn( false );

		$variation = new stdClass();
		$variation->ID = mt_rand( 11, 20 );
		$original_id = mt_rand( 21, 30 );

		$variations = array( $variation );

		\WP_Mock::wpFunction( 'get_posts', array(
			'return' => $variations
		));

		\WP_Mock::wpFunction( 'absint', array(
			'args'   => array( $variation->ID ),
			'return' => $variation->ID
		));

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->with( $variation->ID )->willReturn( $original_id );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $original_id, 'wcml_sync_files', true ),
			'return' => false
		));

		$subject = $this->get_subject();

		ob_start();
		$subject->lock_variable_fields( true );
		$output = ob_get_clean();

		$this->assertContains( 'wcml_lock_variation_fields( {"'.$variation->ID.'":true} );', $output );
	}


	/**
	 * @test
	 */
	public function it_should_force_woocommerce_native_editor_for_wcml_products_screen(){

		$subject = $this->get_subject();

		$current_screen = new stdClass();
		$current_screen->id = 'woocommerce_page_wpml-wcml';

		\WP_Mock::wpFunction( 'get_current_screen', array(
			'return' => $current_screen
		));

		$this->assertEquals( 1, $subject->force_woocommerce_native_editor_for_wcml_products_screen( false ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_force_woocommerce_native_editor_for_not_defined_screen(){

		$subject = $this->get_subject();

		$current_screen = null;

		\WP_Mock::wpFunction( 'get_current_screen', array(
			'return' => $current_screen
		));

		$this->assertFalse( $subject->force_woocommerce_native_editor_for_wcml_products_screen( false ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_force_woocommerce_native_editor_for_not_wcml_products_screen(){

		$subject = $this->get_subject();

		$current_screen = new stdClass();
		$current_screen->id = 'a-non-wcml-dashboard';

		\WP_Mock::wpFunction( 'get_current_screen', array(
			'return' => $current_screen
		));

		$this->assertFalse( $subject->force_woocommerce_native_editor_for_wcml_products_screen( false ) );
	}


}
