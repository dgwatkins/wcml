<?php
//Check Dependencies


class EightCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if have any warning messages and status');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ////////////////////////////
        // Check Dependecies      //
        ////////////////////////////


        $I->amGoingTo('Disable WPML');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->click('Deactivate', '#wpml-media');

        $I->wait(1);

        $I->see('Activate', '#wpml-media');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('WooCommerce Multilingual');

        $I->see('Required plugins');

        $I->seeElement('.otgs-ico-warning');

        $I->see('WPML Media is either not installed or not active.');

        $I->seeLink('Get WPML Media','/wp-admin/plugin-install.php?tab=commercial#wpml');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->click('Activate', '#wpml-media');

        $I->wait(1);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('WooCommerce Multilingual');

        $I->dontsee('Required plugins');

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
