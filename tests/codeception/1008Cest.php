<?php
//Check Dependencies


class EightCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if have warning dependencies messages');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ////////////////////////////
        // Check Dependecies      //
        ////////////////////////////


        $I->amGoingTo('Disable WPML');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->deactivatePlugin('wpml-media');

        $I->wait(1);
        
        $I->seeActivatePlugin('wpml-media');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('WooCommerce Multilingual');

        $I->see('Required plugins');

        $I->seeElement('.otgs-ico-warning');

        $I->see('WPML Media is either not installed or not active.');

        $I->seeLink('Get WPML Media','/wp-admin/plugin-install.php?tab=commercial#wpml');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->activatePlugin('wpml-media');

        $I->wait(1);

        $I->seeDeactivatePlugin('wpml-media');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('WooCommerce Multilingual');

        $I->dontSee('Required plugins');

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
