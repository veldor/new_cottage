<?php

use app\assets\MainAsset;
use app\assets\TariffsAsset;
use app\models\selection_classes\TariffsInfo;
use app\models\utils\CashHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JqueryAsset;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $data TariffsInfo */

TariffsAsset::register($this);
ShowLoadingAsset::register($this);
$this->title = 'Тарифы на услуги';

// форма для поиска тарифов по времени

$form = ActiveForm::begin(['id' => 'searchTariffs', 'options' => ['class' => 'form-horizontal bg-default no-print'],'validateOnSubmit'  => false, 'enableAjaxValidation' => false, 'action' => ['/tariffs']]);
echo $form->field($model, 'startDate', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->input('date', ['class' => 'date-start form-control'])
    ->label('С');
echo $form->field($model, 'finishDate', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->input('date', ['class' => 'date-finish form-control'])
    ->label('По');
echo '<div class="col-sm-12 text-center"><div class=" btn-group"><button type="button" class="btn btn-default date-selector" data-period="day">За день</button><button type="button" class="btn btn-default date-selector" data-period="month">За месяц</button><button type="button" class="btn btn-default date-selector" data-period="year">За год</button></div></div>';
echo "<div class='col-sm-12 text-center margened'>";
echo Html::submitButton('Сформировать', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';
ActiveForm::end();
?>

<div class="col-sm-12 text-center">
    <div class="btn-group" data-toggle="buttons">
        <label class="btn btn-primary active">
            <input type="checkbox" class="switcher" data-switch="powerBlock" checked> Электроэнергия
        </label>
        <label class="btn btn-primary active">
            <input type="checkbox" class="switcher" data-switch="membershipBlock" checked> Членские взносы
        </label>
        <label class="btn btn-primary active">
            <input type="checkbox" class="switcher" data-switch="targetBlock" checked> Целевые взносы
        </label>
    </div>
</div>

<div class="col-sm-12 text-center margened">
    <a href="<?=Url::toRoute("/tariffs/fill")?>" class="btn btn-info">Добавить тариф</a>
</div>

<div class="row">
    <div class="col-sm-12" id="powerBlock">
        <h2 class="text-center">Тарифы на электроэнергию</h2>
        <?php
        if(!empty($data->powerTariffs)){
            echo "<table class='table table-striped table-condensed table-hover cursor-pointer'><tr><th>Месяц</th><th>Лимит</th><th>Ц1</th><th>Ц2</th></tr>";
            foreach ($data->powerTariffs as $powerTariff) {
                if(!empty($powerTariff)){
                    echo "<tr class='control-element' data-type='energy' data-period='{$powerTariff->month}'><td>{$powerTariff->month}</td><td>Лимит: {$powerTariff->power_limit} кВт*ч</td><td>" . CashHandler::toRubles($powerTariff->power_cost) . "</td><td>" . CashHandler::toRubles($powerTariff->power_overcost) . "</td></tr>";
                }
            }
            echo "</table>";
        }
        ?>
    </div>
    <div class="col-sm-12" id="membershipBlock">
        <h2 class="text-center">Тарифы на членские взносы</h2>
        <?php
        if(!empty($data->membershipTariffs)){
            echo "<table class='table table-striped table-condensed table-hover cursor-pointer'><tr><th>Квартал</th><th>С участка</th><th>С сотки</th></tr>";
            foreach ($data->membershipTariffs as $membershipTariff) {
                if(!empty($membershipTariff)){
                    echo "<tr class='control-element' data-type='membership' data-period='{$membershipTariff->quarter}'><td>{$membershipTariff->quarter}</td><td>" . CashHandler::toRubles($membershipTariff->pay_for_cottage) . "</td><td>" . CashHandler::toRubles($membershipTariff->pay_for_meter) . "</td></tr>";
                }
            }
            echo "</table>";
        }
        ?>
    </div>
    <div class="col-sm-12" id="targetBlock">
        <h2 class="text-center">Тарифы на целевые взносы</h2>
        <?php
        if(!empty($data->targetTariffs)){
            echo "<table class='table table-striped table-condensed table-hover cursor-pointer'><th>Год</th><th>С участка</th><th>С сотки</th>";
            foreach ($data->targetTariffs as $tariff) {
                if(!empty($tariff)){
                    echo "<tr class='control-element' data-type='target' data-period='{$tariff->year}'><td>{$tariff->year}</td><td>" . CashHandler::toRubles($tariff->pay_for_cottage) . "</td><td>" . CashHandler::toRubles($tariff->pay_for_meter) . "</td></tr>";
                }
            }
            echo "</table>";
        }
        ?>
    </div>
</div>
