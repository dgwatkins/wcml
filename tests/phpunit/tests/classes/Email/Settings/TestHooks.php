<?php

namespace WCML\Email\Settings;

/**
 * This code was refactored and we will limit test to basic coverage
 * since it's also covered with a CC test.
 *
 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/WPMLCC-1047
 *
 * @group email
 * @group email-settings
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dpShouldNotAddHooks
	 *
	 * @param bool   $isAdmin
	 * @param string $pagenow
	 * @param mixed  $_get
	 */
	public function itShouldNotAddHooks( $isAdmin, $pagenow, $_get ) {
		$GLOBALS['pagenow'] = $pagenow;
		$_GET               = $_get;

		\WP_Mock::userFunction( 'is_admin', [ 'return' => $isAdmin ] );

		$subject = $this->getSubject();

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ $subject, 'showLanguageLinksForWcEmails' ] );

		$subject->add_hooks();

		unset( $GLOBALS['pagenow'], $_GET );
	}

	public function dpShouldNotAddHooks() {
		return [
			'not admin' => [
				false,
				'admin.php',
				[ 'page' => 'wc-settings', 'tab' => 'email' ],
			],
			'not admin.php' => [
				true,
				'post.php',
				[ 'page' => 'wc-settings', 'tab' => 'email' ],
			],
			'not WC Settings' => [
				true,
				'admin.php',
				[ 'tab' => 'email' ],
			],
			'not email tab' => [
				true,
				'admin.php',
				[ 'page' => 'wc-settings' ],
			],
			'no GET var' => [
				true,
				'admin.php',
				[],
			],
		];
	}

	/**
	 * @test
	 */
	public function itShouldAddHooksAndSetEmailsStringLanguage() {
		$optionStringValue = 'The email subject from option';
		$emailType         = 'woocommerce_customer_on_hold_order_settings';
		$emailElement      = 'subject';
		$key               = 'wcml_lang-' . $emailType . '-' . $emailElement;
		$langFromOption    = 'de';
		$langFromPost      = 'fr';
		$domain            = 'admin_texts_' . $emailType;
		$name              = '[' . $emailType . ']' . $emailElement;
		$emailInputKey     = str_replace( '_settings', '', $emailType ) . '_' . $emailElement;
		$stringValue       = 'The email subject from POST';

		$GLOBALS['pagenow'] = 'admin.php';
		$_GET               = [ 'page' => 'wc-settings', 'tab' => 'email' ];
		$_POST              = [
			$key           => $langFromPost,
			$emailInputKey => $stringValue,
		];

		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ $emailType, true ],
			'return' => [
				'subject' => $optionStringValue,
			],
		] );

		\WP_Mock::onFilter( 'wpml_register_single_string' )
			->with( $domain, $name, $stringValue, false );

		$wcmlStrings = $this->getWcmlStrings();
		$wcmlStrings->method( 'get_string_language' )
			->with( $optionStringValue, $domain )
			->willReturn( $langFromOption );
		$wcmlStrings->expects( $this->once() )
			->method( 'set_string_language' )
			->with( $stringValue, $domain, $name, $langFromPost );

		$subject = $this->getSubject( null, $wcmlStrings );

		\WP_Mock::expectActionAdded( 'admin_footer', [ $subject, 'showLanguageLinksForWcEmails' ] );

		$subject->add_hooks();

		unset( $GLOBALS['pagenow'], $_GET, $_POST );
	}

	/**
	 * @test
	 */
	public function itShouldShowLanguageLinksForWcEmails() {
		$stringValue      = 'The email subject';
		$defaultLang      = 'fr';
		$section          = 'wc_email_customer_on_hold_order';
		$emailOptionName  = 'woocommerce_customer_on_hold_order_settings';
		$emailOptionValue = [
			'subject' => $stringValue,
		];
		$domain            = 'admin_texts_' . $emailOptionName;
		$name              = '[' . $emailOptionName . ']subject';

		$_GET = [
			'page'    => 'wc-settings',
			'tab'     => 'email',
			'section' => $section,
		];

		\WP_Mock::userFunction( 'apply_filters', [
			'return_arg' => 1,
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ $emailOptionName ],
			'return' => $emailOptionValue,
		] );

		\WP_Mock::userFunction( 'admin_url', [
			'times'  => 1,
			'args'   => [ 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=admin_texts_' . $emailOptionName . '&search=' . $stringValue ],
			'return' => 'http://path/to/string-translation',
		] );

		\Mockery::mock( 'overload:WPML_Simple_Language_Selector' )
		        ->shouldReceive( 'render' )
				->with(
					[
						'id'                 => $emailOptionName . '_subject_language_selector',
						'name'               => 'wcml_lang-' . $emailOptionName . '-subject',
						'selected'           => $defaultLang,
						'show_please_select' => false,
						'echo'               => true,
						'style'              => 'width: 18%;float: left',
					]
				);

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_default_language' )->willReturn( $defaultLang );

		$wcmlStrings = $this->getWcmlStrings();
		$wcmlStrings->expects( $this->once() )
			->method( 'get_string_language' )
			->with( $stringValue, $domain, $name );

		$subject = $this->getSubject( $sitepress, $wcmlStrings );

		ob_start();
		$subject->showLanguageLinksForWcEmails();
		$output = ob_get_clean();

		$this->assertRegExp( '#woocommerce_customer_on_hold_order_subject#', $output );
	}

	private function getSubject( $sitepress = null, $wcmlStrings = null ) {
		$sitepress   = $sitepress ?: $this->getSitepress();
		$wcmlStrings = $wcmlStrings ?: $this->getWcmlStrings();

		return new Hooks( $sitepress, $wcmlStrings );
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
			->setMethods(
				[
					'get_default_language',
				]
			)->disableOriginalConstructor()->getMock();
	}

	private function getWcmlStrings() {
		return $this->getMockBuilder( \WCML_WC_Strings::class )
			->setMethods(
				[
					'get_string_language',
					'set_string_language',
				]
			)->disableOriginalConstructor()->getMock();
	}
}
