<?php


namespace app\controllers;


use app\models\database\DataPowerHandler;
use app\models\database\DepositHandler;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class InfoController extends Controller
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
                        'actions' => ['float'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionFloat($type, $id)
    {
        // верну данные для отображения
        Yii::$app->response->format = Response::FORMAT_JSON;
        switch ($type) {
            case 'power':
                return DataPowerHandler::periodInfo($id);
            case 'deposit':
                return DepositHandler::cottageInfo($id);
        }
    }
}