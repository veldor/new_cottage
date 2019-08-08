<?php


namespace app\controllers;

use app\models\database\FinesHandler;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class FinesController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['change', 'lock'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionChange($action, $finesId){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if($action === 'disable'){
            return FinesHandler::disableFine($finesId);
        }
        elseif ($action === 'enable'){
            return FinesHandler::enableFine($finesId);
        }
    }
    public function actionLock($action, $finesId = null){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(Yii::$app->request->isGet){
            if($action === 'lock-fine'){
                return FinesHandler::lockFine($finesId);
            }
        }
        if(Yii::$app->request->isPost){
            if($action === 'unlock-fine'){
                return FinesHandler::unlock($finesId);
            }
            $model = new FinesHandler();
            $model->load(Yii::$app->request->post());
            return $model->lock();
        }
    }
}