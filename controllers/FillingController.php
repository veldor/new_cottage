<?php


namespace app\controllers;


use app\models\Registry;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\UploadedFile;

class FillingController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/accessError', 403);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'registry'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(){
        $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
        $registryModel->getUnhandled();
        return $this->render('filling', ['model' => $registryModel]);
    }
    public function actionRegistry(){
        if(Yii::$app->request->isPost) {
            $errorMessage = null;
            $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
            $registryModel->file = UploadedFile::getInstances($registryModel, 'file');
            $registryModel->handleRegistry();
            return $this->redirect('/filling', 301);
        }
    }
}