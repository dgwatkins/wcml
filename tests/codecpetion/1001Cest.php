<?php
//Installation and configuration of WPML


class OneCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Install WPML');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ////////////////////////
        // WPML Installation  //
        ///////////////////////

        $I->amGoingTo('Activate WPML Plugins');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->click('Activate', '#wpml-multilingual-cms');


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
