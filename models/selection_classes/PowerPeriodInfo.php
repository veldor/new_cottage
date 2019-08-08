<?php


namespace app\models\selection_classes;


use app\models\database\DataPowerHandler;
use app\models\database\RegistredCountersHandler;

class PowerPeriodInfo
{
    /**
     * @var RegistredCountersHandler
     */
    public $counter;
    /**
     * @var DataPowerHandler
     */
    public $lastData;
}