<?php

use app\models\database\DataMembershipHandler;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model DataMembershipHandler */

$form = ActiveForm::begin(['id' => 'changeMembership', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/membership/change']]);

echo $form->field($model, 'id', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'cottage_number', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'individual_pay_for_field', ['template' =>
    '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::RUBLE . '</span></div>{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01', 'value' => $model->individual_pay_for_field ? CashHandler::toMathRubles($model->individual_pay_for_field) : ''])
    ->label('Индивидуальная цена с сотки');
echo $form->field($model, 'individual_pay_for_cottage', ['template' =>
    '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::RUBLE . '</span></div>{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01', 'value' => $model->individual_pay_for_cottage    ? CashHandler::toMathRubles($model->individual_pay_for_cottage) : ''])
    ->label('Индивидуальная цена с участка');

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();