<?php

namespace WCML\API\VendorAddon;

use WPML\API\MakeMock;
use WPML\Element\API\LanguagesMock;
use WPML\LIB\WP\OnActionMock;
use WPML\LIB\WP\WPDBMock;

/**
 * @group api/vendor-addon
 */
class TestHooks extends \OTGS_TestCase {

	use LanguagesMock;
	use MakeMock;
	use OnActionMock;
	use WPDBMock;

	const DEFAULT_LANG   = 'fr';
	const SECONDARY_LANG = 'de';

	const USER_ID = 44;
	const VENDOR_CAPABILITY = 'the_seller';

	public function setUp() {
		global $sitepress;
		$sitepress = null;

		parent::setUp();
		$this->setupLanguagesMock();
		$this->setUpMakeMock();
		$this->setUpOnAction();
		$this->setUpWPDBMock();
		$this->setDefaultLanguage( self::DEFAULT_LANG );
		$this->setActiveLanguages( [ self::SECONDARY_LANG ] );
	}

	public function tearDown() {
		$this->tearDownOnAction();
		$this->tearDownLanguagesMock();
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpCanTranslate
	 *
	 * @param bool $canTranslate
	 */
	public function itShouldNotFilterTranslatorPermissionIfNotVendor( $canTranslate ) {
		$postId = 123;
		$args   = [ 'post_id' => $postId ];

		$this->mockConfig();
		$this->mockUser( false, $canTranslate );

		( new Hooks() )->add_hooks();

		$this->assertSame(
			$canTranslate,
			$this->runFilter( 'wpml_override_is_translator', $canTranslate, self::USER_ID, $args )
		);
	}

	public function dpCanTranslate() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @test
	 */
	public function itShouldAllowVendorToTranslateHisProduct() {
		$productId = 123;
		$args      = [ 'post_id' => $productId ];
		$product   = (object) [ 'post_author' => (string) self::USER_ID ];

		$this->mockConfig();
		$this->mockUser( true, true, self::getAllLangPairs() );

		\WP_Mock::userFunction( 'get_post' )
			->with( $productId )
			->andReturn( $product );

		( new Hooks() )->add_hooks();

		$this->assertTrue(
			$this->runFilter( 'wpml_override_is_translator', false, self::USER_ID, $args )
		);
	}

	/**
	 * @test
	 */
	public function itShouldNotAllowVendorToTranslateProductFromAnotherVendor() {
		$productId = 123;
		$args      = [ 'post_id' => $productId ];
		$product   = (object) [ 'post_author' => '87' ]; // another product author

		$this->mockConfig();
		$this->mockUser( true, true, self::getAllLangPairs() );

		\WP_Mock::userFunction( 'get_post' )
			->with( $productId )
			->andReturn( $product );

		( new Hooks() )->add_hooks();

		$this->assertFalse(
			$this->runFilter( 'wpml_override_is_translator', false, self::USER_ID, $args )
		);
	}

	/**
	 * @test
	 * @dataProvider dpMissingTranslatorRequirements
	 *
	 * @param bool  $canTranslate
	 * @param array $langPairs
	 */
	public function itShouldSetTranslatorRequirementsAndAllowVendorToTranslateHisProduct( $canTranslate, $langPairs ) {
		$productId = 123;
		$args      = [ 'post_id' => $productId ];
		$product   = (object) [ 'post_author' => (string) self::USER_ID ];

		$this->mockConfig();
		$user = $this->mockUser( true, $canTranslate, $langPairs, true );

		\WP_Mock::expectAction( 'wpml_tm_ate_synchronize_translators' );
		\WP_Mock::expectAction( 'wpml_tm_add_translation_role', $user, Hooks::TRANSLATE_CAPABILITY );

		\WP_Mock::userFunction( 'get_post' )
			->with( $productId )
			->andReturn( $product );

		( new Hooks() )->add_hooks();

		$this->assertTrue(
			$this->runFilter( 'wpml_override_is_translator', false, self::USER_ID, $args )
		);
	}

	public function dpMissingTranslatorRequirements() {
		return [
			'can translate but has outdated lang pairs' => [
				true,
				[ 'some outdated lang pairs' ],
			],
			'has correct lang pairs but cannot translate' => [
				false,
				self::getAllLangPairs(),
			],
		];
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterUserMeta() {
		$value = 'The user meta value';

		$this->mockConfig();

		( new Hooks() )->add_hooks();

		$this->assertSame(
			$value,
			$this->runFilter( 'get_user_metadata', $value, self::USER_ID, 'some_key' )
		);
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterLanguageColumnDisplayIfNotVendor() {
		$value = 'The user meta value';

		$this->mockConfig();
		$this->mockUser( false );

		( new Hooks() )->add_hooks();

		$this->assertSame(
			$value,
			$this->runFilter( 'get_user_metadata', $value, self::USER_ID, Hooks::COLUMN_USER_OPTION )
		);
	}

	/**
	 * @test
	 */
	public function itShouldForceLanguageColumnDisplayIfVendor() {
		$this->mockConfig();
		$this->mockUser( true );

		( new Hooks() )->add_hooks();

		$this->assertEquals(
			[ [] ],
			$this->runFilter( 'get_user_metadata', 'The user meta value', self::USER_ID, Hooks::COLUMN_USER_OPTION )
		);
	}

	private function mockConfig() {
		\WP_Mock::onFilter( 'wcml_vendor_addon_configuration' )
		        ->with( null )
		        ->reply( [ 'vendor_capability' => self::VENDOR_CAPABILITY ] );
	}

	private function mockUser( $isVendor, $canTranslate = null, $langPairs = null, $expectUserUpdate = false ) {
		global $wpdb;

		$metaKey = $wpdb->prefix . 'language_pairs';

		$user = \Mockery::mock( \WPML_User::class );
		$user->shouldReceive( 'has_cap' )
		     ->with( self::VENDOR_CAPABILITY )
		     ->andReturn( $isVendor );
		$user->shouldReceive( 'has_cap' )
		     ->with( Hooks::TRANSLATE_CAPABILITY )
		     ->andReturn( $canTranslate );
		$user->shouldReceive( 'get' )
		     ->with( $metaKey )
		     ->andReturn( $langPairs );

		if ( $expectUserUpdate ) {
			$user->shouldReceive( 'add_cap' )
				->times( 1 )
				->with( Hooks::TRANSLATE_CAPABILITY );

			$user->shouldReceive( 'update_meta' )
				->times( 1 )
				->with( $metaKey, self::getAllLangPairs() );
		}

		$userFactory = $this->mockMake( \WPML_WP_User_Factory::class );
		$userFactory->shouldReceive( 'create' )
			->with( self::USER_ID )
			->andReturn( $user );

		return $user;
	}

	private static function getAllLangPairs() {
		return [ self::DEFAULT_LANG => [ self::SECONDARY_LANG => 1 ] ];
	}
}
