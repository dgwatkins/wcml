<?php
//Check Custom options in Multi Currency


class SixteenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if currency switcher options are working ok');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check currency switcher options are working ok //
        ///////////////////////////////////////////////////////////////

        //$I->amGoingTo('Check currency switcher options are working ok');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->see('WooCommerce Multilingual');

        $I->executeJS('window.scrollTo(0,500);');

        // Check Drop-down menu style

        $I->amGoingTo('check if the drop-down style meu is working ok');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option#wcml_currency_switcher-EUR');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option#wcml_currency_switcher-USD');

        $I->wait(1);

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option#wcml_currency_switcher-AED');

        $I->dragAndDrop('.wcml_currencies_order_EUR','#wcml_currencies_order:nth-child(2)');

        $I->wait(2);

        $I->waitForElement('#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher',10);

        $I->seeOptionIsSelected('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher','US Dollars ($) - USD');

        $I->dragAndDrop('.wcml_currencies_order_USD','#wcml_currencies_order:nth-child(2)');

        $I->wait(2);

        $I->dragAndDrop('.wcml_currencies_order_AED','#wcml_currencies_order:nth-child(2)');

        $I->wait(2);

        $I->dragAndDrop('.wcml_currencies_order_EUR','.wcml_currencies_order_USD');

        $I->wait(2);

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->seeElement('div.product_meta select.wcml_currency_switcher option#wcml_currency_switcher-USD');

        $I->seeElement('div.product_meta select.wcml_currency_switcher option#wcml_currency_switcher-AED');

        $I->seeElement('div.product_meta select.wcml_currency_switcher option#wcml_currency_switcher-EUR');

        // Check list of currencies menu style - Vertical

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->amGoingTo('check list of currencies menu vertical style');

        $I->selectOption('div.wcml-section-content-inner ul.wcml_curr_style li label input[value=list]','List of currencies');

        $I->waitForElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_vertical',10);

        $I->wait(2);

        $I->waitForElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_vertical',10);

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_vertical li[rel=EUR]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_vertical li[rel=USD]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_vertical li[rel=AED]');

        $I->see('US Dollars ($) - USD','ul.wcml_currency_switcher.curr_list_vertical .wcml-active-currency');

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_vertical li[rel=EUR]');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_vertical li[rel=USD]');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_vertical li[rel=AED]');

        // Check list of currencies menu style - Horizontal

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->amGoingTo('check list of currencies menu horizontal style');

        $I->selectOption('.wcml_curr_style select[name=wcml_curr_sel_orientation]', 'Horizontal');

        $I->waitForElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal',10);

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=USD]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=AED]');

        $I->see('US Dollars ($) - USD','ul.wcml_currency_switcher.curr_list_horizontal .wcml-active-currency');

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=USD]');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=AED]');

        // Check different template parameters for currencies

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->amGoingTo('check different template parameters for currencies');

        $I->fillField('div.wcml-section-content-inner input[name=wcml_curr_template]','%code% - %symbol% - %name%');

        $I->click('Save changes');

        $I->executeJS('window.scrollTo(0,500);');

        $I->see('EUR - € - Euros','.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

        $I->amOnPage('product/test-product/');

        $I->see('EUR - € - Euros', 'div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

        // Change switcher to default

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->amGoingTo('change switcher to drop down');

        $I->selectOption('div.wcml-section-content-inner ul.wcml_curr_style li label input[value=dropdown]','Drop-down menu');

        $I->waitForElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher',10);

        $I->wait(2);

        // Check visibility of currency switcher

        $I->amGoingTo('check visibility of currency switcher');

        $I->uncheckOption('.wcml_curr_visibility input[name=currency_switcher_product_visibility]');

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->dontSeeElement('div.product_meta select.wcml_currency_switcher option#wcml_currency_switcher-USD');

        $I->dontseeElement('div.product_meta select.wcml_currency_switcher option#wcml_currency_switcher-AED');

        $I->dontseeElement('div.product_meta select.wcml_currency_switcher option#wcml_currency_switcher-EUR');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->checkOption('.wcml_curr_visibility input[name=currency_switcher_product_visibility]');

        $I->click('Save changes');
    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
