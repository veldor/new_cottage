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

echo $form->field($model, 'counterSerial', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->label('Серийный номер счётчика');

echo $form->field($model, 'startValue', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '1'])
    ->label('Начальные показания счётчика');

echo "<div class='form-group margened'><div class='col-sm-5'><label class='control-label'>Первый месяц учёта</label></div><div class='col-sm-7'>";
try {
    echo DatePicker::widget([
        'model' => $model,
        'attribute' => 'firstCountedMonth',
        'options' => ['placeholder' => 'Выберите месяц'],
        'type' => DatePicker::TYPE_COMPONENT_PREPEND,
        'pluginOptions' => [
            'format' => 'yyyy-mm',
            'autoclose' => true,
            'minViewMode' => 1,
        ]
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
    die;
}
echo "</div></div>";

echo "<div class='row' id='monthsList'></div>";

echo "<div class='margened text-center'>" . Html::submitButton('Сохранить', ['class' => 'btn btn-success']) . "</div>";

ActiveForm::end();

?>

<script>
    function handleMonths() {
        let monthsList = $('#monthsList');
        let firstMonthInput = $('input#registredcountershandler-firstcountedmonth');
        firstMonthInput.on('change.edit', function () {
            monthsList.html('');
            let link = $(this);
            if ($(this).val()) {
                sendAjax('get', '/get/counter-start/' + $(this).val(), function (answer) {
                    if (answer['error']) {
                        makeInformer('warning', 'Ошибка', answer['error']);
                        link.val('');
                    } else if (answer['text']) {
                        monthsList.html(answer['text']);
                    }
                });
            }
        });
    }

    handleMonths();

</script>
