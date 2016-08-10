<?php
//Check Custom options in Multi Currency

/**
 * @group currency
 */


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

        $I->amGoingTo('Check currency switcher options are working ok');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->see('WooCommerce Multilingual');

        $I->executeJS('window.scrollTo(0,500);');

        // Check real time change of the cureency preview

        $I->amGoingTo('Check if the reaatime preview is working well');

        $I->amGoingTo('Add Bitcoin Currency');

        $I->click('.wcml_add_currency');

        $I->see('Add new currency', '.ui-dialog-title');

        $I->pressKey('.ui-dialog #wcml_currency_options_code_','Bitcoin',WebDriverKeys::ENTER);

        $I->click('.ui-dialog .currency_options_save');

        $I->waitForElementNotVisible('.ui-dialog','10');

        $I->wait(3);

        $I->seeElement('.currency_table tbody tr#currency_row_BTC.wcml-row-currency');

        $I->seeElement('#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option[value=BTC]');

        $I->amGoingTo('Delete Bitcoin Currency');

        $I->click('#currency_row_del_USD .wcml-col-delete');

        $I->waitForElementNotVisible('#currency_row_del_BTC .wcml-col-delete','10');

        $I->dontSeeElement('.currency_table tbody tr#currency_row_BTC.wcml-row-currency');

        $I->dontSeeElement('#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option[value=BTC]');

        // Check Drop-down menu style

        $I->amGoingTo('check if the drop-down style meu is working ok');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option[value="EUR"]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option[value="USD"]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher option[value="AED"]');

        $I->dragAndDrop('.wcml_currencies_order_USD','#wcml_currencies_order li.wcml_currencies_order_EUR');

        $I->wait(2);

        $I->waitForElement('#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher',10);

        $I->seeOptionIsSelected('div#wcml_curr_sel_preview.wcml-currency-preview select.wcml_currency_switcher','United States dollar ($) - USD');

        $I->wait(2);

        $I->dragAndDrop('.wcml_currencies_order_USD','#wcml_currencies_order li.wcml_currencies_order_AED');

        $I->wait(2);

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->seeElement('div.product_meta select.wcml_currency_switcher option[value="EUR"]:nth-child(1)');

        $I->seeElement('div.product_meta select.wcml_currency_switcher option[value="USD"]:nth-child(3)');

        $I->seeElement('div.product_meta select.wcml_currency_switcher option[value="AED"]:nth-child(2)');

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

        $I->see('United States dollar ($) - USD','ul.wcml_currency_switcher.curr_list_vertical .wcml-active-currency');

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_vertical li[rel=EUR]:nth-child(1)');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_vertical li[rel=USD]:nth-child(3)');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_vertical li[rel=AED]:nth-child(2)');

        // Check list of currencies menu style - Horizontal

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->amGoingTo('check list of currencies menu horizontal style');

        $I->selectOption('.wcml_curr_style select[name=wcml_curr_sel_orientation]', 'Horizontal');

        $I->waitForElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal',10);

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=USD]');

        $I->seeElement('div#wcml_curr_sel_preview.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=AED]');

        $I->see('United States dollar ($) - USD','ul.wcml_currency_switcher.curr_list_horizontal .wcml-active-currency');

        $I->click('Save changes');

        $I->amOnPage('product/test-product/');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]:nth-child(1)');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=USD]:nth-child(3)');

        $I->seeElement('div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=AED]:nth-child(2)');

        // Check different template parameters for currencies

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->executeJS('window.scrollTo(0,500);');

        $I->amGoingTo('check different template parameters for currencies');

        $I->fillField('div.wcml-section-content-inner input[name=wcml_curr_template]','%code% - %symbol% - %name%');

        $I->click('Save changes');

        $I->executeJS('window.scrollTo(0,500);');

        $I->see('EUR - € - Euro','.wcml-currency-preview ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

        $I->amOnPage('product/test-product/');

        $I->see('EUR - € - Euro', 'div.product_meta ul.wcml_currency_switcher.curr_list_horizontal li[rel=EUR]');

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
