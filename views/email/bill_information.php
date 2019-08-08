<?php

use app\assets\MainAsset;
use app\models\database\ContactsHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\FinesHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\TariffMembershipHandler;
use app\models\database\TariffPowerHandler;
use app\models\database\TariffTargetHandler;
use app\models\selection_classes\BillInfo;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use yii\web\View;

MainAsset::register($this);


/* @var $this View */
/* @var $bill_info BillInfo */

$this->title = "Счёт #" . $bill_info->bill->id;

// Найду плательщика
$payer = ContactsHandler::findOne($bill_info->bill->payerId);

?>

<div class="row">
    <div class="col-sm-12">
        <h3 class="text-center">Вам выставлен счёт.</h3>
        <p>В этом письме квитанция для оплаты счёта и подробная информация о его содержимом.</p>
    </div>
    <div class="col-sm-12">
        <table class="table table-hover">
            <tr><td>Номер счёта</td><td><?=$bill_info->bill->id?></td></tr>
            <tr><td>Номер участка</td><td><?=$bill_info->cottage->cottage_number?></td></tr>
            <tr><td>Плательщик</td><td><?=$payer->contact_name;?></td></tr>
            <tr>
                <td>Счёт создан</td>
                <td>
                    <?php try {
                        echo TimeHandler::timestampToDate($bill_info->bill->time_create);
                    } catch (Exception $e) {
                    } ?>
                </td>
            </tr>
            <tr><td>Сумма к оплате</td><td><b class="text-info"><?= CashHandler::toRubles($bill_info->bill->bill_summ)?></b></td></tr>
            <tr><td>Оплата с депозита</td><td><b class="text-info"><?= CashHandler::toRubles($bill_info->bill->from_deposit)?></b></td></tr>
            <tr><td>Скидка</td><td><b class="text-info"><?= CashHandler::toRubles($bill_info->bill->discount)?></b></td></tr>
            <tr><td>Оплачено</td><td><b class="text-success"><?= CashHandler::toRubles($bill_info->bill->payed)?></b></td></tr>
            <?php
            $transactionsText = '';
            // отображу транзакции по счёту
            if(!empty($bill_info->transactions)){
                foreach ($bill_info->transactions as $transaction) {
                    $transactionsText.= "<a target='_blank' href='/transaction/show/{$transaction->transaction->id}'>{$transaction->transaction->id}</a><br>";
                }
            }
            else{
                $transactionsText = 'отсутствуют';
            }
            echo "<tr><td>Транзакции по счёту</td><td>$transactionsText</td></tr>";
            ?>
            <tr><td colspan="2" class=""><h2 class="text-info">Состав счёта</h2></td></tr>
            <tr><td colspan="2" class=""><h3 class="text-primary">Электроэнергия</h3></td></tr>
            <?php
            if(!empty($bill_info->billPower)){
                echo "<tr><td>Месяцев к оплате</td><td>" . count($bill_info->billPower) . "</td></tr>";
                $totalAmount = 0;
                $totalPayed = 0;
                foreach ($bill_info->billPower as $item) {
                    $totalAmount += $item->start_summ;
                    $powerInfo = DataPowerHandler::findOne($item->power_data_id);
                    $tariff = TariffPowerHandler::findOne(['month' => $powerInfo->month]);
                    // оплата по данному счёту
                    $payedInThisBill = 0;
                    $payedTotal = 0;

                    // лимит
                    $powerLimit = $powerInfo->individual_limit ? $powerInfo->individual_limit : ($powerInfo->is_limit_ignored ? 0 : $tariff->power_limit);
                    $payed = PayedPowerHandler::find()->where(['period_id' => $item->power_data_id])->all();
                    if(!empty($payed)){
                        foreach ($payed as $payedItem) {
                            $payedTotal += $payedItem->summ;
                            if($payedItem->bill_id === $bill_info->bill->id){
                                $payedInThisBill += $payedItem->summ;
                            }
                        }
                    }
                    try {
                        $payUp = TimeHandler::timestampToDate($powerInfo->pay_up_date);
                    } catch (Exception $e) {
                    }
                    $totalPayed += $payedInThisBill;
                    $powerCost = $powerInfo->individual_cost ? CashHandler::toRubles($powerInfo->individual_cost) : CashHandler::toRubles($tariff->power_cost);
                    $powerOvercost = $powerInfo->individual_overcost ? CashHandler::toRubles($powerInfo->individual_overcost) : CashHandler::toRubles($tariff->power_overcost);
                    echo "<tr class='shadowed'><td>Период</td><td>" . TimeHandler::getFullFromShotMonth($powerInfo->month) . "</td></tr>";
                    echo "<tr><td>Начислено за месяц</td><td><b class='text-warning'>" . CashHandler::toRubles($powerInfo->total_pay) . "</b></td></tr>";
                    echo "<tr><td>Оплачено</td><td><b class='text-success'>" . CashHandler::toRubles($powerInfo->payed_summ) . "</b></td></tr>";
                    echo "<tr><td>Потребление электроэнергии</td><td><b class='text-info'>{$powerInfo->difference} " . GrammarHandler::KILOWATT . "</b></td></tr>";
                    echo "<tr><td>Начальные показания</td><td><b class='text-primary'>{$powerInfo->old_data} " . GrammarHandler::KILOWATT . "</b></td></tr>";
                    echo "<tr><td>Конечные показания</td><td><b class='text-primary'>{$powerInfo->new_data} " . GrammarHandler::KILOWATT . "</b></td></tr>";
                    echo "<tr><td>Льготный лимит</td><td><b class='text-primary'>$powerLimit " . GrammarHandler::KILOWATT . "</b></td></tr>";
                    echo "<tr><td>Льготный тариф</td><td><b class='text-primary'>$powerCost</b></td></tr>";
                    echo "<tr><td>Льготная стоимость</td><td><b class='text-warning'>{$powerInfo->in_limit_data} " . GrammarHandler::KILOWATT . " * $powerCost = " . CashHandler::toRubles($powerInfo->in_limit_pay) . "</b></td></tr>";
                    echo "<tr><td>Обычный тариф</td><td><b class='text-primary'>$powerOvercost</b></td></tr>";
                    echo "<tr><td>Сверх лимита</td><td><b class='text-warning'>{$powerInfo->over_limit_data} " . GrammarHandler::KILOWATT . " * $powerOvercost = " . CashHandler::toRubles($powerInfo->over_limit_pay) . "</b></td></tr>";
                    echo "<tr><td>К оплате по счёту</td><td><b class='text-danger'>" . CashHandler::toRubles($item->start_summ) . "</b></td></tr>";
                    echo "<tr><td>Оплачено всего</td><td><b class='text-success'>" . CashHandler::toRubles($payedTotal) . "</b></td></tr>";
                    echo "<tr><td>Оплачено по данному счёту</td><td><b class='text-success'>" . CashHandler::toRubles($payedInThisBill) . "</b></td></tr>";
                    echo "<tr><td>Срок оплаты</td><td><b class='text-success'> до $payUp</b></td></tr>";
                }
                echo "<tr class='important'><td>Итого начислено за электроэнергию</td><td><b class='text-danger'>" . CashHandler::toRubles($totalAmount) . "</b></td></tr>";
                echo "<tr class='important'><td>Из них оплачено</td><td><b class='text-danger'>" . CashHandler::toRubles($totalPayed) . "</b></td></tr>";
            }
            else echo "<tr><td colspan='2'>Не оплачивается</td></tr>";
            echo '<tr><td colspan="2" class=""><h3 class="text-primary">Членские взносы</h3></td></tr>';
            if(!empty($bill_info->billMembership)){
                echo "<tr><td>Кварталов к оплате</td><td>" . count($bill_info->billMembership) . "</td></tr>";
                $totalAmount = 0;
                $totalPayed = 0;
                foreach ($bill_info->billMembership as $item) {
                    $membershipInfo = DataMembershipHandler::findOne($item->membership_data_id);
                    $tariff = TariffMembershipHandler::findOne(['quarter' => $membershipInfo->quarter]);
                    // оплата по данному счёту
                    $payedInThisBill = 0;
                    $payedTotal = 0;
                    $totalAmount += $item->start_summ;
                    // лимит
                    $payed = PayedMembershipHandler::find()->where(['period_id' => $item->membership_data_id])->all();
                    if(!empty($payed)){
                        foreach ($payed as $payedItem) {
                            $payedTotal += $payedItem->summ;
                            if($payedItem->bill_id === $bill_info->bill->id){
                                $payedInThisBill += $payedItem->summ;
                                $totalPayed += $payedInThisBill;
                            }
                        }
                    }
                    try {
                        $payUp = TimeHandler::timestampToDate($membershipInfo->pay_up_date);
                    } catch (Exception $e) {
                    }

                    $payForCottage = $membershipInfo->individual_pay_for_cottage ? $membershipInfo->individual_pay_for_cottage : $tariff->pay_for_cottage;
                    $payForSquare = $membershipInfo->individual_pay_for_field ? $membershipInfo->individual_pay_for_field : $tariff->pay_for_meter;
                    $payForMeter = $payForSquare / 100;
                    echo "<tr class='shadowed'><td>Период</td><td>" . TimeHandler::getFullFromShotQuarter($membershipInfo->quarter) . "</td></tr>";
                    echo "<tr><td>Начислено за квартал</td><td><b class='text-warning'>" . CashHandler::toRubles($membershipInfo->total_pay) . "</b></td></tr>";
                    echo "<tr><td>Оплачено</td><td><b class='text-success'>" . CashHandler::toRubles($membershipInfo->payed_summ) . "</b></td></tr>";
                    echo "<tr><td>Площадь участка</td><td><b class='text-info'>{$membershipInfo->square} m<sup>2</sup></b></td></tr>";
                    echo "<tr><td>Оплата с участка</td><td><b class='text-info'>" . CashHandler::toRubles($payForCottage) . "</sup></b></td></tr>";
                    echo "<tr><td>Оплата с сотки</td><td><b class='text-info'>" . CashHandler::toRubles($payForSquare) . "</sup></b></td></tr>";
                    echo "<tr><td>Оплата с метра<sup>2</sup></td><td><b class='text-info'>" . CashHandler::toRubles($payForMeter) . "</sup></b></td></tr>";
                    echo "<tr><td>Итого с площади</td><td><b class='text-info'>" . CashHandler::toRubles($payForMeter) . " * {$membershipInfo->square} m<sup>2</sup> = " . CashHandler::toRubles($membershipInfo->total_pay - $payForCottage) . "</sup></b></td></tr>";
                    echo "<tr><td>К оплате по счёту</td><td><b class='text-danger'>" . CashHandler::toRubles($item->start_summ) . "</b></td></tr>";
                    echo "<tr><td>Оплачено всего</td><td><b class='text-success'>" . CashHandler::toRubles($payedTotal) . "</b></td></tr>";
                    echo "<tr><td>Оплачено по данному счёту</td><td><b class='text-success'>" . CashHandler::toRubles($payedInThisBill) . "</b></td></tr>";
                    echo "<tr><td>Срок оплаты</td><td><b class='text-success'> до $payUp</b></td></tr>";
                }
                echo "<tr class='important'><td>Итого начислено за членские взносы</td><td><b class='text-danger'>" . CashHandler::toRubles($totalAmount) . "</b></td></tr>";
                echo "<tr class='important'><td>Из них оплачено</td><td><b class='text-danger'>" . CashHandler::toRubles($totalPayed) . "</b></td></tr>";
            }
            else echo "<tr><td colspan='2'>Не оплачивается</td></tr>";

            echo '<tr><td colspan="2" class=""><h3 class="text-primary">Целевые взносы</h3></td></tr>';
            if(!empty($bill_info->billTarget)){
                echo "<tr><td>Лет к оплате</td><td>" . count($bill_info->billTarget) . "</td></tr>";
                $totalAmount = 0;
                $totalPayed = 0;
                foreach ($bill_info->billTarget as $item) {
                    $targetInfo = DataTargetHandler::findOne($item->year_id);
                    $tariff = TariffTargetHandler::findOne(['year' => $targetInfo->year]);
                    // оплата по данному счёту
                    $payedInThisBill = 0;
                    $payedTotal = 0;
                    $totalAmount += $item->start_summ;
                    // лимит
                    $payed = PayedTargetHandler::find()->where(['period_id' => $item->year_id])->all();
                    if(!empty($payed)){
                        foreach ($payed as $payedItem) {
                            $payedTotal += $payedItem->summ;
                            if($payedItem->bill_id === $bill_info->bill->id){
                                $payedInThisBill += $payedItem->summ;
                                $totalPayed += $payedInThisBill;
                            }
                        }
                    }
                    try {
                        $payUp = TimeHandler::timestampToDate($membershipInfo->pay_up_date);
                    } catch (Exception $e) {
                    }

                    $payForCottage = $targetInfo->individual_pay_for_cottage ? $targetInfo->individual_pay_for_cottage : $tariff->pay_for_cottage;
                    $payForSquare = $targetInfo->individual_pay_for_field ? $targetInfo->individual_pay_for_field : $tariff->pay_for_meter;
                    $payForMeter = $payForSquare / 100;
                    echo "<tr class='shadowed'><td>Период</td><td>{$targetInfo->year} год</td></tr>";
                    echo "<tr><td>Цель платежа</td><td>{$tariff->pay_description}</td></tr>";
                    echo "<tr><td>Начислено за год</td><td><b class='text-warning'>" . CashHandler::toRubles($targetInfo->total_pay) . "</b></td></tr>";
                    echo "<tr><td>Оплачено</td><td><b class='text-success'>" . CashHandler::toRubles($targetInfo->payed_summ) . "</b></td></tr>";
                    echo "<tr><td>Площадь участка</td><td><b class='text-info'>{$targetInfo->square} m<sup>2</sup></b></td></tr>";
                    echo "<tr><td>Оплата с участка</td><td><b class='text-info'>" . CashHandler::toRubles($payForCottage) . "</sup></b></td></tr>";
                    echo "<tr><td>Оплата с сотки</td><td><b class='text-info'>" . CashHandler::toRubles($payForSquare) . "</sup></b></td></tr>";
                    echo "<tr><td>Оплата с метра<sup>2</sup></td><td><b class='text-info'>" . CashHandler::toRubles($payForMeter) . "</sup></b></td></tr>";
                    echo "<tr><td>Итого с площади</td><td><b class='text-info'>" . CashHandler::toRubles($payForMeter) . " * {$targetInfo->square} m<sup>2</sup> = " . CashHandler::toRubles($targetInfo->total_pay - $payForCottage) . "</sup></b></td></tr>";
                    echo "<tr><td>К оплате по счёту</td><td><b class='text-danger'>" . CashHandler::toRubles($item->start_summ) . "</b></td></tr>";
                    echo "<tr><td>Оплачено всего</td><td><b class='text-success'>" . CashHandler::toRubles($payedTotal) . "</b></td></tr>";
                    echo "<tr><td>Оплачено по данному счёту</td><td><b class='text-success'>" . CashHandler::toRubles($payedInThisBill) . "</b></td></tr>";
                    echo "<tr><td>Срок оплаты</td><td><b class='text-success'> до $payUp</b></td></tr>";
                }
                echo "<tr class='important'><td>Итого начислено за целевые взносы</td><td><b class='text-danger'>" . CashHandler::toRubles($totalAmount) . "</b></td></tr>";
                echo "<tr class='important'><td>Из них оплачено</td><td><b class='text-danger'>" . CashHandler::toRubles($totalPayed) . "</b></td></tr>";
            }
            else echo "<tr><td colspan='2'>Не оплачивается</td></tr>";

            echo '<tr><td colspan="2" class=""><h3 class="text-primary">Разные взносы</h3></td></tr>';
            if(!empty($bill_info->billSingle)){
                echo "<tr><td>Платежей к оплате</td><td>" . count($bill_info->billSingle) . "</td></tr>";
                $totalAmount = 0;
                $totalPayed = 0;
                foreach ($bill_info->billSingle as $item) {
                    $singleInfo = DataSingleHandler::findOne($item->single_id);
                    // оплата по данному счёту
                    $payedInThisBill = 0;
                    $payedTotal = 0;
                    $totalAmount += $item->start_summ;
                    // лимит
                    $payed = PayedSingleHandler::find()->where(['pay_id' => $item->single_id])->all();
                    if(!empty($payed)){
                        foreach ($payed as $payedItem) {
                            $payedTotal += $payedItem->summ;
                            if($payedItem->bill_id === $bill_info->bill->id){
                                $payedInThisBill += $payedItem->summ;
                                $totalPayed += $payedInThisBill;
                            }
                        }
                    }
                    try {
                        $payUp = TimeHandler::timestampToDate($membershipInfo->pay_up_date);
                    } catch (Exception $e) {
                    }

                    echo "<tr class='shadowed'><td>Цель платежа</td><td>{$singleInfo->pay_description}</td></tr>";
                    echo "<tr><td>Начислено</td><td><b class='text-warning'>" . CashHandler::toRubles($singleInfo->total_pay) . "</b></td></tr>";
                    echo "<tr><td>Оплачено</td><td><b class='text-success'>" . CashHandler::toRubles($singleInfo->payed_summ) . "</b></td></tr>";
                    echo "<tr><td>К оплате по счёту</td><td><b class='text-danger'>" . CashHandler::toRubles($item->start_summ) . "</b></td></tr>";
                    echo "<tr><td>Оплачено всего</td><td><b class='text-success'>" . CashHandler::toRubles($payedTotal) . "</b></td></tr>";
                    echo "<tr><td>Оплачено по данному счёту</td><td><b class='text-success'>" . CashHandler::toRubles($payedInThisBill) . "</b></td></tr>";
                    echo "<tr><td>Срок оплаты</td><td><b class='text-success'> до $payUp</b></td></tr>";
                }
                echo "<tr class='important'><td>Итого начислено за разные взносы</td><td><b class='text-danger'>" . CashHandler::toRubles($totalAmount) . "</b></td></tr>";
                echo "<tr class='important'><td>Из них оплачено</td><td><b class='text-danger'>" . CashHandler::toRubles($totalPayed) . "</b></td></tr>";
            }
            else echo "<tr><td colspan='2'>Не оплачивается</td></tr>";

            echo '<tr><td colspan="2" class=""><h3 class="text-primary">Пени</h3></td></tr>';
            if(!empty($bill_info->billFines)){
                $totalAmount = 0;
                $totalPayed = 0;
                foreach ($bill_info->billFines as $billFine) {
                    // оплата по данному счёту
                    $payedInThisBill = 0;
                    $payedTotal = 0;
                    $finesInfo = FinesHandler::findOne($billFine->fines_id);
                    $type = FinesHandler::$types[$finesInfo->pay_type];
                    $period = FinesHandler::getPeriod($finesInfo->pay_type, $finesInfo->period_id);
                    $payed = PayedFinesHandler::find()->where(['fines_id' => $billFine->fines_id])->all();
                    if(!empty($payed)){
                        foreach ($payed as $payedItem) {
                            $payedTotal += $payedItem->summ;
                            if($payedItem->bill_id === $bill_info->bill->id){
                                $payedInThisBill += $payedItem->summ;
                                $totalPayed += $payedInThisBill;
                            }
                        }
                    }
                    echo "<tr class='shadowed'><td>Цель</td><td>$type: {$period}</td></tr>";
                    echo "<tr><td>Начислено</td><td><b class='text-warning'>" . CashHandler::toRubles($finesInfo->summ) . "</b></td></tr>";
                    echo "<tr><td>К оплате по счёту</td><td><b class='text-danger'>" . CashHandler::toRubles($billFine->start_summ) . "</b></td></tr>";
                    echo "<tr><td>Оплачено всего</td><td><b class='text-success'>" . CashHandler::toRubles($payedTotal) . "</b></td></tr>";
                    echo "<tr><td>Оплачено по данному счёту</td><td><b class='text-success'>" . CashHandler::toRubles($payedInThisBill) . "</b></td></tr>";
                }
            }
            ?>
        </table>
    </div>
</div>
