<?php
//Enable and configure WCML


class FiveCest
{
    public function _before(AcceptanceTester $I)
    {

        $I->wantTo('Install and Configure WCML');

        $I->wait(3);

        // Login Procedure
        $I->wp_login('admin', '123456');

        /////////////////////////////////////
        // WCML Activation and Wizard Run //
        ////////////////////////////////////

        $I->amGoingTo('to export the db for later use');

        $I->exportDb('wcml_run');

        $I->amGoingTo('to activate WCML');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->activatePlugin('woocommerce-multilingual');

        $I->wait(2);

        $I->see('Let\'s turn your WooCommerce shop multilingual');

        $I->seeLink('I\'ll do the setup later');

        $I->click('Let\'s continue');

        $I->see('Translate Store Pages');

        $I->see('All store pages must be translated in the languages configured on the site.');

        $I->click('Continue');

        $I->see('Select Translatable Attributes');

        $I->click('Continue');

        $I->see('Enable Multiple Currencies');

        $I->see('Enable the multi-currency mode');

        $I->click('Continue');

        $I->see('Setup Complete');

        $I->click('Close setup');

        $I->seeInCurrentUrl('wp-admin/admin.php?page=wpml-wcml&tab=status&src=setup');

        $I->exportDb('wcml_finish');

        ///////////////////////////////////////////////
        // WCML Activation and Click later in Wizard //
        //////////////////////////////////////////////

        $I->amGoingTo('enable wcml and click run wizard later');

        $I->importDb('wcml_run');

        $I->wait(3);

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->activatePlugin('woocommerce-multilingual');

        $I->wait(3);

        $I->see('Let\'s turn your WooCommerce shop multilingual');

        $I->seeLink('I\'ll do the setup later');

        $I->click('I\'ll do the setup later');

        $I->seeInCurrentUrl('wp-admin/admin.php?page=wpml-wcml&src=setup_later');

        $I->seeElement('#wcml-setup-wizard');

        $I->see('Prepare your WooCommerce store to run multilingual!');

        $I->see('Start the Setup Wizard');

        $I->see('Skip');

        $I->exportDb('skip_setup');

        $I->click('Skip');

        $I->dontSeeElement('#wcml-setup-wizard');

        $I->importDb('skip_setup');

        $I->wait(3);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->seeElement('#wcml-setup-wizard');

        $I->click('Start the Setup Wizard');

        $I->wait(2);

        $I->see('Let\'s turn your WooCommerce shop multilingual');

        /////////////////////////////////////////
        // WCML Activation with other plugins //
        ////////////////////////////////////////

        $I->amGoingTo('enable wcml with other plugins');

        $I->importDb('wcml_run');

        $I->wait(3);

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->deactivatePlugin('wpml-translation-management');

        $I->checkOption('table.plugins tr[data-slug=wpml-translation-management] th.check-column input');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->checkOption('table.plugins tr[data-slug=woocommerce-multilingual] th.check-column input');

        $I->selectOption('#bulk-action-selector-bottom','Activate');

        $I->click('#doaction2');

        $I->see('Prepare your WooCommerce store to run multilingual!');

        $I->click('Start the Setup Wizard');

        $I->wait(2);

        $I->see('Let\'s turn your WooCommerce shop multilingual');

        $I->importDb('wcml_finish');

        $I->wait(3);

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {

    }
}
