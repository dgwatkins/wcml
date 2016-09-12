<?php

//Shipping Tests


class shipping_testsCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('run shipping tests');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Shipping Tests //
        ///////////////////////////////////////////////////////////////

        /**
         * Preliminary Steps for Shipping
         */

        // Multi Currency Section

        $I->amGoingTo('prepeare multi-currency');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        if ($I->seePageHasElement('#currency-switcher')) {

            $I->uncheckOption('#multi_currency_independent');

            $I->click('Save changes');
        }

        if ($I->dontSeePageHasElement('#currency-switcher')) {

            $I->checkOption('#multi_currency_independent');

            $I->click('Save changes');
        }

        if ($I->seePageHasElement('#currency_row_del_USD .wcml-col-delete')) {

            // Edit USD Currency

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
        }

        if ($I->dontSeePageHasElement('#currency_row_del_USD .wcml-col-delete')) {

            // Add USD Currency

            $I->amGoingTo('Add New Currency');

            $I->click('.wcml_add_currency');

            $I->see('Add new currency', '.ui-dialog-title');

            $I->pressKey('.ui-dialog #wcml_currency_options_code_','UNITEDS',WebDriverKeys::ENTER);

            $I->fillField('.ui-dialog-content input#wcml_currency_options_rate_.ext_rate', '2');

            $I->seeOptionIsSelected('.ui-dialog-content #wcml_currency_options_position_', 'Left');

            $I->seeInField('.ui-dialog-content div.wpml-form-row input.currency_option_thousand_sep',',');

            $I->seeInField('.ui-dialog-content div.wpml-form-row input.currency_option_decimal_sep','.');

            $I->seeInField('.ui-dialog-content div.wpml-form-row input.currency_option_decimals','2');

            $I->seeOptionIsSelected('.ui-dialog-content #wcml_currency_options_rounding_', 'disabled');

            $I->click('.ui-dialog .currency_options_save');

            $I->wait(4);

            $I->see('1 EUR = 2 USD','#currency_row_USD.wcml-row-currency td.wcml-col-rate');
        }

        // WC Taxes Section

        $I->amGoingTo('prepeare wc taxes');

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings');

        $I->checkOption('#woocommerce_calc_taxes');

        $I->click('Save changes');

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings&tab=tax&section=standard');

        $I->click('Insert row');

        $I->fillField('input[data-attribute=tax_rate]','23');

        $I->fillField('input[data-attribute=tax_rate_name]','FPA');

        $I->click('Save changes');

        $I->wait(3);

        $I->seeElement('input[value="23.0000"]');

        $I->seeElement('input[value="FPA"]');

        $I->amGoingTo('disable Tax for now');

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings');

        $I->uncheckOption('#woocommerce_calc_taxes');

        $I->click('Save changes');

        // Shipping Zone and Class

        $I->amGoingTo('add a shipping zone');

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings&tab=shipping');

        $I->see('Shipping Zones');

        $I->click('Add shipping zone');

        $I->fillField('input[data-attribute=zone_name]','Test Zone');

        $I->click('#s2id_autogen2');

        $I->fillField('#s2id_autogen2','Greece');

        $I->click('.select2-result-label');

        $I->click('Save changes');

        $I->wait(3);

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes');

        $I->click('Add Shipping Class');

        $I->fillField('input[data-attribute=name]','Shippo Classy');

        $I->click('Save Shipping Classes');

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
