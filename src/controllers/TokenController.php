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
use Exception;

/**
 * Token Controller
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
class TokenController extends Controller
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
            $results = DynamicEmailTemplatePro::$plugin->tokenService->fetchToken();
            return $this->renderTemplate(                                                                                                                 
                'dynamic-email-template-pro/_tokens/index',                                                                                                                              
                [                                                                                                                                                     
                    'results' => $results,  
                ]                                                                                                                                                     
            );
        }
        catch(Exception $e) {
            // $session->error = $e->getMessage();
        }
    }
   
    public function actionAddTokens()
    {
        $resData = [];
        $resData['id'] = null;
        
        return $this->renderTemplate(                                                                                                                 
            'dynamic-email-template-pro/_tokens/_edit', 
            [
                'data'  => $resData,
            ]                                                                                                                                                                                                                                                                                  
        );
    }

    
    public function actionSaveToken()
    {
        $data = Craft::$app->getRequest()->post();
        $resData = [];
        $resData['name'] = $data['name'];
        $resData['token'] = $data['token'];
        $resData['token_description'] = $data['description'];
        $resData['id'] = $data['id'];

        $session = Craft::$app->getSession();
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try {
            $result = DynamicEmailTemplatePro::$plugin->tokenService->saveTokens();
            $session->setNotice(Craft::t('dynamic-email-template-pro', 'Token ' .$data['name'].' saved successfully'));
        }
        catch (Exception $e){

            if(strlen($data['description'])>250){
                $message = "Description should not exceed the limit 250 characters";
            }else{
                $message = "Token already exists with name ". $data['name'].", try a different name";
            }
            $session->error = $message;
            // $transaction->rollBack();
            return $this->renderTemplate(                                                                                                                 
                'dynamic-email-template-pro/_tokens/_edit',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        return $this->redirect('dynamic-email-template-pro');
    }

   
    public function actionRemove()
    {
        $data = Craft::$app->getRequest()->post();
        $data_name = Craft::$app->getRequest()->get();
        $session = Craft::$app->getSession();
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try{
            // $tokens = DynamicEmailTemplatePro::$plugin->tokenService->getTokenById($data_name['id']);
            $message = DynamicEmailTemplatePro::$plugin->tokenService->removeToken();
            $destinationUrl = isset($_GET['destination']) ? $_GET['destination'] : false;
            if ($destinationUrl) {
                Craft::$app->getResponse()->redirect($destinationUrl);
                Craft::$app->end();
                return;
            }
        }
        catch(Exception $e) {
            // $transaction->rollBack();
            // $session->error = $e->getMessage();
        }

    }

    public function actionUpdate()
    {
        $data = Craft::$app->getRequest()->get();
        $session = Craft::$app->getSession();
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try{
            $results = DynamicEmailTemplatePro::$plugin->tokenService->updateToken();
            return $this->renderTemplate(                                                                                                                 
                'dynamic-email-template-pro/_tokens/_edit',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $results,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        catch(Exception $e) {
            // $transaction->rollBack();
            // $session->error = $e->getMessage();
        }

    }

}
