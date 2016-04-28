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

        $I->amGoingTo('Check WCML produt interface');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_wcml');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('Products', '.nav-tab-active');

        $I->seeElement('td.wpml-col-languages span i.otgs-ico-original');

        $I->seeElement('td.wpml-col-languages a');

        //$I->seeElement('.js-wcml-translation-dialog-trigger', ['data-id' => '12']);

        $I->click('td.wpml-col-languages a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->click('.wpml-translation-action-buttons div.alignleft button.cancel');

        $I->wait(3);

        $I->see('WooCommerce Multilingual');

        $I->click('Test Product');

        $I->seeInCurrentUrl('/wp-admin/post.php?post=');

        $I->see('Edit Product');

        $I->waitForElement('.icl_odd_row',10);

        $I->click('.icl_odd_row a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->click('.wpml-translation-action-buttons div.alignleft button.cancel');

        $I->wait(3);

        /* Check with native screen */

        $I->amGoingTo('change translation method');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_native');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        // I am here

        $I->click('Test Product');

        $I->seeInCurrentUrl('/wp-admin/post.php?post=');

        $I->see('Edit Product');

        $I->click('.icl_translations_table .icl_odd_row a');

        $I->see('Add New Product');

        $I->seeElement('.error');

        //NOT FINISHED
        // I should test if the locks are correct
        // I should change the translation interface again after the tests

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
