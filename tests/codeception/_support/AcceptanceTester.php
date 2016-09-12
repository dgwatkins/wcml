<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 *
 * List of Commands in this file :
 * exportDb, importDb, wp_login, activatePlugin, deactivatePlugin, seeActivatePlugin, seeDeactivatePlugin,
 * addNameProducts, seePageHasElement, dontSeePageHasElement, savePermalinks, simpleProducts, createCategory,
 * createAttribute, createVarProduct, createLocalVarProduct, enableMultiCurrency, disableMultiCurrency
*/

class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    /** Export DB dump */
    public function exportDb($filename) {

        // Export DB Dump to _output folder
        $this->runShellCommand('wp db export '.$filename.'.sql; cp '.$filename.'.sql ../tests/_output/');

        echo "Success: Exported to $filename.sql\n";
    }

    /** Import DB dump with Reset of DB */
    public function importDb($filename) {

        // Reset DB
        $this->runShellCommand('wp db reset --yes');

        echo "Success: Database Reset!\n";

        // Import DB Dump from _output folder
        $this->runShellCommand('wp db import ../tests/_output/'.$filename.'.sql');

        echo "Success: DB imported from $filename.sql\n";
    }

    /** WP Login Function */
    public function wp_login($name, $password)
    {
        $I = $this;

        // Login Procedure
        $I->amGoingTo('Login to the Backend');

        $I->amOnPage('/wp-admin/');

        $I->fillField('#user_login', $name);
        $I->fillField('#user_pass', $password);

        $I->click('Log In');

        $I->see('Dashboard');
    }

    /** Activate WP Plugins Action */
    public function activatePlugin( $pluginSlug ) {
        //$this->click( "#{$pluginSlug} span.activate > a:first-of-type" );
        $this->click( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate > a:first-of-type" );
    }

    /** Deactivate WP Plugins Action */
    public function deactivatePlugin( $pluginSlug ) {
        //$this->click( "#{$pluginSlug} span.deactivate > a:first-of-type" );
        $this->click( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate > a:first-of-type" );
    }

    /** Check if Activate WP Plugin Action */
    public function seeActivatePlugin( $pluginSlug ) {
        $this->waitForElementVisible( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate",10);
        $this->seeElement( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate" );
    }

    /** Check if Deactivate WP Plugin Action */
    public function seeDeactivatePlugin( $pluginSlug ) {
        $this->waitForElementVisible( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate",10);
        $this->seeElement( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate" );
    }

    /** Add Data Name in Product List WCML Action */
    public function addNameProducts () {
        $this->executeJS('return jQuery(".wpml-list-table td.wpml-col-title").each( function() {
        var title = jQuery.trim( jQuery(this).find("a").html() );
        var tr = jQuery(this).parent();
        tr.find("td").each( function(){
            jQuery(this).attr("data-from-title", title)
        })

    });
        '
        );

    }

    /**
     * @param $element
     * @return bool
     * Check if Element is in Page and run additional tests
     */
    function seePageHasElement($element)
    {
        try {
            $this->seeElement($element);
        } catch (\PHPUnit_Framework_AssertionFailedError $f) {
            return false;
        }
        return true;
    }

    /**
     * @param $element
     * @return bool
     * Check if Element is in Page and run additional tests
     */
    function dontSeePageHasElement($element)
    {
        try {
            $this->dontSeeElement($element);
        } catch (\PHPUnit_Framework_AssertionFailedError $f) {
            return false;
        }
        return true;
    }

    /**
	 * Saves permalinks
	 */
    public function savePermalinks() {
        $this->amOnPage( '/wp-admin/options-permalink.php' );
        $this->click( '#submit' );
    }

    /** Create WC Simple Product Action
     *  Please when you run this function :
     *  Select Name of Product and How many Number you wish to create
     */
    public function simpleProducts ($name, $number) {
        for ($x = 1; $x <= $number; $x++) {

            $this->amOnPage('/wp-admin/post-new.php?post_type=product');

            $this->see('Add New Product');

            $this->fillField('Product name', $name.$x);

            $this->click('#content-html');

            $this->fillField('#content', $name.$x);

            $this->fillField('_regular_price', '10');

            $this->wait(2);

            $this->click('#publish');

            $this->waitForElement('div#message.updated.notice.notice-success.is-dismissible', '10');

            $this->wait(2);
        }
    }

    /** Create WC Product Category Action
     *  Please when you run this function :
     *  Select Name of Category you wish to create
     */
    public function createCategory ($name) {

        $this->amGoingTo('Create a Product Category');

        $this->amOnPage('/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product');

        $this->see('Product Categories');

        $this->fillField('tag-name', $name);

        $this->click('Add New Product Category');

        $this->wait(2);
    }

    /** Create WC Product Attribute Action
     *  Please when you run this function :
     *  Select Name of Attribute you wish to create
     *  Select 1,2 or 3 Term Names for the Attribute
     */
    public function createAttribute ($name,$attr1,$attr2,$attr3) {

        $this->amGoingTo('Creat a Global Attribute');

        $this->amOnPage('/wp-admin/edit.php?post_type=product&page=product_attributes');

        $this->see('Attributes');

        $this->fillField('attribute_label', $name);

        $this->click('Add Attribute');

        $this->wait(2);

        // Add Terms into Attribute

        $slug = strtolower($name);

        $this->amOnPage('/wp-admin/edit-tags.php?taxonomy=pa_'.$slug.'&post_type=product');

        $this->see($name);

        //Attribute 1

        $this->fillField('tag-name', $attr1);

        $this->click('Add New '.$name);

        $this->wait(2);

        //Attribute 2

        $this->fillField('tag-name', $attr2);

        $this->click('Add New '.$name);

        $this->wait(2);

        //Attribute 3

        $this->fillField('tag-name', $attr3);

        $this->click('Add New '.$name);

        $this->wait(2);

    }

    /** Create WC Variable Product with Global Attribute Action
     *  Please when you run this function :
     *  Select Name of Product you wish to create
     *  Select the Global Attribute for the Variable Product
     */
    public function createVarProduct($name,$attribute){
        $this->amOnPage('/wp-admin/post-new.php?post_type=product');

        $this->see('Add New Product');

        $this->fillField('Product name', $name);

        $this->click('#content-html');

        $this->fillField('#content', $name);

        $this->selectOption('#product-type','Variable product');

        $this->executeJS('window.scrollTo(0,500);');

        $this->click('.attribute_tab');

        $this->selectOption('.attribute_taxonomy', $attribute);

        $this->click('.add_attribute');

        $this->waitForElementVisible('.woocommerce_attribute',10);

        $this->wait(3);

        $this->click('.select_all_attributes');

        $this->checkOption('input.checkbox[name="attribute_variation[0]"]');

        $this->click('Save attributes');

        $this->waitForElementNotVisible('.blockUI');

        $this->waitForElementVisible('.variations_tab',15);

        $this->wait(6);

        $this->click('.variations_tab');

        $this->wait(6);

        $this->selectOption('.variation_actions','Create variations from all attributes');

        $this->click('.do_variation_action');

        $this->acceptPopup();

        $this->wait(6);

        $this->acceptPopup();

        $this->selectOption('#field_to_edit','Set regular prices');

        $this->wait(6);

        $this->click('Go');

        $this->typeInPopup('10');

        $this->acceptPopup();

        $this->wait(6);

        $this->click('#publish');

        $this->wait(2);
    }

    /** Create WC Variable Product with Local Attribute Action
     *  Please when you run this function :
     *  Select Name of Attribute you wish to create
     *  Set 2 Term Names for the Attribute
     */
    public function createLocalVarProduct($name,$attribute,$term1,$term2){
        $this->amOnPage('/wp-admin/post-new.php?post_type=product');

        $this->see('Add New Product');

        $this->fillField('Product name', $name);

        $this->click('#content-html');

        $this->fillField('#content', $name);

        $this->selectOption('#product-type','Variable product');

        $this->executeJS('window.scrollTo(0,500);');

        $this->click('.attribute_tab');

        $this->click('.add_attribute');

        $this->waitForElementVisible('.woocommerce_attribute',10);

        $this->wait(3);

        $this->fillField('.woocommerce_attribute_data.wc-metabox-content table td.attribute_name input.attribute_name',$attribute);

        $this->fillField('.woocommerce_attribute_data.wc-metabox-content table tbody tr td textarea',$term1.'|'.$term2);

        $this->checkOption('input.checkbox[name="attribute_variation[0]"]');

        $this->click('Save attributes');

        $this->waitForElementNotVisible('.blockUI');

        $this->waitForElementVisible('.variations_tab',15);

        $this->wait(6);

        $this->click('.variations_tab');

        $this->wait(6);

        $this->selectOption('.variation_actions','Create variations from all attributes');

        $this->click('.do_variation_action');

        $this->acceptPopup();

        $this->wait(6);

        $this->acceptPopup();

        $this->selectOption('#field_to_edit','Set regular prices');

        $this->wait(6);

        $this->click('Go');

        $this->typeInPopup('10');

        $this->acceptPopup();

        $this->wait(6);

        $this->click('#publish');

        $this->wait(2);
    }

    /** Enable Multi currency
     *  No Params
     */
    public function enableMultiCurrency(){
        $this->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $this->checkOption('#multi_currency_independent');

        $this->click('Save changes');

        $this->wait(2);

        $this->seeElement('#currency-switcher');
    }

    /** Disable Multi currency
     *  No Params
     */
    public function disableMultiCurrency(){
        $this->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=multi-currency');

        $this->uncheckOption('#multi_currency_independent');

        $this->click('Save changes');

        $this->wait(2);

        $this->dontseeElement('#currency-switcher');
    }
    
}
