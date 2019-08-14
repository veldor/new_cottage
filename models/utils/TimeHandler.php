<?php


namespace app\models\utils;


use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use DateTime;
use Exception;
use yii\base\InvalidArgumentException;

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
        $parts = explode('-', $quarter);
        $year = $parts[0];
        $changedQuarter = $parts[1];
        if ($param > 0) {
            while ($param > 0) {
                --$param;
                if ($changedQuarter == 4) {
                    $changedQuarter = 1;
                    ++$year;
                } else {
                    ++$changedQuarter;
                }
            }
        }
        if ($param < 0) {
            while ($param < 0) {
                ++$param;
                if ($changedQuarter == 1) {
                    $changedQuarter = 4;
                    --$year;
                } else {
                    --$changedQuarter;
                }
            }
        }
        return $year . '-' . $changedQuarter;
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

    /**
     * @param $month string
     * @param bool $endMonth
     * @return int
     */
    public static function checkMonthDifference($month, $endMonth = false): int
    {
        // считаю разницу между введённым значением и текущим кварталом
        $info = self::isMonth($month);
        if ($endMonth) {
            $endMonthInfo = self::isMonth($endMonth);
        } else {
            $endMonthInfo = self::isMonth(self::getCurrentMonth());
        }
        if ($endMonthInfo['year'] === $info['year']) {
            // если оплачен этот год- проверяю, если месяц меньше текущего- получаю разницу вычитанием
            return $endMonthInfo['month'] - $info['month'];
        }
        // проверю, в какую сторону считать
        if ($info['full'] <= $endMonthInfo['full']) {
            // если неоплачено за несколько лет- считаю разницу между годами. За все больше одного беру по 4 квартала, плюс кварталы в этом году, плюс кварталы в крайнем году неоплаты
            $difference = $endMonthInfo['year'] - $info['year'];
            // возвращаю сумму кватралов в этом году и неоплаченных кварталов прошлого года
            return $endMonthInfo['month'] + (12 - $info['month']) + (($difference - 1) * 12);
        }
        $difference = $info['year'] - $endMonthInfo['year'];
        return -((12 - $endMonthInfo['month']) + $info['month'] + (($difference - 1) * 12));
    }

    /**
     * Получаю список месяцев между двумя датами
     * @param $month string
     * @param $endMonth string
     * @return array|null
     */
    public static function getMonthsList($month, $endMonth = '')
    {
        // составлю массив месяцев
        $unpayed = null;
        $count = self::checkMonthDifference($month, $endMonth);
        if ($count) {
            $month = self::isMonth($month)['full'];
            $match = null;
            preg_match('/^(\d{4})\W*(\d{2})$/', $month, $match);
            list(, $year, $startMonth) = $match;
            if ($count > 0) {
                while ($count > 0) {
                    $unpayed[$year . '-' . $startMonth] = ['monthNumber' => $startMonth, 'year' => $year];
                    --$count;
                    if ($startMonth === '12' || $startMonth === 12) {
                        $startMonth = '01';
                        ++$year;
                    } else {
                        ++$startMonth;
                        if ($startMonth < 10) {
                            $startMonth = '0' . $startMonth;
                        }
                    }
                }
            } else if ($count < 0) {
                --$startMonth;
                while ($count < 0) {
                    $unpayed[$year . '-' . $startMonth] = ['monthNumber' => $startMonth, 'year' => $year];
                    if ($startMonth < 10) {
                        $startMonth = '0' . $startMonth;
                    }
                    if ($startMonth === '01' || $startMonth === 1 || $startMonth === '1') {
                        $startMonth = '12';
                        --$year;
                    } else {
                        --$startMonth;
                    }
                    ++$count;
                }
                $unpayed = array_reverse($unpayed);
            }
            return $unpayed;
        }
        return null;
    }

    /**
     * @param $month string
     * @return array
     */
    public static function isMonth($month): array
    {
        $match = null;
        if (preg_match('/^(\d{4})\W*([0-1]?\d)$/', $month, $match) && $match[2] > 0 && $match[2] < 13 && self::isYear($match[1])) {
            if ($match[2] < 10) {
                $match[2] = '0' . (int)$match[2];
            }
            return ['full' => "$match[1]-$match[2]", 'year' => $match[1], 'month' => $match[2]];
        }
        throw new InvalidArgumentException("Значение \"$month\" не является месяцем");
    }

    /**
     * @param $year string|int
     * @return int
     */
    public static function isYear($year): int
    {
        $year = (int)$year;
        if ($year > 1900 && $year < 3000) {
            return $year;
        }
        throw new InvalidArgumentException("Значение \"$year\" не является годом");
    }

    /**
     * @param $q
     * @return array|null
     */
    public static function getQuarterList($q)
    {
        if (is_string($q)) {
            $start = self::isQuarter($q);
            $end = self::isQuarter(self::getCurrentQuarter());
        }
        elseif (is_array($q)) {
            $start = self::isQuarter($q['start']);
            $end = self::isQuarter($q['finish']);
        }
        else {
            throw new InvalidArgumentException('Неверный параметр даты');
        }
        // составлю массив кварталов
        $unpayed = null;
        $count = self::checkQuarterDifference($start['full'], $end['full']);
        if ($count === 0) {
            return [];
        }
        if ($count > 0) {
            $quarter = $end['quarter'];
            $year = $end['year'];
        }
        else{
            $quarter = $start['quarter'];
            $year = $start['year'];
            $count = abs($count);
        }
        while ($count > 0) {
            $unpayed[$year . '-' . $quarter] = ['quarterNumber' => $quarter, 'year' => $year];
            if ($quarter === 4) {
                $quarter = 1;
                ++$year;
            }
            else {
                ++$quarter;
            }
            --$count;
        }
        return $unpayed;
    }

    /**
     * @param $quarter
     * @return array
     */
    public static function isQuarter($quarter): array
    {
        $match = null;
        if (preg_match('/^\s*(\d{4})\W*([1-4])\s*$/', $quarter, $match) && $match[1] > 0 && $match[2] < 5 && self::isYear($match[1])) {
            return ['full' => "$match[1]-$match[2]", 'year' => (int)$match[1], 'quarter' => (int)$match[2]];
        }
        throw new InvalidArgumentException("Значение \"$quarter\" не является кварталом");
    }

    /**
     * @param $s
     * @param bool $f
     * @return float|int|mixed
     */
    public static function checkQuarterDifference($s, $f = false)
    {
        // считаю разницу между введённым значением и текущим кварталом
        $start = self::isQuarter($s);
        if ($f) {
            $finish = self::isQuarter($f);
        }
        else {
            $finish = self::isQuarter(self::getCurrentQuarter());
        }
        if ($start['year'] === $finish['year']) {
            // если оплачен этот год- проверяю, если квартал меньше текущего- получаю разницу вычитанием
            return $start['quarter'] - $finish['quarter'];
        }
        // проверю, в какую сторону считать
        if ($start['full'] <= $finish['full']) {
            // если неоплачено за несколько лет- считаю разницу между годами. За все больше одного беру по 4 квартала, плюс кварталы в этом году, плюс кварталы в крайнем году неоплаты
            $difference = $start['year'] - $finish['year'];
            // возвращаю сумму кватралов в этом году и неоплаченных кварталов прошлого года
            return $start['quarter'] + (4 - $finish['quarter']) + (($difference - 1) * 4);
        }
        $difference = $start['year'] - $finish['year'];
        return ((4 - $finish['quarter']) + $start['quarter'] + (($difference - 1) * 4));
    }
}
