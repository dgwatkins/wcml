<?php

/**
 * @group refactoring
 */
class Test_WCML_Admin_Menu_Documentation_Links extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction( 'admin_url' );
		\WP_Mock::wpPassthruFunction( '__' );
		\WP_Mock::wpPassthruFunction( 'esc_html__' );
		\WP_Mock::wpPassthruFunction( 'esc_html' );
		\WP_Mock::wpPassthruFunction( 'esc_js' );
	}

	public function tearDown() {
		unset( $_GET['taxonomy'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_inits_hook() {
		$subject = new WCML_Admin_Menu_Documentation_Links( null, null, $this->createMock( 'WCML_Tracking_Link' ) );
		\WP_Mock::expectActionAdded( 'admin_footer', array( $subject, 'render' ) );
		$subject->init_hooks();
	}

	/**
	 * @test
	 */
	public function it_returns_null_if_a_post_is_null() {
		$subject = new WCML_Admin_Menu_Documentation_Links( null, null, $this->createMock( 'WCML_Tracking_Link' ) );
		$this->assertNull( $subject->render() );
	}

	/**
	 * @test
	 */
	public function it_renders_product_edit_notice() {
		$pattern = '/.*quick_edit_notice.*/';
		$this->expectOutputRegex($pattern);

		$post_id = 12;
		$post = $this->getMockBuilder('WP_Post')->getMock();
		$post->ID = $post_id;
		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'product',
		) );

		$subject = new WCML_Admin_Menu_Documentation_Links( $post, 'edit.php', $this->createMock( 'WCML_Tracking_Link' ) );
		$subject->render();
	}

	/**
	 * @test
	 */
	public function it_renders_attributes_notice() {
		$doclink = 'http://google.com';
		$message = 'How to translate attributes';

		$this->expectOutputRegex("/.*$message.*/");
		$this->expectOutputRegex( '/.*' . preg_quote( $doclink, '/' ) . '.*/' );

		$post_id = 12;
		$post = $this->getMockBuilder('WP_Post')->getMock();
		$post->ID = $post_id;
		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'post',
		) );

		$_GET['taxonomy'] = 'pa_something';

		$link_tracking = $this->createMock( 'WCML_Tracking_Link' );
		$link_tracking->method( 'generate' )->willReturn( $doclink );

		$subject = new WCML_Admin_Menu_Documentation_Links( $post, 'edit-tags.php',  $link_tracking);
		$subject->render();
	}

	/**
	 * @test
	 */
	public function it_renders_category_notice() {
		$doclink = 'http://someurl.com';
		$message = 'How to translate product categories';

		$this->expectOutputRegex("/.*$message.*/");
		$this->expectOutputRegex( '/.*' . preg_quote( $doclink, '/' ) . '.*/' );

		$post_id = 12;
		$post = $this->getMockBuilder('WP_Post')->getMock();
		$post->ID = $post_id;
		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'post',
		) );

		$_GET['taxonomy'] = 'product_cat';

		$link_tracking = $this->createMock( 'WCML_Tracking_Link' );
		$link_tracking->method( 'generate' )->willReturn( $doclink );

		$subject = new WCML_Admin_Menu_Documentation_Links( $post, 'edit-tags.php',  $link_tracking);
		$subject->render();
	}
}
