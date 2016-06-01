<?php
//Check if columns are correct in Product Tab


class EightteenCest
{
    public function _before(AcceptanceTester $I)
    {

        $I->wantTo('Check if columns are correct in Product Tab');

        // Login Procedure
        $I->wp_login('admin', '123456');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');
        
        $I->see('WooCommerce Multilingual');

        // Begin Checking Columns
        $I->amGoingTo('Check the Columns');
        
        $I->seeElement('thead tr  th.column-thumb .wc-image');
        
        $I->seeElement('thead tr th.wpml-col-title ');
        
        $I->see('Product','thead tr th.wpml-col-title span');

        $I->click('thead tr th.wpml-col-title a span');

        $I->seeElement('thead tr th.wpml-col-title a span.sorting-indicator');

        $I->seeElement('thead tr th.wpml-col-languages');

        $I->see('Categories','thead tr th.column-categories');

        $I->seeElement('thead tr th.column-product_type');

        $I->see('Date','thead tr th.column-date');

        $I->click('thead tr th.column-date a span');

        $I->seeElement('thead tr th.column-date a span.sorting-indicator');

        //Begin Checking Pagination
        
        $I->amGoingTo('create some dummy products for checking pagination');

        $I->simpleProducts('Test Product Pame', 2);

        $I->amGoingTo('check if pagination is correct');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
