<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

use Codeception\Actor;
use Codeception\Lib\Friend;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 *
 */
class UnitTester extends Actor
{
    use _generated\UnitTesterActions;

}
