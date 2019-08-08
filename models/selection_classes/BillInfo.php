<?php


namespace app\models\selection_classes;

use app\models\database\BankTransactionsHandler;
use app\models\database\BillFinesHandler;
use app\models\database\BillMembershipHandler;
use app\models\database\BillPowerHandler;
use app\models\database\BillsHandler;
use app\models\database\BillSingleHandler;
use app\models\database\BillTargetHandler;
use app\models\database\CottagesHandler;

/**
 *
 * @property BillsHandler $bill Информация об счёте
 * @property BankTransactionsHandler $bankTransaction Информация об банковской транзакции
 * @property TransactionInfo[] $transactions Информация об транзакциях по счёту
 * @property CottagesHandler $cottage Информация об участке
 * @property BillFinesHandler[] $billFines Информация об участке
 * @property BillPowerHandler[] $billPower Информация об участке
 * @property BillMembershipHandler[] $billMembership Информация об участке
 * @property BillTargetHandler[] $billTarget Информация об участке
 * @property BillSingleHandler[] $billSingle Информация об участке
 */

class BillInfo
{
    public $bankTransaction;
    public $bill;
    public $transactions;
    public $cottage;
    public $billFines;
    public $billPower;
    public $billMembership;
    public $billTarget;
    public $billSingle;
}