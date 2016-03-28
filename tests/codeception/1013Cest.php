<?php
//Check Product Path Synchronization


class ThirteenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if product translation interface is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if Path Sync  is working correct //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check Translation Date Sync');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');



    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
