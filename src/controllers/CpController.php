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
class CpController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    // protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    public function actionMailTemplates()
    {

        // $this->requirePermission('manageTemplates');

        $session = Craft::$app->getSession();
        try{
            $results = EmailTemplates::$plugin->emailTemplatesService->fetchTemplates();
            return $this->renderTemplate(                                                                                                                 
                'email-templates/mailtemplates',                                                                                                                              
                [                                                                                                                                                     
                    'results' => $results,
                ]                                                                                                                                                     
            );
        }
        catch(Exception $e) {
            $session->error = $e->getMessage();
        }

    }
    
    public function actionAddTemplate()
    {
        // $this->requirePermission('manageTemplates');
        $resData = [];

        $options = EmailTemplates::$plugin->emailTemplatesService->getOptions();
        $values = EmailTemplates::$plugin->emailTemplatesService->getTemplateTokens();

        $resData['tokens']['options'] =  $options;
        $resData['tokens']['values'] =  $values;
        $resData['templateId'] =  '';

        return $this->renderTemplate(                                                                                                                 
            'email-templates/mailtemplates/add.html', 
            [
                'data' => $resData
            ]                                                                                                                                                                                                                                                                                  
        );
    }
  
 
    public function actionSaveTemplate()
    {
        // $this->requirePermission('manageTemplates');

        $resData = [];
        $data = Craft::$app->getRequest()->post();
        $session = Craft::$app->getSession();
        $resData['name'] = $data['name'];
        $template_unique_id = $data['template_unique_id'];
        $resData['from'] = $data['from'];
        $resData['alias'] = $data['alias'];
        $resData['template_unique_id'] = preg_replace('/\s+/', '_', $template_unique_id);
        $resData['tokens']['options'] = EmailTemplates::$plugin->emailTemplatesService->getOptions($data['templateId']);
        $resData['tokens']['values'] =  $data['tokens'];
        $resData['description'] = $data['description'];
        $resData['templateId'] = $data['templateId'];
        // $transaction = Craft::$app->getDb()->beginTransaction();   
        try {
                $result = EmailTemplates::$plugin->emailTemplatesService->saveTemplates();
                $session->setNotice(Craft::t('email-templates', 'Template '.$data['name'].' saved successfully'));
            }
        catch (Exception $e){
            // $transaction->rollBack();
            // $session->error = $e->getMessage();
            if(strlen($data['description'])>250){
                $message = "Description should not exceed the limit 250 characters";
            }else{
                $message = "Template already exists with name ". $data['name'].", try a different name";
            }
            $session->error = $message;
            return $this->renderTemplate(                                                                                                                 
                'email-templates/mailtemplates/add',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                              
                ]                                                                                                                                                     
            );
        }

        $results = EmailTemplates::$plugin->emailTemplatesService->fetchTemplates();

        return $this->redirect('email-templates/mailtemplates');
     
    }

    public function actionUpdateTemplates()
    {
        // $this->requirePermission('manageTemplates');

        $data = Craft::$app->getRequest()->get();
        $id = $data['id'];
        $session = Craft::$app->getSession();
        $resData = [];
        try{
            $result = EmailTemplates::$plugin->emailTemplatesService->TemplateById($id);

            $options = EmailTemplates::$plugin->emailTemplatesService->getOptions($result['id']);
            $values = EmailTemplates::$plugin->emailTemplatesService->getTemplateTokens($id);

            $resData['tokens']['options'] =  $options;
            $resData['tokens']['values'] =  $values;
            $resData['name'] = $result['name'];
            $resData['from'] = $result['from'];
            $resData['alias'] = $result['alias'];
            $resData['template_unique_id'] = $result['email_template_id'];
            $resData['description'] = $result['description'];
            $resData['templateId'] = $result['id'];

            return $this->renderTemplate(                                                                                                                 
                'email-templates/mailtemplates/add',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        catch(Exception $e) {
            // $session->error = $e->getMessage();
            
        }
    }

    public function actionRemoveTemplates()
    {
        // $this->requirePermission('manageTemplates');
        $data_name = Craft::$app->getRequest()->get();
        $data = Craft::$app->getRequest()->post();
        $session = Craft::$app->getSession();
        try{
            $template = EmailTemplates::$plugin->emailTemplatesService->TemplateById($data_name['id']);
            $result = EmailTemplates::$plugin->emailTemplatesService->removeTemplates();
            
            $session->setNotice(Craft::t('email-templates', 'Template '.$template['name']. ' removed successfully'));
            $destinationUrl = isset($_GET['destination']) ? $_GET['destination'] : false;
            if ($destinationUrl) {
                Craft::$app->getResponse()->redirect($destinationUrl);
                Craft::$app->end();
                return;
            }
        }
        catch(Exception $e) {
            $session->error = $e->getMessage();
        }
    }


    public function actionFetchTemplates()
    {
        $data = Craft::$app->getRequest()->post();
        $session = Craft::$app->getSession();
        try{
            $result = EmailTemplates::$plugin->emailTemplatesService->fetchTemplates($data);
            $results = json_encode($result);
            return $results;
        }
        catch(Exception $e) {
            $session->error = $e->getMessage();
        }
    }

}
