<?php
//Further configuration of Woocommerce


class FourCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Further Configure WC');

        $I->loginAsAdmin();//Only if Run this test Seperately

        //////////////////////

        $I->amGoingTo('Configure the Selling Country');

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings');

        $I->click('#select2-chosen-2');

        $I->fillField('#s2id_autogen2_search', 'Αττική');

        $I->click('.select2-match');

        //////////////////////

        $I->amGoingTo('Configure the Default Currency');

        $I->click('#select2-chosen-7');

        $I->fillField('#s2id_autogen7_search', 'Euro');

        $I->click('.select2-result-label');

        $I->click('Save changes');

        //////////////////////


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
