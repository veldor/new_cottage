<?php


namespace app\models\selection_classes;


use app\models\database\BillsHandler;
use app\models\database\CottagesHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\TransactionsHandler;

class TransactionInfo
{
    /**
     *
     * @property TransactionsHandler $transaction Информация об транзакциях по счёту
     * @property PayedFinesHandler[] $payedFines Информация об участке
     * @property PayedPowerHandler[] $payedPower Информация об участке
     * @property PayedMembershipHandler[] $payedMembership Информация об участке
     * @property PayedTargetHandler[] $payedTarget Информация об участке
     * @property PayedSingleHandler[] $payedSingle Информация об участке
     * @property BillsHandler $billInfo Информация о счёте
     * @property CottagesHandler $cottageInfo Информация об участке
     */

    public $transaction;
    public $payedFines;
    public $payedPower;
    public $payedMembership;
    public $payedTarget;
    public $payedSingle;
    public $billInfo;
    public $cottageInfo;

}