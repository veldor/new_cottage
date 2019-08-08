<?php


namespace app\models\utils;


class CashHandler
{
    public static function toRubles(int $value)
    {
        return (int) ($value / 100) . '&nbsp;руб.&nbsp;' . $value % 100 . '&nbsp;коп.';
    }

    public static function toMathRubles(int $value)
    {
        $last = $value % 100;
        if($last < 10){
            $last = '0' . $last;
        }
        return (int) ($value / 100) . '.' . $last;
    }

    public static function dividedSumm($summ)
    {
        $summ = str_replace(',', '.', $summ);
        $divided = explode('.', $summ);
        $rubles = $divided[0];
        if(!empty($divided[1])){
            if(strlen($divided[1]) == 1){
                $cents =$divided[1] . '0';
            }
            else{
                $cents = $divided[1];
            }
        }
        else{
            $cents = '00';
        }
        return ['rubles' => $rubles, 'cents' => $cents];
    }

    public static function fromRubles($summ)
    {
        $summ = self::dividedSumm($summ);
        return (int) ($summ['rubles'] . $summ ['cents']);
    }

    public static function countPercent(int $summ, float $percent)
    {
        return $summ / 100 * $percent;
    }
}