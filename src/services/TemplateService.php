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
use ipcraft\dynamicemailtemplatepro\utilities\Database;

/**
 * TemplateService Service
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
class TemplateService extends Component
{
    // Public Methods
    // =========================================================================

    public function fetchTemplates()
    {
        $results = (new \craft\db\Query())
            ->select(['id','name', 'description'])
            ->from(['{{%emailtemplates_templates}}'])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        return $results;
    }

    
    public function getOptions(){
        $tokens = DynamicEmailTemplatePro::$plugin->tokenService->fetchToken();
        if ($tokens) {
            $options = ArrayHelper::map($tokens, 'id', 'name');
        } else {
            $options = [];
        }
        return $options;

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
            ->where(['email_template_id' => $email_template_id])
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


    public function TemplateById($id){
        $results = (new \craft\db\Query())
        ->select(['id','name', 'description','from','alias', 'email_template_id'])
        ->from(['{{%emailtemplates_templates}}'])
        ->where('id = '.$id)
        ->one();
    return $results;

    }


    public function removeTemplates()
    {
        $data = Craft::$app->getRequest()->get();
        if(isset($data['id'])) {
            $id = $data['id'];
            $dbobject = new Database();
            $connection = $dbobject->getDBConnection();
            $connection->open();
            $status = $connection->createCommand()->delete('emailtemplates_templatestokens', 
                'template_id = :template_id', [':template_id' => $id ])->execute();
            
            $response = $connection->createCommand()->delete('emailtemplates_templates', 'id = :id', [':id' => $id ])->execute();
            return $response;
        }
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
        $response['content'] = DynamicEmailTemplatePro::$plugin->contentService->getContentById( $template['id'], $language_id);
        return $response;
    }
}
