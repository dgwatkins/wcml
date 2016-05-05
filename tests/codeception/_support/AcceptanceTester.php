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
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */


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

    public function activatePlugin( $pluginSlug ) {
        //$this->click( "#{$pluginSlug} span.activate > a:first-of-type" );
        $this->click( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate > a:first-of-type" );
    }

    public function deactivatePlugin( $pluginSlug ) {
        //$this->click( "#{$pluginSlug} span.deactivate > a:first-of-type" );
        $this->click( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate > a:first-of-type" );
    }

    public function seeActivatePlugin( $pluginSlug ) {
        $this->waitForElementVisible( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate",10);
        $this->seeElement( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate" );
    }

    public function seeDeactivatePlugin( $pluginSlug ) {
        $this->waitForElementVisible( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate",10);
        $this->seeElement( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate" );
    }

}
