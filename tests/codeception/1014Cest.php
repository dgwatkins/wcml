<?php
//Check Multi Currency Tab

/**
 * @group currency
 */


class FourteenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if Multi-currency functionality is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check Multicurrency Functionality //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check Multi-currency functionality');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->see('WooCommerce Multilingual');

        // Enable and Disable Multi Currency

        $I->see('Enable/disable', '.wcml-section-header');

        $I->checkOption('#multi_currency_independent');

        $I->click('Save changes');

        $I->seeElement('#multi-currency-per-language-details');

        $I->seeElement('#currency-switcher');

        $I->uncheckOption('#multi_currency_independent');

        $I->click('Save changes');

        $I->dontSeeElement('#multi-currency-per-language-details');

        $I->dontSeeElement('#currency-switcher');

        $I->checkOption('#multi_currency_independent');

        $I->click('Save changes');

        $I->seeElement('#multi-currency-per-language-details');

        $I->seeElement('#currency-switcher');

        // Add and Delete Currency

        $I->amGoingTo('Add New Currency and Delete it');

        $I->click('.wcml_add_currency');

        $I->see('Add new currency', '.ui-dialog-title');

        $I->pressKey('.ui-dialog #wcml_currency_options_code_','UNITEDS',WebDriverKeys::ENTER);

        $I->click('.ui-dialog .currency_options_save');

        $I->waitForElementNotVisible('.ui-dialog','10');

        $I->wait(3);

        $I->seeElement('.currency_table tbody tr#currency_row_USD.wcml-row-currency');

        $I->click('#currency_row_del_USD .wcml-col-delete');

        $I->waitForElementNotVisible('#currency_row_del_USD .wcml-col-delete','10');

        $I->dontSeeElement('.currency_table tbody tr#currency_row_USD.wcml-row-currency');

        // Add new Currency

        $I->amGoingTo('Add New Currency');

        $I->reloadPage();

        $I->click('.wcml_add_currency');

        $I->see('Add new currency', '.ui-dialog-title');

        $I->pressKey('.ui-dialog #wcml_currency_options_code_','UNITEDS',WebDriverKeys::ENTER);

        $I->fillField('.ui-dialog-content input#wcml_currency_options_rate_.ext_rate', '1.2');

        $I->seeOptionIsSelected('.ui-dialog-content #wcml_currency_options_position_', 'Left');

        $I->seeInField('.ui-dialog-content div.wpml-form-row input.currency_option_thousand_sep',',');

        $I->seeInField('.ui-dialog-content div.wpml-form-row input.currency_option_decimal_sep','.');

        $I->seeInField('.ui-dialog-content div.wpml-form-row input.currency_option_decimals','2');

        $I->seeOptionIsSelected('.ui-dialog-content #wcml_currency_options_rounding_', 'disabled');

        $I->click('.ui-dialog .currency_options_save');

        $I->wait(4);

        // Edit new Currency

        $I->amGoingTo('Edit Currency');

        $I->reloadPage();

        $I->click('#currency_row_USD .otgs-ico-edit');

        $I->see('Update settings for United States dollar ($)','.ui-dialog-title');

        $I->fillField('.ui-dialog-content input#wcml_currency_options_rate_USD.ext_rate', '2');

        $I->fillField('.ui-dialog-content div.wpml-form-row input.currency_option_thousand_sep','.');

        $I->fillField('.ui-dialog-content div.wpml-form-row input.currency_option_decimal_sep',',');

        $I->click('.ui-dialog .currency_options_save');

        $I->waitForElementNotVisible('.ui-dialog','10');

        $I->wait(3);

        $I->see('1 EUR = 2 USD','#currency_row_USD.wcml-row-currency td.wcml-col-rate');

        // Add one more currency

        $I->amGoingTo('Add one more Currency');

        $I->reloadPage();

        $I->click('.wcml_add_currency');

        $I->see('Add new currency', '.ui-dialog-title');

        $I->pressKey('.ui-dialog #wcml_currency_options_code_','UNITEDA',WebDriverKeys::ENTER);

        $I->fillField('.ui-dialog-content input#wcml_currency_options_rate_.ext_rate', '3');

        $I->click('.ui-dialog .currency_options_save');

        $I->wait(3);

        //Check Frontend

        $I->amGoingTo('Check Prices in frontend for all currencies');

        $I->amOnPage('/product/test-product/');

        $I->selectOption('.product_meta .wcml_currency_switcher','Euro (€) - EUR');

        $I->wait(2);

        $I->see('€10.00','.product div.summary div p.price span.amount');

        $I->selectOption('.product_meta .wcml_currency_switcher','United States dollar ($) - USD');

        $I->wait(2);

        $I->see('$20,00','.product div.summary div p.price span.amount');

        $I->selectOption('.product_meta .wcml_currency_switcher','United Arab Emirates dirham (د.إ) - AED');

        $I->wait(2);

        $I->see('د.إ30.00','.product div.summary div p.price span.amount');

        $I->wait(2);

        // Disable Currency for language

        $I->amGoingTo('Disable one Currency per Language');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->click('#currency_row_langs_AED .currency_languages .on [data-language=en]');

        $I->waitForElementVisible('#currency_row_langs_AED .currency_languages [data-lang=en] .otgs-ico-no',10);

        $I->click('#currency_row_langs_USD .currency_languages .on [data-language=el]');

        $I->waitForElementVisible('#currency_row_langs_USD .currency_languages [data-lang=el] .otgs-ico-no',10);

        $I->click('Save changes');

        // Check in the frontend

        $I->amGoingTo('Frontend if the Currencies are correct');

        $I->amOnPage('/product/test-product/');

        $I->seeElement('.product .product_meta .wcml_currency_switcher option[value = "EUR"]');

        $I->seeElement('.product .product_meta .wcml_currency_switcher option[value = "USD"]');

        $I->dontSeeElement('.product .product_meta .wcml_currency_switcher option[value = "AED"]');

        $I->amOnPage('/προϊόν/δοκιμαστικό-προϊόν/?lang=el');

        $I->seeElement('.product .product_meta .wcml_currency_switcher option[value = "EUR"]');

        $I->seeElement('.product .product_meta .wcml_currency_switcher option[value = "AED"]');

        $I->dontSeeElement('.product .product_meta .wcml_currency_switcher option[value = "USD"]');

        $I->selectOption('.wcml_currency_switcher','AED');

        $I->wait(2);

        // Disable Currency for language

        $I->amGoingTo('Disable one Currency per Language');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->click('#currency_row_langs_AED .currency_languages .on [data-language=en]');

        $I->waitForElementVisible('#currency_row_langs_AED .currency_languages [data-lang=en] .otgs-ico-yes',10);

        $I->click('#currency_row_langs_USD .currency_languages .on [data-language=el]');

        $I->waitForElementVisible('#currency_row_langs_USD .currency_languages [data-lang=el] .otgs-ico-yes',10);

        $I->click('Save changes');
        
        // Check default currency

        $I->amGoingTo('if default currency is working');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->selectOption('.default_currency select[rel=en]','USD');

        $I->wait(1);

        $I->selectOption('.default_currency select[rel=el]','EUR');

        $I->wait(1);

        $I->waitForElementVisible('#wcml_mc_options_submit');

        $I->click('Save changes');

        $I->resetCookie('_wcml_dashboard_currency');

        $I->amOnPage('/product/test-product/');

        $I->see('$20,00','.product div.summary div p.price span.amount');

        $I->seeElement('.product .product_meta .wcml_currency_switcher option[value="USD"]');

        $I->amOnPage('/προϊόν/δοκιμαστικό-προϊόν/?lang=el');

        $I->see('€10.00','.product div.summary div p.price span.amount');

        $I->seeElement('.product .product_meta .wcml_currency_switcher option[value="EUR"]');

        $I->wait(3);

        // Check prevent window

        $I->amGoingTo('see if prevent window apperas if not save options');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->checkOption('#display_custom_prices');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $I->acceptPopup();

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
