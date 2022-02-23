<?php


namespace WCML\Upgrade\Command;


class MigrateCorruptedBookings implements UpgradeCommand {
	
	public function run() {
		global $wpdb;
		
		if ( ! get_option( '_wcml_5_0_0_migrate_corrupted_booking_required' ) ) {
			add_option( '_wcml_5_0_0_migrate_corrupted_booking_required' );
		}
		
		$returnBookingDuplicates = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_duplicate_of' AND meta_value IS NOT NULL" ) );
		
		$updateCorrectData = function ( $bookingDuplicate, $wpdb ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = '' WHERE meta_key = '_booking_product_id' AND post_id = %d", $bookingDuplicate->post_id ) );
		};
		
		wpml_collect( $returnBookingDuplicates )
			->each( $updateCorrectData );
		
		delete_option( '_wcml_5_0_0_migrate_corrupted_booking_required' );
	}
}