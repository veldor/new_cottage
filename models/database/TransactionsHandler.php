<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\TransactionInfo;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\EmailHandler;
use app\models\utils\TimeHandler;
use Exception;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $bill_id [int(10) unsigned]  Идентификатор счёта
 * @property int $cottage_id [int(10) unsigned]  Идентификатор участка
 * @property int $summ [bigint(20) unsigned]  Сумма платежа
 * @property string $transaction_reason Описание транзакции
 * @property int $payDate [bigint(20) unsigned]  Фактическая дата оплаты
 * @property int $bankDate [bigint(20) unsigned]  Дата поступления средств на счёт
 */

class TransactionsHandler extends ActiveRecord
{
    public $power;
    public $membership;
    public $target;
    public $single;
    public $fines;

    public $notify = false;

    // имя таблицы
    const SCENARIO_DISTRIBUTE = 'distribute';

    /**
     * @param $id
     * @return TransactionsHandler
     * @throws ExceptionWithStatus
     */
    public static function get($id)
    {
        $transaction = self::findOne($id);
        if(empty($transaction)){
            throw new ExceptionWithStatus('Транзакция не найдена');
        }
        return $transaction;
    }

    /**
     * @param $id
     * @return TransactionInfo
     * @throws ExceptionWithStatus
     */
    public static function getTransactionInfo($id)
    {
        $info = new TransactionInfo();
        $info->transaction = self::get($id);
        $info->billInfo = BillsHandler::get($info->transaction->bill_id);
        $info->cottageInfo = CottagesHandler::get($info->transaction->cottage_id);
        $info->payedPower = PayedPowerHandler::find()->where(['transaction_id' => $id])->all();
        $info->payedMembership = PayedMembershipHandler::find()->where(['transaction_id' => $id])->all();
        $info->payedTarget = PayedTargetHandler::find()->where(['transaction_id' => $id])->all();
        $info->payedSingle = PayedSingleHandler::find()->where(['transaction_id' => $id])->all();
        $info->payedFines = PayedFinesHandler::find()->where(['transaction_id' => $id])->all();
        return $info;
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['id', 'bill_id', 'cottage_id', 'date', 'summ', 'bill_cast'],
            self::SCENARIO_DISTRIBUTE => ['id', 'bill_id', 'cottage_id', 'date', 'summ', 'bill_cast', 'power', 'membership', 'target', 'single', 'fines', 'notify'],

        ];
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return "transactions";
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function distribute()
    {
        $transaction = new DbTransaction();

        $dealTime = time();

        $billInfo = BillsHandler::findOne($this->bill_id);
        // создам нулевую транзакцию для учёта
        $newTransaction = new TransactionsHandler();
        $newTransaction->bill_id = $this->bill_id;
        $newTransaction->cottage_id = $this->cottage_id;
        $newTransaction->summ = 0;
        $newTransaction->payDate = $this->payDate ? TimeHandler::getCustomTimestamp($this->payDate) : $dealTime;
        $newTransaction->bankDate = $this->bankDate ? TimeHandler::getCustomTimestamp($this->bankDate) : $dealTime;
        $newTransaction->transaction_reason = 'Распределение скидки и оплаты с депозита';
        $newTransaction->save();

        if($billInfo->from_deposit > 0){
            // добавлю сведения об операции в отчёт по депозиту
            $depositItem = DepositHandler::findOne(['bill_id' => $billInfo->id, 'destination' => 'in']);
            $depositItem->transaction_id = $newTransaction->id;
            $depositItem->save();
        }
        if($billInfo->discount > 0){
            $discountItem = DiscountHandler::findOne(['bill_id' => $billInfo->id]);
            $discountItem->transaction_id = $newTransaction->id;
            $discountItem->save();
        }

        // получу сумму для распределения
        $amount = CashHandler::fromRubles($this->summ);
        $dividedAmount = 0;
        // разберу категории
        if(!empty($this->membership)){
            foreach ($this->membership as $key=>$value) {
                $toPay = CashHandler::fromRubles($value);
                if($value > 0){
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataMembershipHandler::findOne(BillMembershipHandler::findOne($key)->membership_data_id);
                    $payed = PayedMembershipHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if(!empty($payed)){
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if($payedAmount == $periodInfo->total_pay){
                            die('Доработать излишнюю оплату членских взносов');
                        }
                    }
                    else{
                        // проверю, что оплаченная сумма не больше необходимой
                        if($toPay > $periodInfo->total_pay){
                            die('переплата');
                        }
                        // создам оплату членских взносов
                        $newPayedMembership = new PayedMembershipHandler();
                        $newPayedMembership->transaction_id = $newTransaction->id;
                        $newPayedMembership->quarter = $periodInfo->quarter;
                        $newPayedMembership->summ = $toPay;
                        $newPayedMembership->pay_date = $dealTime;
                        $newPayedMembership->period_id = $periodInfo->id;
                        $newPayedMembership->cottage_id = $this->cottage_id;
                        $newPayedMembership->bill_id = $this->bill_id;
                        $newPayedMembership->save();
                        $periodInfo->payed_summ = $toPay;
                        if($periodInfo->total_pay == $toPay){
                            $periodInfo->is_full_payed = 1;
                        }
                        else{
                            $periodInfo->is_partial_payed = 1;
                        }
                        $periodInfo->save();
                    }
                }
            }
        }

        if(!empty($this->power)){
            foreach ($this->power as $key=>$value) {
                $toPay = CashHandler::fromRubles($value);
                if($value > 0){
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataPowerHandler::findOne(BillPowerHandler::findOne($key)->power_data_id);
                    $payed = PayedPowerHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if(!empty($payed)){
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if($payedAmount == $periodInfo->total_pay){
                            die('Доработать излишнюю оплату членских взносов');
                        }
                    }
                    else{
                        // проверю, что оплаченная сумма не больше необходимой
                        if($toPay > $periodInfo->total_pay){
                            die('переплата');
                        }
                        // создам оплату членских взносов
                        $newPayedPower = new PayedPowerHandler();
                        $newPayedPower->transaction_id = $newTransaction->id;
                        $newPayedPower->month = $periodInfo->month;
                        $newPayedPower->counter_id = $periodInfo->counter_id;
                        $newPayedPower->summ = $toPay;
                        $newPayedPower->pay_date = $dealTime;
                        $newPayedPower->period_id = $periodInfo->id;
                        $newPayedPower->cottage_id = $this->cottage_id;
                        $newPayedPower->bill_id = $this->bill_id;
                        $newPayedPower->save();
                        $periodInfo->payed_summ = $toPay;
                        if($periodInfo->total_pay == $toPay){
                            $periodInfo->is_full_payed = 1;
                        }
                        else{
                            $periodInfo->is_partial_payed = 1;
                        }
                        $periodInfo->save();
                    }
                }
            }
        }
        if(!empty($this->target)){
            foreach ($this->target as $key=>$value) {
                $toPay = CashHandler::fromRubles($value);
                if($value > 0){
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataTargetHandler::findOne(BillTargetHandler::findOne($key)->year_id);
                    $payed = PayedTargetHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if(!empty($payed)){
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if($payedAmount == $periodInfo->total_pay){
                            die('Доработать излишнюю оплату целевых взносов');
                        }
                    }
                    else{
                        // проверю, что оплаченная сумма не больше необходимой
                        if($toPay > $periodInfo->total_pay){
                            die('переплата');
                        }
                        // создам оплату членских взносов
                        $newPayedTarget = new PayedTargetHandler();
                        $newPayedTarget->transaction_id = $newTransaction->id;
                        $newPayedTarget->year = $periodInfo->year;
                        $newPayedTarget->summ = $toPay;
                        $newPayedTarget->pay_date = $dealTime;
                        $newPayedTarget->period_id = $periodInfo->id;
                        $newPayedTarget->cottage_id = $this->cottage_id;
                        $newPayedTarget->bill_id = $this->bill_id;
                        $newPayedTarget->save();
                        $periodInfo->payed_summ = $toPay;
                        if($periodInfo->total_pay == $toPay){
                            $periodInfo->is_full_payed = 1;
                        }
                        else{
                            $periodInfo->is_partial_payed = 1;
                        }
                        $periodInfo->save();
                    }
                }
            }
        }
        if(!empty($this->single)){
            foreach ($this->single as $key=>$value) {
                $toPay = CashHandler::fromRubles($value);
                if($value > 0){
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataSingleHandler::findOne(BillSingleHandler::findOne($key)->single_id);
                    $payed = PayedSingleHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if(!empty($payed)){
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if($payedAmount == $periodInfo->total_pay){
                            die('Доработать излишнюю оплату целевых взносов');
                        }
                    }
                    else{
                        // проверю, что оплаченная сумма не больше необходимой
                        if($toPay > $periodInfo->total_pay){
                            die('переплата');
                        }
                        // создам оплату членских взносов
                        $newPayedSingle = new PayedSingleHandler();
                        $newPayedSingle->transaction_id = $newTransaction->id;
                        $newPayedSingle->pay_id = $periodInfo->id;
                        $newPayedSingle->summ = $toPay;
                        $newPayedSingle->pay_date = $dealTime;
                        $newPayedSingle->cottage_id = $this->cottage_id;
                        $newPayedSingle->bill_id = $this->bill_id;
                        $newPayedSingle->save();
                        $periodInfo->payed_summ = $toPay;
                        if($periodInfo->total_pay == $toPay){
                            $periodInfo->is_full_payed = 1;
                        }
                        else{
                            $periodInfo->is_partial_payed = 1;
                        }
                        $periodInfo->save();
                    }
                }
            }
        }
        if(!empty($this->fines)){
            foreach ($this->fines as $key=>$value) {
                $toPay = CashHandler::fromRubles($value);
                if($value > 0){
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = FinesHandler::findOne(BillFinesHandler::findOne($key)->fines_id);
                    $payed = PayedFinesHandler::find()->where(['fines_id' => $periodInfo->id])->all();
                    if(!empty($payed)){
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if($payedAmount == $periodInfo->summ){
                            die('Доработать излишнюю оплату целевых взносов');
                        }
                    }
                    else{
                        // проверю, что оплаченная сумма не больше необходимой
                        if($toPay > $periodInfo->summ){
                            die('переплата');
                        }
                        // создам оплату членских взносов
                        $newPayedFines = new PayedFinesHandler();
                        $newPayedFines->transaction_id = $newTransaction->id;
                        $newPayedFines->fines_id = $periodInfo->id;
                        $newPayedFines->summ = $toPay;
                        $newPayedFines->pay_date = $dealTime;
                        $newPayedFines->cottage_id = $this->cottage_id;
                        $newPayedFines->bill_id = $this->bill_id;
                        $newPayedFines->save();
                        $periodInfo->payed_summ = $toPay;
                        if($periodInfo->summ == $toPay){
                            $periodInfo->is_full_payed = 1;
                        }
                        else{
                            $periodInfo->is_partial_payed = 1;
                        }
                        $periodInfo->save();
                    }
                }
            }
        }
        if($dividedAmount != $amount){
            $transaction->rollbackTransaction();
            return ['info' => 'Распределены не все средства'];
        }
        $billInfo->is_undistributed = 0;
        $billInfo->save();
        if($this->notify){
            EmailHandler::sendTransactionInfo($newTransaction->id);
        }
        $transaction->commitTransaction();
        return ['status' => 1, 'message' => 'Средства успешно распределены'];
    }
}