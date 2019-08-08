<?php

use app\models\EditContact;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model EditContact */

$form = ActiveForm::begin(['id' => 'deleteContactEmail', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/cottage/edit/edit-contact/delete-mail/' . $model->emailId]]);

echo $form->field($model, 'emailId', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo "<h2>Удалить адрес электронной почты?</h2>";

echo Html::submitButton('Да, точно удалить', ['class' => 'btn btn-success']);

ActiveForm::end();