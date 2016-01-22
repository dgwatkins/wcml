<?php
//Installation and configuration of Woocommerce


class ThreeCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Install and Configure WC');

        $I->loginAsAdmin();//Only if Run this test Seperately

        $I->amGoingTo('Activate Woocommerce Plugin');
        $I->amOnPluginsPage(); //In order that the activatePlugin() works, the webdriver needs to be in Plugins page

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
        $I->checkOption('#woocommerce_enable_cod');
        $I->click("Continue");

        $I->waitForElement(".button-secondary", 15);
        $I->click("No thanks");
        $I->wait(1);
        $I->click("Return to the WordPress Dashboard");


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
