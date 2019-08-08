<?php


namespace app\controllers;


use app\models\database\BillsHandler;
use app\models\database\EmailsHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\FileAttachment;
use app\models\utils\EmailHandler;
use app\models\utils\PDFHandler;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class EmailController extends Controller
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
                        'actions' => ['send-bill'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $billId
     * @throws ExceptionWithStatus
     */
    public function actionSendBill($billId){
        EmailHandler::sendBIllInfo($billId);
    }
}