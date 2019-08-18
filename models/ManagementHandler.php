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
use app\models\utils\Calculator;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\DomHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use app\priv\Info;
use Throwable;
use Yii;
use yii\db\Exception;

class ManagementHandler
{

    /**
     * @throws Throwable
     */
    public static function eraseDb()
    {
        $transaction = new DbTransaction();
        // удалю всё
        self::truncateDb(BankTransactionsHandler::tableName());
        self::truncateDb(BillFinesHandler::tableName());
        self::truncateDb(CottagesHandler::tableName());
        self::truncateDb(BillMembershipHandler::tableName());
        self::truncateDb(BillSingleHandler::tableName());
        self::truncateDb(BillPowerHandler::tableName());
        self::truncateDb(BillTargetHandler::tableName());
        self::truncateDb(BillsHandler::tableName());
        self::truncateDb(EmailsHandler::tableName());
        self::truncateDb(PhonesHandler::tableName());
        self::truncateDb(ContactsHandler::tableName());
        self::truncateDb(DataMembershipHandler::tableName());
        self::truncateDb(DataTargetHandler::tableName());
        self::truncateDb(DataPowerHandler::tableName());
        self::truncateDb(DataSingleHandler::tableName());
        self::truncateDb(DepositHandler::tableName());
        self::truncateDb(DiscountHandler::tableName());
        self::truncateDb(FinesHandler::tableName());
        self::truncateDb(PayedFinesHandler::tableName());
        self::truncateDb(PayedMembershipHandler::tableName());
        self::truncateDb(PayedPowerHandler::tableName());
        self::truncateDb(PayedTargetHandler::tableName());
        self::truncateDb(PayedSingleHandler::tableName());
        self::truncateDb(RegistredCountersHandler::tableName());
        self::truncateDb(SendMailsHandler::tableName());
        self::truncateDb(TariffMembershipHandler::tableName());
        self::truncateDb(TariffPowerHandler::tableName());
        self::truncateDb(TariffTargetHandler::tableName());
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

    /**
     * @throws Throwable
     */
    public static function migrate()
    {
        self::eraseDb();
        $transaction = new DbTransaction();
        // тарифы
        $tariffList = file_get_contents('Z:/migrate/tariffs_membership.xml');
        $dom = new DomHandler($tariffList);
        $tariffs = $dom->query('/root/item');
        foreach ($tariffs as $tariff) {
            $options = DomHandler::getElemAttributes($tariff);
            $newTariff = new TariffMembershipHandler();
            $newTariff->quarter = $options['quarter'];
            $newTariff->pay_for_meter = CashHandler::fromRubles($options['changed_part']);
            $newTariff->pay_for_cottage = CashHandler::fromRubles($options['fixed_part']);
            $newTariff->search_timestamp = $options['search_timestamp'];
            $newTariff->pay_up_date = TimeHandler::getPayUpQuarter($newTariff->quarter);
            $newTariff->save();
        }
        // тарифы
        $tariffList = file_get_contents('Z:/migrate/tariffs_target.xml');
        $dom = new DomHandler($tariffList);
        $tariffs = $dom->query('/root/item');
        foreach ($tariffs as $tariff) {
            $options = DomHandler::getElemAttributes($tariff);
            $newTariff = new TariffTargetHandler();
            $newTariff->year = $options['year'];
            $newTariff->pay_for_meter = CashHandler::fromRubles($options['float_part']);
            $newTariff->pay_for_cottage = CashHandler::fromRubles($options['fixed_part']);
            $newTariff->search_timestamp = TimeHandler::getYearTimestamp($newTariff->year);
            $newTariff->pay_up_date = $options['payUpTime'];
            $newTariff->pay_description = $options['description'];
            $newTariff->save();
        }
        // тарифы
        $tariffList = file_get_contents('Z:/migrate/tariffs_power.xml');
        $dom = new DomHandler($tariffList);
        $tariffs = $dom->query('/root/item');
        foreach ($tariffs as $tariff) {
            $options = DomHandler::getElemAttributes($tariff);
            $newTariff = new TariffPowerHandler();
            $newTariff->month = $options['targetMonth'];
            $newTariff->power_limit = $options['powerLimit'];
            $newTariff->power_cost = CashHandler::fromRubles($options['powerCost']);
            $newTariff->power_overcost = CashHandler::fromRubles($options['powerOvercost']);
            $newTariff->search_timestamp = $options['searchTimestamp'];
            $newTariff->pay_up_date = TimeHandler::getPayUpMonth($newTariff->month);
            $newTariff->save();
        }

        $cottageListText = file_get_contents('Z:/migrate/cottages.xml');
        $dom = new DomHandler($cottageListText);
        $cottagesList = $dom->query('/root/item');
        foreach ($cottagesList as $cottage) {
            // создам новый участок
            $options = DomHandler::getElemAttributes($cottage);
            $newCottage = new CottagesHandler();
            $newCottage->cottage_number = $options['cottageNumber'];
            $newCottage->deposit = CashHandler::fromRubles($options['deposit']);
            $newCottage->square = $options['cottageSquare'];
            $newCottage->is_have_property_rights = !!$options['cottageHaveRights'];
            $newCottage->is_cottage_register_data = !!$options['cottageRightsData'];
            $newCottage->register_data = $options['passportData'];
            $newCottage->property_data = $options['cottageRightsData'];
            $newCottage->save();

            // добавлю владельца
            if (!empty($options['cottageOwnerPersonals'])) {
                $owner = new ContactsHandler();
                $owner->cottage_id = $newCottage->id;
                $owner->contact_name = $options['cottageOwnerPersonals'];
                $owner->contact_address = GrammarHandler::clearAddress($options['cottageOwnerAddress']);
                $owner->contact_description = $options['cottageOwnerDescription'];
                $owner->is_owner = 1;
                $owner->is_active = 1;
                $owner->save();

                if (!empty($options['cottageOwnerPhone'])) {
                    $phone = new PhonesHandler();
                    $phone->contact_id = $owner->id;
                    $phone->phone_number = GrammarHandler::normalizePhone($options['cottageOwnerPhone']);
                    $phone->is_main = 1;
                    $phone->save();
                }
                if (!empty($options['cottageOwnerEmail'])) {
                    $email = new EmailsHandler();
                    $email->contact_id = $owner->id;
                    $email->email_address = $options['cottageOwnerEmail'];
                    $email->is_main = 1;
                    $email->save();
                }
            }
            // добавлю контакт
            if (!empty($options['cottageContacterPersonals'])) {
                $owner = new ContactsHandler();
                $owner->cottage_id = $newCottage->id;
                $owner->contact_name = $options['cottageContacterPersonals'];
                $owner->is_owner = 0;
                $owner->is_active = 1;
                $owner->save();

                if (!empty($options['cottageContacterPhone'])) {
                    $phone = new PhonesHandler();
                    $phone->contact_id = $owner->id;
                    $phone->phone_number = $options['cottageContacterPhone'];
                    $phone->is_main = 1;
                    $phone->save();
                }
                if (!empty($options['cottageContacterEmail'])) {
                    $email = new EmailsHandler();
                    $email->contact_id = $owner->id;
                    $email->email_address = $options['cottageContacterEmail'];
                    $email->is_main = 1;
                    $email->save();
                }
            }

        }
        $cottageListText = file_get_contents('Z:/migrate/additionalCottages.xml');
        $dom = new DomHandler($cottageListText);
        $cottagesList = $dom->query('/root/item');
        foreach ($cottagesList as $cottage) {
            // создам новый участок
            $options = DomHandler::getElemAttributes($cottage);
            $newCottage = new CottagesHandler();
            $newCottage->cottage_number = $options['masterId'] . '-a';
            $newCottage->is_power = $options['isPower'];
            $newCottage->is_membership = $options['isMembership'];
            $newCottage->is_target = $options['isTarget'];
            $newCottage->is_different_owner = $options['hasDifferentOwner'];
            $newCottage->deposit = CashHandler::fromRubles($options['deposit']);
            $newCottage->square = $options['cottageSquare'];
            $newCottage->is_additional = 1;
            $newCottage->main_cottage_id = CottagesHandler::getIdByNumber($options['masterId'])->id;
            $newCottage->save();

            // добавлю владельца
            if (!empty($options['cottageOwnerPersonals'])) {
                $owner = new ContactsHandler();
                $owner->cottage_id = $newCottage->id;
                $owner->contact_name = $options['cottageOwnerPersonals'];
                $owner->contact_address = GrammarHandler::clearAddress($options['cottageOwnerAddress']);
                $owner->is_owner = 1;
                $owner->is_active = 1;
                $owner->save();

                if (!empty($options['cottageOwnerPhone'])) {
                    $phone = new PhonesHandler();
                    $phone->contact_id = $owner->id;
                    $phone->phone_number = GrammarHandler::normalizePhone($options['cottageOwnerPhone']);
                    $phone->is_main = 1;
                    $phone->save();
                }
                if (!empty($options['cottageOwnerEmail'])) {
                    $email = new EmailsHandler();
                    $email->contact_id = $owner->id;
                    $email->email_address = $options['cottageOwnerEmail'];
                    $email->is_main = 1;
                    $email->save();
                }
            }
            // добавлю контакт
            if (!empty($options['cottageContacterPersonals'])) {
                $owner = new ContactsHandler();
                $owner->cottage_id = $newCottage->id;
                $owner->contact_name = $options['cottageContacterPersonals'];
                $owner->is_owner = 0;
                $owner->is_active = 1;
                $owner->save();

                if (!empty($options['cottageContacterPhone'])) {
                    $phone = new PhonesHandler();
                    $phone->contact_id = $owner->id;
                    $phone->phone_number = $options['cottageContacterPhone'];
                    $phone->is_main = 1;
                    $phone->save();
                }
                if (!empty($options['cottageContacterEmail'])) {
                    $email = new EmailsHandler();
                    $email->contact_id = $owner->id;
                    $email->email_address = $options['cottageContacterEmail'];
                    $email->is_main = 1;
                    $email->save();
                }
            }
        }

        // данные по электроэнергии
        $powerMonthsText = file_get_contents('Z:/migrate/power_months.xml');
        $dom = new DomHandler($powerMonthsText);
        $monthsList = $dom->query('/root/item');
        foreach ($monthsList as $month) {
            $options = DomHandler::getElemAttributes($month);
            $cottage = CottagesHandler::getIdByNumber($options['cottageNumber']);
            // проверю, если ещё не зарегистрирован счётчик- зарегистрирую
            $registeredCounter = RegistredCountersHandler::find()->where(['cottage_id' => $cottage->id])->all();
            if (empty($registeredCounter)) {
                $newCounter = new RegistredCountersHandler();
                $newCounter->cottage_id = $cottage->id;
                $newCounter->last_data = 0;
                $newCounter->is_active = 1;
                $newCounter->counter_serial = 'Номер не присвоен';
                $newCounter->save();
            } else {
                $newCounter = null;
            }
            $newData = new DataPowerHandler();
            if (!empty($newCounter)) {
                $newCounter->last_data = $options['newPowerData'];
                $newCounter->save();
                $newData->counter_id = $newCounter->id;
            } else {
                // найду счётчик, последние данные которого совпадают с начальными данными периода. Если его нет- зарегистрирую новый
                $counter = RegistredCountersHandler::findOne(['cottage_id' => $cottage->id, 'last_data' => $options['oldPowerData']]);
                if (empty($counter)) {
                    $newCounter = new RegistredCountersHandler();
                    $newCounter->cottage_id = $cottage->id;
                    $newCounter->last_data = $options['newPowerData'];
                    $newCounter->is_active = 1;
                    $newCounter->counter_serial = 'Номер не присвоен';
                    $newCounter->save();
                    $newData->counter_id = $newCounter->id;
                } else {
                    $counter->last_data = $options['newPowerData'];
                    $counter->save();
                    $newData->counter_id = $counter->id;
                }
            }
            $newData->cottage_number = $cottage->id;
            $newData->month = $options['month'];
            $newData->filling_date = $options['fillingDate'];
            $newData->old_data = $options['oldPowerData'];
            $newData->new_data = $options['newPowerData'];
            $newData->search_timestamp = $options['searchTimestamp'];
            $newData->pay_up_date = TimeHandler::getPayUpMonth($newData->month);
            $newData->difference = $options['difference'];
            if ($newData->difference > 0) {
                $newData->total_pay = CashHandler::fromRubles($options['totalPay']);
                $newData->in_limit_data = $options['inLimitSumm'];
                $newData->over_limit_data = $options['overLimitSumm'];
                $newData->in_limit_pay = CashHandler::fromRubles($options['inLimitPay']);
                $newData->over_limit_pay = CashHandler::fromRubles($options['overLimitPay']);
            }
            $newData->save();
        }

        // заполню данные по счетам
        $billsText = file_get_contents('Z:/migrate/payment_bills.xml');
        $dom = new DomHandler($billsText);
        $billsList = $dom->query('/root/item');
        foreach ($billsList as $bill) {
            $options = DomHandler::getElemAttributes($bill);
            // найду данные по участку
            $cottageInfo = CottagesHandler::getIdByNumber($options['cottageNumber']);
            $newBill = new BillsHandler();
            $newBill->id = $options['id'];
            $newBill->cottage_number = $cottageInfo->id;
            $newBill->time_create = $options['creationTime'];
            $newBill->from_deposit = CashHandler::fromRubles($options['depositUsed']);
            $newBill->discount = CashHandler::fromRubles($options['discount']);
            $newBill->bill_summ = CashHandler::fromRubles($options['totalSumm']);
            $newBill->payerId = ContactsHandler::findOne(['cottage_id' => $cottageInfo->id])->id;
            $newBill->is_invoice_printed = $options['isInvoicePrinted'];
            $newBill->is_email_sended = $options['isMessageSend'];
            $newBill->save();
        }

        // заполню периоды оплаты членских взносов
        $payedMembershipText = file_get_contents('Z:/migrate/payed_membership.xml');
        $dom = new DomHandler($payedMembershipText);
        $quartersList = $dom->query('/root/item');
        foreach ($quartersList as $quarter) {
            // проверю, зарегистрирован ли данный квартал в данных
            $options = DomHandler::getElemAttributes($quarter);
            $cottageInfo = CottagesHandler::getIdByNumber($options['cottageId']);
            $existentData = DataMembershipHandler::findOne(['cottage_number' => $cottageInfo->id, 'quarter' => $options['quarter']]);
            if (empty($existentData)) {
                $existentData = new DataMembershipHandler();
                $existentData->cottage_number = $cottageInfo->id;
                $existentData->quarter = $options['quarter'];
                $existentData->search_timestamp = TimeHandler::getMonthTimestamp(TimeHandler::getQuarterFirstMonth($existentData->quarter));
                $existentData->pay_up_date = TimeHandler::getPayUpQuarter($existentData->quarter);
                $existentData->square = $cottageInfo->square;
                // расчитаю стоимость
                $tariff = TariffMembershipHandler::findOne(['quarter' => $existentData->quarter]);
                $existentData->total_pay = Calculator::calculateWithSquare($existentData->square, $tariff->pay_for_meter, $tariff->pay_for_cottage);
                $existentData->save();
            }
        }


        $transaction->commitTransaction();
    }

    /**
     * @param $name
     * @throws Exception
     */
    private static function truncateDb($name)
    {
        Yii::$app->db->createCommand()->setRawSql('SET FOREIGN_KEY_CHECKS = 0;')->execute();
        Yii::$app->db->createCommand()->truncateTable($name)->execute();
        Yii::$app->db->createCommand()->setRawSql('SET FOREIGN_KEY_CHECKS = 1;')->execute();
    }
}