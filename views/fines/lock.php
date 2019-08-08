<?php

use app\models\database\FinesHandler;
use app\models\utils\CashHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $info FinesHandler */

$form = ActiveForm::begin(['id' => 'lockFines', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => '/lock-fine']);

echo $form->field($info, 'id', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($info, 'summ', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01', 'value' => CashHandler::toMathRubles($info->summ)])
    ->hint('Данная сумма будет зафиксирована и меняться не будет.')
    ->label('Окончательная сумма пени');

echo "<div class='form-group margened text-center'>" . Html::submitButton('Сохранить', ['class' => 'btn btn-success']) . "</div>";

ActiveForm::end();