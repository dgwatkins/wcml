<?php
//Create a Simple Product


class SixCest
{
    public function _before(AcceptanceTester $I)
    {

        $I->wantTo('Create a product');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ////////////////////////////
        // Create  Simple Product //
        ////////////////////////////

        $I->amGoingTo('Create a Simple WC Product');

        $I->amOnPage('/wp-admin/post-new.php?post_type=product');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Test Product');

        $I->click('#content-html');

        $I->fillField('#content', 'Test Product');

        $I->fillField('_regular_price', '10');

        $I->click('#publish');

        $I->expect('submitted product was added to a list');

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
