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
class ContentController extends Controller
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

    
    public function actionAddContent()
    {
        $resData = [];
        $Language_options = EmailTemplates::$plugin->emailTemplatesService->getLanguageOptions();
        $Template_options = EmailTemplates::$plugin->emailTemplatesService->getTemplateOptions();
        $resData['lang']['options'] =  $Language_options;
        $resData['lang']['values'] =  $Language_options;
        $resData['temp']['options'] =  $Template_options;
        $resData['temp']['values'] =  $Template_options;
        return $this->renderTemplate(                                                                                                                 
            'email-templates/mailcontents/add.html', 
            [
                'data' => $resData
            ]                                                                                                                                                                                                                                                                                  
        );
    }

   
    public function actionSaveContent()
    {
        $data = Craft::$app->getRequest()->post();
        $session = Craft::$app->getSession();
        $resData = [];
        $resData['lang'] = $data['lang'];
        $resData['subject'] = $data['subject'];
        $resData['body'] = $data['body'];
        $resData['tempid'] = $data['tempid'];
        try {
                $result = EmailTemplates::$plugin->emailTemplatesService->saveContents();
            }
        catch (Exception $e){
            $session->error = $e->getMessage();
            return $this->renderTemplate(                                                                                                                 
                'email-templates/mailtemplates/add',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        $results = EmailTemplates::$plugin->emailTemplatesService->fetchTemplates();
        return $this->renderTemplate(                                                                                                                 
            'email-templates/mailtemplates/index',                                                                                                                              
            [                                                                                                                                                     
                'results' => $results,                                                                                                                              
            ]                                                                                                                                                     
        );
    }

  

    public function actionUpdateContents()
    {
        // $this->requirePermission('manageContent');
        $resData = [];

        $Language_options = EmailTemplates::$plugin->emailTemplatesService->getLanguageOptions();
        $Template_options = EmailTemplates::$plugin->emailTemplatesService->getTemplateOptions();
        $resData['lang']['options'] =  $Language_options;
        $resData['lang']['values'] =  $Language_options;
        $resData['temp']['options'] =  $Template_options;
        $resData['temp']['values'] =  $Template_options;
        $data = Craft::$app->getRequest()->post();
        $lang_data = Craft::$app->getRequest()->get();
        $l_id = $lang_data['id'];
        $resData['lang_id'] =  $l_id;
        $temp_id = $lang_data['tempid'];
        $session = Craft::$app->getSession();
        $tokens = EmailTemplates::$plugin->emailTemplatesService->getTemplateTokens($lang_data['tempid']);
        $used_tokens = EmailTemplates::$plugin->emailTemplatesService->UsedTokens($tokens);
        
        try{
            $results = EmailTemplates::$plugin->emailTemplatesService->updateContents();
            if($results){
                foreach($results as $res){
                    $resData['result']['subject'] =  $res['subject'];
                    $resData['result']['body'] =  $res['body'];
                    $resData['result']['t_id'] =  $res['t_id'];
                    $resData['result']['tokens'] =  $used_tokens;
                }
            }
            else{
                $resData['result']['subject'] =  null;
                $resData['result']['body'] =  null;
                $resData['result']['t_id'] =  $temp_id;
                $resData['result']['tokens'] =  $used_tokens;

            }
            return $this->renderTemplate(                                                                                                                 
                'email-templates/mailcontents/update.html',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,
                    'manageTokens' => Craft::$app->user->checkPermission('manageTokens'),
                    'manageTemplates' => Craft::$app->user->checkPermission('manageTemplates'),
                    'manageContent' =>    Craft::$app->user->checkPermission('manageContent'),                                                                                                                            
                ]                                                                                                                                                     
            );
            
        }
        catch(Exception $e) {
            $session->error = $e->getMessage();
        }

    }

    public function actionEditContent()
    {
        $data = Craft::$app->getRequest()->post();
        $resData = [];
        $resData['subject'] = $data['subject'];
        $resData['body'] = $data['body'];
        $resData['lang'] = $data['lang'];
        $resData['tempid'] = $data['tempid'];
        $session = Craft::$app->getSession();

        try {
            $result = EmailTemplates::$plugin->emailTemplatesService->saveUpdatedContent();
        }
        catch (Exception $e){
            $session->error = $e->getMessage();
            return $this->renderTemplate(                                                                                                                 
                'email-templates/mailcontents/update',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,
                    'manageTokens' => Craft::$app->user->checkPermission('manageTokens'),
                    'manageTemplates' => Craft::$app->user->checkPermission('manageTemplates'),
                    'manageContent' =>    Craft::$app->user->checkPermission('manageContent'),                                                                                                                               
                ]                                                                                                                                                     
            );
        }

        $results = EmailTemplates::$plugin->emailTemplatesService->fetchTemplates();

        return $this->redirect('email-templates/mailtemplates');
    }

}
