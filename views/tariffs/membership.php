<?php

use app\assets\AppAsset;
use app\models\database\TariffMembershipHandler;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use yii\web\View;
use yii\widgets\ActiveForm;

AppAsset::register($this);

/* @var $this View */
/* @var $quaters array */

$form = ActiveForm::begin(['id' => 'fillTariffs', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'validateOnSubmit' => false, 'enableAjaxValidation' => false, 'action' => ['/tariffs/fill-membership']]);

// найду последний заполненный месяц для получения ссылочных тарифных ставок
$lastTariff = TariffMembershipHandler::find()->orderBy('quarter DESC')->one();
if (!empty($lastTariff)) {
    $payForMeter = $lastTariff->pay_for_meter;
    $payForCottage = $lastTariff->pay_for_cottage;
} else {
    $payForMeter = 0;
    $payForCottage = 0;
}
$first = true;
foreach ($quaters as $quarter) {
    if ($first) {
        $header = "<h4><b class='text-success'>" . TimeHandler::getFullFromShotQuarter($quarter) . "</b></h4>";
        $first = false;
    } else {
        $header = "<h4><b class='text-success'>" . TimeHandler::getFullFromShotQuarter($quarter) . "</b> <button class='btn btn-default copy-previous'><span class='glyphicon glyphicon-arrow-down text-success'></span> Копировать предыдущий</button></h4>";
    }
    echo "<div class='col-sm-6 col-sm-offset-2 period-set'>
                 $header
                <label class='col-sm-12'>Оплата с сотки <div class='input-group'><input data-type='meter' class='form-control' min='0' step='0.01' type='number' name='TariffMembershipHandler[membership][{$quarter}][meter]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($payForMeter) . "'><span class='glyphicon glyphicon-chevron-left'></span>" . CashHandler::toRubles($payForMeter) . "</button></span></div></label>
                <label class='col-sm-12'>Оплата с участка <div class='input-group'><input data-type='cottage' class='form-control' min='0' step='0.01' type='number' name='TariffMembershipHandler[membership][{$quarter}][cottage]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($payForCottage) . "'><span class='glyphicon glyphicon-chevron-left'></span>" . CashHandler::toRubles($payForCottage) . "</button></span></div></label>
             </div>";
}
echo "";
echo "<div class='col-sm-12 text-center margened'><button class='btn btn-success' type='submit'>Сохранить тарифы</button></div>";

ActiveForm::end();

$this->registerJsFile('/js/utils.js', ['depends' => [yii\web\JqueryAsset::class]]);
