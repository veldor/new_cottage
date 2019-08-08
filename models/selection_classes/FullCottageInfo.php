<?php

namespace app\models\selection_classes;

use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;

/**
 *
 * @property CottagesHandler[] $cottageInfo Информация об участке
 * @property DataPowerHandler[] $powerDuties Информация об долгах за электричество
 * @property DataMembershipHandler[] $membershipDuties Информация об участке
 * @property DataTargetHandler[] $targetDuties Информация об участке
 * @property DataSingleHandler[] $singleDuties Информация об участке
 * @property int $fullPowerDuty Информация об участке
 * @property int $fullMembershipDuty Информация об участке
 * @property int $fullTargetDuty Информация об участке
 * @property int $fullSingleDuty Информация об участке
 */
class FullCottageInfo
{
    public $cottageInfo;

    public $powerDuties;
    public $membershipDuties;
    public $targetDuties;
    public $singleDuties;

    public $fullPowerDuty;
    public $fullMembershipDuty;
    public $fullTargetDuty;
    public $fullSingleDuty;

    public $isPowerPayUp;
    public $isMembershipPayUp;
    public $isTargetPayUp;
    public $isSinglePayUp;
}