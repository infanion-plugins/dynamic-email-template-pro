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
use ipcraft\emailtemplates\Sendemail;
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
class TokensController extends Controller
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
  
    public function actionIndex()
    {
        $session = Craft::$app->getSession();
        try{
            $results = EmailTemplates::$plugin->emailTemplatesService->fetchToken();
            return $this->renderTemplate(                                                                                                                 
                'email-templates/tokens/index',                                                                                                                              
                [                                                                                                                                                     
                    'results' => $results,  
                ]                                                                                                                                                     
            );
        }
        catch(Exception $e) {
            $session->error = $e->getMessage();
        }
    }
   
    public function actionAddTokens()
    {
        $resData = [];
        $resData['id'] = null;
        
        return $this->renderTemplate(                                                                                                                 
            'email-templates/tokens/add', 
            [
                'data'  => $resData,
            ]                                                                                                                                                                                                                                                                                  
        );
    }

    
    public function actionSaveToken()
    {
        // $this->requirePermission('manageTokens');
        $data = Craft::$app->getRequest()->post();

        $resData = [];
        $resData['name'] = $data['name'];
        $resData['token'] = $data['token'];
        $resData['token_description'] = $data['description'];
        $resData['id'] = $data['id'];

        $session = Craft::$app->getSession();
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try {
            $result = EmailTemplates::$plugin->emailTemplatesService->saveTokens();
            $session->setNotice(Craft::t('email-templates', 'Token ' .$data['name'].' saved successfully'));
        }
        catch (Exception $e){
            // $session->error = $e->getMessage();
            if(strlen($data['description'])>250){
                $message = "Description should not exceed the limit 250 characters";
            }else{
                $message = "Token already exists with name ". $data['name'].", try a different name";
            }
            $session->error = $message;
            // $transaction->rollBack();
            return $this->renderTemplate(                                                                                                                 
                'email-templates/tokens/add',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        return $this->redirect('email-templates');
    }

   
    public function actionRemove()
    {
        // $this->requirePermission('manageTokens');
        $data = Craft::$app->getRequest()->post();
        $data_name = Craft::$app->getRequest()->get();
        $session = Craft::$app->getSession();
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try{
            $tokens = EmailTemplates::$plugin->emailTemplatesService->getTokenById($data_name['id']);
            $message = EmailTemplates::$plugin->emailTemplatesService->removeToken();
            // $session->setNotice(Craft::t('email-templates', $message));
            $destinationUrl = isset($_GET['destination']) ? $_GET['destination'] : false;
            if ($destinationUrl) {
                Craft::$app->getResponse()->redirect($destinationUrl);
                Craft::$app->end();
                return;
            }
        }
        catch(Exception $e) {
            // $transaction->rollBack();
            $session->error = $e->getMessage();
        }

    }

    public function actionUpdate()
    {
        // $this->requirePermission('manageTokens');
        $data = Craft::$app->getRequest()->get();
        $session = Craft::$app->getSession();
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try{
            $results = EmailTemplates::$plugin->emailTemplatesService->updateToken();
            return $this->renderTemplate(                                                                                                                 
                'email-templates/tokens/add',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $results,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        catch(Exception $e) {
            // $transaction->rollBack();
            $session->error = $e->getMessage();
        }

    }


}
