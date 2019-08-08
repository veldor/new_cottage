<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.04.2019
 * Time: 9:42
 */

use app\models\BankDetails;
use app\models\database\DataPowerHandler;
use app\models\FinesHandler;
use app\models\selection_classes\BillInfo;
use app\models\TargetHandler;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use yii\web\View;

/* @var $this View */
/* @var $info */

/** @var BillInfo $billInfo */
$billInfo = $info['billInfo'];
/** @var BankDetails $bankInfo */
$bankInfo = $info['bankInfo'];

$bankDetails = '';

if (!empty($billInfo->bill->discount)) {
    $bankDetails .= '<br/>Скидка: ' . CashHandler::toRubles($billInfo->bill->discount);
}
if (!empty($billInfo->bill->from_deposit)) {
    $bankDetails .= '<br/>Оплачено с депозита: ' . CashHandler::toRubles($billInfo->bill->from_deposit);
}
if(!empty($billInfo->billPower)) {
    $bankDetails .= '<br/>Элекроэнергия';
    foreach ($billInfo->billPower as $item) {
        $powerInfo = DataPowerHandler::findOne($item->power_data_id);
        $bankDetails .= '<p>' . TimeHandler::getFullFromShotMonth($powerInfo->month) . ' ' . CashHandler::toRubles($powerInfo->total_pay) . '</p>';
    }
}

/** @var BankDetails $bankInfo */

/*$payInfo = $info['billInfo']['billInfo'];
$paymentContent = $info['billInfo']['paymentContent'];
$bankInfo = $info['bankInfo'];
$fromDeposit = CashHandler::toRubles($payInfo->depositUsed);
$discount = CashHandler::toRubles($payInfo->discount);
$realSumm = CashHandler::rublesMath(CashHandler::toRubles($payInfo->totalSumm) - $fromDeposit - $discount);
$smoothSumm = CashHandler::toSmoothRubles($realSumm);
$depositText = '';
if (!empty($fromDeposit)) {
    $depositText = '<br/>Оплачено с депозита: ' . CashHandler::toSmoothRubles($fromDeposit);
}
$discountText = '';
if (!empty($discount)) {
    $discountText = '<br/>Скидка: ' . CashHandler::toSmoothRubles($discount);
}

$powerText = '';
$memText = '';
$tarText = '';
$singleText = '';
$finesText = '';*/

$qr = $bankInfo->drawQR();

