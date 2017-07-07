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