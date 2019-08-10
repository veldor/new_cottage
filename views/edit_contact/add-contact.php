<?php

use app\models\EditContact;
use kartik\switchinput\SwitchInput;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model EditContact */

if ($model->cottageInfo->is_additional) {
    echo "<h2 class='text-danger text-center'>Обратите внимание, вы добавляете контакт дополнительному участку!</h2>";
}

$form = ActiveForm::begin(['id' => 'addContact', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/cottage/edit/edit-contact/add-contact/' . $model->cottageId]]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);

try {
    echo $form->field($model, 'isOwner', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-4">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group margened']])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText' => 'Да',
            'offText' => 'Нет',
            'handleWidth' => 20,
        ]
    ])
        ->label('Владелец');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}

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