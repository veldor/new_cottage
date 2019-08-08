<?php


namespace app\models\utils;


use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;

class Calculator
{
    /**
     * @param $duties DataTargetHandler[]|DataPowerHandler[]|DataMembershipHandler[]|DataSingleHandler[]
     * @return int
     */
    public static function calculateDebt($duties){
        $duty = 0;
        if (!empty($duties)) {
            foreach ($duties as $item) {
                $duty += $item->total_pay;
                $duty -= $item->payed_summ;
            }
        }
        return $duty;
    }

    public static function calculateWithSquare(int $square, int $pay_for_field, int $pay_for_cottage)
    {
        return (int)($pay_for_cottage + ($pay_for_field / 100 * $square));
    }
}