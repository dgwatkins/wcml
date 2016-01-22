<?php
//Installation and configuration of WPML


class OneCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Install WPML');

        $I->loginAsAdmin();

        $I->amGoingTo('Activate WPML Plugins');
        $I->amOnPluginsPage(); //In order that the activatePlugin() works, the webdriver needs to be in Plugins page

        $I->activatePlugin('wpml-multilingual-cms');

        $I->amGoingTo('Configure WPML');
        $I->click('No thanks, I will configure myself');
        $I->click('Next'); //Let's English as Default language

        $I->checkOption('Greek');
        $I->wait(1);
        $I->click('Next');

        $I->wait(5);
        $I->checkOption('form input[name=icl_lang_sel_footer]');
        $I->waitForElement(".button-primary", 15);
        $I->wait(1);
        $I->click('Next');

        $I->wait(3);
        $I->waitForElement(".button-primary", 15);
        $I->click('Remind me later');

        $I->wait(5);
        $I->click('Finish');
		
    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
