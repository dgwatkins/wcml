<?php
//Ckeck Order functionality


class TwentySevenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('to check if order functionality is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if order functionality is working correct //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('creat a new order in specific language');

        $I->amOnPage('/wp-admin/edit.php?post_type=shop_order');

        $I->see('Orders');

        $I->click('Add Order');

        $I->selectOption('#dropdown_shop_order_language','Greek');

        $I->acceptPopup();

        $I->click('.add-line-item');

        $I->wait(1);

        $I->click('.add-order-item');

        $I->fillField('.wc-backbone-modal .select2-input', 'test');

        $I->waitForElementVisible('.select2-results',10);

        $I->click('.select2-result-label');

        $I->click('Add');

        $I->wait(5);

        $I->click('Save Order');

        //$I->click('View','.view');


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
