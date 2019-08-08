<?php

use app\models\database\CottagesHandler;
use app\models\database\DataPowerHandler;
use app\models\utils\CashHandler;
use app\models\utils\GrammarHandler;
use yii\web\View;


/* @var $this View */
/* @var $data DataPowerHandler[] */

if (!empty($data)) {
    $total = 0;
    $fullPayed = 0;
    $partialPayed = 0;
    $forPay = 0;
    $payed = 0;
    $usedEnergy = 0;
    $cottageDetails = '';
    /** @var DataPowerHandler $datum */
    foreach ($data as $datum) {
        if (CottagesHandler::findOne($datum->cottage_number)->is_power && $datum->difference > 0) {
            $cottageDetails .= "<tr><td>Участок №" . CottagesHandler::getNumberById($datum->cottage_number) . "</td><td>";
            ++$total;
            if ($datum->is_partial_payed) {
                $partialPayed++;
                $cottageDetails .= "<span class='text-info'>Потрачено: {$datum->difference} " . GrammarHandler::KILOWATT . ", оплачено частично, " . CashHandler::toRubles($datum->payed_summ) . '</span>';
            }
            elseif($datum->is_full_payed){
                $fullPayed++;
                $cottageDetails .= "<span class='text-success'>Потрачено: {$datum->difference} " . GrammarHandler::KILOWATT . ", оплачено полностью, " . CashHandler::toRubles($datum->payed_summ) . '</span>';
            }
            else{
                $cottageDetails .= "<span class='text-danger'>Потрачено: {$datum->difference} " . GrammarHandler::KILOWATT . ", не оплачено</span>";
            }
            $usedEnergy += $datum->difference;
            $forPay += $datum->total_pay;
            $payed += $datum->payed_summ;
            $cottageDetails .= "</td>";
        }
    }
    echo "<table class='table table-condensed'>";
    echo "<tr><td>Потрачено электроэнергии</td><td><b class='text-info'>$usedEnergy " . GrammarHandler::KILOWATT . "</b></td></tr>";
    echo "<tr><td>Общая сумма к оплате</td><td><b class='text-danger'>" . CashHandler::toRubles($forPay) . "</b></td></tr>";
    echo "<tr><td>Оплачено</td><td><b class='text-success'>" . CashHandler::toRubles($payed) . "</b></td></tr>";
    echo "<tr><td>Зарегистрировано участков</td><td><b class='text-info'>$total</b></td></tr>";
    echo "<tr><td>Оплатили полностью</td><td><b class='text-success'>$fullPayed</b></td></tr>";
    echo "<tr><td>Оплатили частично</td><td><b class='text-info'>$partialPayed</b></td></tr>";
    echo "<tr><td>Не оплатили</td><td><b class='text-danger'>" . ($total - $fullPayed - $partialPayed) . "</b></td></tr>";
    echo $cottageDetails;
    echo "</table>";
} else {
    echo "<h1 class='text-center'>За этот месяц ещё никто ничего не платил</h1>";
}
