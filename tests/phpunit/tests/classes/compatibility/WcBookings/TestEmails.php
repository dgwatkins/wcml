<?php

namespace WCML\Compatibility\WcBookings;

use tad\FunctionMocker\FunctionMocker;

/**
 * Class TestEmails
 *
 * @group compatibility
 * @group wc-bookings
 */
class TestEmails extends \OTGS_TestCase {

	private function getSubject( $sitepress = null, $woocommerceWpml = null, $woocommerce = null ) {
		if ( null === $woocommerceWpml ) {
			$woocommerceWpml = $this->getMockBuilder( \woocommerce_wpml::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( null === $woocommerce ) {
			$woocommerce = $this->getMockBuilder( \woocommerce::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( null === $sitepress ) {
			$sitepress = $this->getMockBuilder( \SitePress::class )
				->disableOriginalConstructor()
				->getMock();
		}

		$emails = new Emails( $sitepress, $woocommerceWpml, $woocommerce );
		$emails->init();

		return $emails;
	}

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wcml_emails_options_to_translate', [ $subject, 'optionsToTranslate' ] );
		\WP_Mock::expectFilterAdded( 'wcml_emails_text_keys_to_translate', [ $subject, 'keysToTranslate' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_email_get_option', [ $subject, 'translateHeadingAndSubject' ], 20, 4 );

		\WP_Mock::expectActionAdded( 'woocommerce_admin_new_booking_notification', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );
		\WP_Mock::expectActionAdded( 'woocommerce_booking_confirmed_notification', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );
		\WP_Mock::expectActionAdded( 'wc-booking-reminder', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );
		\WP_Mock::expectActionAdded( 'woocommerce_booking_pending-confirmation_to_cancelled_notification', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );
		\WP_Mock::expectActionAdded( 'woocommerce_booking_confirmed_to_cancelled_notification', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );
		\WP_Mock::expectActionAdded( 'woocommerce_booking_paid_to_cancelled_notification', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );
		\WP_Mock::expectActionAdded( 'woocommerce_booking_unpaid_to_cancelled_notification', function() {}, Emails::PRIORITY_BEFORE_EMAIL_TRIGGER );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itAddsBookingsEmailsOptionsToTranslate() {
		$subject = $this->getSubject();
		$options = $subject->optionsToTranslate( [] );

		$this->assertContains( 'woocommerce_new_booking_settings', $options );
	}

	/**
	 * @test
	 */
	public function itAddsBookingsEmailsTextKeysToTranslate() {
		$subject  = $this->getSubject();
		$textKeys = $subject->keysToTranslate( [] );

		$this->assertEquals( [ 'subject_confirmation','heading_confirmation' ], $textKeys );
	}

	/**
	 * @test
	 * @dataProvider dpTranslateEmailsTextStrings
	 *
	 * @param string $key
	 * @param string $objectId
	 * @param bool   $isAdminEmail
	 * @param string $translatedValue
	 */
	public function itTranslatesEmailsTextStrings( $key, $class, $isAdminEmail, $translatedValue ) {
		$value = 'original value';

		$object = $this->getMockBuilder( $class )
			->disableOriginalConstructor()
			->getMock();
		$object->$key = 'some value';

		FunctionMocker::replace( 'get_class', $class );

		$emails = $this->getMockBuilder( \WCML_Emails::class )
			->setMethods( [ 'get_email_translated_string' ] )
			->disableOriginalConstructor()
			->getMock();
		$emails->method( 'get_email_translated_string' )
			->with( $key, $object, $isAdminEmail )
			->willReturn( $translatedValue );

		$woocommerceWpml = $this->getMockBuilder( \woocommerce_wpml::class )
			->disableOriginalConstructor()
			->getMock();
		$woocommerceWpml->emails = $emails;

		$subject = $this->getSubject( null, $woocommerceWpml );

		$this->assertEquals(
			$translatedValue ? $translatedValue : $value,
			$subject->translateHeadingAndSubject( $value, $object , 'some old value', $key )
		);
	}

	public function dpTranslateEmailsTextStrings() {
		return [
			'id:admin_booking_cancelled' => [ 'subject', \WC_Email_Admin_Booking_Cancelled::class, true, 'translated value' ],
			'id:new_booking'             => [ 'subject', \WC_Email_New_Booking::class, true, 'translated value' ],
			'id:booking_cancelled'       => [ 'subject', \WC_Email_Booking_Cancelled::class, false, 'translated value' ],
			'id:booking_confirmed'       => [ 'subject', \WC_Email_Booking_Confirmed::class, false, 'translated value' ],
			'id:booking_reminder'        => [ 'subject', \WC_Email_Booking_Reminder::class, false, 'translated value' ],
			'key:subject'                => [ 'subject', \WC_Email_Booking_Reminder::class, false, 'translated value' ],
			'key:subject_confirmation'   => [ 'subject_confirmation', \WC_Email_Booking_Reminder::class, false, 'translated value' ],
			'key:heading'                => [ 'heading', \WC_Email_Booking_Reminder::class, false, 'translated value' ],
			'key:heading_confirmation'   => [ 'heading_confirmation', \WC_Email_Booking_Reminder::class, false, 'translated value' ],
			'empty translation'          => [ 'heading_confirmation', \WC_Email_Booking_Reminder::class, false, '' ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpEmailNotifications
	 *
	 * @param string $class
	 * @param bool   $isAdmin
	 */
	public function itSendsWithoutDuplicatesToUsers( $class, $isAdmin ) {
		$bookingId   = 123;
		$adminUser   = (object) [
			'ID'    => 1,
			'email' => 'user@example.com',
			'lang'  => 'en',
		];

		$sitepress = $this->getMockBuilder( \SitePress::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_user_admin_language' ] )
			->getMock();
		$sitepress->method( 'get_user_admin_language' )
			->with( $adminUser->ID, true )
			->willReturn( $adminUser->lang );

		$emails = $this->getMockBuilder( \WCML_Emails::class )
			->setMethods( [ 'refresh_email_lang', 'change_email_language' ] )
			->disableOriginalConstructor()
			->getMock();

		\WP_Mock::userFunction( 'get_user_by', [
			'args'   => [ 'email', $adminUser->email ],
			'return' => $adminUser,
		] );

		if ( $isAdmin ) {
			$emails->expects( $this->once() )->method( 'change_email_language' )->with( $adminUser->lang );
		} else {
			$emails->expects( $this->once() )->method( 'refresh_email_lang' )->with( $bookingId );
		}

		$woocommerceWpml = $this->getMockBuilder( \woocommerce_wpml::class )
			->disableOriginalConstructor()
			->getMock();
		$woocommerceWpml->emails = $emails;

		$wcMailBookingCancelled = $this->getMockBuilder( $class )
			->disableOriginalConstructor()
			->setMethods( [ 'trigger' ] )
			->getMock();
		$wcMailBookingCancelled->expects( $this->once() )->method( 'trigger' )->with( $bookingId );
		$wcMailBookingCancelled->enabled = 'yes';
		if ( $isAdmin ) {
			$wcMailBookingCancelled->recipient = $adminUser->email;
		}

		$mailer = $this->getMockBuilder( \WC_Emails::class )
			->disableOriginalConstructor()
			->getMock();
		$mailer->emails = [ $class => $wcMailBookingCancelled ];

		$woocommerce = $this->getMockBuilder( \woocommerce::class )
			->setMethods( [ 'mailer' ] )
			->disableOriginalConstructor()
			->getMock();
		$woocommerce->method( 'mailer' )
			->willReturn( $mailer );

		$subject = $this->getSubject( $sitepress, $woocommerceWpml, $woocommerce );

		$subject->sendWithoutDuplicates( $bookingId, $class );

		$this->assertEquals( 'no', $wcMailBookingCancelled->enabled );
	}

	public function dpEmailNotifications() {
		return [
			'user notification'  => [ \WC_Email_Booking_Cancelled::class, false ],
			'admin notification' => [ \WC_Email_Admin_Booking_Cancelled::class, true  ],
		];
	}

}
