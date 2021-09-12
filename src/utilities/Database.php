<?php
/**
 * Dynamic email template Pro plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens.
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\dynamicemailtemplatepro\utilities;

use ipcraft\dynamicemailtemplatepro\DynamicEmailTemplatePro;

use Craft;
use craft\base\Utility;

/**
 * Dynamic email template Pro Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    Infanion
 * @package   DynamicEmailTemplatePro
 * @since     1.0.0
 */
class Database
{
   
    public function getDBConnection(){
        $DB_DRIVER   = Craft::parseEnv('$DB_DRIVER');
        $DB_SERVER   = Craft::parseEnv('$DB_SERVER');
        $DB_PORT     = Craft::parseEnv('$DB_PORT');
        $DB_DATABASE = Craft::parseEnv('$DB_DATABASE');
        $DB_USER     = Craft::parseEnv('$DB_USER');
        $DB_PASSWORD = Craft::parseEnv('$DB_PASSWORD');

    
        $connection = new \yii\db\Connection([
            'dsn' => "$DB_DRIVER:host=$DB_SERVER;port=$DB_PORT;dbname=$DB_DATABASE",
            'username' => "$DB_USER",
            'password' => "$DB_PASSWORD",
        ]);
        return $connection;
    }
   
    
    
}
