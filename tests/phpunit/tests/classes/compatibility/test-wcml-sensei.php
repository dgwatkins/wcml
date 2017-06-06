<?php

class Test_WCML_Sensei extends OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** var WPML_Custom_Columns */
	private $wpml_custom_columns;


	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$this->wpdb = $this->stubs->wpdb();

		$this->wpml_custom_columns = $this->getMockBuilder( 'WPML_Custom_Columns' )
		                                  ->disableOriginalConstructor()
		                                  ->getMock();


	}

	private function get_subject() {
		return new WCML_Sensei( $this->sitepress, $this->wpdb, $this->wpml_custom_columns );
	}

	/**
	 * @test
	 */
	public function construct() {
		$subject = $this->get_subject();
		$this->assertInstanceOf( 'WCML_Sensei', $subject );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		\WP_Mock::expectFilterAdded( 'manage_edit-lesson_columns', array(
			$this->wpml_custom_columns,
			'add_posts_management_column'
		) );
		\WP_Mock::expectFilterAdded( 'manage_edit-course_columns', array(
			$this->wpml_custom_columns,
			'add_posts_management_column'
		) );
		\WP_Mock::expectFilterAdded( 'manage_edit-question_columns', array(
			$this->wpml_custom_columns,
			'add_posts_management_column'
		) );
		\WP_Mock::expectActionAdded( 'save_post', array( $subject, 'save_post_actions' ), 100, 2 );
		\WP_Mock::expectActionAdded( 'sensei_log_activity_after', array( $subject, 'log_activity_after' ), 10, 3 );
		\WP_Mock::expectFilterAdded( 'sensei_bought_product_id', array( $subject, 'filter_bought_product_id' ), 10, 2 );
		\WP_Mock::expectActionAdded( 'delete_comment', array( $subject, 'delete_user_activity' ) );
		\WP_Mock::expectActionAdded( 'pre_get_comments', array( $subject, 'pre_get_comments' ) );
		$subject->add_hooks();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

	}

	/**
	 * @test
	 */
	public function remove_wp_before_admin_bar_render_on_sensei_message_post_type() {
		$subject = $this->get_subject();
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		$_GET['post_type'] = 'sensei_message';

		\WP_Mock::wpFunction( 'remove_action', [
			'times' => 1,
			'args'  => [ 'wp_before_admin_bar_render', [ $this->sitepress, 'admin_language_switcher' ] ]
		] );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function DONT_remove_wp_before_admin_bar_render_on_OTHER_post_type() {
		$subject = $this->get_subject();
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		$_GET['post_type'] = rand_str( 16 );

		\WP_Mock::wpFunction( 'remove_action', array( 'times' => 0 ) );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function remove_wp_before_admin_bar_render_on_sensei_grading_page() {
		$subject = $this->get_subject();
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		$_GET['page'] = 'sensei_grading';

		\WP_Mock::wpFunction( 'remove_action', [
			'times' => 1,
			'args'  => [ 'wp_before_admin_bar_render', [ $this->sitepress, 'admin_language_switcher' ] ]
		] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function DONT_remove_wp_before_admin_bar_render_on_OTHER_page() {
		$subject = $this->get_subject();
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		$_GET['page'] = rand_str( 16 );

		\WP_Mock::wpFunction( 'remove_action', array( 'times' => 0 ) );
		$subject->add_hooks();
	}

}
