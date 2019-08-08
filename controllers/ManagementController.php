<?php


namespace app\controllers;


use app\models\ManagementHandler;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ManagementController extends Controller
{
    public function behaviors()
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
                        'actions' => ['index', 'db-erase', 'db-fill'],
                        'roles' => ['manager'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(){
        return $this->render('index');
    }

    /**
     * @return array
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function actionDbErase(){
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            ManagementHandler::eraseDb();
            return ['status' => 1, 'message' => 'База данных полностью очищена'];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionDbFill(){
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            ManagementHandler::fillDb();
            return ['status' => 1, 'message' => 'База данных полностью очищена'];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}