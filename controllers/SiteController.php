<?php

namespace app\controllers;

use app\models\exceptions\ExceptionWithStatus;
use app\models\LoginForm;
use app\models\MainView;
use app\models\utils\CashHandler;
use app\models\utils\Fix;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
                        'actions' => ['index', 'error', 'auth'],
                        'roles' => ['@'],

                    ],

                    [
                        'allow' => true,
                        'actions' => ['logout'],
                        'roles' => ['@'],
                    ],

                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
                    ],

                    [
                        'allow' => true,
                        'actions' => ['migration', 'test'],
                        'roles' => ['manager'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        // на главной странице будет отображаться информация о всех зарегистрированных участках.
        $model = new MainView();
        $this->layout = 'float';
        return $this->render('index', ['model' => $model]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     * @throws Exception
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Migration action.
     *
     */
    public function actionMigration()
    {
        /*Migration::migrateCottages();
        Migration::migrateContacts();
        Migration::migratePhones();
        Migration::migrateEmails();
        Migration::migrateTariffs();
        Migration::migratePowerData();
        Migration::migrateMembershipData();
        Migration::migrateTargetData();
        Migration::migrateSingleData();
        Migration::migratePayments();*/
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function actionTest(){
        //echo CashHandler::toMathRubles(50109);
        Fix::fix();
        //EmailHandler::notify(1,"Тестовый заголовок", "Тут тест, %USERNAME");
    }
}
