<?php

namespace WCML\Email;

use WPML\Collect\Support\Collection;

class Fields {

	/**
	 * @return Collection
	 */
	public static function get() {
		$fields = [
			'additional_content',
			'heading',
			'heading_downloadable',
			'heading_full',
			'heading_paid',
			'heading_partial',
			'subject',
			'subject_downloadable',
			'subject_full',
			'subject_paid',
			'subject_partial',
		];

		/**
		 * Filter the email fields to translate.
		 *
		 * @param array $fields
		 */
		return wpml_collect( apply_filters( 'wcml_emails_text_keys_to_translate', $fields ) );
	}
}