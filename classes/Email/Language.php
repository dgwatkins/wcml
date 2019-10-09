<?php

namespace WCML\Email;

use SitePress;

class Language {

	/** @var SitePress */
	private $sitepress;

	/** @var string|null */
	private $locale;

	/** @var string|null */
	private $admin;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function change( $lang ) {
		$this->getAdminLang();
		$this->sitepress->switch_lang( $lang, true );
		$this->locale = $this->sitepress->get_locale( $lang );
	}

	/**
	 * @return string|null
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * @return string|null|bool
	 */
	public function getAdminLang() {
		if ( ! $this->admin ) {
			$this->admin = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
		}

		return $this->admin;
	}

	/**
	 * @param int $userId
	 *
	 * @return string|null|bool
	 */
	public function getUserLang( $userId ) {
		return $this->sitepress->get_user_admin_language( $userId, true );
	}
}
