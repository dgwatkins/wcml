<?php
//Installation of ST, TM, MT


class TwoCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Enable the ST, TM, MT Plugins');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////
        // Other WPML PLugin Activation  //
        //////////////////////////////////

        $I->amGoingTo('enable ST, TM, Media');

        $I->amOnPage('/wp-admin/plugins.php');

        $I->see('Plugins');

        $I->click('Activate', '#wpml-string-translation');
        $I->wait(1);
        $I->click('Activate', '#wpml-translation-management');
        $I->wait(1);
        $I->click('Activate', '#wpml-media'); //to be checked because it shows error


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
