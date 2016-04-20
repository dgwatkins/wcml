<?php
//Installation and configuration of WordPress


class ZeroCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Install WordPress');

        $I->amOnPage('/wp-admin/');

        $I->selectOption('#language','English (United States)');

        $I->click('Continue');

        $I->see('Welcome');

        $I->fillField('#weblog_title', 'WCML Test Site');

        $I->fillField('#user_login','admin');

        $I->fillField('#pass1-text','123456'); //possible bug. It adds only 1

        $I->fillField('#admin_email','den@exo.com');

        $I->fillField('#pass1-text','123456');

        $I->checkOption('.pw-checkbox');

        $I->checkOption('#blog_public');

        $I->click('Install WordPress');

        $I->click('Log In');

        $I->wait(1);

        $I->fillField('#user_login', 'admin');

        $I->fillField('#user_pass', '123456');

        $I->click('Log In');

        $I->see('Dashboard');

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
