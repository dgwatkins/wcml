<?php


namespace WCML\Upgrade\Command;

class MigrateCorruptedBookings implements Command {
	
	/**
	 * @var wpdb
	 */
	private $wpdb;
	
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}
	
	/**
	 * @return bool
	 */
	public function run() {
		$offset = 0;
		
		while ( true ) {
			$returnBookingDuplicates = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = '_booking_duplicate_of' AND meta_value IS NOT NULL LIMIT %d,20", $offset ) );
			
			if ( empty( $returnBookingDuplicates ) ) {
				break;
			}
			
			$updateCorrectData = function ( $bookingDuplicate ) {
				$this->wpdb->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->postmeta} SET meta_value = '' WHERE meta_key = '_booking_product_id' AND post_id = %d", $bookingDuplicate->post_id ) );
			};
			
			wpml_collect( $returnBookingDuplicates )
				->each( $updateCorrectData );
			
			$offset += 20;
		}
		
		return true;
	}
}