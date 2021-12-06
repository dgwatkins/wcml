<?php

namespace WCML\AdminNotices;

/**
 * @group admin-notices
 * @group wcml-3198
 */
class TestCachePlugins extends \OTGS_TestCase {

	public function setUp() {
		parent::setUp();
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );
	}

	/**
	 * @test
	 */
	public function itShouldNotAddHooksIfNotUsingMultiCurrency() {
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( false );

		$subject = $this->getSubject();

		\WP_Mock::expectActionNotAdded(' admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotAddHooksIfAlreadyHasNotice() {
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( true );

		$notices = $this->getNotices();
		$notices->method( 'get_notice' )
			->with( CachePlugins::NOTICE_ID )
			->willReturn( $this->getNotice() );

		$subject = $this->getSubject( $notices );

		\WP_Mock::expectActionNotAdded(' admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotAddHooksIfHasNoActiveCachePlugin() {
		$plugins = [
			'foo/bar' => [
				'Description' => 'The best foo/bar plugin',
			],
		];

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( true );

		$notices = $this->getNotices();
		$notices->method( 'get_notice' )
			->with( CachePlugins::NOTICE_ID )
			->willReturn( false );

		\WP_Mock::userFunction( 'get_plugins' )->andReturn( $plugins );
		\WP_Mock::userFunction( 'is_plugin_active' )->andReturn( true );

		$subject = $this->getSubject( $notices );

		\WP_Mock::expectActionNotAdded(' admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider dpShouldAddHooksIfHasActiveCachePlugin
	 *
	 * @param string $keyword
	 */
	public function itShouldAddHooksIfHasActiveCachePlugin( $keyword ) {
		$plugins = [
			'foo/bar' => [
				'Description' => 'The best foo/bar plugin',
			],
			'super-rocket/plugin.php' => [
				'Description' => "The best $keyword plugin in the world",
			],
		];

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( true );

		$notices = $this->getNotices();
		$notices->method( 'get_notice' )
			->with( CachePlugins::NOTICE_ID )
			->willReturn( false );

		\WP_Mock::userFunction( 'get_plugins' )->andReturn( $plugins );
		\WP_Mock::userFunction( 'is_plugin_active' )->andReturn( true );

		$subject = $this->getSubject( $notices );

		\WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	public function dpShouldAddHooksIfHasActiveCachePlugin() {
		return [
			[ 'cache' ],
			[ 'caching' ],
		];
	}

	/**
	 * @test
	 */
	public function itShouldAddNotice() {
		$notice = $this->getNotice();
		$notice->expects( $this->once() )
			->method( 'set_css_class_types' )
			->with( 'notice-warning' );
		$notice->expects( $this->once() )
			->method( 'set_restrict_to_screen_ids' )
			->with( RestrictedScreens::get() );
		$notice->expects( $this->once() )
			->method( 'set_dismissible' )
			->with( true );

		$notices = $this->getNotices();
		$notices->method( 'create_notice' )
			->with( CachePlugins::NOTICE_ID )
			->willReturn( $notice );
		$notices->expects( $this->once() )
			->method( 'add_notice' )
			->with( $notice );

		$subject = $this->getSubject( $notices );

		$subject->addNotice();
	}

	private function getSubject( $notices = null ) {
		$notices = $notices ?: $this->getNotices();

		return new CachePlugins( $notices );
	}

	private function getNotices() {
		return $this->getMockBuilder( '\WPML_Notices' )
			->setMethods(
				[
					'get_notice',
					'create_notice',
					'add_notice',
				]
			)->disableOriginalConstructor()->getMock();
	}

	private function getNotice() {
		return $this->getMockBuilder( '\WPML_Notice' )
		            ->setMethods(
			            [
			            	'set_css_class_types',
				            'set_restrict_to_screen_ids',
				            'set_dismissible',

			            ]
		            )->disableOriginalConstructor()->getMock();
	}
}
