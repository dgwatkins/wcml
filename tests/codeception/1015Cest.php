<?php
//Check Custom Prices in Secondary Languages

/**
 * @group currency
 */


class FifteenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if option for custom prices in secondary currencies is working ok');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check custom prices in secondary currencies //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check custom prices in secondary currencies');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->see('WooCommerce Multilingual');

        // Enable and Disable Custom Prices Option

        $I->seeElement('#display_custom_prices');

        $I->checkOption('#display_custom_prices');

        $I->click('Save changes');

        $I->amOnPage('/shop/');

        $I->dontSee('Test Product','ul.products a h3');

        $I->amOnPage('/wp-admin/post.php?post=12&action=edit&lang=en');

        $I->executeJS('window.scrollTo(0,500);');

        $I->click('input[id^="wcml_custom_prices_manually"]');

        $I->waitForElement('.wcml_custom_prices_manually_block');

        $I->fillField('#_custom_regular_price[USD]','21');

        $I->fillField('#_custom_regular_price[AED]','51');

        $I->click('#publish');

        $I->waitForElement('div#message.updated.notice.notice-success.is-dismissible','10');

        $I->amOnPage('/shop/');

        $I->See('Test Product','ul.products a h3');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->see('WooCommerce Multilingual');

        $I->seeElement('#display_custom_prices');

        $I->uncheckOption('#display_custom_prices');

        $I->click('Save changes');
        
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
