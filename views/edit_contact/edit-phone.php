<?php

use app\models\EditContact;
use kartik\switchinput\SwitchInput;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model EditContact */

$form = ActiveForm::begin(['id' => 'editContactPhone', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-contact/change-phone/' . $model->phoneId]]);

echo $form->field($model, 'phoneId', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'phoneNumber', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'tel'])
    ->label('Номер телефона');

echo $form->field($model, 'description', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textarea()
    ->label('Примечание');

try {
    echo $form->field($model, 'makeMain', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-4">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group margened']])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText' => 'Да',
            'offText' => 'Нет',
            'handleWidth' => 20,
        ]
    ])
        ->label('Назначить основным');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();