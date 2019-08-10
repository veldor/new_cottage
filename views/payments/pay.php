<?php

use app\assets\BillPayAsset;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataTargetHandler;
use app\models\database\FinesHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\Pay;
use app\models\selection_classes\BillInfo;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use kartik\switchinput\SwitchInput;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $bill_info BillInfo */
/* @var $model Pay */

$this->title = "Счёт #" . $bill_info->bill->id . ', оплата';

BillPayAsset::register($this);
ShowLoadingAsset::register($this);

$totalAmount = $bill_info->bill->bill_summ - $bill_info->bill->payed;

echo "<div class='row'><div class='col-sm-12'><h1 class='text-center'>Распределение средств</h1></div>";

echo "<div class='col-sm-12'><h1 class='text-center'>Счёт #{$bill_info->bill->id}</h1></div>";
echo "<div class='col-sm-12'><h2 class='text-center'>Участок #{$bill_info->cottage->cottage_number}</h2></div>";
echo "<div class='col-sm-12'><h2 class='text-center'>Счёт на сумму <b class='text-warning'>" . CashHandler::toRubles($bill_info->bill->bill_summ) . "</b></h2></div>";
echo "<div class='col-sm-12'><h2 class='text-center'>Оплачено <b class='text-success'>" . CashHandler::toRubles($bill_info->bill->payed) . "</b></h2></div>";
if($totalAmount > 0){
    echo "<div class='col-sm-12'><h2 class='text-center'>Осталось оплатить <b class='text-danger'>" . CashHandler::toRubles($totalAmount) . "</b></h2></div>";
}
elseif($totalAmount == 0){
    echo "<div class='col-sm-12'><h2 class='text-center'><b class='text-success'>Оплачено полностью</b></h2></div>";
}
else{
    echo "<div class='col-sm-12'><h2 class='text-center'>Оплачено полностью, переплата <b class='text-success'>" . CashHandler::toRubles(- $totalAmount) . "</b></h2></div>";
}
if($totalAmount < 0){
    $totalAmount = 0;
}
$form = ActiveForm::begin(['id' => 'payBill', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/pay/bill']]);

echo $form->field($model, 'billId', ['options' => ['class' => 'hidden',], 'template' => '{input}'])->hiddenInput([ 'value' => $bill_info->bill->id])->label(false);
echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden', ], 'template' => '{input}'])->hiddenInput(['value' => $bill_info->bill->cottage_number])->label(false);

echo $form->field($model, 'payedSumm', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-4"><div class="input-group"><span class="btn btn-success input-group-addon all-distributed-button">Максимум</span>{input}<span class="input-group-addon">₽</span></div>{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01',  'data-max-summ' => $totalAmount])
    ->hint('Оплачено по счёту')
    ->label('Сумма');

$value = !empty($bill_info->bankTransaction) && empty($bill_info->bankTransaction->bounded_transaction_id) ? $bill_info->bankTransaction->bank_operation_id : '';
echo $form->field($model, 'bankTransactionId', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-4">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'value' => $value])
    ->hint('Идентификатор банковской транзакции')
    ->label('Банковская транзакция');

echo "<div class='col-sm-12' id='bankTransactionInfo'></div>";

echo '<div class="form-group margened"><div class="col-sm-5"><label class="control-label" for="payCustomDate">Дата платежа</label></div><div class="col-sm-4"><input type="date" class="form-control" id="payCustomDate" name="Pay[payCustomDate]"></div></div>';
echo "<div class='margened'></div>";
echo '<div class="form-group margened"><div class="col-sm-5"><label class="control-label" for="getCustomDate">Дата поступления на счёт</label></div><div class="col-sm-4"><input type="date" class="form-control" id="getCustomDate" name="Pay[getCustomDate]"></div></div>';

echo "<div class='col-sm-12'><h2 class='text-center'>Детали</h2></div>";

if(!empty($bill_info->billPower)){
    foreach ($bill_info->billPower as $powerItem) {
        $popover = '';
        $powerInfo = DataPowerHandler::findOne($powerItem->power_data_id);
        $popover .= 'Электроэнергия <b class="text-info">' . TimeHandler::getFullFromShotMonth($powerInfo->month) . '</b><br/>';
        $popover .= 'Всего к оплате <b class="text-warning">' . CashHandler::toRubles($powerInfo->total_pay) . '</b><br/>';
        $popover .= 'Всего оплачено <b class="text-success">' . CashHandler::toRubles($powerInfo->payed_summ) . '</b><br/>';
        $popover .= 'Осталось оплатить всего <b class="text-success">' . CashHandler::toRubles($powerInfo->total_pay - $powerInfo->payed_summ) . '</b><br/>';
        $popover .= 'К оплате по счёту <b class="text-warning">' . CashHandler::toRubles($powerItem->start_summ) . '</b><br/>';
        $payedAmount = 0;
        $payed = PayedPowerHandler::find()->where(['period_id' => $powerItem->power_data_id, 'bill_id' => $bill_info->bill->id])->all();
        if(!empty($payed)){
            foreach ($payed as $payedItem) {
                $payedAmount += $payedItem->summ;
            }
        }
        $max = $powerItem->start_summ - $payedAmount;
        $popover .= 'Оплачено по счёту <b class="text-success">' . CashHandler::toRubles($payedAmount) . '</b><br/>';

        $popover .= 'Осталось оплатить по счёту <b class="text-success">' . CashHandler::toRubles($powerItem->start_summ - $payedAmount) . '</b><br/>';
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Электроэнергия (" . TimeHandler::getFullFromShotMonth($powerInfo->month) . ")</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[power][{$powerItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
    }
}
if(!empty($bill_info->billMembership)){
    foreach ($bill_info->billMembership as $membershipItem) {
        $membershipInfo = DataMembershipHandler::findOne($membershipItem->membership_data_id);
        // Получу цифры
        $amount = CashHandler::toRubles($membershipItem->start_summ);
        $popover = "К оплате: $amount<br/>";
        $payedAmount = 0;
        $payed = PayedMembershipHandler::find()->where(['period_id' => $membershipItem->membership_data_id, 'bill_id' => $bill_info->bill->id])->all();
        if(!empty($payed)){
            foreach ($payed as $payedItem) {
                $payedAmount += $payedItem->summ;
            }
        }
        $popover .= "Уже оплачено: " . CashHandler::toRubles($payedAmount);
        $max = $membershipItem->start_summ - $payedAmount;
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Членские взносы (" . TimeHandler::getFullFromShotQuarter($membershipInfo->quarter) . ")</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[membership][{$membershipItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
    }
}
if(!empty($bill_info->billTarget)){
    foreach ($bill_info->billTarget as $targetItem) {
        $targetInfo = DataTargetHandler::findOne($targetItem->year_id);
        // Получу цифры
        $amount = CashHandler::toRubles($targetItem->start_summ);
        $popover = "К оплате: $amount<br/>";
        $payedAmount = 0;
        $payed = PayedTargetHandler::find()->where(['period_id' => $targetItem->year_id, 'bill_id' => $bill_info->bill->id])->all();
        if(!empty($payed)){
            foreach ($payed as $payedItem) {
                $payedAmount += $payedItem->summ;
            }
        }
        $popover .= "Уже оплачено: " . CashHandler::toRubles($payedAmount);
        $max = $targetItem->start_summ - $payedAmount;
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Целевые взносы ({$targetInfo->year} год)</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[target][{$targetItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
    }
}
if(!empty($bill_info->billSingle)){
    foreach ($bill_info->billSingle as $singleItem) {
        // Получу цифры
        $amount = CashHandler::toRubles($singleItem->start_summ);
        $popover = "К оплате: $amount<br/>";
        $payedAmount = 0;
        $payed = PayedSingleHandler::find()->where(['pay_id' => $singleItem->single_id, 'bill_id' => $bill_info->bill->id])->all();
        if(!empty($payed)){
            foreach ($payed as $payedItem) {
                $payedAmount += $payedItem->summ;
            }
        }
        $popover .= "Уже оплачено: " . CashHandler::toRubles($payedAmount);
        $max = $singleItem->start_summ - $payedAmount;
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Разные взносы</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[single][{$singleItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
    }
}
if(!empty($bill_info->billFines)){
    foreach ($bill_info->billFines as $finesItem) {
        $finesInfo = FinesHandler::findOne($finesItem->fines_id);
        // Получу цифры
        $amount = CashHandler::toRubles($finesItem->start_summ);
        $popover = "К оплате: $amount<br/>";
        $payedAmount = 0;
        $payed = PayedFinesHandler::find()->where(['fines_id' => $finesItem->fines_id, 'bill_id' => $bill_info->bill->id])->all();
        if(!empty($payed)){
            foreach ($payed as $payedItem) {
                $payedAmount += $payedItem->summ;
            }
        }
        $popover .= "Уже оплачено: " . CashHandler::toRubles($payedAmount);
        $max = $finesItem->start_summ - $payedAmount;
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Пени (" . FinesHandler::$types[$finesInfo->pay_type] . ")</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[fines][{$finesItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
    }
}

try {
    echo $form->field($model, 'notify', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-4">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group margened']])->widget(SwitchInput::class, [
        'type' => SwitchInput::CHECKBOX,
        'pluginOptions' => [
            'onText'=>'Да',
            'offText'=>'Нет',
            'handleWidth'=>20,
        ]
    ])
        ->label('Оповестить по электронной почте');

} catch (Exception $e) {
    echo $e->getMessage();
    die('i broke');
}

ActiveForm::end();
echo "</div>";

?>
<div id="toolbar">
    <div class="btn-group pull-left">
        <button type="button" id="submitForm" class="btn btn-default">Оплатить</button>
    </div>
    <div class="pull-left"><span id="bill-status" class="text-default"></span></div>
</div>