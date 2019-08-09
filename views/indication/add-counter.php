<?php

use app\models\database\RegistredCountersHandler;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model RegistredCountersHandler */

$form = ActiveForm::begin(['id' => 'addCounter', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/counter/add']]);

echo "<h2 class='text-center'>Регистрация счётчика электроэнергии</h2>";

echo $form->field($model, 'cottage_id', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo '<label class="control-label">Birth Date</label>';
try {
    echo DatePicker::widget([
        'name' => 'dp_2',
        'type' => DatePicker::TYPE_COMPONENT_PREPEND,
        'value' => '23-Feb-1982',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'dd-M-yyyy'
        ]
    ]);
} catch (Exception $e) {
}

echo $form->field($model, 'last_data', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '1'])
    ->label('Показания счётчика');

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();
