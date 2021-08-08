<?php
/**
 * template plugin for Craft CMS 3.x
 *
 * for managing email templates
 *
 * @link      infanion.com
 * @copyright Copyright (c) 2021 infanion
 */

namespace ipcraft\emailtemplates\utilities;

use ipcraft\emailtemplates\EmailTemplates;
use ipcraft\emailtemplates\assetbundles\emailtemplatesutilityutility\EmailTemplatesUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * template Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    infanion
 * @package   Template
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
