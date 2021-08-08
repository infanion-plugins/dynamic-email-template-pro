<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\emailtemplates;

use ipcraft\emailtemplates\services\EmailTemplatesService as EmailTemplatesServiceService;
use ipcraft\emailtemplates\variables\EmailTemplatesVariable;
use ipcraft\emailtemplates\twigextensions\EmailTemplatesTwigExtension;
use ipcraft\emailtemplates\models\Settings;
use ipcraft\emailtemplates\fields\EmailTemplatesField as EmailTemplatesFieldField;
use ipcraft\emailtemplates\utilities\EmailTemplatesUtility as EmailTemplatesUtilityUtility;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Infanion
 * @package   EmailTemplates
 * @since     1.0.0
 *
 * @property  EmailTemplatesServiceService $emailTemplatesService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class EmailTemplates extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * EmailTemplates::$plugin
     *
     * @var EmailTemplates
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * EmailTemplates::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new EmailTemplatesTwigExtension());

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'email-templates/default';
                $event->rules['api/email-templates/get'] = 'email-templates/default/fetch-token-list';
            }
        );
  
        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['email-templates'] = 'email-templates/tokens/index';
                $event->rules['email-templates/mailtemplates'] = 'email-templates/cp/mail-templates';
                $event->rules['email-templates/tokens/new'] = 'email-templates/tokens/add-tokens';
                $event->rules['email-templates/tokens/edit'] = 'email-templates/tokens/edit-tokens';
                $event->rules['email-templates/template-new'] = 'email-templates/cp/add-template';
                $event->rules['email-templates/send'] = 'email-templates/mail/send';
                $event->rules['email-templates/remove'] = 'email-templates/tokens/remove';
                $event->rules['email-templates/update'] = 'email-templates/tokens/update';
                $event->rules['email-templates/updatecontents'] = 'email-templates/content/update-contents';
                $event->rules['email-templates/removetemplates'] = 'email-templates/cp/remove-templates';
                $event->rules['email-templates/mailtemplates-update'] = 'email-templates/cp/update-templates';
                $event->rules['email-templates/test-email'] = 'email-templates/test/test-email';
            }
        );

        // Register our elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
            }
        );

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = EmailTemplatesFieldField::class;
            }
        );

        // Register our utilities
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = EmailTemplatesUtilityUtility::class;
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('emailTemplates', EmailTemplatesVariable::class);
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        $this->_registerPermissions();

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'email-templates',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions[Craft::t('email-templates', 'Dynamic email template Pro')] = [
                'manageTokens' => ['label' => Craft::t('email-templates', 'Create, Update and Remove tokens')],
                'manageTemplates' => ['label' => Craft::t('email-templates', 'Create, Update and Remove Email templates')],
                'manageContent' => ['label' => Craft::t('email-templates', 'Create, Update and Remove Email template content')],
            ];
        });
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'email-templates/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
