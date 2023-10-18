<?php

namespace WCML\Compatibility\WcBookings\Templates;

use WP_Mock;
use WPML\LIB\WP\WPDBMock;
use WPML\LIB\WP\PostMock;
use WPML\LIB\WP\OnActionMock;
use WPML\Element\API\PostTranslations;
use WPML\Element\API\TranslationsMock;
use WPML\Element\API\LanguagesMock;


class TestMyBookings extends \OTGS_TestCase {
	use LanguagesMock;
	use OnActionMock;
	use PostMock;
	use TranslationsMock;
	use WPDBMock;

	public function setUp() {
		parent::setUp();
		$this->setupLanguagesMock();
		$this->setUpOnAction();
		$this->setUpPostMock();
		$this->setupElementTranslations();
		$this->setUpWPDBMock();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		$this->tearDownLanguagesMock();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itFiltersByCurrentLanguage() {
		$originalLanguage = 'en';
		$currentLanguage  = 'fr';
		$originalBookingId = 1;
		$bookingId         = 2;
		$originalProductId = 10;
		$productId         = 11;

		$this->setDefaultLanguage( $originalLanguage );
		$this->setActiveLanguages( [ $originalLanguage, $currentLanguage ] );
		$this->setCurrentLanguage( $currentLanguage );

		$this->mockPostType( $originalBookingId, 'wc_booking' );
		$this->mockPostType( $bookingId, 'wc_booking' );
		PostTranslations::setAsSource( $originalBookingId, $originalLanguage );
		PostTranslations::setAsTranslationOf( $originalBookingId, $bookingId, $currentLanguage );

		$this->mockPostType( $originalProductId, 'product' );
		$this->mockPostType( $productId, 'product' );
		PostTranslations::setAsSource( $originalProductId, $originalLanguage );
		PostTranslations::setAsTranslationOf( $originalProductId, $productId, $currentLanguage );

		global $wpml_post_translations;
		$wpml_post_translations = $this->getMockBuilder( '\WPML_Post_Translation' )
			->setMethods( [ 'get_element_lang_code' ] )
			->disableOriginalConstructor()
			->getMock();
		$wpml_post_translations->method( 'get_element_lang_code' )
			->with( $originalBookingId )
			->willReturn( $originalLanguage );

		$originalBooking = $this->getMockBuilder( 'WC_Booking' )
			->setMethods( [ 'get_id' ] )
			->disableOriginalConstructor()
			->getMock();
		$originalBooking->method( 'get_id' )
			->willReturn( $originalBookingId );

		$tables = [
			'Upcoming' => [
				'bookings' => [
					$originalBooking,
				]
			]
		];

		( new MyBookings() )->add_hooks();

		$result = $this->runFilter( 'woocommerce_bookings_account_tables', $tables );
		$this->assertEmpty( $result['Upcoming']['bookings'] );
	}

}
