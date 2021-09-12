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
 * Content Controller
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

    // public function actionAddContent()
    // {
    //     $resData = [];
    //     $Language_options = DynamicEmailTemplatePro::$plugin->contentService->getLanguageOptions();
    //     $Template_options = DynamicEmailTemplatePro::$plugin->contentService->getTemplateOptions();
    //     $resData['lang']['options'] =  $Language_options;
    //     $resData['lang']['values'] =  $Language_options;
    //     $resData['temp']['options'] =  $Template_options;
    //     $resData['temp']['values'] =  $Template_options;
    //     return $this->renderTemplate(                                                                                                                 
    //         'email-templates/mailcontents/add.html', 
    //         [
    //             'data' => $resData
    //         ]                                                                                                                                                                                                                                                                                  
    //     );
    // }

   
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
                $result = DynamicEmailTemplatePro::$plugin->contentService->saveContents();
            }
        catch (Exception $e){
            // $session->error = $e->getMessage();
            return $this->renderTemplate(                                                                                                                 
                'dynamic-email-template-pro/_content/_edit',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                              
                ]                                                                                                                                                     
            );
        }
        $results = DynamicEmailTemplatePro::$plugin->contentService->fetchTemplates();
        return $this->renderTemplate(                                                                                                                 
            'dynamic-email-template-pro/_templates',                                                                                                                              
            [                                                                                                                                                     
                'results' => $results,                                                                                                                              
            ]                                                                                                                                                     
        );
    }

  

    public function actionUpdateContents()
    {
        $resData = [];

        $Language_options =  DynamicEmailTemplatePro::$plugin->contentService->getLanguageOptions();
        $Template_options =  DynamicEmailTemplatePro::$plugin->contentService->getTemplateOptions();
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
        $tokens = DynamicEmailTemplatePro::$plugin->contentService->getTemplateTokens($lang_data['tempid']);
        $used_tokens = DynamicEmailTemplatePro::$plugin->contentService->UsedTokens($tokens);
        
        try{
            $results = DynamicEmailTemplatePro::$plugin->contentService->updateContents();
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
                'dynamic-email-template-pro/_content/_edit',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData,                                                                                                                          
                ]                                                                                                                                                     
            );
            
        }
        catch(Exception $e) {
            // $session->error = $e->getMessage();
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
            $result = DynamicEmailTemplatePro::$plugin->contentService->saveUpdatedContent();
        }
        catch (Exception $e){
            // $session->error = $e->getMessage();
            return $this->renderTemplate(                                                                                                                 
                'dynamic-email-template-pro/_content/_edit',                                                                                                                              
                [                                                                                                                                                     
                    'data' => $resData                                                                                                                             
                ]                                                                                                                                                     
            );
        }

        $results = DynamicEmailTemplatePro::$plugin->contentService->fetchTemplates();

        return $this->redirect('dynamic-email-template-pro/templates');
    }
}
