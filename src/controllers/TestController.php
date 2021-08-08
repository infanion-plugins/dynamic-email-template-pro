<?php
namespace ipcraft\emailtemplates\controllers;

use ipcraft\emailtemplates\EmailTemplates;

use Craft;
use craft\web\Controller;
use Exception;

class TestController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionTestEmail()
    {
        $data = Craft::$app->getRequest()->post();

        $session = Craft::$app->getSession();
        try{
            return EmailTemplates::$plugin->emailTemplatesService->getTemplateTosendMail($id, $lang);

            // return EmailTemplates::$plugin->emailService->exampleService($id, $lang);

        }
        catch(Exception $e) {
            $session->error = $e->getMessage();
        }
    }

   

}
