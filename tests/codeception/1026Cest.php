<?php
//Check Coupons Page


class TwentySixCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('to check if coupons are translated or not');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check coupons if are translated or not //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check coupons page in backend');

        $I->amOnPage('/wp-admin/edit.php?post_type=shop_coupon');

        $I->see('Coupons');

        $I->dontSee('#wp-admin-bar-WPML_ALS');

        $I->click('Add Coupon');

        $I->see('Add New Coupon');

        $I->fillField('#title', 'test_coupon');

        $I->fillField('#coupon_amount', '20');

        $I->click('Usage Restriction');

        $I->fillField('input#s2id_autogen6.select2-input.select2-default', 'Δοκιμ');

        $I->waitForElement('.select2-results', 10);

        $I->wait(2);

        $I->seeElement('.select2-no-results');

        $I->click('.select2-drop-mask');

        $I->fillField('input#s2id_autogen6.select2-input.select2-default', 'Test Product');

        $I->waitForElement('.select2-result-label', 10);

        $I->click('.select2-match');

        $I->click('#exclude_sale_items');

        $I->click('Publish');

        $I->wait(2);

        //To Be Continued



    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
