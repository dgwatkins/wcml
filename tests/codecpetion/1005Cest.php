<?php
//Enable and configure WCML


class FiveCest
{
    public function _before(AcceptanceTester $I)
    {
        // Login Procedure
        $I->wantTo('Further Configure WC');

        $I->amOnPage('/wp-admin/');

        $I->fillField('#user_login', 'admin');
        $I->fillField('#user_pass', '123456');

        $I->click('Log In');

        $I->see('Dashboard');

        //////////////////////
        // WCML Activation

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->click('Activate', '#woocommerce-multilingual');

        $I->wait(2);

        //////////////////////
        // WCML configuration

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->see('WooCommerce Multilingual');

        $I->click('Create missing translations');

        $I->click('Translate URLs');





    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