/*if (!empty($paymentContent['power']) || !empty($paymentContent['additionalPower'])) {
    $dueDate = TimeHandler::getPowerDueDate();
    $summ = 0;
    $oldData = null;
    $newData = null;
    $difference = null;
    $usedPower = [];
    $values = '';
    if (!empty($paymentContent['power'])) {
        $summ = $paymentContent['power']['summ'];
        foreach ($paymentContent['power']['values'] as $value) {
            $tempOldData = $value["old-data"];
            $tempNewData = $value["new-data"];
            if (empty($oldData)) {
                $oldData = $tempOldData;
            }
            if ($tempNewData >= $oldData) {
                $newData = $tempNewData;
                $difference += $tempNewData - $tempOldData;
            } else {
                // очевидно, был заменён счётчик
                $usedPower[] = ['start' => $oldData, 'finish' => $newData, 'difference' => $difference];
                $oldData = $tempOldData;
                $newData = $tempNewData;
                $difference = $newData - $oldData;

            }
        }
        $usedPower[] = ['start' => $oldData, 'finish' => $newData, 'difference' => $difference];
        foreach ($usedPower as $item) {
            $values .= "Последние оплаченные показания: {$item['start']} " . CashHandler::KW . ", новые показания: {$item['finish']}" . CashHandler::KW . ", итого потреблено: {$item['difference']}" . CashHandler::KW . " ";
        }
        $values .= "На сумму: " . CashHandler::toSmoothRubles($summ);
    }
    if (!empty($paymentContent['additionalPower'])) {
        $values .= "Дополнительный участок: ";
        $summ = 0;
        $oldData = null;
        $newData = null;
        $difference = null;
        $usedPower = [];
        $usedPower = [];
        $summ = $paymentContent['additionalPower']['summ'];
        foreach ($paymentContent['additionalPower']['values'] as $value) {
            $tempOldData = $value["old-data"];
            $tempNewData = $value["new-data"];
            if (empty($oldData)) {
                $oldData = $tempOldData;
            }
            if ($tempNewData >= $oldData) {
                $newData = $tempNewData;
                $difference += $tempNewData - $tempOldData;
            } else {
                // очевидно, был заменён счётчик
                $usedPower[] = ['start' => $oldData, 'finish' => $newData, 'difference' => $difference];
                $oldData = $tempOldData;
                $newData = $tempNewData;
                $difference = $newData - $oldData;
            }
        }
        $usedPower[] = ['start' => $oldData, 'finish' => $newData, 'difference' => $difference];
        foreach ($usedPower as $item) {
            $values .= "Последние оплаченные показания: {$item['start']} " . CashHandler::KW . ", новые показания: {$item['finish']}" . CashHandler::KW . ", итого потреблено: {$item['difference']}" . CashHandler::KW . " ";
        }
        $values .= "На сумму: " . CashHandler::toSmoothRubles($summ);
    }
    $powerText = '<p>Электроэнергия: ' . $values . ' (срок оплаты: до ' . $dueDate . " года)</p>";
}
if (!empty($paymentContent['membership']) || !empty($paymentContent['additionalMembership'])) {

    $summ = 0;
    $values = '';
    if (!empty($paymentContent['membership'])) {
        $summ += $paymentContent['membership']['summ'];
        foreach ($paymentContent['membership']['values'] as $value) {
            // проверю срок оплаты
            $values .= '<b>' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            if (TimeHandler::checkOverdueQuarter($value['date'])) {
                $values .= '(платёж просрочен)  ';
            } else {
                $values .= '(срок оплаты: до ' . TimeHandler::getPayUpQuarter($value['date']) . ')  ';
            }
        }
    }
    if (!empty($paymentContent['additionalMembership'])) {
        $summ += $paymentContent['additionalMembership']['summ'];
        foreach ($paymentContent['additionalMembership']['values'] as $value) {
            $values .= '<b>' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            if (TimeHandler::checkOverdueQuarter($value['date'])) {
                $values .= '(платёж просрочен)  ';
            } else {
                $values .= '(срок оплаты: до ' . TimeHandler::getPayUpQuarter($value['date']) . ')  ';
            }
        }
    }
    $memText = '<p>Членские взносы: всего ' . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '</p>';
}
if (!empty($paymentContent['target']) || !empty($paymentContent['additionalTarget'])) {
    $summ = 0;
    $values = '';
    if (!empty($paymentContent['target'])) {
        $summ += $paymentContent['target']['summ'];
        foreach ($paymentContent['target']['values'] as $value) {
            $values .= '<b>' . $value['year'] . ' год : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            // проверю просроченность платежа
            $payUpTime = TargetHandler::getPayUpTime($value['year']);
            if ($payUpTime < time()) {
                $values .= '(платёж просрочен)  ';
            } else {
                $values .= '(срок оплаты: до ' . TimeHandler::getDatetimeFromTimestamp($payUpTime) . ')  ';
            }
        }
    }
    if (!empty($paymentContent['additionalTarget'])) {
        $summ += $paymentContent['additionalTarget']['summ'];
        foreach ($paymentContent['additionalTarget']['values'] as $value) {
            $values .= '<b>' . $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']) . ', ';
        }
    }
    $tarText = "<p>Целевые взносы: всего " . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '</p><br/>';
}
if (!empty($paymentContent['single'])) {
    $summ = 0;
    $values = '';
    $summ += $paymentContent['single']['summ'];
    foreach ($paymentContent['single']['values'] as $value) {
        $values .= '<b>' . $value['description'] . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
    }
    $singleText = "<p>Дополнительно: всего " . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '</p>';
}

$fines = Table_view_fines_info::find()->where(['bill_id' => $payInfo->id])->all();
if(!empty($fines)){
    $finesSumm = 0;
    foreach ($fines as $fine) {
        $finesSumm += $fine->start_summ;
        if($fine->pay_type === 'membership'){
            $fullPeriod = TimeHandler::getFullFromShortQuarter($fine->period);
        }
        else if($fine->pay_type === 'power'){
            $fullPeriod = TimeHandler::getFullFromShotMonth($fine->period);
        }
        else{
            $fullPeriod = $fine->period;
        }
        $finesText .= FinesHandler::$types[$fine->pay_type] . " за {$fullPeriod} просрочено на {$fine->start_days} дней на сумму " . CashHandler::toSmoothRubles($fine->start_summ) . ', ';
    }
    $finesText = "<p>Пени: всего " . CashHandler::toSmoothRubles($finesSumm) . ", в том числе " . substr($finesText, 0, strlen($finesText) - 2) . '</p>';
}*/

