<?php
class Test_WCML_Media extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	function setUp(){
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();
	}

	/**
	 * @return woocommerce_wpml
	 */
	public function get_woocommerce_wpml(){

		return $this->getMockBuilder('woocommerce_wpml')
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return SitePress
	 */
	public function get_sitepress() {

		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return WCML_Media
	 */
	private function get_subject( $woocommerce_wpml = null, $sitepress = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}



		return new WCML_Media( $woocommerce_wpml, $sitepress , $this->wpdb );
	}

	/**
	 * @test
	 */
	function it_should_sync_variation_thumbnail_id(){

		$variation_id = mt_rand( 1, 10 );
		$translated_variation_id = mt_rand( 11, 20 );
		$lang = rand_str();
		$thumbnail_id  = mt_rand( 21, 30 );
		$translated_thumbnail  = mt_rand( 31, 40 );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $variation_id, '_thumbnail_id', true ),
			'return' => $thumbnail_id
		) );

		WP_Mock::onFilter( 'translate_object_id' )->with( $thumbnail_id, 'attachment', false, $lang )->reply( $translated_thumbnail );


		WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $translated_variation_id, '_thumbnail_id', $translated_thumbnail ),
			'return' => true
		) );

		WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $variation_id, '_wpml_media_duplicate', 1 ),
			'return' => true
		) );

		WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $variation_id, '_wpml_media_featured', 1 ),
			'return' => true
		) );

		$subject = $this->get_subject();
		$subject->sync_variation_thumbnail_id( $variation_id, $translated_variation_id, $lang );

	}

	/**
	 * @test
	 */
	function it_should_duplicate_and_sync_variation_thumbnail_id(){

		$variation_id = mt_rand( 1, 10 );
		$translated_variation_id = mt_rand( 11, 20 );
		$lang = rand_str();
		$thumbnail_id  = mt_rand( 21, 30 );
		$translated_thumbnail  = mt_rand( 31, 40 );

		$media_duplication_mock = $this->getMockBuilder( 'WPML_Media_Attachments_Duplication' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array ( 'create_duplicate_attachment' ) )
		                               ->getMock();
		$media_duplication_mock->method( 'create_duplicate_attachment' )->willReturn( $translated_thumbnail );

		$media_duplication_factory_mock = \Mockery::mock('overload:WPML_Media_Attachments_Duplication_Factory');
		$media_duplication_factory_mock->shouldReceive('create')->andReturn( $media_duplication_mock );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $variation_id, '_thumbnail_id', true ),
			'return' => $thumbnail_id
		) );

		WP_Mock::onFilter( 'translate_object_id' )->with( $thumbnail_id, 'attachment', false, $lang )->reply( null );


		WP_Mock::userFunction( 'wp_get_post_parent_id', array(
			'args' => array( $thumbnail_id ),
			'return' => mt_rand( 41, 50 )
		) );

		WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $translated_variation_id, '_thumbnail_id', $translated_thumbnail ),
			'return' => true
		) );

		WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $variation_id, '_wpml_media_duplicate', 1 ),
			'return' => true
		) );

		WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $variation_id, '_wpml_media_featured', 1 ),
			'return' => true
		) );

		$subject = $this->get_subject();
		$subject->sync_variation_thumbnail_id( $variation_id, $translated_variation_id, $lang );

	}

}