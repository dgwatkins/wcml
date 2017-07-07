<?php

if ( ! function_exists( 'random_int') ) {
	/**
	 * @param int $min
	 * @param int $max
	 *
	 * @return int
	 */
	function random_int ( $min, $max ){
		return rand( $min, $max );
	}
}

if ( ! function_exists( 'cal_days_in_month') ) {
	/**
	 * @param int $calendar
	 * @param int $month
	 * @param int $year
	 *
	 * @return int
	 */
	function cal_days_in_month ( $calendar, $month, $year ){
		return date('t', mktime(0, 0, 0, $month, 1, $year));
	}
}

if( ! defined( 'CAL_GREGORIAN' ) ){
	define( 'CAL_GREGORIAN', 1 );
}


