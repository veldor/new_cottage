<?php

use app\models\EditCottageBase;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model EditCottageBase */

$form = ActiveForm::begin(['id' => 'changeSquare', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-cottage/change-square/' . $model->cottageId]]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'square', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '1'])
    ->hint('В квадратных метрах!')
    ->label('Площадь');

echo $form->field($model, 'changeDate', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'date'])
    ->hint('Внимание! Все платежи, рассчитываемые по площади, начиная с этой даты, будут пересчитаны с новыми значениями. Образовавшаяся задолженность будет добавлена к стоимости платежа, образовавшийся излишек будет зачислен на депозит')
    ->label('Дата изменения');

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();