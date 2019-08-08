<?php


namespace app\controllers;


use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\RegistredCountersHandler;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class IndicationController extends Controller
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
                        'actions' => ['power', 'counter', 'membership'],
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
     * @throws Throwable
     */
    public function actionPower($action, $id = null){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(Yii::$app->request->isGet){
        if($action === 'change'){
            $current = DataPowerHandler::findOne($id);
            if (DataPowerHandler::find()->where(['counter_id' => $current->counter_id])->andWhere(['>', 'month', $current->month])->count() > 0) {
                return ['info' => 'Можно изменить только последние заполненные показания'];
            }
            // проверю, изменить можно только последние показания
                $model = DataPowerHandler::findOne($id);

                return ['title' => 'Изменение показаний счётчика', 'html' => $this->renderAjax('change-power', ['model' => $model])];

            }
        }
        if (Yii::$app->request->isPost) {
            if($action === 'delete'){
                return DataPowerHandler::deleteIndication($id);
            }
            elseif ($action === 'change'){
                $model = new DataPowerHandler();
                $model->load(Yii::$app->request->post());
                return $model->changeData();
            }
        }
    }
    /**
     * @param $action
     * @param $id
     * @return array
     * @throws Throwable
     */
    public function actionMembership($action, $id = null){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(Yii::$app->request->isGet){
        if($action === 'change'){
            // проверю, изменить можно только последние показания
                $model = DataMembershipHandler::findOne($id);
                return ['title' => 'Изменение показаний по членским взносам', 'html' => $this->renderAjax('change-membership', ['model' => $model])];

            }
        }
        if (Yii::$app->request->isPost) {
            if ($action === 'change'){
                $model = new DataMembershipHandler();
                $model->load(Yii::$app->request->post());
                return $model->changeData();
            }
        }
    }

    public function actionCounter($action, $id = null){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(Yii::$app->request->isGet){
            switch ($action){
                case 'add' :
                    $model = new RegistredCountersHandler(['scenario' => RegistredCountersHandler::SCENARIO_REGISTER_COUNTER]);
                    $model->cottage_id = $id;
                    return ['title' => 'Добавление счётчика', 'html' => $this->renderAjax('add-counter', ['model' => $model])];
            }
        }
        if(Yii::$app->request->isPost){
            switch ($action){
                case 'disable' :
                    return RegistredCountersHandler::disable($id);
                case 'delete' :
                    return RegistredCountersHandler::deleteItem($id);
                case 'enable' :
                    return RegistredCountersHandler::enable($id);
                case 'add' :
                    $model = new RegistredCountersHandler(['scenario' => RegistredCountersHandler::SCENARIO_REGISTER_COUNTER]);
                    $model->load(Yii::$app->request->post());
                    return $model->register();

            }
        }

    }
}