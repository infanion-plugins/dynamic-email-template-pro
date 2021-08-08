<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\emailtemplates\migrations;

use ipcraft\emailtemplates\EmailTemplates;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * Email Templates Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Infanion
 * @package   EmailTemplates
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

    // emailtemplates_emailtemplatesrecord table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%emailtemplates_emailtemplatesrecord}}');
        $tableSchema_token = Craft::$app->db->schema->getTableSchema('{{%emailtemplates_tokens}}');
        $tableSchema_templates = Craft::$app->db->schema->getTableSchema('{{%emailtemplates_templates}}');
        $tableSchema_content = Craft::$app->db->schema->getTableSchema('{{%emailtemplates_templatecontent}}');
        $tableSchema_templatetokens = Craft::$app->db->schema->getTableSchema('{{%emailtemplates_templatestokens}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%emailtemplates_emailtemplatesrecord}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'siteId' => $this->integer()->notNull(),
                    'some_field' => $this->string(255)->notNull(),
                ]
            );
        }
        if ($tableSchema_token === null) {
            $this->createTable(
                '{{%emailtemplates_tokens}}',
                [
                    'id' => $this->primaryKey(),
                    'name' => $this->string(255)->notNull(),
                    'token' => $this->string(255)->notNull()->unique(),
                    'token_description' => $this->string(255)->notNull(),
                    // 'siteId' => $this->integer()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        

        if ($tableSchema_templates === null) {
            $this->createTable(
                '{{%emailtemplates_templates}}',
                [
                    'id' => $this->primaryKey(),
                    'name' => $this->string(),
                    'description' => $this->string(255),
                    'email_template_id' => $this->string()->notNull()->unique(),
                    // 'siteId' => $this->integer()->notNull(),
                    'from' => $this->string(255),
                    'cc' => $this->string(255),
                    'bcc' => $this->string(255),
                    'password' => $this->string(255),
                    'alias' => $this->string(255),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );
        }

        if ($tableSchema_templatetokens === null) {
            $this->createTable(
                '{{%emailtemplates_templatestokens}}',
                [
                    'id' => $this->primaryKey(),
                    'template_id' => $this->integer()->notNull(),
                    'tokens_id' => $this->integer(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );
        }

        if ($tableSchema_content === null) {
            $this->createTable(
                '{{%emailtemplates_templatecontent}}',
                [
                    'id' => $this->primaryKey(),
                    't_id' => $this->integer()->notNull(),
                    'language' => $this->string()->notNull(),
                    'subject' => $this->string()->notNull(),
                    'body' => $this->longText()->notNull(),
                    'siteId' => $this->integer()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );

        }

       

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
    // emailtemplates_emailtemplatesrecord table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%emailtemplates_emailtemplatesrecord}}',
                'some_field',
                true
            ),
            '{{%emailtemplates_emailtemplatesrecord}}',
            'some_field',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
    // emailtemplates_emailtemplatesrecord table
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%emailtemplates_emailtemplatesrecord}}', 'siteId'),
            '{{%emailtemplates_emailtemplatesrecord}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%emailtemplates_templatecontent}}', 't_id'),
            '{{%emailtemplates_templatecontent}}',
            't_id',
            '{{%emailtemplates_templates}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%emailtemplates_templatestokens}}', 'template_id'),
            '{{%emailtemplates_templatestokens}}',
            'template_id',
            '{{%emailtemplates_templates}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%emailtemplates_templatestokens}}', 'tokens_id'),
            '{{%emailtemplates_templatestokens}}',
            'tokens_id',
            '{{%emailtemplates_tokens}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
    // emailtemplates_emailtemplatesrecord table
        $this->dropTableIfExists('{{%emailtemplates_emailtemplatesrecord}}');
        $this->dropTableIfExists('{{%emailtemplates_templatestokens}}');
        $this->dropTableIfExists('{{%emailtemplates_tokens}}');
        $this->dropTableIfExists('{{%emailtemplates_templatecontent}}');
        $this->dropTableIfExists('{{%emailtemplates_templates}}');
    }
}
