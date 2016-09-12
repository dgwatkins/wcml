<?php
//Installation and configuration of Woocommerce


class ThreeCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Install and Configure WC');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////
        // Woocommerce Installation      //
        //////////////////////////////////

        $I->amGoingTo('Activate Woocommerce Plugin');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->activatePlugin('woocommerce');
        
        $I->amGoingTo('Run Woocommerce Setup');

        $I->waitForElement(".button-primary", 20);

        $I->click("Let's Go!");

        $I->waitForElement(".button-primary", 15);
        
        $I->click("Continue");

        $I->wait(1);

        $I->click("Continue");

        $I->wait(1);
        
        $I->click("Continue");

        $I->wait(1);
        
        $I->checkOption('.wc-wizard-gateway-bacs');
        
        $I->click("Continue");

        $I->waitForElement(".button-secondary", 15);
        
        $I->click("No thanks");
        
        $I->wait(1);
        
        $I->click("Return to the WordPress Dashboard");

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
