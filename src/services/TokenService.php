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
use ipcraft\dynamicemailtemplatepro\utilities\Database;
use craft\helpers\Db;

/**
 * TokenService Service
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
class TokenService extends Component
{
    // Public Methods
    // =========================================================================

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

    public function getTokenById($id){
        $results = (new \craft\db\Query())
        ->select(['id','name', 'token','token_description'])
        ->from(['{{%emailtemplates_tokens}}'])
        ->where('id = '.$id)
        ->one();
    return $results;
    }

    public function removeToken()
    {
        $data = Craft::$app->getRequest()->get();
        $usedtoken = $this->checkToken($data['id']);
        $session = Craft::$app->getSession();
        $tokens = $this->getTokenById($data['id']);

        if($usedtoken){
            $template = DynamicEmailTemplatePro::$plugin->templateService->TemplateById($usedtoken[0]['template_id']);
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
                $session->setNotice(Craft::t('dynamic-email-template-pro',  'Token '.$tokens['name'].' deleted successfully'));
                return $response;
            }
        }
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

    public function validateToken($token){
        $token = $this->TokenstartsWith($token,'!');
        return $this->TokenendsWith($token,'!');
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
}
