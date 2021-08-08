<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\emailtemplates\utilities;

use ipcraft\emailtemplates\EmailTemplates;
use ipcraft\emailtemplates\assetbundles\emailtemplatesutilityutility\EmailTemplatesUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * Email Templates Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    Infanion
 * @package   EmailTemplates
 * @since     1.0.0
 */
class EmailTemplatesUtility extends Utility
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
        return Craft::t('email-templates', 'EmailTemplatesUtility');
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
        return 'emailtemplates-email-templates-utility';
    }

    /**
     * Returns the path to the utility's SVG icon.
     *
     * @return string|null The path to the utility SVG icon
     */
    public static function iconPath()
    {
        return Craft::getAlias("@ipcraft/emailtemplates/assetbundles/emailtemplatesutilityutility/dist/img/EmailTemplatesUtility-icon.svg");
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
        Craft::$app->getView()->registerAssetBundle(EmailTemplatesUtilityUtilityAsset::class);

        $someVar = 'Have a nice day!';
        return Craft::$app->getView()->renderTemplate(
            'email-templates/_components/utilities/EmailTemplatesUtility_content',
            [
                'someVar' => $someVar
            ]
        );
    }
}
