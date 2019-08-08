<?php

use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\utils\CashHandler;
use yii\web\View;

/* @var $this View
 * @var $data DataMembershipHandler[]
 */

if (!empty($data)) {
    $total = 0;
    $fullPayed = 0;
    $partialPayed = 0;
    $square = 0;
    $forPay = 0;
    $payed = 0;
    $cottageDetails = '';
    foreach ($data as $datum) {
        if (CottagesHandler::findOne($datum->cottage_number)->is_membership) {
            $cottageDetails .= "<tr><td>Участок №" . CottagesHandler::getNumberById($datum->cottage_number) . "</td><td>";
            ++$total;
            if ($datum->is_partial_payed) {
                $cottageDetails .= "<span class='text-info'>Площадь: {$datum->square} м<sup>2</sup>, Оплачено частично, " . CashHandler::toRubles($datum->payed_summ) . '</span>';
                ++$partialPayed;
            } elseif ($datum->is_full_payed) {
                $cottageDetails .= "<span class='text-success'>Площадь: {$datum->square} м<sup>2</sup>, Оплачено полностью, " . CashHandler::toRubles($datum->payed_summ) . '</span>';
                ++$fullPayed;
            } else {
                $cottageDetails .= "<span class='text-danger'> Не оплачено</span>";
            }
            $square += $datum->square;
            $forPay += $datum->total_pay;
            $payed += $datum->payed_summ;
            $cottageDetails .= "</td>";
        }
    }
    echo "<table class='table table-condensed'>";
    echo "<tr><td>Общая расчётная площадь</td><td><b class='text-info'>$square m<sup>2</sup></b></td></tr>";
    echo "<tr><td>Общая сумма к оплате</td><td><b class='text-danger'>" . CashHandler::toRubles($forPay) . "</b></td></tr>";
    echo "<tr><td>Оплачено</td><td><b class='text-success'>" . CashHandler::toRubles($payed) . "</b></td></tr>";
    echo "<tr><td>Зарегистрировано участков</td><td><b class='text-info'>$total</b></td></tr>";
    echo "<tr><td>Оплатили полностью</td><td><b class='text-success'>$fullPayed</b></td></tr>";
    echo "<tr><td>Оплатили частично</td><td><b class='text-info'>$partialPayed</b></td></tr>";
    echo "<tr><td>Не оплатили</td><td><b class='text-danger'>" . ($total - $fullPayed - $partialPayed) . "</b></td></tr>";
    echo $cottageDetails;
    echo "</table>";
} else {
    echo "<h1 class='text-center'>За этот квартал ещё никто ничего не платил</h1>";
}