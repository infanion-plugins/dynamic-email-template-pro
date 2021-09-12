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
use ipcraft\dynamicemailtemplatepro\assetbundles\dynamicemailtemplateproutilityutility\DynamicEmailTemplateProUtilityUtilityAsset;

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
class DynamicEmailTemplateProUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this utility.
     *
     * @return string The display name of this utility.
     */
    public static function displayName(): string
    {
        return Craft::t('dynamic-email-template-pro', 'DynamicEmailTemplateProUtility');
    }

    /**
     * Returns the utility’s unique identifier.
     *
     * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
     *
     * @return string
     */
    public static function id(): string
    {
        return 'dynamicemailtemplatepro-dynamic-email-template-pro-utility';
    }

    /**
     * Returns the path to the utility's SVG icon.
     *
     * @return string|null The path to the utility SVG icon
     */
    public static function iconPath()
    {
        return Craft::getAlias("@ipcraft/dynamicemailtemplatepro/assetbundles/dynamicemailtemplateproutilityutility/dist/img/DynamicEmailTemplateProUtility-icon.svg");
    }

    /**
     * Returns the number that should be shown in the utility’s nav item badge.
     *
     * If `0` is returned, no badge will be shown
     *
     * @return int
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * Returns the utility's content HTML.
     *
     * @return string
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(DynamicEmailTemplateProUtilityUtilityAsset::class);

        $someVar = 'Have a nice day!';
        return Craft::$app->getView()->renderTemplate(
            'dynamic-email-template-pro/_components/utilities/DynamicEmailTemplateProUtility_content',
            [
                'someVar' => $someVar
            ]
        );
    }
}
