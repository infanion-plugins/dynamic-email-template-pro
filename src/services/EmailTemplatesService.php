<?php
/**
 * Email Templates plugin for Craft CMS 3.x
 *
 * You can build and manage your email templates used in your Craft website or Craft Commerce. Emails can be sent dynamically from your application, by using tokens 
 *
 * @link      https://www.infanion.com/
 * @copyright Copyright (c) 2021 Infanion
 */

namespace ipcraft\emailtemplates\services;

use ipcraft\emailtemplates\EmailTemplates;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use ipcraft\emailtemplates\utilities\Database;
use craft\db\Connection;
use craft\helpers\Db;
use craft\services;
use craft\helpers\DateTimeHelper;
use craft\helpers\ArrayHelper;
use Exception;
use craft\errors\SiteNotFoundException;
use craft\models\Site;
use craft\helpers\Assets;


/**
 * EmailTemplatesService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Infanion
 * @package   EmailTemplates
 * @since     1.0.0
 */
class EmailTemplatesService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     EmailTemplates::$plugin->emailTemplatesService->exampleService()
     *
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (EmailTemplates::$plugin->getSettings()->someAttribute) {
        }

        return $result;
    }

    public function validateToken($token){
        $token = $this->TokenstartsWith($token,'!');
        return $this->TokenendsWith($token,'!');
    }
    /*
    This function is to create tokens in emailtemplates_tokens table
    with token name and description
    */
    public function saveTokens()
    {
        $data = Craft::$app->getRequest()->post();
        $name = $data['name'];
        $token = $this->validateToken($data['token']);
        $token_description = $data['description'];
        $tokenId = $data['id'];
        $now = Db::prepareDateForDb(new \DateTime());
        if ($token == trim($token) && strpos($token, ' ') !== false) {
            $token = str_replace(' ', '', $token);
        }
        if( $tokenId){
            Craft::$app->getDb()->createCommand()
                ->update('{{%emailtemplates_tokens}}', [
                                                        'name' => $name, 
                                                        'token' => $token,
                                                        'token_description' => $token_description,
                                                        'dateUpdated' => $now],
                                                        ['in', 'id', $tokenId])
                ->execute();
        }else{
            Db::insert('emailtemplates_tokens', [
                'name' => $name,
                'token' => $token,
                'token_description' => $token_description,
                'dateCreated' => $now,
                'dateUpdated' => $now,
            ], false);
        }
        return true;
    }

    public function TokenstartsWith ($string, $startString)
    {    
        if($string[0] == '!'){
            return $string;
        }
        else{
            return '!'.$string;
        }
    }

    
    public function TokenendsWith($string, $endString)
    {
        if($string[-1] == '!'){
            return $string;
        }
        else{
            $correct_token = $string.'!';
            return $correct_token;
        }
    }

    public function fetchToken()
    {
        /*
        This function is to fetch all the token details from DB
        */
        $results = (new \craft\db\Query())
            ->select(['id','name','token', 'token_description'])
            ->from(['{{%emailtemplates_tokens}}'])
            ->orderBy(['name'=> SORT_ASC])
            ->all();
        return $results;
    }

    /*This funtion is to check whether particular token is
    used in any template */
    public function checkToken($id){
        return (new \craft\db\Query())
        ->select(['id','template_id'])
        ->from(['{{%emailtemplates_templatestokens}}'])
        ->where('tokens_id = '.$id)
        ->all();
    }

    public function removeToken()
    {
        $data = Craft::$app->getRequest()->get();
        $usedtoken = $this->checkToken($data['id']);
        $session = Craft::$app->getSession();
        $tokens = EmailTemplates::$plugin->emailTemplatesService->getTokenById($data['id']);

        if($usedtoken){
            $template = $this->TemplateById($usedtoken[0]['template_id']);
            $session->error = "Oops! Token ".$tokens['name']." is linked with template ".$template['name'].". To delete this token first you have to remove it from template.";
            return false;
        }
        else{
            if(isset($data['id'])) {
                $id = $data['id'];
                $dbobject = new Database();
                $connection = $dbobject->getDBConnection();
                $connection->open();
                $response = $connection->createCommand()->delete('emailtemplates_tokens', 'id = :id', [':id' => $id ])->execute();
                $session->setNotice(Craft::t('email-templates',  'Token '.$tokens['name'].' deleted successfully'));
                return $response;
            }
        }
    }
    
    public function removeTemplates()
    {
        $data = Craft::$app->getRequest()->get();
        if(isset($data['id'])) {
            $id = $data['id'];
            $dbobject = new Database();
            $connection = $dbobject->getDBConnection();
            $connection->open();
            $response = $connection->createCommand()->delete('emailtemplates_templates', 'id = :id', [':id' => $id ])->execute();
            return $response;
        }
    }


    public function saveTemplates()
    {
        $data = Craft::$app->getRequest()->post();
        $now = Db::prepareDateForDb(new \DateTime());
        $name = $data['name'];
        $description = $data['description'];
        $template_unique_id = $data['template_unique_id'];
        $email_template_id = preg_replace('/\s+/', '', $template_unique_id);
        $from = $data['from'];
        $alias = $data['alias'];
        $tokens = $data['tokens'];
        $templateId = $data['templateId'];
        if($templateId){
            Craft::$app->getDb()->createCommand()
            ->update('{{%emailtemplates_templates}}', [
                        'name' => $name, 
                        'description' => $description,
                        'from' => $from,
                        'alias' => $alias,
                        'email_template_id' => $email_template_id, 
                        'dateUpdated' => $now],
                        ['in','id', $templateId])
            ->execute();

            $dbobject = new Database();
            $connection = $dbobject->getDBConnection();
            $connection->open();
            $response = $connection->createCommand()->delete('emailtemplates_templatestokens', 
                'template_id = :template_id', [':template_id' => $templateId ])->execute();
        }else{
            $res = Db::insert('emailtemplates_templates', [
                'name' => $name,
                'description' => $description,
                'from' => $from,
                'alias' => $alias,
                'email_template_id' => $email_template_id,
                'dateCreated' => Db::prepareDateForDb(new \DateTime()),
                'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
            ], false);
        }

        $newrecord = (new \craft\db\Query())
            ->select(['id','name', 'email_template_id','description'])
            ->from(['{{%emailtemplates_templates}}'])
            ->where(['ilike', 'email_template_id', $email_template_id])
            ->one();

            if ($tokens){
                foreach ($tokens as $token){
                    $res = Db::insert('emailtemplates_templatestokens', [
                        'template_id' => $newrecord['id'],
                        'tokens_id' => $token,
                        'dateCreated' => Db::prepareDateForDb(new \DateTime()),
                        'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
                    ], false);
                }
            }
        return true;
    }

    public function fetchTemplates()
    {
        $results = (new \craft\db\Query())
            ->select(['id','name', 'description'])
            ->from(['{{%emailtemplates_templates}}'])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        return $results;
    }

    public function fetchContents()
    {
        $results = (new \craft\db\Query())
            ->select(['id','t_id','subject', 'language'])
            ->from(['{{%emailtemplates_templatecontent}}'])
            ->all();
        return $results;
    }

    public function updateToken(){
        $data = Craft::$app->getRequest()->get();
        $id = $data['id'];
        $results = (new \craft\db\Query())
        ->select(['id','name','token', 'token_description'])
        ->from(['{{%emailtemplates_tokens}}'])
        ->where(['id'=>$id])
        ->one();
        return $results;
    }
    

    public function getOptions(){
        $tokens = $this->fetchToken();
        if ($tokens) {
            $options = ArrayHelper::map($tokens, 'id', 'name');
        } else {
            $options = [];
        }
        return $options;

    }

    public function TemplateById($id){
        $results = (new \craft\db\Query())
        ->select(['id','name', 'description','from','alias', 'email_template_id'])
        ->from(['{{%emailtemplates_templates}}'])
        ->where('id = '.$id)
        ->one();
    return $results;

    }

    public function TemplateByUniqueId($uid){
        $response = [];
        $sites = Craft::$app->sites->getAllSites();
        foreach($sites as $site){
            if($site['primary'] == 1){
                $language_id = $site['id'];
            }
        }
        $response = [];
        $template =  (new \craft\db\Query())
        ->select(['id','name', 'description','from','alias', 'email_template_id'])
        ->from(['{{%emailtemplates_templates}}'])
        ->where(['email_template_id' => $uid])
        ->one();
        
        $response['template'] = $template;
        $response['content'] = $this->getContentById( $template['id'], $language_id);
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

    public function getLanguageOptions(){
        $sites = Craft::$app->sites->getAllSites();
        if($sites){
            $options = ArrayHelper::map($sites, 'name', 'name');
        }else {
            $options = [];
        }
        return $options;
    }

    public function getLanguageValues($content_id = null){
        $results = [];
        if($content_id){
            $results = Craft::$app->sites->getAllSites();
        }
        return array_column($results, 'content_id');
    }

    public function getTemplateOptions(){
        $templates = $this->fetchTemplates();
        if ($templates) {
            $options = ArrayHelper::map($templates, 'id', 'name');
        } else {
            $options = [];
        }
        return $options;
    }

    public function getContentTokens($template_id = null){
        $results = [];
        if($template_id){
            $results = (new \craft\db\Query())
            ->select(['tokens_id'])
            ->from(['{{%emailtemplates_templates}}'])
            ->where('template_id = '.$template_id)
            // ->orderBy(['id', 'desc'])
            ->all();
        }
        return array_column($results, 'tokens_id');
    }

    public function saveContents()
    {
        $data = Craft::$app->getRequest()->post();
        $language = $data['language'];
        $subject = $data['subject'];
        $body = $data['body'];
        $template = $data['template'];
        $siteId = null;
        $sites = Craft::$app->sites->getAllSites();
        foreach($sites as $site){
            if ($site['primary'] == 1){
                $siteId = $site['id'];
            }
        }
        $now = Db::prepareDateForDb(new \DateTime());
        Db::insert('emailtemplates_templatecontent', [
            't_id' => $template,
            'language' => $language,
            'subject' => $subject,
            'body' => $body,
            'siteId' => $siteId,
            'dateCreated' => $now,
            'dateUpdated' => $now,
        ], false);
        return true;
        
    }
    
    public function getPrimarySite(): Site
    {
        if ($this->_primarySite === null) {
            throw new SiteNotFoundException('No primary site exists');
        }

        return $this->_primarySite;
    }

    public function removeContents()
    {
        $data = Craft::$app->getRequest()->get();
            if(isset($data['id'])) {
            $id = $data['id'];
            $dbobject = new Database();
            $connection = $dbobject->getDBConnection();
            $connection->open();
            $response = $connection->createCommand()->delete('emailtemplates_templatecontent', 'id = :id', [':id' => $id ])->execute();
            return $response;
        }
    }

    public function updateContents(){

        $data = Craft::$app->getRequest()->get();
        $id = $data['id'];
        $temp_id = $data['tempid'];

        $results = (new \craft\db\Query())
        ->select(['id','t_id','language', 'subject','body'])
        ->from(['{{%emailtemplates_templatecontent}}'])
        ->where(['t_id'=>$temp_id,'language'=>$id])
        ->all();
        return $results;
    }
    
    public function saveUpdatedContent(){
        $data = Craft::$app->getRequest()->post();
        // $content_id = Craft::$app->getRequest()->get();
        $ids = $data['tempid'];
        $language = $data['lang'];
        $subject = $data['subject'];
        $body = $data['body'];
        $siteId = null;
        $sites = Craft::$app->sites->getAllSites();
        foreach($sites as $site){
            if ($site['primary'] == 1){
                $siteId = $site['id'];
            }
        }
        $now = Db::prepareDateForDb(new \DateTime());
       
        $check = $this->checkEmailContent($ids,$language);
        if($check === 0){
            $siteId = null;
            $sites = Craft::$app->sites->getAllSites();
            foreach($sites as $site){
                if ($site['primary'] == 1){
                    $siteId = $site['id'];
                }
            }
            Db::insert('emailtemplates_templatecontent', [
            't_id' => $ids,
            'language' => $language,
            'subject' => $subject,
            'body' => $body,
            'siteId' => $siteId,
            'dateCreated' => $now,
            'dateUpdated' => $now,
            ], false);
        }
        else{
            Craft::$app->getDb()->createCommand()
            ->update('emailtemplates_templatecontent', [
                                                            'subject' => $subject,
                                                            'body' => $body, 
                                                        ], 
                                                        [
                                                            'and',
                                                            'language = :language',
                                                            't_id = :id'], 
                                                        [
                                                            ':language' => $language,
                                                            ':id' => $ids
                                                            ])->execute();

            return true;
        
        }
        
    }

    public function checkEmailContent($t_id,$lang)
    {
        $results = (new \craft\db\Query())
            ->select(['id','t_id','subject', 'language'])
            ->from(['{{%emailtemplates_templatecontent}}'])
            ->where(['t_id' => $t_id, 'language' => $lang])
            ->all();
            if($results){
                return 1;
            }
            else{
                return 0;
            }
    }

    public function UsedTokens($tokens){
        $token_names = [];
        foreach($tokens as $token){
            $results1 = (new \craft\db\Query())
            ->select(['name','token'])
            ->from(['{{%emailtemplates_tokens}}'])
            ->where('id = '.$token )
            ->all();
            $token_names[] = $results1;
        }
        $new_arr = [];
        foreach($token_names as $t){
            foreach($t as $tk){
                $new_arr[$tk['name']] =$tk['token'];
            }
        }
        return $new_arr;
    }

    public function replaceTokens($data, $tokenValues = []){
        if($tokenValues != null){
            foreach($tokenValues as $key => $val){
                $data = $this->replace_tokens($key, $val, $data);
            }
        }
        return $data;
    }

    // sending mail
    public function getmailDetails($temp_uid,$tokens_data,$to){

        $body = null;
        $subject = null;
        $sites = Craft::$app->sites->getAllSites();
        foreach($sites as $site){
            if($site['primary'] == 1){
                $language_id = $site['id'];
            }
        }
        $template_details = (new \craft\db\Query())
        ->select(['id'])
        ->from(['{{%emailtemplates_templates}}'])
        ->where(['email_template_id' => $temp_uid ])
        ->all();
        foreach($template_details as $temp){
            $temp_id = $temp['id'];
        }
        $mail_details = (new \craft\db\Query())
        ->select(['id','subject','body'])
        ->from(['{{%emailtemplates_templatecontent}}'])
        ->where(['t_id' => $temp_id,'language' => $language_id])
        ->all();
        foreach($mail_details as $temp){
            $body = $temp['body'];
            $subject = $temp['subject'];

        }
        foreach($tokens_data as $key => $val){
            $subject = $this->replace_tokens($key,$val,$subject);
            $body = $this->replace_tokens($key,$val,$body);
        }
        $result = ['body' => $body,
                   'subject' => $subject  ];
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
        $response['template'] = $this->TemplateById($temp_id);
        $response['content'] = $this->getContentById($temp_id, $language_id);
        $used_tokens = $this->getTemplateTokens($temp_id);
        $response['tokens'] = $this->UsedTokens($used_tokens);

        return $response;

    }

    public function getContentById($temp_id, $id){

        return (new \craft\db\Query())
            ->select(['id','t_id','language', 'subject','body'])
            ->from(['{{%emailtemplates_templatecontent}}'])
            ->where(['t_id'=>$temp_id,'language'=>$id])
            ->one();
    }

    public function replace_tokens($token, $replace, $body){
        return str_replace($token, $replace, $body);
    }

    public function getMailData(){
        $data = Craft::$app->getRequest()->post();
        $template_id = Craft::$app->getRequest()->get();
        $t_id = $template_id['id'];
        $to = $data['to'];
        $sub = $data['subject'];
        $body = $data['body'];
        $template_unique_id = $data['template_unique_id'];
        $for_tokens = [];
        $used_tokens = $this->getTemplateTokens($t_id);
        $tokens = $this->UsedTokens($used_tokens);

        foreach($tokens as $token){
            $for_tokens[$token] = $data[$token];
        }
        $response = [];
        $response['template_uniqueid'] = $template_unique_id;
        $response['tokens'] = $for_tokens;
        $response['to'] = $to;
        $response['sub'] = $sub;
        $response['body'] = $body;
        return $response;
    }

    public function getTokenById($id){
        $results = (new \craft\db\Query())
        ->select(['id','name', 'token','token_description'])
        ->from(['{{%emailtemplates_tokens}}'])
        ->where('id = '.$id)
        ->one();
    return $results;
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

        $templateDetails = EmailTemplates::$plugin->emailTemplatesService->TemplateByUniqueId($templateUniqueId);
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
                $error = Craft::t('Email could not be sent', 'email-templates');
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
}
