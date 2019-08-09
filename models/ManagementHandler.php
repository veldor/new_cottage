<?php


namespace app\models;


use app\models\database\BankTransactionsHandler;
use app\models\database\BillFinesHandler;
use app\models\database\BillMembershipHandler;
use app\models\database\BillPowerHandler;
use app\models\database\BillsHandler;
use app\models\database\BillSingleHandler;
use app\models\database\BillTargetHandler;
use app\models\database\ContactsHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\DepositHandler;
use app\models\database\DiscountHandler;
use app\models\database\EmailsHandler;
use app\models\database\FinesHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\PhonesHandler;
use app\models\database\RegistredCountersHandler;
use app\models\database\SendMailsHandler;
use app\models\database\TariffMembershipHandler;
use app\models\database\TariffPowerHandler;
use app\models\database\TariffTargetHandler;
use app\models\utils\DbTransaction;
use app\priv\Info;
use Throwable;

class ManagementHandler
{

    /**
     * @throws Throwable
     */
    public static function eraseDb()
    {
        $transaction = new DbTransaction();
        // удалю всё
        BankTransactionsHandler::deleteAll();
        BillFinesHandler::deleteAll();
        BillMembershipHandler::deleteAll();
        BillSingleHandler::deleteAll();
        BillPowerHandler::deleteAll();
        BillTargetHandler::deleteAll();
        BillsHandler::deleteAll();
        EmailsHandler::deleteAll();
        PhonesHandler::deleteAll();
        ContactsHandler::deleteAll();
        DataMembershipHandler::deleteAll();
        DataTargetHandler::deleteAll();
        DataPowerHandler::deleteAll();
        DataSingleHandler::deleteAll();
        DepositHandler::deleteAll();
        DiscountHandler::deleteAll();
        FinesHandler::deleteAll();
        PayedFinesHandler::deleteAll();
        PayedMembershipHandler::deleteAll();
        PayedPowerHandler::deleteAll();
        PayedTargetHandler::deleteAll();
        PayedSingleHandler::deleteAll();
        RegistredCountersHandler::deleteAll();
        SendMailsHandler::deleteAll();
        TariffMembershipHandler::deleteAll();
        TariffPowerHandler::deleteAll();
        TariffTargetHandler::deleteAll();
        CottagesHandler::deleteAll();
        $transaction->commitTransaction();
    }

    /**
     * @throws exceptions\ExceptionWithStatus
     */
    public static function fillDb()
    {
        $transaction = new DbTransaction();
        // создам заглушки для необходимого количества участков
        $requiredQuantity = Info::COTTAGES_QUANTITY;
        $counter = 1;
        while ($counter <= $requiredQuantity) {
            // проверю, нет ли в базе участка с данным адресом
            if (!CottagesHandler::findOne(['cottage_number' => $counter])) {
                $newCottage = new CottagesHandler();
                $newCottage->cottage_number = $counter;
                $newCottage->is_membership = 0;
                $newCottage->is_power = 0;
                $newCottage->is_target = 0;
                $newCottage->square = 0;
                $newCottage->save();
            }
            $counter++;
        }
        $transaction->commitTransaction();
    }
}