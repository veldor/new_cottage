<?php

use app\models\database\CottagesHandler;
use app\models\database\DataTargetHandler;
use app\models\database\TariffTargetHandler;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model DataTargetHandler */
/* @var $tariffs TariffTargetHandler[] */
/* @var $cottageInfo CottagesHandler */


$form = ActiveForm::begin(['id' => 'enableTarget', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/target/enable']]);

echo "<h2 class='text-center'>Оплата целевых взносов</h2>";

echo $form->field($model, 'cottage_number', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);

foreach ($tariffs as $tariff) {
    $fullSummToPay = $tariff->pay_for_cottage + $tariff->pay_for_meter / 100 * $cottageInfo->square;

    echo "
            <div class='col-sm-8 col-sm-offset-2'><h2 class='text-center text-success'>{$tariff->year} год</h2>
            <div class='col-sm-8 col-sm-offset-2'><h3 class='text-center text-success'>Всего к оплате <b class='text-warning'>" . CashHandler::toRubles($fullSummToPay) . "</b></h3></div>
            <div class='form-group'><div class='col-sm-5'><label class='control-label'>Оплачено ранее</label></div><div class='col-sm-7'><div class='input-group'><input class='form-control' type='number' step='1' name='DataTargetHandler[targets][$tariff->year]'/><span class='input-group-addon'>" . GrammarHandler::RUBLE . "</span></div></div></div>
            </div><div class='clearfix'></div>
            ";
}

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success']);

ActiveForm::end();

?>

<script>
    function handleSend() {
        let form = $('form#enableTarget');
        form.on('submit.send', function (e) {
            e.preventDefault();
            sendAjax('post', '/target/enable', simpleAnswerHandler, form, true);
        });
    }

    handleSend();

</script>