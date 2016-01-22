<?php
//Check WC Pages Warnings


class NineCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if wc pages are created automatically');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ////////////////////////////////////////
        // Check if Pages are translated      //
        ///////////////////////////////////////


        $I->amGoingTo('Check if Pages are translated');

        $I->wantTo('Check that all the pages are there');

        $I->amOnPage('wp-admin/edit.php?post_type=page&lang=all');

        $I->see('Pages');

        $I->see('Cart');

        $I->see('Checkout');

        $I->see('My Account');

        $I->see('Shop');

        $I->see('Καλάθι');

        $I->see('Κατάστημα');

        $I->see('Ο λογαριασμός μου');

        $I->see('Ολοκλήρωση αγοράς');

        $I->wantTo('Delete all teh store pages');

        $I->checkOption('#cb-select-all-1');

        $I->selectOption('#bulk-action-selector-top', 'Move to Trash');

        $I->click('Apply', '.bulkactions');

        $I->wait(1);

        $I->click('Trash');

        $I->click('.language_all');

        $I->checkOption('#cb-select-all-1');

        $I->selectOption('#bulk-action-selector-top', 'Delete Permanently');

        $I->click('Apply', '.bulkactions');

        $I->wantTo('Check if there is a warning in wcml status page');

        $I->amOnPage('wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->seeElement('.otgs-ico-warning');

        $I->see('One or more WooCommerce pages have not been created.');

        $I->seeLink('Install WooCommerce Pages','/wp-admin/admin.php?page=wc-status&tab=tools');

        $I->wantTo('Install missing pages');

        $I->click('Install WooCommerce Pages');

        $I->wait(1);

        $I->click('Install pages');

        $I->see('All missing WooCommerce pages was installed successfully.');

        $I->amOnPage('wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->seeElement('.otgs-ico-warning');

        $I->see('WooCommerce store pages do not exist for these languages:');

        $I->seeInPageSource('/wp-content/plugins/sitepress-multilingual-cms/res/flags/el.png');

        $I->see('Greek');

        $I->click('Create missing translations');

        $I->wait(1);

        $I->wantTo('See if we have any warnings');

        $I->seeElement('.otgs-ico-ok');

        $I->see('WooCommerce store pages are translated to all the site\'s languages.');

        $I->wantTo('See if we all the pages are back');

        $I->amOnPage('wp-admin/edit.php?post_type=page&lang=all');

        $I->see('Pages');

        $I->see('Cart');

        $I->see('Checkout');

        $I->see('My Account');

        $I->see('Shop');

        $I->see('Καλάθι');

        $I->see('Κατάστημα');

        $I->see('Ο λογαριασμός μου');

        $I->see('Ολοκλήρωση αγοράς');


    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
