<?php
//Enable and configure WCML


class FiveCest
{
    public function _before(AcceptanceTester $I)
    {

        $I->wantTo('Install and Configure WCML');

        // Login Procedure
        $I->wp_login('admin', '123456');

        //////////////////////
        // WCML Activation  //
        //////////////////////

        $I->amGoingTo('to activate WCML');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->activatePlugin('woocommerce-multilingual');

        $I->wait(2);

        ////////////////////////
        // WCML configuration//
        ///////////////////////

        $I->amGoingTo('run the instllation wizard of WCML');

        $I->click('Run the Setup Wizard');

        $I->see('Welcome to WooCommerce Multilingual!');

        $I->click('Start');

        $I->see('Translate Store Pages');

        $I->checkOption('.wcml-status-list li label input[name="create_pages"]');

        $I->click('Continue');

        $I->see('Select translatable attributes');

        $I->click('Continue');

        $I->see('Enable multiple currencies');

        $I->click('Continue');

        $I->see('Select the translation interface');

        $I->checkOption('.wcml-setup-content ul.no-bullets li label input[name="translation_interface"]');

        $I->click('Continue');

        $I->see('Ready!');

        $I->click('Start translating products');

        $I->seeInCurrentUrl('wp-admin/admin.php?page=wpml-wcml&tab=products&src=setup');

        $I->see('WooCommerce Multilingual');

        $I->wait(2);


        //Old code without installation wizard
        /*$I->amGoingTo('configure WCML');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->see('WooCommerce Multilingual');

        $I->click('Create missing translations');

        // This is the code for translating URL's if they are
        I->click('Translate URLs');

        $I->click('[data-base=product]');

        $I->wait(2);

        $I->fillField('#base-translation', 'προϊόν');

        $I->click('Save');

        $I->wait(2);

        $I->seeElement('.wcml-tax-translation-list .otgs-ico-ok');

        $I->wait(2);*/


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {

    }
}
