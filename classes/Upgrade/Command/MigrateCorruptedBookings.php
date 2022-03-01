<?php


namespace WCML\Upgrade\Command;

class MigrateCorruptedBookings implements Command {
	/**
	 * @var \wpdb $wpdb
	 */
	private $wpdb;
	
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}
	
	/**
	 * @return bool
	 */
	public function run() {
		
		$this->wpdb->query(
			"UPDATE {$this->wpdb->postmeta} p
 INNER JOIN {$this->wpdb->postmeta} b
 ON p.post_id = b.post_id AND p.meta_key = '_booking_product_id'
   SET p.meta_value = ''
   WHERE b.meta_key = '_booking_duplicate_of' AND b.meta_value IS NOT NULL"
		);
		
		return true;
	}
}
