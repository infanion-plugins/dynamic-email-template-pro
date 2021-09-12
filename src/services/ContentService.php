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
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

/**
 * ContentService Service
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
class ContentService extends Component
{
    // Public Methods
    // =========================================================================

    public function getLanguageOptions(){
        $sites = Craft::$app->sites->getAllSites();
        if($sites){
            $options = ArrayHelper::map($sites, 'name', 'name');
        }else {
            $options = [];
        }
        return $options;
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

    public function fetchTemplates()
    {
        $results = (new \craft\db\Query())
            ->select(['id','name', 'description'])
            ->from(['{{%emailtemplates_templates}}'])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        return $results;
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
       
        $check = $this->checkEmailContent($ids, $language);
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

    public function getContentById($temp_id, $id){

        return (new \craft\db\Query())
            ->select(['id','t_id','language', 'subject','body'])
            ->from(['{{%emailtemplates_templatecontent}}'])
            ->where(['t_id'=>$temp_id,'language'=>$id])
            ->one();
    }

}
