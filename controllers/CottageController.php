<?php


namespace app\controllers;


use app\models\CottageInfo;
use app\models\database\CottagesHandler;
use app\models\database\FinesHandler;
use app\models\EditContact;
use app\models\EditCottageBase;
use app\models\exceptions\ExceptionWithStatus;
use Exception;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CottageController extends Controller
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
                        'actions' => ['show', 'edit', 'create-additional', 'switch-individual', 'switch-use'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $cottageNumber
     * @return string|null
     * @throws Exception
     */
    public function actionShow($cottageNumber)
    {
        try {
            $cottageInfo = new CottageInfo($cottageNumber);
            FinesHandler::check($cottageNumber);
            /** @var CottageInfo $cottageInfo */
            return $this->render('/cottage/view', ['info' => $cottageInfo]);
        } catch (ExceptionWithStatus $e) {

        }
        return null;
    }

    /**
     * @param $type
     * @param $action
     * @param $id
     * @return array
     * @throws Exception
     * @throws Throwable
     */
    public function actionEdit($type, $action, $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            if (Yii::$app->request->isGet) {
                if ($type === 'edit-contact') {
                    switch ($action) {
                        case 'add-contact':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_ADD_CONTACT]);
                            $model->fillCottageId($id);
                            return ['title' => 'Добавление контакта', 'html' => $this->renderAjax('add-contact', ['model' => $model])];
                        case 'change-contact':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_CHANGE_CONTACT]);
                            $model->fillContactInfo($id);
                            return ['title' => 'Добавление контакта', 'html' => $this->renderAjax('/edit_contact/change-cottage', ['model' => $model])];
                        case 'add-mail':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_ADD_EMAIL]);
                            $model->fillContactId($id);
                            return ['title' => 'Добавление email', 'html' => $this->renderAjax('/edit_contact/add-mail', ['model' => $model])];
                        case 'add-phone':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_ADD_PHONE]);
                            $model->fillContactId($id);
                            return ['title' => 'Добавление номера телефона', 'html' => $this->renderAjax('/edit_contact/add-phone', ['model' => $model])];
                        case 'delete-mail':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_DELETE_EMAIL]);
                            $model->fillEmailId($id);
                            return ['title' => 'Удаление email', 'html' => $this->renderAjax('/edit_contact/delete-mail', ['model' => $model])];
                        case 'delete-contact':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_DELETE_CONTACT]);
                            $model->fillContactId($id);
                            return ['title' => 'Удаление контакта', 'html' => $this->renderAjax('/edit_contact/delete-contact', ['model' => $model])];
                        case 'delete-phone':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_DELETE_PHONE]);
                            $model->fillPhoneId($id);
                            return ['title' => 'Удаление номера телефона', 'html' => $this->renderAjax('/edit_contact/delete-phone', ['model' => $model])];
                        case 'change-mail':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_EDIT_EMAIL]);
                            $model->fillEmailInfo($id);
                            return ['title' => 'Изменение email', 'html' => $this->renderAjax('/edit_contact/edit-mail', ['model' => $model])];
                        case 'change-phone':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_EDIT_PHONE]);
                            $model->fillPhoneInfo($id);
                            return ['title' => 'Изменение номера телефона', 'html' => $this->renderAjax('/edit_contact/edit-phone', ['model' => $model])];
                    }
                } elseif ($type === 'edit-cottage') {
                    switch ($action) {
                        case 'switch-register':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_SWITCH_REGISTER]);
                            $model->fillCottageId($id);
                            return ['title' => 'Изменить наличие сведений для реестра', 'html' => $this->renderAjax('/edit_cottage/switch-register', ['model' => $model])];
                        case 'switch-rights':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_SWITCH_RIGHTS]);
                            $model->fillCottageId($id);
                            return ['title' => 'Изменить наличие права собственности', 'html' => $this->renderAjax('/edit_cottage/switch-rights', ['model' => $model])];
                        case 'change-rights':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_CHANGE_RIGHTS]);
                            $model->fillPropertyData($id);
                            return ['title' => 'Изменить данные права собственности', 'html' => $this->renderAjax('/edit_cottage/change-rights', ['model' => $model])];
                        case 'change-deposit':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_CHANGE_DEPOSIT]);
                            $model->fillDeposit($id);
                            return ['title' => 'Изменить сумму депозита', 'html' => $this->renderAjax('/edit_cottage/change-deposit', ['model' => $model])];
                        case 'change-square':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_CHANGE_SQUARE]);
                            $model->fillSquare($id);
                            return ['title' => 'Изменить площадь участка', 'html' => $this->renderAjax('/edit_cottage/change-square', ['model' => $model])];
                    }
                }
            }
            if (Yii::$app->request->isPost) {
                if ($type === 'edit-contact') {
                    switch ($action) {
                        case 'add-mail':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_ADD_EMAIL]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->saveEmail();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'add-contact':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_ADD_CONTACT]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->saveContact();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'add-phone':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_ADD_PHONE]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->savePhone();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'delete-mail':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_DELETE_EMAIL]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->deleteEmail();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'delete-phone':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_DELETE_PHONE]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->deletePhone();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'delete-contact':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_DELETE_CONTACT]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->deleteContact();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'change-mail':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_EDIT_EMAIL]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->changeEmail();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'change-phone':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_EDIT_PHONE]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->changePhone();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'change-contact':
                            $model = new EditContact(['scenario' => EditContact::SCENARIO_CHANGE_CONTACT]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->changeContact();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                    }
                } elseif ($type === 'edit-cottage') {
                    switch ($action) {
                        case 'switch-register':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_SWITCH_REGISTER]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->switchRegister();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'switch-rights':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_SWITCH_RIGHTS]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->switchRights();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'change-rights':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_CHANGE_RIGHTS]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->changeRights();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'change-deposit':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_CHANGE_DEPOSIT]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->changeDeposit();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                        case 'change-square':
                            $model = new EditCottageBase(['scenario' => EditCottageBase::SCENARIO_CHANGE_SQUARE]);
                            $model->load(Yii::$app->request->post());
                            if ($model->validate()) {
                                return $model->changeSquare();
                            }
                            return [
                                'errors' => $model->errors
                            ];
                    }
                }
            }
        } catch (ExceptionWithStatus $e) {
            return ['error' => $e->getMessage()];
        }
        return ['info' => 'Действие не назначено'];
    }

    /**
     * @param $cottageId
     * @return array
     * @throws ExceptionWithStatus
     * @throws NotFoundHttpException
     */
    public function actionCreateAdditional($cottageId)
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // если у данного участка ещё нет дополнительного- добавляю
            if (!CottagesHandler::haveAdditional($cottageId)) {
                CottagesHandler::addAdditional($cottageId);
                return ['status' => 1, 'message' => 'Заглушки для участков созданы'];
            }
            return ['error' => "Данный участок уже имеет дополнительный"];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @param $cottageId
     * @return array
     * @throws NotFoundHttpException
     * @throws ExceptionWithStatus
     */
    public function actionSwitchIndividual($cottageId)
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return CottagesHandler::switchIndividual($cottageId);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}