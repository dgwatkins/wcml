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

        $I->activatePlugin('woocommerce-multilingual');

        $I->wait(2);

        ////////////////////////
        // WCML configuration//
        ///////////////////////

        $I->amGoingTo('');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->see('WooCommerce Multilingual');

        $I->click('Create missing translations');

        // This is the code for translating URL's if they are
        /*$I->click('Translate URLs');

        $I->click('[data-base=product]');

        $I->wait(2);

        $I->fillField('#base-translation', 'προϊόν');

        $I->click('Save');

        $I->wait(2);*/

        $I->seeElement('.wcml-tax-translation-list .otgs-ico-ok');

        $I->wait(2);


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {

    }
}
