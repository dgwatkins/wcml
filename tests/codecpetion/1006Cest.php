<?php
//Enable and configure WCML


class SixCest
{
    public function _before(AcceptanceTester $I)
    {
        // Login Procedure
        $I->wantTo('Further Configure WC');

        $I->amOnPage('/wp-admin/');

        $I->fillField('#user_login', 'admin');
        $I->fillField('#user_pass', '123456');

        $I->click('Log In');

        $I->see('Dashboard');

        //////////////////////
        // Create Product

        $I->amOnPage('/wp-admin/post-new.php?post_type=product');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Test Product');

        $I->fillField('.mce-edit-arear', 'Test Product');

        $I->fillField('_regular_price', '10');

        $I->click('#publish');

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
