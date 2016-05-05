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

        $I->activatePlugin('wpml-string-translation');

        $I->seeDeactivatePlugin('wpml-string-translation');
        
        $I->wait(1);
        
        $I->activatePlugin('wpml-translation-management');

        $I->seeDeactivatePlugin('wpml-translation-management');
        
        $I->wait(1);
        
        $I->activatePlugin('wpml-media');
        
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
