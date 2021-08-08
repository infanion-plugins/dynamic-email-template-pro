<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\emailtemplates\variables;

use ipcraft\emailtemplates\EmailTemplates;

use Craft;

/**
 * Email Templates Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.emailTemplates }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Infanion
 * @package   EmailTemplates
 * @since     1.0.0
 */
class EmailTemplatesVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.emailTemplates.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.emailTemplates.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function exampleVariable($optional = null)
    {
        $result = "And away we go to the Twig template...";
        if ($optional) {
            $result = "I'm feeling optional today...";
        }
        return $result;
    }
}
