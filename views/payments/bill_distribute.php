<?php

use app\assets\BillDistributeAsset;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataTargetHandler;
use app\models\database\FinesHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\TransactionsHandler;
use app\models\selection_classes\BillInfo;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use kartik\switchinput\SwitchInput;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;
use yii\widgets\ActiveForm;

BillDistributeAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $bill_info BillInfo */
/* @var $matrix TransactionsHandler */

$this->title = "Счёт #" . $bill_info->bill->id . ', распределение средств';

if(!$bill_info->bill->is_undistributed){
    echo "<script>window.close();</script>";
    die;
}
$payedSumm = $bill_info->bill->payed;

// проверю, есть ли нераспределённые средства
$payed = TransactionsHandler::find()->where(['bill_id' => $bill_info->bill->id])->all();
$accounted = 0;
if(!empty($payed)){
    foreach ($payed as $payedItem) {
        $accounted += $payedItem->summ;
    }
}
if($accounted >= $payedSumm){
    die('все средства учтены');
}
echo "<div class='row'><div class='col-sm-12'><h1 class='text-center'>Распределение средств</h1></div><div class='col-sm-12'>";
echo "<h2 class='text-center'>Счёт #{$bill_info->bill->id}</h2>";
echo "<h2 class='text-center'>Участок #{$bill_info->cottage->cottage_number}</h2>";
$form = ActiveForm::begin(['id' => 'distributeBill', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/bill/distribute']]);

echo $form->field($matrix, 'bill_id', ['options' => ['class' => 'hidden',], 'template' => '{input}'])->hiddenInput([ 'value' => $bill_info->bill->id])->label(false);
echo $form->field($matrix, 'cottage_id', ['options' => ['class' => 'hidden', ], 'template' => '{input}'])->hiddenInput(['value' => $bill_info->bill->cottage_number])->label(false);

echo $form->field($matrix, 'summ', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01', 'readonly' => true, 'value' => CashHandler::toMathRubles($payedSumm - $accounted)])
    ->hint('Сумма нераспределённых средств')
    ->label('Сумма');

if(!empty($bill_info->billPower)){
    foreach ($bill_info->billPower as $powerItem) {
        $powerInfo = DataPowerHandler::findOne($powerItem->power_data_id);
        // Получу цифры
        $amount = CashHandler::toRubles($powerItem->start_summ);
        $popover = "К оплате: $amount<br/>";
        $payedAmount = 0;
        $payed = PayedPowerHandler::find()->where(['period_id' => $powerItem->power_data_id, 'bill_id' => $bill_info->bill->id])->all();
        if(!empty($payed)){
            foreach ($payed as $payedItem) {
                $payedAmount += $payedItem->summ;
            }
        }
        $max = CashHandler::toMathRubles($powerItem->start_summ - $payedAmount);
        $popover .= "Уже оплачено: " . CashHandler::toRubles($payedAmount);
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Электроэнергия (" . TimeHandler::getFullFromShotMonth($powerInfo->month) . ")</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='TransactionsHandler[power][{$powerItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
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
        $max = CashHandler::toMathRubles($membershipItem->start_summ - $payedAmount);
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Членские взносы (" . TimeHandler::getFullFromShotQuarter($membershipInfo->quarter) . ")</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='TransactionsHandler[membership][{$membershipItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
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
        $max = CashHandler::toMathRubles($targetItem->start_summ - $payedAmount);
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Целевые взносы ({$targetInfo->year} год)</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='TransactionsHandler[target][{$targetItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
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
        $max = CashHandler::toMathRubles($singleItem->start_summ - $payedAmount);
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Разные взносы</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='TransactionsHandler[single][{$singleItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
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
        $max = CashHandler::toMathRubles($finesItem->start_summ - $payedAmount);
        echo "<div class='form-group margened payment-details'><div class='col-sm-5'><label class='control-label'>Пени (" . FinesHandler::$types[$finesInfo->pay_type] . ")</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='$max' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='TransactionsHandler[fines][{$finesItem->id}]' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div></div></div>";
    }
}
try {
    echo $form->field($matrix, 'notify', ['template' =>
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
        <button type="button" id="submitForm" class="btn btn-default">Сохранить</button>
    </div>
    <div><span id="bill-status">Не распределено: <span data-amount="<?=CashHandler::toMathRubles($payedSumm - $accounted);?>" id="undistributedAmountView"><?=CashHandler::toRubles($payedSumm - $accounted);?></span></span></div>
</div>

