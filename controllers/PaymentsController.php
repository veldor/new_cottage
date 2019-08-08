<?php


namespace app\controllers;


use app\models\Bill;
use app\models\database\BankTransactionsHandler;
use app\models\database\BillsHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataSingleHandler;
use app\models\database\TransactionsHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\Pay;
use app\models\PowerHandler;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentsController extends Controller
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
                        'actions' => ['edit', 'single', 'bill', 'show-bill', 'show-transaction', 'check-undistributed', 'distribute-bill', 'pay', 'get-transaction', 'confirm-bank-transaction'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $action
     * @param $id
     * @return array
     * @throws Exception
     */
    public function actionEdit($action, $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isGet) {
            switch ($action) {
                case 'create-bill' :
                    $model = new Bill(['scenario' => Bill::SCENARIO_CREATE, 'cottageId' => $id]);
                    $model->fill();
                    // если участок дополнительный- верну предложение выставить счёт главному участку
                if($model->cottageInfo->is_additional){
                    return ['info' => 'Участок является дополнительным. Выставите счёт основному.'];
                }
                    $additionalModel = null;
                    // если есть дополнительный участок- найду его
                    $additionalCottage = CottagesHandler::getAdditionalCottage($id);
                    if(!empty($additionalCottage) && !$additionalCottage->is_different_owner){
                        $additionalModel = new Bill(['scenario' => Bill::SCENARIO_CREATE, 'cottageId' => $additionalCottage->id]);
                        $additionalModel->fill();
                    }
                    return ['title' => 'Создание нового счёта', 'html' => $this->renderAjax('create_bill', ['model' => $model, 'additionalModel' => $additionalModel])];
                case 'fill-energy':
                    $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_FILL, 'cottageId' => $id]);
                    if ($model->fill()) {
                        return ['title' => 'Заполнение данных электроэнергии', 'html' => $this->renderAjax('fill_power', ['model' => $model])];
                    } else {
                        return ['status' => 2, 'message' => 'Данные уже введены.'];
                    }
            }
            return ['info' => 'Действие не назначено'];
        } elseif (Yii::$app->request->isPost) {
            switch ($action) {
                case 'fill-energy':
                    $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_FILL]);
                    $model->load(Yii::$app->request->post());
                    return $model->insertData();
            }
        }
        return ['info' => 'Действие не назначено'];
    }

    /**
     * @param $action
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionSingle($action, $id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isGet) {
            switch ($action) {
                case 'add':
                    $model = new DataSingleHandler(['scenario' => DataSingleHandler::SCENARIO_ADD]);
                    $model->cottage_number = $id;
                    return ['title' => 'Добавление нового разового платежа', 'html' => $this->renderAjax('create_single', ['model' => $model])];
            }
        } elseif (Yii::$app->request->isPost) {
            switch ($action) {
                case 'add':
                    $model = new DataSingleHandler(['scenario' => DataSingleHandler::SCENARIO_ADD]);
                    $model->load(Yii::$app->request->post());
                    return $model->createPay();
            }
        }
        throw new NotFoundHttpException();
    }

    /**
     * @param $action
     * @return array
     * @throws Exception
     */
    public function actionBill($action){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isPost){
            if($action === 'create'){
                $model = new Bill(['scenario' => Bill::SCENARIO_CREATE]);
                $model->load(Yii::$app->request->post());
                return $model->createBill();
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @param $id
     * @return string
     * @throws ExceptionWithStatus
     */
    public function actionShowBill($id){
        $info = BillsHandler::getBillInfo($id);
        return $this->render('bill_information', ['bill_info' => $info]);
    }

    /**
     * @param $id
     * @return string
     * @throws ExceptionWithStatus
     */
    public function actionShowTransaction($id){
        $info = TransactionsHandler::getTransactionInfo($id);
        return $this->render('transaction_information', ['transaction_info' => $info]);
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionCheckUndistributed(){
        $undistributed = BillsHandler::findOne(['is_undistributed' => 1]);
        if(!empty($undistributed)){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['id' => $undistributed->id];
        }
        else
            return null;
    }

    /**
     * @param $id
     * @return string|array
     * @throws ExceptionWithStatus
     * @throws NotFoundHttpException
     */
    public function actionDistributeBill($id = null){
        if(Yii::$app->request->isGet){
            $info = BillsHandler::getBillInfo($id);
            $matrix = new TransactionsHandler(['scenario' => TransactionsHandler::SCENARIO_DISTRIBUTE]);
            return $this->render('bill_distribute', ['bill_info' => $info, 'matrix' => $matrix]);
        }
        elseif(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $matrix = new TransactionsHandler(['scenario' => TransactionsHandler::SCENARIO_DISTRIBUTE]);
            $matrix->load(Yii::$app->request->post());
            return $matrix->distribute();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @param null $id
     * @param null $bankId
     * @return string|array
     * @throws ExceptionWithStatus
     * @throws NotFoundHttpException
     */
    public function actionPay($id = null, $bankId = null){
        if(Yii::$app->request->isGet){
            $info = BillsHandler::getBillInfo($id, $bankId);
            $model = new Pay(['scenario' => Pay::SCENARIO_TYPICAL]);
            return $this->render('pay', ['bill_info' => $info, 'model' => $model]);
        }
        elseif(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $matrix = new Pay(['scenario' => Pay::SCENARIO_TYPICAL]);
            $matrix->load(Yii::$app->request->post());
            return $matrix->pay();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @param $id
     * @return BankTransactionsHandler|null
     */
    public function actionGetTransaction($id){
        if(!empty($id)){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return BankTransactionsHandler::findOne($id);
        }
        return null;
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionConfirmBankTransaction(){
        if(Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}