<?php

use app\models\EditContact;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model EditContact */

$form = ActiveForm::begin(['id' => 'changeContact', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/cottage/edit/edit-contact/change-contact/' . $model->contactNumber]]);

echo $form->field($model, 'contactNumber', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'isOwner', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7"><button type="button" class="btn btn-info">{input}</button>{error}{hint}</div>'])
    ->checkbox()
    ->label('Владелец');

echo $form->field($model, 'contactName', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, Иванов Иван Иванович'])
    ->label('Фамилия имя и отчество контакта.')
    ->hint("<b class='text-success'>Обязательное поле.</b> Буквы, пробелы и тире.");
echo $form->field($model, 'description', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textarea()
    ->label('Дополнительная информация о контакте.')
    ->hint("<b class='text-info'>Необязательное поле.</b> Буквы, пробелы и тире.");
echo $form->field($model, 'contactAddressIndex', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}
									{error}{hint}</div>'])
    ->textInput(['placeholder' => 'Например, 000000'])
    ->label('Почтовый индекс.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($model, 'contactAddressTown', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}
									{error}{hint}</div>'])
    ->textInput(['placeholder' => 'Например, Нижний Новгород'])
    ->label('Город проживания.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($model, 'contactAddressStreet', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}
									{error}{hint}</div>'])
    ->textInput(['placeholder' => 'Например, улица Минина'])
    ->label('Название улицы.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($model, 'contactAddressBuild', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}
									{error}{hint}</div>'])
    ->textInput(['placeholder' => 'Например, 23'])
    ->label('Номер дома.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($model, 'contactAddressFlat', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}
									{error}{hint}</div>'])
    ->textInput(['placeholder' => 'Например, 23'])
    ->label('Номер квартиры.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();