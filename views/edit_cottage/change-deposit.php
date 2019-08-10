<?php

use app\models\EditCottageBase;
use kartik\switchinput\SwitchInput;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model EditCottageBase */

$form = ActiveForm::begin(['id' => 'changeDeposit', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-cottage/change-deposit/' . $model->cottageId]]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

try {
    echo $form->field($model, 'is_register_deposit', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-4">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group margened']])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText' => 'Да',
            'offText' => 'Нет',
            'handleWidth' => 20,
        ]
    ])
        ->hint('Если активно- операция будет отображаться как транзакция. Если неактивно- факт изменения суммы не будет зарегистрирован в системе')
        ->label('Отображать в отчётах');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}

echo $form->field($model, 'deposit', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01'])
    ->hint('После сохранения сумма депозита станет равна введённому значению. Внимание: это не зачисление данной суммы а изменение общей!')
    ->label('Сумма');

echo $form->field($model, 'description', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textarea()
    ->label('Основание изменения депозита');

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();