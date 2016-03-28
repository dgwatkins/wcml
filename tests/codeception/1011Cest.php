<?php
//Check Product Translation Interface


class ElevenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if product translation interface is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if product translation interface is working correct //
        ///////////////////////////////////////////////////////////////

        /*$I->amGoingTo('Check WCML produt interface');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_wcml');

        $I->click('Save changes');

        $I->wait(1);*/

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('Products', '.nav-tab-active');

        $I->seeElement('.js-wcml-translation-dialog-trigger', ['data-id' => '12']);

        $I->click('.js-wcml-translation-dialog-trigger');

        $I->seeElement('.ui-dialog');

        $I->seeElement('.ui-dialog>#wpml-translation-editor-dialog');

        $I->waitForElement('.wpml-dialog-close-button', 10);

        $I->wait(2);

        $I->click('Save & Close');

        $I->wait(2);

        // Doesn't work Need to wait for final version of WPML
        /*$I->click('Test Product');

        $I->seeInCurrentUrl('/wp-admin/post.php?post=12&action=edit&lang=en');

        $I->see('Edit Product');

        $I->waitForElement('.otgs-ico-add',10);

        $I->click('.otgs-ico-add');

        $I->waitForElement('.ui-dialog',10);

        $I->seeElement('.ui-dialog');

        $I->seeElement('.ui-dialog>#wpml-translation-editor-dialog');

        $I->waitForElement('.wpml-dialog-close-button', 10);

        $I->wait(2);

        $I->click('Save & Close');*/

        /* Check with native screen */
        /* It is not ready we should wait for final version of wpml */
        /*$I->amGoingTo('change translation method');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_native');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('.otgs-ico-add');

        $I->seeInCurrentUrl('/wp-admin/post.php?post=12&action=edit&lang=en');

        $I->see('Edit Product');

        $I->wait(1);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        //NOT FINISHED
        // I should change the translation interface again after the tests
        */




    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
