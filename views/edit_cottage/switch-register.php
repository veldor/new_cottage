<?php

use app\models\EditCottageBase;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model EditCottageBase */

$form = ActiveForm::begin(['id' => 'switchRegister', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-cottage/switch-register/' . $model->cottageId]]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

if($model->cottageInfo->is_cottage_register_data){
    echo "<h2>Сбросить данные для реестра?</h2>";
}
else{
    echo "<h2>Данные для реестра предоставлены?</h2>";
}


echo Html::submitButton('Да', ['class' => 'btn btn-success']);

ActiveForm::end();