<?php

class Test_WCML_Translation_Editor extends OTGS_TestCase {

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
		                        ->getMock();

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


}
