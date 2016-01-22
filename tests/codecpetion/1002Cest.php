<?php
//Installation of ST, TM, MT


class TwoCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Enable the ST, TM, MT Plugins');

        $I->loginAsAdmin();//Only if Run this test Seperately

        $I->amGoingTo('ST, TM, Media');
        $I->amOnPluginsPage(); //In order that the activatePlugin() works, the webdriver needs to be in Plugins page

        $I->activatePlugin('wpml-string-translation');
        $I->wait(1);
        $I->activatePlugin('wpml-translation-management');
        $I->wait(1);
        $I->activatePlugin('wpml-media'); //to be checked because it shows error


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
