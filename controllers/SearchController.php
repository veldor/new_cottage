<?php


namespace app\controllers;


use app\models\Search;
use app\models\SearchCottages;
use app\models\SearchTariffs;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\widgets\ActiveForm;

class SearchController extends Controller
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
                        'actions' => ['index'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Search(['scenario' => Search::SCENARIO_BILLS_SEARCH]);
            $model->load(Yii::$app->request->post());
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->isGet) {
            $model = new Search(['scenario' => Search::SCENARIO_BILLS_SEARCH]);
            $tariffsSearch = new SearchTariffs(['scenario' => SearchTariffs::SCENARIO_TARIFFS_SEARCH]);
            $cottagesSearch = new SearchCottages(['scenario' => SearchCottages::SCENARIO_COTTAGES_SEARCH]);
            return $this->render('search', ['settings' => $model, 'searchTariffs' => $tariffsSearch, 'searchCottages' => $cottagesSearch, 'result' => null, 'activeSearch' => null]);
        }
        if (Yii::$app->request->isPost) {
            $search = new Search(['scenario' => Search::SCENARIO_BILLS_SEARCH]);
            $tariffsSearch = new SearchTariffs(['scenario' => SearchTariffs::SCENARIO_TARIFFS_SEARCH]);
            $cottagesSearch = new SearchCottages(['scenario' => SearchCottages::SCENARIO_COTTAGES_SEARCH]);
            if (!empty(Yii::$app->request->post('SearchTariffs'))) {
                $activeSearch = $tariffsSearch;
                $activeSearchName = 'tariffsSearch';
            } elseif (!empty(Yii::$app->request->post('Search'))) {
                $activeSearch = $search;
                $activeSearchName = 'cashSearch';
            } elseif (!empty(Yii::$app->request->post('SearchCottages'))) {
                $activeSearch = $cottagesSearch;
                $activeSearchName = 'cottagesSearch';
            }
            $activeSearch->load(Yii::$app->request->post());
            if ($activeSearch->validate()) {
                $result = $activeSearch->doSearch();
                return $this->render('search', ['settings' => $search, 'searchTariffs' => $tariffsSearch, 'searchCottages' => $cottagesSearch, 'result' => $result, 'activeSearch' => $activeSearchName]);
            }
            return $this->render('search', ['settings' => $search, 'searchTariffs' => $tariffsSearch, 'searchCottages' => $cottagesSearch, 'result' => null, 'activeSearch' => null]);
        }
        return false;
    }
}