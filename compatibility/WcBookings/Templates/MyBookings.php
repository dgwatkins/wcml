<?php

namespace WCML\Compatibility\WcBookings\Templates;

use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class MyBookings implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'woocommerce_bookings_account_tables' )
			->then( spreadArgs( [ $this, 'displayAsTranslated' ] ) );
	}

	/**
	 * @param array[] $tables
	 *
	 * @return array[]
	 */
	public function displayAsTranslated( $tables ) {
		foreach ( $tables as $section => $sectionData ) {
			foreach ( $sectionData['bookings'] as $row => $booking ) {
				if ( ! $booking->get_product() ) {
					$originalBookingId = apply_filters( 'wpml_original_element_id', false, $booking->get_id(), 'post_' . \WCML_Bookings::POST_TYPE );
					$originalBooking = get_wc_booking( $originalBookingId );
					if ( $originalBooking ) {
						$booking->set_product_id( $originalBooking->get_product_id() );
					}
				}
			}
		}

		return $tables;
	}

}
