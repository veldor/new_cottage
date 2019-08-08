<?php


namespace app\controllers;

use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataTargetHandler;
use app\models\selection_classes\TariffsInfo;
use app\models\TariffsHandler;
use app\models\utils\TimeHandler;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TariffsController extends Controller
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
                        'actions' => ['index', 'fill', 'details'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function actionIndex(){
        // покажу тарифы на текущий период
        /** @var TariffsInfo $data */

        $model = new TariffsHandler(['scenario' => TariffsHandler::SCENARIO_SEARCH_TARIFFS]);
        if(Yii::$app->request->isPost){
            $model->load(Yii::$app->request->post());
            $data = $model->fillTariffs();
        }
        else{
            $data = TariffsHandler::getCurrent();
        }
        return $this->render('show', ['data' => $data, 'model' => $model]);
    }
    public function actionFill(){
        $model = new TariffsHandler(['scenario' => TariffsHandler::SCENARIO_FILL]);
        if(Yii::$app->request->isPost){
            $model->load(Yii::$app->request->post());
            $model->saveTariffs();
        }
        $model->getLastFilled();
        return $this->render('fill', ['model' => $model]);
    }

    /**
     * @param $type
     * @param $period
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionDetails($type, $period){
        Yii::$app->response->format = Response::FORMAT_JSON;
        switch ($type){
            case 'membership':
                $data = DataMembershipHandler::find()->where(['quarter' => $period])->orderBy('cottage_number')->all();
                return ['status' => 1, 'header' => 'Отчёт о членских взносах за ' . TimeHandler::getFullFromShotQuarter($period),  'view' => $this->renderAjax('membershipStatistics', ['data' => $data])];
            case 'target':
                $data = DataTargetHandler::find()->where(['year' => $period])->orderBy('cottage_number')->all();
                return ['status' => 1, 'header' => 'Отчёт о целевых взносах за ' . $period . ' год.',  'view' => $this->renderAjax('targetStatistics', ['data' => $data])];
            case 'energy':
                $data = DataPowerHandler::find()->where(['month' => $period])->orderBy('cottage_number')->all();
                return ['status' => 1, 'header' => 'Отчёт о потреблённой электроэнергии за ' . TimeHandler::getFullFromShotMonth($period) . '.',  'view' => $this->renderAjax('energyStatistics', ['data' => $data])];
        }
        throw new NotFoundHttpException();
    }
}