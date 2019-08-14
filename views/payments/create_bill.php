<?php

use app\models\Bill;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataTargetHandler;
use app\models\utils\CashHandler;
use kartik\switchinput\SwitchInput;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model Bill */
/* @var $additionalModel Bill */

echo "<div class='row'><div class='col-sm-12'>";

$form = ActiveForm::begin(['id' => 'createBill', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/bill/create']]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);


try {
    echo $form->field($model, 'notify', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-12 margened']])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText' => 'Да',
            'offText' => 'Нет',
            'handleWidth' => 20,
        ]
    ])
        ->label('Оповестить по электронной почте');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}

try {
    echo $form->field($model, 'print', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-12 margened']])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText' => 'Да',
            'offText' => 'Нет',
            'handleWidth' => 20,
        ]
    ])
        ->label('Распечатать квитанцию');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}

echo $form->field($model, 'targetOwner', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->dropDownList($model->ownersList)
    ->hint('Выберите владельца, на чьё имя будет выставлен счёт')
    ->label('Плательщик');

if ($model->cottageInfo->is_power) {
    echo "<h3 class='text-center'>Электроэнергия</h3>";
    if (!empty($model->powerData)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Месяц</th><th>ID</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($model->powerData as $item) {
            /** @var DataPowerHandler $value */
            foreach ($item as $value) {
                $summToPay = $value->total_pay - $value->payed_summ;
                echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[power][{$value->counter_id}][{$value->id}][value]' name='Bill[power][{$value->counter_id}][{$value->id}][pay]' checked='checked'/></td><td>{$value->month}</td><td>{$value->counter_id}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[power][{$value->counter_id}][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<h4 class='text-center text-success'>Долгов нет</h4>";
    }
}
if ($model->cottageInfo->is_membership) {
    echo "<h3 class='text-center'>Членские взносы</h3>";
    if (!empty($model->membershipData)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Квартал</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($model->membershipData as $value) {
            $summToPay = $value->total_pay - $value->payed_summ;
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[membership][{$value->id}][value]' name='Bill[membership][{$value->id}][pay]' checked='checked'/></td><td>{$value->quarter}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[membership][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<h4 class='text-center text-success'>Долгов нет</h4>";
    }
}
if ($model->cottageInfo->is_target) {
    echo "<h3 class='text-center'>Целевые взносы</h3>";
    if (!empty($model->targetData)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Год</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($model->targetData as $value) {
            $summToPay = $value->total_pay - $value->payed_summ;
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[target][{$value->id}][value]' name='Bill[target][{$value->id}][pay]' checked='checked'/></td><td>{$value->year}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[target][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<h4 class='text-center text-success'>Долгов нет</h4>";
    }
}
echo "<h3 class='text-center'>Разовые взносы</h3>";
if (!empty($model->singleData)) {
    echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Цель</th><th>Сумма</th><th>К оплате</th></tr>";
    foreach ($model->singleData as $value) {
        $summToPay = $value->total_pay - $value->payed_summ;
        echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[single][{$value->id}][value]' name='Bill[single][{$value->id}][pay]' checked='checked'/></td><td>{$value->pay_description}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[single][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
    }
    echo "</table>";
} else {
    echo "<h4 class='text-center text-success'>Долгов нет</h4>";
}

echo "<h3 class='text-center'>Пени</h3>";
if (!empty($model->finesData)) {
    echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Тип</th><th>Сумма</th><th>К оплате</th></tr>";
    foreach ($model->finesData as $value) {
        switch ($value->pay_type){
            case 'membership' :
                                $period = 'Членские ' . DataMembershipHandler::findOne($value->period_id)->quarter;
                                break;
            case 'target' :
                                $period = 'Целевые ' . DataTargetHandler::findOne($value->period_id)->year;
                                break;
            case 'power' :
                                $period = 'Электричество ' . DataPowerHandler::findOne($value->period_id)->month;
                                break;
        }
        $summToPay = $value->summ - $value->payed_summ;
        echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[fines][{$value->id}][value]' name='Bill[fines][{$value->id}][pay]' checked='checked'/></td><td>$period</td><td>" . CashHandler::toRubles($value->summ) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[fines][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
    }
    echo "</table>";
} else {
    echo "<h4 class='text-center text-success'>Долгов нет</h4>";
}

// проверю наличие дополнительного участка
if(!empty($additionalModel)){
    echo "<h2 class='text-center'>Дополнительный участок</h2>";
    if ($additionalModel->cottageInfo->is_power) {
        echo "<h3 class='text-center'>Электроэнергия</h3>";
        if (!empty($additionalModel->powerData)) {
            echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Месяц</th><th>ID</th><th>Сумма</th><th>К оплате</th></tr>";
            foreach ($additionalModel->powerData as $item) {
                /** @var DataPowerHandler $value */
                foreach ($item as $value) {
                    $summToPay = $value->total_pay - $value->payed_summ;
                    echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[power][{$value->counter_id}][{$value->month}][value]' name='Bill[power][{$value->counter_id}][{$value->id}][pay]' checked='checked'/></td><td>{$value->month}</td><td>{$value->counter_id}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[power][{$value->counter_id}][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<h4 class='text-center text-success'>Долгов нет</h4>";
        }
    }
    if ($additionalModel->cottageInfo->is_membership) {
        echo "<h3 class='text-center'>Членские взносы</h3>";
        if (!empty($additionalModel->membershipData)) {
            echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Квартал</th><th>Сумма</th><th>К оплате</th></tr>";
            foreach ($additionalModel->membershipData as $value) {
                $summToPay = $value->total_pay - $value->payed_summ;
                echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[membership][{$value->quarter}][value]' name='Bill[membership][{$value->id}][pay]' checked='checked'/></td><td>{$value->quarter}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[membership][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h4 class='text-center text-success'>Долгов нет</h4>";
        }
    }
    if ($additionalModel->cottageInfo->is_target) {
        echo "<h3 class='text-center'>Целевые взносы</h3>";
        if (!empty($additionalModel->targetData)) {
            echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Год</th><th>Сумма</th><th>К оплате</th></tr>";
            foreach ($additionalModel->targetData as $value) {
                $summToPay = $value->total_pay - $value->payed_summ;
                echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[target][{$value->year}][value]' name='Bill[target][{$value->id}][pay]' checked='checked'/></td><td>{$value->year}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[target][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h4 class='text-center text-success'>Долгов нет</h4>";
        }
    }
    echo "<h3 class='text-center'>Разовые взносы</h3>";
    if (!empty($additionalModel->singleData)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Цель</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($additionalModel->singleData as $value) {
            $summToPay = $value->total_pay - $value->payed_summ;
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[single][{$value->id}][value]' name='Bill[single][{$value->id}][pay]' checked='checked'/></td><td>{$value->pay_description}</td><td>" . CashHandler::toRubles($value->total_pay) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[single][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<h4 class='text-center text-success'>Долгов нет</h4>";
    }

    echo "<h3 class='text-center'>Пени</h3>";
    if (!empty($additionalModel->finesData)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Тип</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($additionalModel->finesData as $value) {
            switch ($value->pay_type){
                case 'membership' :
                    $period = 'Членские ' . DataMembershipHandler::findOne($value->period_id)->quarter;
                    break;
                case 'target' :
                    $period = 'Целевые ' . DataTargetHandler::findOne($value->period_id)->year;
                    break;
                case 'power' :
                    $period = 'Электричество ' . DataPowerHandler::findOne($value->period_id)->month;
                    break;
            }
            $summToPay = $value->summ - $value->payed_summ;
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='Bill[fines][{$value->id}][value]' name='Bill[fines][{$value->id}][pay]' checked='checked'/></td><td>$period</td><td>" . CashHandler::toRubles($value->summ) . "</td><td><input type='number' class='form-control bill-pay' step='0.01'  name='Bill[fines][{$value->id}][value]' value='" . CashHandler::toMathRubles($summToPay) . "'/></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<h4 class='text-center text-success'>Долгов нет</h4>";
    }
}

if($model->cottageInfo->deposit > 0){
    $maxValue = CashHandler::toMathRubles($model->cottageInfo->deposit);
    echo "<table class='table table-condensed table-striped'><tr><td><input type='checkbox' class='pay-activator' data-for='Bill[deposit]' name='Bill[depositActivator]' checked='checked'/></td><td>На депозите: " . CashHandler::toRubles($model->cottageInfo->deposit) . "</td><td>Оплатить с депозита</td><td><input type='number' class='form-control' step='0.01' data-available='{$maxValue}' id='depositInput' name='Bill[deposit]' value='" .  $maxValue . "'/></td></tr></table>";
}
else{
    echo "<h3 class='text-center text-info'>Депозит участка пуст</h3>";
}

echo "<table class='table table-condensed table-striped'><tr><td><input type='checkbox' class='pay-activator' data-for='Bill[discount]' name='Bill[discountActivator]' /></td><td>Скидка</td><td><input type='number' class='form-control' step='0.01' id='discountInput' name='Bill[discount]' disabled='disabled'/></td><td><input type='text' class='form-control' name='Bill[discountReason]' placeholder='Причина скидки' /></td></tr></table>";
echo "</div>";

ActiveForm::end();

echo "</div>";

echo "<script>" . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/js/handleBillCreate.js') . "</script>";