$text = "
<div class='description margened'><span>ПАО СБЕРБАНК</span><span class='pull-right''>Форма №ПД-4</span></div>

<div class='text-center bottom-bordered'><b>{$bankInfo->name}</b></div>
<div class='text-center description margened'><span>(Наименование получателя платежа)</span></div>
<div class='bottom-bordered'><span><b>ИНН</b> {$bankInfo->payerInn} <b>КПП</b> {$bankInfo->kpp}</span><span class='pull-right'>{$bankInfo->personalAcc}</span></div>
<div class='description margened'><span>(инн получателя платежа)</span><span class='pull-right'>(номер счёта получателя платежа)</span></div>
<div class='bottom-bordered text-center'><span><b>БИК</b> {$bankInfo->bik} ({$bankInfo->bankName})</span></div>
<div class='text-center description margened'><span>(Наименование банка получателя платежа)</span></div>
<div class='bottom-bordered text-underline'><b>Участок </b>№{$bankInfo->cottageNumber} ;<b> ФИО:</b> {$bankInfo->lastName}; <b>Назначение:</b> {$bankInfo->purpose};</b></div>
<div class='description margened text-center'><span>(назначение платежа)</span></div>
<div class='text-center bottom-bordered'><b>Сумма: {$bankInfo->summ}</b></div>
<div class='description margened text-center'><span>(сумма платежа)</span></div>

<div class='description margened'><span>С условиями приёма указанной в платёжном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен. </span><br/><br/><span class='pull-right'>Подпись плательщика <span class='sign-span bottom-bordered'></span></span></div>
";
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Квитанции</title>
    <style type="text/css">
        div#invoiceWrapper {
            width: 180mm;
            margin: auto;
            font-size: 10px;
        }

        .margened {
            margin-bottom: 10px;
            margin-top: 5px;
        }

        td.leftSide {
            text-align: center;
            width: 65mm;
            border-right: 1px solid black;
        }

        img.qr-img {
            width: 80%;
        }

        .bottom-bordered {
            border-bottom: 1px solid black;
        }

        .description {
            font-size: 8px;
        }

        .text-underline {
        }

        .margened {
            margin-bottom: 10px;
        }

        .sign-span {
            width: 20mm;
            display: inline-block;
        }

        table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
            padding: 8px;
            line-height: 1.42857143;
            vertical-align: top;
            border-top: 1px solid #ddd;
        }

        .pull-right {
            float: right !important;
        }

        .text-center {
            text-align: center;
        }

        img.logo-img {
            width: 50%;
            margin-left: 25%;
        }
        p{
            line-height: 2;
        }
    </style>
</head>
<body>
<div id="invoiceWrapper">
    <img class="logo-img" src="<?php echo '/graphics/logo.png'; ?>" alt="logo">
    <table class="table">
        <tr>
            <td class="leftSide">
                <h3>Извещение</h3>
            </td>
            <td class="rightSide">
                <?= $text ?>
            </td>
        </tr>
        <tr>
            <td class="leftSide">
                <h3>Квитанция</h3>
                <img class="qr-img" src="<?= $qr ?>" alt=""/>
            </td>
            <td class="rightSide">
                <?= $text ?>
            </td>
        </tr>
    </table>
    <?=$bankDetails?>
</div>
<script>window.print()</script>
</body>
</html>

