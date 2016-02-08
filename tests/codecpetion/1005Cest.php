<?php
//Enable and configure WCML


class FiveCest
{
    public function _before(AcceptanceTester $I)
    {
        // Login Procedure
        $I->wp_login('admin', '123456');

        //////////////////////
        // WCML Activation  //
        //////////////////////

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->click('Activate', '#woocommerce-multilingual');

        $I->wait(2);

        ////////////////////////
        // WCML configuration//
        ///////////////////////

        $I->amGoingTo('');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->see('WooCommerce Multilingual');

        $I->click('Create missing translations');

        $I->click('Translate URLs');

        $I->click('[data-base=product]');

        $I->wait(2);

        $I->fillField('#base-translation', 'προϊόν');

        $I->click('Save');


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
