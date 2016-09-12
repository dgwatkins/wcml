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

        $I->activatePlugin('wpml-multilingual-cms');

        $I->seeDeactivatePlugin('wpml-multilingual-cms');

        $I->amGoingTo('Configure WPML');

        $I->click('Configure WPML');

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

        ////////////////////////////////////////////
        // Add WPML Widget and remove all others  //
        ////////////////////////////////////////////

        $I->amGoingTo('Disable widgets and add wpml lang selector');

        $I->amOnPage('/wp-admin/widgets.php');

        $I->click('#sidebar-1 [id*="search"]');

        $I->wait(1);

        $I->click('#sidebar-1 [id*="search"] .widget-control-remove');

        $I->waitForElementNotVisible('#sidebar-1 [id*="search"]', 10);

        $I->click('#sidebar-1 [id*="recent-posts"]');

        $I->wait(1);

        $I->click('#sidebar-1 [id*="recent-posts"] .widget-control-remove');

        $I->waitForElementNotVisible('#sidebar-1 [id*="recent-posts"]', 10);

        $I->click('#sidebar-1 [id*="recent-comments"]');

        $I->wait(1);

        $I->click('#sidebar-1 [id*="recent-comments"] .widget-control-remove');

        $I->waitForElementNotVisible('#sidebar-1 [id*="recent-comments"]', 10);

        $I->click('#sidebar-1 [id*="archives"]');

        $I->wait(1);

        $I->click('#sidebar-1 [id*="archives"] .widget-control-remove');

        $I->waitForElementNotVisible('#sidebar-1 [id*="archives"]', 10);

        $I->click('#sidebar-1 [id*="categories"]');

        $I->wait(1);

        $I->click('#sidebar-1 [id*="categories"] .widget-control-remove');

        $I->waitForElementNotVisible('#sidebar-1 [id*="categories"]', 10);

        $I->click('#sidebar-1 [id*="meta"]');

        $I->wait(1);

        $I->click('#sidebar-1 [id*="meta"] .widget-control-remove');

        $I->waitForElementNotVisible('#sidebar-1 [id*="meta"]', 10);

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
