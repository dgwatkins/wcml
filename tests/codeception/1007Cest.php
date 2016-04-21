<?php
//WP Permalink Test with String Context Changes


class SevenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if string context is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ////////////////////////////
        // Permalink Tests        //
        ////////////////////////////

        $I->amGoingTo('Disable WCML');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->deactivatePlugin('woocommerce-multilingual');

        $I->wait(1);

        $I->amGoingTo('add 2 more languages in WPML');

        $I->amOnPage('/wp-admin/admin.php?page=sitepress-multilingual-cms%2Fmenu%2Flanguages.php');

        $I->see('Setup WPML');

        $I->amGoingTo('add French');

        $I->click('Add / Remove languages');

        $I->click('.available-languages input[value="fr"]');

        $I->wait(2);

        $I->executeJS('window.scrollTo(0,500);');

        $I->wait(2);

        $I->click('#icl_save_language_selection');

        $I->executeJS('window.scrollTo(0,0);');

        $I->wait(10);

        $I->expect('French added in WPML');

        $I->seeInPageSource('<label for="default_language_fr">French </label>');

        $I->amGoingTo('re-activate WCML');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->activatePlugin('woocommerce-multilingual');

        $I->wait(1);

        $I->amOnPage('wp-admin/admin.php?page=wpml-wcml&tab=slugs');

        $I->expect('French added in WCML');

        $I->seeInPageSource('<span title="French">');

        $I->seeInPageSource('<span title="Greek">');

        $I->wait(1);

        $I->amOnPage('/wp-admin/options-permalink.php');

        $I->seeInPageSource('placeholder="product-category"');

        $I->seeInPageSource('placeholder="product-tag"');

        $I->amOnPage('wp-admin/admin.php?page=wpml-wcml&tab=slugs');

        $I->expect('French added in WCML');

        $I->seeInPageSource('<span title="French">');

        $I->wait(1);

        $I->amGoingTo('remove French and Zulu languages in WPML');

        $I->amOnPage('/wp-admin/admin.php?page=sitepress-multilingual-cms%2Fmenu%2Flanguages.php');

        $I->see('Setup WPML');

        $I->amGoingTo('remove French');

        $I->click('Add / Remove languages');

        $I->click('.available-languages input[value="fr"]');

        $I->wait(2);

        $I->executeJS('window.scrollTo(0,500);');

        $I->wait(2);

        $I->click('#icl_save_language_selection');

        $I->executeJS('window.scrollTo(0,0);');

        $I->wait(10);

        $I->expect('French removed in WPML');

        $I->wait(1);

        $I->amOnPage('/wp-admin/admin.php?page=sitepress-multilingual-cms%2Fmenu%2Flanguages.php');

        $I->see('Setup WPML');

        $I->seeInPageSource('<label for="default_language_en">English (default)</label>');

        $I->seeInPageSource('<label for="default_language_el">Greek </label>');

        $I->dontSeeInPageSource('<label for="default_language_fr">French </label>');

        $I->wait(1);


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
