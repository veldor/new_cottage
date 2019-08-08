<?php


namespace app\controllers;


use app\models\database\BillsHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\BillInfo;
use yii\filters\AccessControl;
use yii\web\Controller;

class PrintController extends Controller
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
                        'actions' => ['bill'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $billId
     * @return string
     * @throws ExceptionWithStatus
     */
    public function actionBill($billId){
        $this->layout = false;
        /** @var BillInfo $info */
        $info = BillsHandler::getBankInfo($billId);
        $info['billInfo']->bill->is_invoice_printed = 1;
        $info['billInfo']->bill->save();
        return $this->render('/email/bank-invoice-pdf', ['info' => $info]);
    }
}