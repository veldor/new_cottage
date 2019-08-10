<?php

use app\assets\AppAsset;
use app\models\database\TariffPowerHandler;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use yii\web\View;
use yii\widgets\ActiveForm;

AppAsset::register($this);

/* @var $this View */
/* @var $months array */

$form = ActiveForm::begin(['id' => 'fillTariffs', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'validateOnSubmit' => false, 'enableAjaxValidation' => false, 'action' => ['/tariffs/fill-power']]);

// найду последний заполненный месяц для получения ссылочных тарифных ставок
$lastTariff = TariffPowerHandler::find()->orderBy('month DESC')->one();
if (!empty($lastTariff)) {
    $limit = $lastTariff->power_limit;
    $cost = CashHandler::toMathRubles($lastTariff->power_cost);
    $overcost = CashHandler::toMathRubles($lastTariff->power_overcost);
} else {
    $limit = 0;
    $cost = 0;
    $overcost = 0;
}
$first = true;
foreach ($months as $month) {
    if ($first) {
        $header = "<h4><b class='text-success'>" . TimeHandler::getFullFromShotMonth($month) . "</b></h4>";
        $first = false;
    } else {
        $header = "<h4><b class='text-success'>" . TimeHandler::getFullFromShotMonth($month) . "</b> <button class='btn btn-default copy-previous'><span class='glyphicon glyphicon-arrow-down text-success'></span> Копировать предыдущий</button></h4>";
    }
    echo "<div class='col-sm-4 col-sm-offset-4 period-set'>
                 $header
                <label class='col-sm-12'>Льготный лимит<div class='input-group'><input data-type='limit' class='form-control' min='0' step='1' type='number' name='TariffPowerHandler[energy][{$month}][limit]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='$limit'><span class='glyphicon glyphicon-chevron-left'></span>$limit  " . GrammarHandler::KILOWATT . "</button></span></div></label>
                <label class='col-sm-12'>Льготная стоимость " . GrammarHandler::KILOWATT . "<div class='input-group'><input data-type='cost' class='form-control' min='0' step='0.01' type='number' name='TariffPowerHandler[energy][{$month}][cost]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='$cost'><span class='glyphicon glyphicon-chevron-left'></span>$cost " . GrammarHandler::RUBLE . "</button></span></div></label>
                <label class='col-sm-12'>Стоимость " . GrammarHandler::KILOWATT . "<div class='input-group'><input data-type='overcost' class='form-control' min='0' step='0.01' type='number' name='TariffPowerHandler[energy][{$month}][overcost]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='$overcost'><span class='glyphicon glyphicon-chevron-left'></span>$overcost " . GrammarHandler::RUBLE . "</button></span></div></label>
             </div>";
}
echo "";
echo "<div class='col-sm-12 text-center margened'><button class='btn btn-success' type='submit'>Сохранить тарифы</button></div>";

ActiveForm::end();

$this->registerJsFile('/js/utils.js', ['depends' => [yii\web\JqueryAsset::class]]);