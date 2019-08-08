<?php

use app\models\EditCottageBase;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model EditCottageBase */

$form = ActiveForm::begin(['id' => 'switchRights', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-cottage/switch-rights/' . $model->cottageId]]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

if($model->cottageInfo->is_have_property_rights){
    echo "<h2>Сбросить данные прав собственности?</h2>";
}
else{
    echo "<h2>Данные о правах собственности предоставлены?</h2>";
}


echo Html::submitButton('Да', ['class' => 'btn btn-success']);

ActiveForm::end();