<?php

use app\assets\MainAsset;
use app\models\TariffsHandler;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;
use yii\widgets\ActiveForm;

MainAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $model TariffsHandler */

$this->title = "Заполнение тарифов";

$form = ActiveForm::begin(['id' => 'fillTariffs', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'validateOnSubmit' => false, 'enableAjaxValidation' => false, 'action' => ['/tariffs/fill']]);
$month = TimeHandler::getNeighborMonth($model->lastFilledPower->month, +1);
$quarter = TimeHandler::getNeighborQuarter($model->lastFilledMembership->quarter, +1);
$year = (int)$model->lastFilledTarget->year + 1;
echo "<div class='row'>";
echo "<div class='col-sm-12'>
            <h2>Добавление тарифов</h2>
           </div>
          <div class='col-sm-12'>
            <div class='col-sm-4'>
                 <h3>Электроэнергия</h3>
                 <h4><b>" . TimeHandler::getFullFromShotMonth($month) . "</b></h4>
                <label class='col-sm-12'>Льготный лимит<div class='input-group'><input class='form-control' min='0' step='1' type='number' name='TariffsHandler[energy][{$month}][limit]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='{$model->lastFilledPower->power_limit}'><span class='glyphicon glyphicon-chevron-left'></span>{$model->lastFilledPower->power_limit}  " . GrammarHandler::KILOWATT . "</button></span></div></label>
                <label class='col-sm-12'>Льготная стоимость " . GrammarHandler::KILOWATT . "<div class='input-group'><input class='form-control' min='0' step='0.01' type='number' name='TariffsHandler[energy][{$month}][cost]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($model->lastFilledPower->power_cost) . "'><span class='glyphicon glyphicon-chevron-left'></span> " . CashHandler::toMathRubles($model->lastFilledPower->power_cost) . " " . GrammarHandler::RUBLE . "</button></span></div></label>
                <label class='col-sm-12'>Стоимость " . GrammarHandler::KILOWATT . "<div class='input-group'><input class='form-control' min='0' step='0.01' type='number' name='TariffsHandler[energy][{$month}][overcost]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($model->lastFilledPower->power_overcost) . "'><span class='glyphicon glyphicon-chevron-left'></span> " . CashHandler::toMathRubles($model->lastFilledPower->power_overcost) . " " . GrammarHandler::RUBLE . "</button></span></div></label>
             </div>
            <div class='col-sm-4'>
                 <h3>Членские</h3>
                 <h4><b>" . TimeHandler::getFullFromShotQuarter($quarter) . "</b></h4>
                
                <label class='col-sm-12'>С участка <div class='input-group'><input class='form-control' min='0' step='0.01' type='number' name='TariffsHandler[membership][{$quarter}][fixed]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($model->lastFilledMembership->pay_for_cottage) . "'><span class='glyphicon glyphicon-chevron-left'></span> " . CashHandler::toMathRubles($model->lastFilledMembership->pay_for_cottage) . " " . GrammarHandler::RUBLE . "</button></span></div></label>
                <label class='col-sm-12'>С сотки <div class='input-group'><input class='form-control' min='0' step='0.01' type='number' name='TariffsHandler[membership][{$quarter}][float]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($model->lastFilledMembership->pay_for_meter) . "'><span class='glyphicon glyphicon-chevron-left'></span> " . CashHandler::toMathRubles($model->lastFilledMembership->pay_for_meter) . " " . GrammarHandler::RUBLE . "</button></span></div></label>
             </div>
            <div class='col-sm-4'>
                 <h3>Целевые</h3>
                 <h4><b>$year год</b></h4>
                <label class='col-sm-12'>С участка <div class='input-group'><input class='form-control' min='0' step='0.01' type='number' name='TariffsHandler[target][{$year}][fixed]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($model->lastFilledTarget->pay_for_cottage) . "'><span class='glyphicon glyphicon-chevron-left'></span> " . CashHandler::toMathRubles($model->lastFilledTarget->pay_for_cottage) . " " . GrammarHandler::RUBLE . "</button></span></div></label>
                <label class='col-sm-12'>С сотки <div class='input-group'><input class='form-control' min='0' step='0.01' type='number' name='TariffsHandler[target][{$year}][float]'/><span class='input-group-btn'><button type='button' class='btn btn-default autofill' data-fill='" . CashHandler::toMathRubles($model->lastFilledTarget->pay_for_meter) . "'><span class='glyphicon glyphicon-chevron-left'></span> " . CashHandler::toMathRubles($model->lastFilledTarget->pay_for_meter) . " " . GrammarHandler::RUBLE . "</button></span></div></label>
                <label class='col-sm-12'>Оплатить до <input class='form-control' type='date' name='TariffsHandler[target][{$year}][payUp]'/></label>
                <label class='col-sm-12'>Цель<textarea class='form-control' name='TariffsHandler[target][{$year}][description]'></textarea></label>
             </div>
            </div>
            <div class='col-sm-12 text-center'><button class='btn btn-success' type='submit'>Сохранить тарифы</button></div>";
echo "</div>";
ActiveForm::end();
$this->registerJsFile('/js/utils.js', ['depends' => [yii\web\JqueryAsset::class]]);
