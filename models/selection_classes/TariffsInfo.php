<?php


namespace app\models\selection_classes;


use app\models\database\TariffMembershipHandler;
use app\models\database\TariffPowerHandler;
use app\models\database\TariffTargetHandler;

class TariffsInfo
{
    /**
     * @var TariffPowerHandler[]
     */
    public $powerTariffs;
    /**
     * @var TariffMembershipHandler[]
     */
    public $membershipTariffs;
    /**
     * @var TariffTargetHandler[]
     */
    public $targetTariffs;
}