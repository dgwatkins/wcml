<?php

namespace WCML\Rest;

use WPML\FP\Str;

class UrlHooks implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	const AFTER_URL_CONVERTER_REST_HOOKS = 20;

	/** @var \WPML_URL_Converter $urlConverter */
	private $urlConverter;

	/** @var \SitePress $sitepress */
	private $sitepress;

	public function __construct( \WPML_URL_Converter $urlConverter, \SitePress $sitepress ) {
		$this->urlConverter = $urlConverter;
		$this->sitepress    = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'rest_url', [ $this, 'handleLanguageInDirectories' ], self::AFTER_URL_CONVERTER_REST_HOOKS, 2 );
	}

	/**
	 * WCML does not support WC REST endpoints that includes the language directory.
	 * To make sure it not used programmatically, we are filtering the REST URL
	 * and we strip the language directory if needed.
	 *
	 * This also applies to default language in directory.
	 *
	 * For secondary language URL, we'll add the "lang" query var to keep the
	 * language information in the new URL.
	 *
	 * @param string $url
	 * @param string $path
	 *
	 * @return string
	 */
	public function handleLanguageInDirectories( $url, $path ) {
		if ( self::isWcRestUrl( $path ) && $this->isLanguageInDirectories() ) {
			$urlLang     = $this->urlConverter->get_language_from_url( $url );
			$defaultLang = $this->sitepress->get_default_language();
			$strippedUrl = $this->stripLangDirectory( $url, $urlLang );

			if ( $urlLang === $defaultLang ) {
				return $strippedUrl;
			} else {
				return add_query_arg( [ 'lang' => $urlLang ], $strippedUrl );
			}
		}

		return $url;
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	private static function isWcRestUrl( $path ) {
		return Str::startsWith( '/wc/v', $path );
	}

	/** @return bool */
	private function isLanguageInDirectories() {
		return (int) $this->sitepress->get_setting( 'language_negotiation_type' )
		       === constant('WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY' );
	}

	/**
	 * @param string $url
	 * @param string $urlLang
	 *
	 * @return string
	 */
	private function stripLangDirectory( $url, $urlLang ) {
		$absUrl  = rtrim( $this->urlConverter->get_abs_home(), '/' );
		$pattern = '/^(' . preg_quote( $absUrl, '/' ) . '\/)(' . $urlLang .'\/)/';
		return preg_replace( $pattern, '$1', $url, 1 );
	}
}
