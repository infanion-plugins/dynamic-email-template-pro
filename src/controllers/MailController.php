<?php
/**
 * Dynamic email template Pro plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens.
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\dynamicemailtemplatepro\controllers;

use ipcraft\dynamicemailtemplatepro\DynamicEmailTemplatePro;

use Craft;
use craft\web\Controller;

/**
 * Mail Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Infanion
 * @package   DynamicEmailTemplatePro
 * @since     1.0.0
 */
class MailController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    public function actionSend()
    {
        $template_id = Craft::$app->getRequest()->get();
        $t_id = $template_id['id'];
        $results = DynamicEmailTemplatePro::$plugin->sendMailService->getTemplateTosendMail($t_id);
        return $this->renderTemplate(                                                                                                                 
            'dynamic-email-template-pro/_testmail/_testmail', 
            [
                'data' => $results
            ]                                                                                                                                                                                                                                                                                  
        );

    }

    public function actionSendMail(){
        $data = Craft::$app->getRequest()->post();
        $templateUniqueId = $data['template_unique_id'];
        $templateId = $data['templateId'];
        $receivers = $data['to'];
        $replyTo = '';
        $attachments = '';
        $ecc = $data['cc'];
        $ebcc = $data['bcc'];
        $tokens = DynamicEmailTemplatePro::$plugin->sendMailService->getTemplateTokens($templateId);
        $usedtokens = DynamicEmailTemplatePro::$plugin->tokenService->UsedTokens($tokens);
        $tokensValues = DynamicEmailTemplatePro::$plugin->sendMailService->prepareTokenValues($data, $usedtokens);

        $status = DynamicEmailTemplatePro::$plugin->sendMailService->sendMail($templateUniqueId, 
                                                                $tokensValues, 
                                                                $receivers, 
                                                                $replyTo, 
                                                                $ecc, 
                                                                $ebcc,
                                                                $attachments);
                                                                
        $session = Craft::$app->getSession();
        if($status){
            $session->setNotice(Craft::t('dynamic-email-template-pro', 'Email sent successfully'));
        }else{
            $session->error = (Craft::t('dynamic-email-template-pro', 'Email not sent, check email configurations'));
        }

        $this->redirect('dynamic-email-template-pro/templates');

    }
}
