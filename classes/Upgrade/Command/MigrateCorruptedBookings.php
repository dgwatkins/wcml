<?php


namespace WCML\Upgrade\Command;

class MigrateCorruptedBookings implements Command {
	/**
	 * @var \wpdb $wpdb
	 */
	private $wpdb;
	
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}
	
	/**
	 * @return bool
	 */
	public function run() {
		$offset = 0;
		
		if ( ! get_option( '_wcml_5_0_0_migrate_corrupted_booking_required' ) ) {
			add_option( '_wcml_5_0_0_migrate_corrupted_booking_required' );
		} else {
			$offset = get_option( '_wcml_5_0_0_migrate_corrupted_booking_required' );
		}
		
		$returnBookingDuplicates = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT post_id FROM {$this->wpdb->postmeta}
																					WHERE meta_key = '_booking_duplicate_of'
																					  AND meta_value IS NOT NULL
																					  LIMIT %d,20", $offset ) );
		
		if ( empty( $returnBookingDuplicates ) ) {
			delete_option( '_wcml_5_0_0_migrate_corrupted_booking_required' );
			
			return true;
		}
		
		$updateCorrectData = function ( $bookingDuplicate ) {
			$this->wpdb->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->postmeta}
													   SET meta_value = ''
													   WHERE meta_key = '_booking_product_id'
													     AND post_id = %d", $bookingDuplicate->post_id ) );
		};
		
		wpml_collect( $returnBookingDuplicates )
			->each( $updateCorrectData );
		
		update_option( '_wcml_5_0_0_migrate_corrupted_booking_required', $offset += 20 );
		
		return false;
	}
}
