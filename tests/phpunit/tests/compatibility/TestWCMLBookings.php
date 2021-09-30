<?php

class TestWCMLBookings extends OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider getDataSyncsUpdatedBookingMeta
	 * @param string $postType
	 * @param int    $bookingId
	 * @param array  $translatedBookings
	 * @param array  $expectedGetPostMeta
	 * @param array  $expectUpdatePostMeta
	 */
	public function itSyncsUpdatedBookingMeta( $postType, $bookingId, $translatedBookings, $expectedGetPostMeta, $expectUpdatePostMeta ) {
		$numberOfCalls = count( $translatedBookings );

		$wpmlPostTranslations = $this->getMockBuilder( WPML_Post_Translation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_element_translations' ] )
			->getMock();
		$wpmlPostTranslations->expects( $this->exactly( $numberOfCalls ) )
			->method( 'get_element_translations' )
			->with( $bookingId, false, false )
			->willReturn( $translatedBookings );

		WP_Mock::userFunction(
			'get_post_type',
			[
				'times'  => 2,
				'args'   => $bookingId,
				'return' => $postType,
			]
		);
		WP_Mock::userFunction(
			'maybe_unserialize',
			[
				'times'      => $numberOfCalls,
				'return_arg' => 1,
			]
		);

		foreach ( $expectedGetPostMeta as $values ) {
			list($return, $args) = $values;
			WP_Mock::userFunction(
				'get_post_meta',
				[
					'times'  => 1,
					'args'   => $args,
					'return' => $return,
				]
			);
		}

		foreach ( $expectUpdatePostMeta as $args ) {
			WP_Mock::userFunction(
				'update_post_meta',
				[
					'args' => $args,
				]
			);
		}

		$this->getSubject( null, null, null, null, null, $wpmlPostTranslations )->save_booking_action_handler( $bookingId );
	}

	/** @return array [ $postType, $bookingId, $translatedBookings, $expectedGetPostMeta, $expectUpdatePostMeta ] */
	public function getDataSyncsUpdatedBookingMeta() {
		return [
			[ 'page', 1, [], [], [] ],
			[
				'wc_booking',
				100,
				[ 'fr' => 200 ],
				[
					// [ $returns, $args ]
					[ 2, [ 100, '_booking_product_id', true ] ],
					[ 3, [ 100, '_booking_resource_id', true ] ],
					[ 'a:0:{}', [ 100, '_booking_persons', true ] ],
					[ 5, [ 100, '_booking_cost', true ] ],
					[ 20201231, [ 100, '_booking_start', true ] ],
					[ 20201229, [ 100, '_booking_end', true ] ],
					[ 1, [ 100, '_booking_all_day', true ] ],
					[ 0, [ 100, '_booking_parent_id', true ] ],
					[ 1, [ 100, '_booking_customer_id', true ] ],
				],
				[
					// [ $args ]
					[ 200, '_booking_product_id', 2 ],
					[ 200, '_booking_resource_id', 3 ],
					[ 200, '_booking_persons', [] ],
					[ 200, '_booking_cost', 5 ],
					[ 200, '_booking_start', 20201231 ],
					[ 200, '_booking_end', 20201229 ],
					[ 200, '_booking_all_day', 1 ],
					[ 200, '_booking_parent_id', 0 ],
					[ 200, '_booking_customer_id', 1 ],
				],
			],
		];
	}

	private function getSubject(
		$sitepress = null,
		$woocommerce_wpml = null,
		$woocommerce = null,
		$wpdb = null,
		$wpmlElementTranslationPackage = null,
		$wpmlPostTranslations = null
	 ) {
		if ( null === $sitepress ) {
			$sitepress = $this->getMockBuilder( SitePress::class )
				->disableOriginalConstructor()
				->setMethods( [ 'get_element_language_details' ] )
				->getMock();
			$sitepress->expects( $this->any() )
				->method( 'get_element_language_details' )
				->willReturn(
					(object) [
						'trid'                 => 1,
						'language_code'        => 'en',
						'source_language_code' => 'en',
					]
				);
		}

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->getMockBuilder( woocommerce_wpml::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( null === $woocommerce ) {
			$woocommerce = $this->getMockBuilder( woocommerce::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( null === $wpdb ) {
			$wpdb = $this->getMockBuilder( wpdb::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( null === $wpmlElementTranslationPackage ) {
			$wpmlElementTranslationPackage = $this->getMockBuilder( WPML_Element_Translation_Package::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( null === $wpmlPostTranslations ) {
			$wpmlPostTranslations = $this->getMockBuilder( WPML_Post_Translation::class )
				->disableOriginalConstructor()
				->getMock();
		}

		return new WCML_Bookings(
			$sitepress,
			$woocommerce_wpml,
			$woocommerce,
			$wpdb,
			$wpmlElementTranslationPackage,
			$wpmlPostTranslations
		);
	}
}
