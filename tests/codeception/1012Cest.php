<?php
//Check Product Date Synchronization


class TwelveCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if product synchronization is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if Data Sync  is working correct //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check Translation Date Sync');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->checkOption('#wcml_products_sync_date');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->see('Edit Product');

        $I->seeElement('.edit-timestamp');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->click('.edit-timestamp');

        $I->selectOption('select[name=mm]', '11-Nov');

        $I->fillField('#jj', '30');

        $I->fillField('#aa', '2015');

        $I->click('.save-timestamp');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->click('#publishing-action #publish');

        $I->amOnPage('/wp-admin/edit.php?post_type=product&lang=el');

        $I->see('Products');

        $I->see('2015/11/30', 'td.column-date');

        // Check Translation Date UnSync

        $I->amGoingTo('Check Date UnSync');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->uncheckOption('#wcml_products_sync_date');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/edit.php?post_type=product&lang=en');

        $I->see('Products');

        $I->click('Test Product');

        $I->waitForElement('#wpbody-content', 10);

        $I->click('.edit-timestamp');

        $I->selectOption('select[name=mm]', '08-Aug');

        $I->fillField('#jj', '09');

        $I->fillField('#aa', '1982');

        $I->click('.save-timestamp');

        $I->click('#publish');

        $I->amOnPage('/wp-admin/edit.php?post_type=product&lang=en');

        $I->see('Products');

        $I->see('1982/08/09', '.column-date');

        $I->amOnPage('/wp-admin/edit.php?post_type=product&lang=el');

        $I->see('Products');

        $I->see('2015/11/30', 'td.column-date');

        $I->wait(1);

        $I->amGoingTo('Re-enable Translation Date Sync');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->checkOption('#wcml_products_sync_date');

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
