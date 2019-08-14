<?php


namespace app\controllers;


use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\DepositHandler;
use app\models\database\FinesHandler;
use app\models\exceptions\ExceptionWithStatus;
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

    /**
     * @param $type
     * @param $id
     * @return array
     * @throws ExceptionWithStatus
     */
    public function actionFloat($type, $id)
    {
        // верну данные для отображения
        Yii::$app->response->format = Response::FORMAT_JSON;
        switch ($type) {
            case 'power':
                return DataPowerHandler::periodInfo($id);
            case 'membership':
                return DataMembershipHandler::periodInfo($id);
            case 'target':
                return DataTargetHandler::periodInfo($id);
            case 'single':
                return DataSingleHandler::periodInfo($id);
            case 'deposit':
                return DepositHandler::cottageInfo($id);
            case 'fines':
                return FinesHandler::periodInfo($id);
        }
    }
}