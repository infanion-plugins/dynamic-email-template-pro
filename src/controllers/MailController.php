<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\emailtemplates\controllers;

use ipcraft\emailtemplates\EmailTemplates;
use Craft;
use craft\web\Controller;
use Exception;
use craft\web\Request;
use craft\helpers\App;
use craft\helpers\Template;
use craft\web\View;
use craft\elements\User;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;
use yii\mail\MessageInterface;
use craft\services\Users;
use craft\elements\Entry;
use craft\helpers\Assets;

/**
 * Default Controller
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
 * @package   EmailTemplates
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

    

    /**
     * function
     *
     * @param [template_unique_id,token-values,to] send the subject and data in the mailcontent part
     * @param [type] $to => send to users list to whom mail should be send.
     * @return true/false
     */
    public function send_mail($temp_uid,$tokens_value,$to): bool{

        $settings = App::mailSettings();
        $result = EmailTemplates::$plugin->emailTemplatesService->getmailDetails($temp_uid,$tokens_value,$to);

        $subject = $result['subject'];
        $textBody = $result['body'];

        try {
            return Craft::$app
                    ->getMailer()
                    ->compose()
                    ->setTo($to)
                    ->setSubject($subject)
                    ->setHtmlBody($textBody)
                    // ->setCc(null)
                    // ->setBcc($bcc_mail)
                    ->send();
        } catch (\Throwable $e) {
            $eMessage = $e->getMessage();
            // Remove the stack trace to get rid of any sensitive info. Note that Swiftmailer includes a debug
            // backlog in the exception message. :-/
            $eMessage = substr($eMessage, 0, strpos($eMessage, 'Stack trace:') - 1);
            Craft::warning('Error sending email: ' . $eMessage);

            return false;
        }

    }

    public function actionSend()
    {
        $template_id = Craft::$app->getRequest()->get();
        $t_id = $template_id['id'];
        $results = EmailTemplates::$plugin->emailTemplatesService->getTemplateTosendMail($t_id);
        return $this->renderTemplate(                                                                                                                 
            'email-templates/mailtemplates/send', 
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
        $tokens = EmailTemplates::$plugin->emailTemplatesService->getTemplateTokens($templateId);
        $usedtokens = EmailTemplates::$plugin->emailTemplatesService->UsedTokens($tokens);
        $tokensValues = EmailTemplates::$plugin->emailTemplatesService->prepareTokenValues($data, $usedtokens);

        $status = EmailTemplates::$plugin->emailTemplatesService->sendMail($templateUniqueId, 
                                                                $tokensValues, 
                                                                $receivers, 
                                                                $replyTo, 
                                                                $ecc, 
                                                                $ebcc,
                                                                $attachments);
                                                                
        $session = Craft::$app->getSession();
        if($status){
            $session->setNotice(Craft::t('email-templates', 'Email sent successfully'));
        }else{
            $session->error = (Craft::t('email-templates', 'Email not sent, check email configurations'));
        }

        $this->redirect('email-templates/mailtemplates');

    }
}
