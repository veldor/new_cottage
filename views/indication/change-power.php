<?php

use app\models\database\DataPowerHandler;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use kartik\switchinput\SwitchInput;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model DataPowerHandler */
$form = ActiveForm::begin(['id' => 'changePower', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false,  'action' => ['/power/change']]);


echo $form->field($model, 'id', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'cottage_number', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'counter_id', ['template' =>
    '<div class="col-sm-7">{label}</div><div class="col-sm-5">{input}{error}{hint}</div>'])
    ->textInput(['readonly' => 'true'])
    ->label('Идентификатор счётчика');

echo $form->field($model, 'old_data', ['template' =>
    '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::KILOWATT . '</span></div>{error}{hint}</div>'])
    ->textInput(['readonly' => 'true'])
    ->label('Предыдущие показания');

echo $form->field($model, 'new_data', ['template' =>
    '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::KILOWATT . '</span></div>{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '1'])
    ->label('Текущие показания');

try {
    echo $form->field($model, 'is_limit_ignored', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5">{input}{error}{hint}</div>'])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText'=>'Да',
            'offText'=>'Нет',
            'handleWidth'=>20,
        ]
    ])
        ->label('Игнорировать лимит');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}
if ($model->is_individual_tariff) {
    echo $form->field($model, 'individual_limit', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::KILOWATT . '</span></div>{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '1'])
        ->label('Индивидуальный льготный лимит');

    echo $form->field($model, 'individual_cost', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::RUBLE . '</span></div>{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01', 'value' => CashHandler::toMathRubles($model->individual_cost)])
        ->label('Индивидуальная льготная стоимость ' . GrammarHandler::KILOWATT);

    echo $form->field($model, 'individual_overcost', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::RUBLE . '</span></div>{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01', 'value' => CashHandler::toMathRubles($model->individual_overcost)])
        ->label('Индивидуальная стоимость ' . GrammarHandler::KILOWATT);
} else {
    echo $form->field($model, 'individual_limit', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::KILOWATT . '</span></div>{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '1'])
        ->label('Индивидуальный льготный лимит');

    echo $form->field($model, 'individual_cost', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::RUBLE . '</span></div>{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01'])
        ->label('Индивидуальная льготная стоимость ' . GrammarHandler::KILOWATT);

    echo $form->field($model, 'individual_overcost', ['template' =>
        '<div class="col-sm-7">{label}</div><div class="col-sm-5"><div class="input-group">{input}<span class="input-group-addon">' . GrammarHandler::RUBLE . '</span></div>{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01'])
        ->label('Индивидуальная стоимость ' . GrammarHandler::KILOWATT);
}

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();

?>

<script>
    function handleChangePower() {
        let form = $('form#changePower');
        form.on('submit.send', function (e) {
            e.preventDefault();
            sendAjax('post', '/power/change', simpleAnswerHandler, form, true);
        });
    }

    handleChangePower();
</script>
