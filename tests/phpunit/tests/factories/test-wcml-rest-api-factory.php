<?php

/**
 * @author              OnTheGo Systems
 *
 * @group               factory
 * @group               wcml-1964
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_WCML_REST_API_Factory extends OTGS_TestCase {
	/**
	 * @test
	 */
	function it_creates_nothing_because_of_missing_sitepress() {
		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();

		$wc_version = '1.0';
		$subject    = new WCML_REST_API_Factory( $wc_version, $wcml );

		Mockery::mock( 'WooCommerce' );

		WP_Mock::wpFunction(
			'get_option',
			array(
				'times' => 0,
				'args'  => array( 'woocommerce_api_enabled' ),
			)
		);

		$this->assertNull( $subject->create() );
	}

	/**
	 * @test
	 */
	function it_creates_nothing_because_of_missing_woocommerce() {
		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		WP_Mock::wpFunction(
			'get_option',
			array(
				'times' => 0,
				'args'  => array( 'woocommerce_api_enabled' ),
			)
		);

		$wc_version = '1.0';
		$subject    = new WCML_REST_API_Factory( $wc_version, $wcml, $sitepress );

		$this->assertNull( $subject->create() );
	}

	/**
	 * @test
	 */
	function it_creates_nothing_because_of_disabled_api() {
		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		Mockery::mock( 'WooCommerce' );

		WP_Mock::wpFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_api_enabled' ),
				'return' => 'no',
			)
		);

		$wc_version = '1.0';
		$subject    = new WCML_REST_API_Factory( $wc_version, $wcml, $sitepress );

		$this->assertNull( $subject->create() );
	}

	/**
	 * @test
	 */
	function it_creates_an_instance_of_WCML_WooCommerce_Rest_API_Support() {
		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		Mockery::mock( 'WooCommerce' );

		WP_Mock::wpFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_api_enabled' ),
				'return' => 'yes',
			)
		);

		$wc_version = '1.0';

		$subject = new WCML_REST_API_Factory( $wc_version, $wcml, $sitepress );

		$actual = $subject->create();
		$this->assertInstanceOf( 'WCML_WooCommerce_Rest_API_Support', $actual, 'Got ' . get_class( $actual ) );
	}

	/**
	 * @test
	 */
	function it_creates_an_instance_of_WCML_REST_API_Support_V1() {
		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		Mockery::mock( 'WooCommerce' );

		$WCML_REST_API_Support_V1 = Mockery::mock( 'overload:WCML_REST_API_Support_V1' );
		$WCML_REST_API_Support_V1->shouldReceive( 'initialize' )->once();

		$WCML_REST_API_Support = Mockery::mock( 'overload:WCML_REST_API_Support' );
		$WCML_REST_API_Support->shouldReceive( 'is_rest_api_request' )->once()->andReturn( true );
		$WCML_REST_API_Support->shouldReceive( 'get_api_request_version' )->once()->andReturn( 1 );
		$WCML_REST_API_Support->shouldReceive( 'initialize' )->never();

		WP_Mock::wpFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_api_enabled' ),
				'return' => 'yes',
			)
		);

		$_SERVER['REQUEST_URI'] = '';

		$wc_version = '3.0';

		$subject = new WCML_REST_API_Factory( $wc_version, $wcml, $sitepress );

		$actual = $subject->create();
		$this->assertInstanceOf( 'WCML_REST_API_Support_V1', $actual, 'Got ' . get_class( $actual ) );
	}

	/**
	 * @test
	 */
	function it_creates_an_instance_of_WCML_REST_API_Support() {
		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		Mockery::mock( 'WooCommerce' );

		$WCML_REST_API_Support_V1 = Mockery::mock( 'overload:WCML_REST_API_Support_V1' );
		$WCML_REST_API_Support_V1->shouldReceive( 'initialize' )->never();

		$WCML_REST_API_Support = Mockery::mock( 'overload:WCML_REST_API_Support' );
		$WCML_REST_API_Support->shouldReceive( 'is_rest_api_request' )->once()->andReturn( true );
		$WCML_REST_API_Support->shouldReceive( 'get_api_request_version' )->once()->andReturn( 2 );
		$WCML_REST_API_Support->shouldReceive( 'initialize' )->once();

		WP_Mock::wpFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_api_enabled' ),
				'return' => 'yes',
			)
		);

		$_SERVER['REQUEST_URI'] = '';

		$wc_version = '3.0';

		$subject = new WCML_REST_API_Factory( $wc_version, $wcml, $sitepress );

		$actual = $subject->create();
		$this->assertInstanceOf( 'WCML_REST_API_Support', $actual, 'Got ' . get_class( $actual ) );
	}

}