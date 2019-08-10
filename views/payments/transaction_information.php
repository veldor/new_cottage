<?php

use app\assets\MainAsset;
use app\models\database\BankTransactionsHandler;
use app\models\database\ContactsHandler;
use app\models\database\DepositHandler;
use app\models\database\DiscountHandler;
use app\models\database\FinesHandler;
use app\models\selection_classes\TransactionInfo;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use yii\web\View;


/* @var $this View */
/* @var $transaction_info TransactionInfo */

MainAsset::register($this);

$this->title = "Транзакция #{$transaction_info->transaction->id}";

// Найду плательщика
$payer = ContactsHandler::findOne($transaction_info->billInfo->payerId);

$bankTransactionInfo = BankTransactionsHandler::findOne(['bounded_transaction_id' => $transaction_info->transaction->id]);
$bankTransactionId = !empty($bankTransactionInfo) ? $bankTransactionInfo->bank_operation_id : 'Не привязана';

$fromDeposit = DepositHandler::findOne(['transaction_id' => $transaction_info->transaction->id, 'destination' => 'out']);
$fromDepositSumm = !empty($fromDeposit) ? CashHandler::toRubles($fromDeposit->summ) : '--';
$toDepositItems = DepositHandler::find()->where(['transaction_id' => $transaction_info->transaction->id, 'destination' => 'in'])->all();
$toDeposit = 0;
if(!empty($toDepositItems)){
    foreach ($toDepositItems as $toDepositItem) {
        $toDeposit += $toDepositItem->summ;
    }
}
$toDepositSumm = !empty($toDeposit) ? CashHandler::toRubles($toDeposit) : '--';
$discount = DiscountHandler::findOne(['transaction_id' => $transaction_info->transaction->id]);
$discountSumm = !empty($discount) ? CashHandler::toRubles($discount->summ) : '--';

?>

<div class="row">
    <div class="col-sm-12">
        <table class="table table-hover">
            <tr><td>Номер счёта</td><td><a target="_blank" href="/bill/show/<?=$transaction_info->billInfo->id?>"><?=$transaction_info->billInfo->id?></a></td></tr>
            <tr><td>Номер участка</td><td><a target="_blank" href="/cottage/show/<?=$transaction_info->cottageInfo->id?>"><?=$transaction_info->cottageInfo->cottage_number?></a></td></tr>
            <tr><td>Плательщик</td><td><?=$payer->contact_name;?></td></tr>
            <tr><td>Банковская транзакция</td><td><?=$bankTransactionId?></td></tr>
            <tr><td>Дата оплаты</td><td><?= TimeHandler::timestampToDate($transaction_info->transaction->payDate)?></td></tr>
            <tr><td>Дата поступления на счёт</td><td><?= TimeHandler::timestampToDate($transaction_info->transaction->bankDate)?></td></tr>
            <tr><td>Сумма транзакции</td><td><?= CashHandler::toRubles($transaction_info->transaction->summ)?></td></tr>
            <tr><td>Оплата с депозита</td><td><?= $fromDepositSumm?></td></tr>
            <tr><td>Скидка</td><td><?= $discountSumm?></td></tr>
            <tr><td>Зачислено на депозит</td><td><?= $toDepositSumm?></td></tr>

            <?php
            if(!empty($transaction_info->payedPower)){
                echo "<tr><td colspan='2'><h2 class='text-center'>Оплачено электричество</h2></td></tr>";
                foreach ($transaction_info->payedPower as $item) {
                    echo " <tr><td>" . TimeHandler::getFullFromShotMonth($item->month) . "</td><td>" . CashHandler::toRubles($item->summ) . "</td></tr>";
                }
            }
            if(!empty($transaction_info->payedMembership)){
                echo "<tr><td colspan='2'><h2 class='text-center'>Оплачены членские взносы</h2></td></tr>";
                foreach ($transaction_info->payedMembership as $item) {
                    echo " <tr><td>" . TimeHandler::getFullFromShotQuarter($item->quarter) . "</td><td>" . CashHandler::toRubles($item->summ) . "</td></tr>";
                }
            }
            if(!empty($transaction_info->payedTarget)){
                echo "<tr><td colspan='2'><h2 class='text-center'>Оплачены целевые взносы</h2></td></tr>";
                foreach ($transaction_info->payedTarget as $item) {
                    echo " <tr><td>{$item->year} год</td><td>" . CashHandler::toRubles($item->summ) . "</td></tr>";
                }
            }
            if(!empty($transaction_info->payedSingle)){
                echo "<tr><td colspan='2'><h2 class='text-center'>Оплачены разные взносы</h2></td></tr>";
                foreach ($transaction_info->payedSingle as $item) {
                    echo " <tr><td>{$item->pay_id}</td><td>" . CashHandler::toRubles($item->summ) . "</td></tr>";
                }
            }
            if(!empty($transaction_info->payedFines)){
                echo "<tr><td colspan='2'><h2 class='text-center'>Оплачены пени</h2></td></tr>";
            foreach ($transaction_info->payedFines as $item) {
                $finesInfo = FinesHandler::findOne($item->fines_id);
                $type = FinesHandler::$types[$finesInfo->pay_type];
                $period = FinesHandler::getPeriod($finesInfo->pay_type, $finesInfo->period_id);
                echo " <tr><td>$type $period</td><td>" . CashHandler::toRubles($item->summ) . "</td></tr>";
            }
            }
            ?>
        </table>
    </div>
</div>
