<?php

use app\models\EditContact;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model EditContact */

$form = ActiveForm::begin(['id' => 'addContactPhone', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-contact/add-phone/' . $model->contactNumber]]);

echo $form->field($model, 'contactNumber', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'phoneNumber', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'tel'])
    ->label('Номер телефона');

echo $form->field($model, 'description', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textarea()
    ->label('Примечание');

echo $form->field($model, 'makeMain', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->checkbox()
    ->label('Назначить основным номером');

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();