<?php

use app\models\database\DataMembershipHandler;
use kartik\date\DatePicker;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model DataMembershipHandler */


$form = ActiveForm::begin(['id' => 'enableMembership', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/membership/enable']]);

echo "<h2 class='text-center'>Оплата целевых взносов</h2>";

echo $form->field($model, 'cottage_number', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($model, 'firstCountedQuarter', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->label('Первый регистрируемый квартал');

echo "<div class='row' id='quarterList'></div>";

ActiveForm::end();

?>

<script>
    function handleQuarters() {
        let monthsList = $('#quarterList');
        let firstMonthInput = $('input#datamembershiphandler-firstcountedquarter');
        firstMonthInput.on('change.edit', function () {
            monthsList.html('');
            let link = $(this);
            if ($(this).val()) {
                sendAjax('get', '/get/membership-start/' + $(this).val(), function (answer) {
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

    handleQuarters();

</script>