<?php


namespace app\models\utils;


use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use DateTime;
use Exception;

class TimeHandler{

/** @var string[] $months русские названия месяцев*/
public static $months = ['Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря',];

    /**
     * Получение временной метки начала года
     * @param $year
     * @return int
     */
    public static function getYearTimestamp($year){
        $date = DateTime::createFromFormat('j-m-Y H-i-s', "1-1-$year 12-00-00");
        return $date->getTimestamp();
    }

    public static function getMonthTimestamp($month)
    {
        // получу отметку времения 2 числа первого месяца данного года - второго, чтобы исключить поправку на часовой пояс
        $match = null;
        preg_match('/^(\d{4})\W*(\d{2})$/', $month, $match);
        return strtotime("2-$match[2]-$match[1]");
    }


    public static function getYearFromTimestamp(int $timestamp)
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return $date->format('Y');
    }


    /**
     * @param string $quarter
     * @return int
     */
    public static function getPayUpQuarter($quarter)
    {
        // получу первый месяц квартала
        $explodedQuarter = explode('-', $quarter);
        $month = $explodedQuarter[1] * 3 - 2;
        $date = DateTime::createFromFormat('j-m-Y H-i-s', "1-{$month}-{$explodedQuarter[0]} 12-00-00");
        $date->modify('+1 month');
        $date->modify('-1 day');
        return $date->getTimestamp();
    }

    /**
     * @param $duties DataTargetHandler[]|DataPowerHandler[]|DataMembershipHandler[]|DataSingleHandler[]
     * @return bool
     */
    public static function checkPayUp($duties)
    {
        if(!empty($duties)){
            foreach ($duties as $duty) {
                if($duty->pay_up_date < time()){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param int $timestamp
     * @return string
     * @throws Exception
     */
    public static function timestampToDate(int $timestamp)
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $answer = '';
        $day = $date->format('d');
        $answer .= $day;
        $month = mb_strtolower(self::$months[$date->format('m') - 1]);
        $answer .= ' ' . $month . ' ';
        $answer .= $date->format('Y') . ' года.';
        return $answer;
    }

    /**
     * @param int $timestamp
     * @return string
     * @throws Exception
     */
    public static function timestampToShortDate(int $timestamp)
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return date_format($date, 'Y-m-d');
    }

    public static function timestampToShortTime(int $timestamp)
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return date_format($date, 'H:i:s');
    }

    public static function dateToTimestamp(string $changeDate)
    {
        $date = DateTime::createFromFormat('Y-m-j H-i-s', "$changeDate 12-00-00");
        return $date->getTimestamp();
    }

    /**
     * @return string
     */
    public static function getCurrentMonth()
    {
        return strftime('%Y-%m', strtotime(date('Y-m')));
    }

    /**
     * @return string
     */
    public static function getCurrentQuarter()
    {
        return self::quarterFromMonth(strftime('%Y-%m', strtotime(date('Y-m'))));
    }


    /**
     * @return string
     */
    public static function getCurrentYear()
    {
        return strftime('%Y', strtotime(date('Y')));
    }

    /**
     * @param string $month
     * @return int
     */
    public static function quarterFromMonth($month)
    {
        $explodedMonth = explode('-', $month);
        return $explodedMonth[0] . '-' . ((int) ($explodedMonth[1] / 3) + 1);
    }

    /**
     * @param $month
     * @param $shift
     * @return bool|string
     */
    public static function getNeighborMonth($month, $shift)
    {
        $date = DateTime::createFromFormat('Y-m', $month);
        $date->modify($shift .' month');
        return date_format($date, 'Y-m');
    }

    public static function getFullFromShotMonth($shortMonth)
    {
        return strftime('%B %Y', DateTime::createFromFormat('Y-m-d', $shortMonth . '-10')->getTimestamp());
    }

    /**
     * @param $month
     * @return int
     */
    public static function getPayUpMonth($month)
    {
        $date = DateTime::createFromFormat('Y-m-j H-i-s', "{$month}-10 12-00-00");
        $date->modify('+1 month');
        return $date->getTimestamp();
    }

    public static function getNeighborQuarter($quarter, int $param)
    {
        // получу первый месяц квартала
        $month = self::getQuarterFirstMonth($quarter);
        $targetMonth = self::getNeighborMonth($month, $param * 4);
        return self::quarterFromMonth($targetMonth);
    }

    public static function getQuarterFirstMonth($quarter){
        $explodedQuarter = explode('-', $quarter);
        $month = $explodedQuarter[1] * 3 - 2;
        if($month < 10){
            $month = 0 . $month;
        }
        return $explodedQuarter[0] . '-' . $month;
    }

    public static function getFullFromShotQuarter($quarter)
    {
        $explodedQuarter = explode('-', $quarter);
        return $explodedQuarter[1] . ' квартал ' . $explodedQuarter[0] . ' года';
    }

    /**
     * @param int $payUp
     * @param int|null $payDay
     * @return bool|string
     * @throws Exception
     */
    public static function checkDayDifference(int $payUp, int $payDay = null)
    {
        // получу дату из временной метки
        $date = new DateTime();
        $date->setTimestamp($payUp);
        if(!empty($payDay)){
            $newDate = new DateTime();
            $newDate->setTimestamp($payDay);
            $interval = $date->diff($newDate);
            $diff = $interval->format('%R%a');
            return $diff;
        }
        $nowDatetime = new DateTime();
        $interval = $date->diff($nowDatetime);
        $diff = $interval->format('%R%a');
        if($diff > 0){
            return $diff;
        }
        return false;
    }

    /**
     * @param string $pay_date
     * @param string|null $payTime
     * @return int
     * @throws Exception
     */
    public static function getCustomTimestamp(string $pay_date, string $payTime = null)
    {
        $dates = explode('-', $pay_date);
        $date = new DateTime();
        if(!empty($payTime)){
            $times = explode('-', $payTime);
            $date->setDate($dates[2],$dates[1],$dates[0]);
            $date->setTime($times[0],$times[1],$times[2]);
            return $date->getTimestamp();
        }
        $date->setDate($dates[0],$dates[1],$dates[2]);
        return $date->getTimestamp();
    }
}
