<?php

use app\models\database\DataSingleHandler;
use app\models\utils\CashHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model DataSingleHandler */

$form = ActiveForm::begin(['id' => 'createSingle', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/single/add/']]);

echo $form->field($model, 'cottage_number', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'total_pay', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01'])
    ->label('Стоимость');

echo $form->field($model, 'pay_description', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textarea()
    ->label('Цель платежа');

echo "<div class='form-group margened text-center'>" . Html::submitButton('Сохранить', ['class' => 'btn btn-success']) . "</div>";

ActiveForm::end();
