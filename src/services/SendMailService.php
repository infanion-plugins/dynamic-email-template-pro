<?php
/**
 * Dynamic email template Pro plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens.
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\dynamicemailtemplatepro\services;

use ipcraft\dynamicemailtemplatepro\DynamicEmailTemplatePro;

use Craft;
use craft\base\Component;
use craft\helpers\Assets;
use craft\helpers\App;

/**
 * SendMailService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Infanion
 * @package   DynamicEmailTemplatePro
 * @since     1.0.0
 */
class SendMailService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     DynamicEmailTemplatePro::$plugin->sendMailService->exampleService()
     *
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (DynamicEmailTemplatePro::$plugin->getSettings()->someAttribute) {
        }

        return $result;
    }

    public function getTemplateTosendMail($temp_id){
        $sites = Craft::$app->sites->getAllSites();
        foreach($sites as $site){
            if($site['primary'] == 1){
                $language_id = $site['id'];
            }
        }
        $response = [];
        $response['template'] = DynamicEmailTemplatePro::$plugin->templateService->TemplateById($temp_id);
        $response['content'] = DynamicEmailTemplatePro::$plugin->contentService->getContentById($temp_id, $language_id);
        $used_tokens = DynamicEmailTemplatePro::$plugin->templateService->getTemplateTokens($temp_id);
        $response['tokens'] = DynamicEmailTemplatePro::$plugin->tokenService->UsedTokens($used_tokens);

        return $response;

    }

    public function getTemplateTokens($template_id = null){
        $results = [];
        if($template_id){
            $results = (new \craft\db\Query())
            ->select(['tokens_id'])
            ->from(['{{%emailtemplates_templatestokens}}'])
            ->where('template_id = '.$template_id)
            // ->orderBy(['id', 'asc'])
            ->all();
        }
        return array_column($results, 'tokens_id');
    }

    

    public function prepareTokenValues($data, $tokens){
        $response = [];
        foreach($tokens as $token){
            $response[$token] = $data[$token] ?? $token;
        }
        return $response;
    }

    public function sendMail($templateUniqueId, $tokensValues = [], $receivers = [], $replyTo = '', $ecc = [], $ebcc = [], $attachments = ''){
        
        $originalLanguage = Craft::$app->language;
        
        $craftMailSettings = App::mailSettings();

        $templateDetails = DynamicEmailTemplatePro::$plugin->templateService->TemplateByUniqueId($templateUniqueId);
        try {
            $fileName = '';
            $options = ['fileName' => $fileName . '.pdf', 'contentType' => 'application/pdf'];

            $mailer = Craft::$app->getMailer();

            $newEmail = Craft::createObject(['class' => $mailer->messageClass, 'mailer' => $mailer]);
            
            $fromEmail = $templateDetails['template']['from'] ?: $craftMailSettings->fromEmail;

            $fromEmail = Craft::parseEnv($fromEmail);
            if ($fromEmail) {
                $newEmail->setFrom($fromEmail);
            }
            $fromName =$templateDetails['template']['alias'] ?: $craftMailSettings->fromName;
            $fromName = Craft::parseEnv($fromName);

            if ($fromName && $fromEmail) {
                $newEmail->setFrom([$fromEmail => $fromName]);
            }

            if(!is_array($receivers)){
                $emails = (string)$receivers;
                $emails = str_replace(';', ',', $emails);
                $emails = preg_split('/[\s,]+/', $emails);
            }else{
                $emails = $receivers;
            }
            $newEmail->setTo($emails);

            if(!is_array($ebcc)){
                $bcc = (string)$ebcc;
                $bcc = str_replace(';', ',', $bcc);
                $bcc = preg_split('/[\s,]+/', $bcc);
            }else{
                $bcc = $ebcc;
            }


            if (array_filter($bcc)) {
                $newEmail->setBcc($bcc);
            }

            if(!is_array($ecc)){
                $cc = (string)$ecc;
                $cc = str_replace(';', ',', $cc);
                $cc = preg_split('/[\s,]+/', $cc);
            }else{
                $cc = $ecc;
            }

            if (array_filter($cc)) {
                $newEmail->setCc($cc);
            }
            $replyTo = $replyTo ?? $craftMailSettings->replyTo;
            if ($replyTo) {
                $newEmail->setReplyTo($replyTo);
            }

            $subject = $templateDetails['content']['subject'] ?? '';
            
            $body = $templateDetails['content']['body'] ?? '';

            $newEmail->setSubject($this->replaceTokens($subject, $tokensValues));
            $newEmail->setHtmlBody($this->replaceTokens($body, $tokensValues));

            if($attachments){
                $fileExtension = pathinfo($attachments);
                $tempPath = Assets::tempFilePath($fileExtension);
                $fileName = basename($attachments);
                $options = ['fileName' => $fileName, 'contentType' => 'application/pdf'];
                $newEmail->attach($tempPath, $options);
            }

            if (!Craft::$app->getMailer()->send($newEmail)) {
                $error = Craft::t('Email could not be sent', 'dynamic-email-template-pro');
                return false;
            }
            return true;
        }
            
        catch (\Exception $e){
            // $error = Craft::t('Email could not be sent', [
            //     'error' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine(),
            //     'email' => $templateDetails['template']['name']
            // ]);
            return false;
        }
    }

    public function replaceTokens($data, $tokenValues = []){
        if($tokenValues != null){
            foreach($tokenValues as $key => $val){
                $data = $this->replace_tokens($key, $val, $data);
            }
        }
        return $data;
    }

    public function replace_tokens($token, $replace, $body){
        return str_replace($token, $replace, $body);
    }

}
