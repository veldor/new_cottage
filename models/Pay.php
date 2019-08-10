<?php


namespace app\models;


use app\models\database\BankTransactionsHandler;
use app\models\database\BillFinesHandler;
use app\models\database\BillMembershipHandler;
use app\models\database\BillPowerHandler;
use app\models\database\BillsHandler;
use app\models\database\BillSingleHandler;
use app\models\database\BillTargetHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\DepositHandler;
use app\models\database\FinesHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\TransactionsHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\EmailHandler;
use app\models\utils\TimeHandler;
use Exception;
use yii\base\Model;

class Pay extends Model
{
    // СЦЕНАРИИ
    const SCENARIO_TYPICAL = 'typical';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_TYPICAL => ['cottageId', 'billId', 'payedSumm', 'targetOwner', 'deposit', 'power', 'membership', 'target', 'single', 'fines', 'notify', 'bankTransactionId', 'payCustomDate', 'getCustomDate'],
        ];
    }

    public $cottageId;
    public $billId;
    public $payedSumm;
    public $targetOwner;
    public $deposit;
    public $power;
    public $membership;
    public $target;
    public $single;
    public $fines;
    public $bankTransactionId;

    public $payCustomDate; // фактическая дата оплаты
    public $getCustomDate; // дата поступления оплаты на банковский счёт

    public $notify = 0;

    /**
     * @return array
     * @throws exceptions\ExceptionWithStatus
     * @throws Exception
     */
    public function pay()
    {
        $transaction = new DbTransaction();


        // получу сумму для распределения
        $amount = CashHandler::fromRubles($this->payedSumm);
        $dealTime = time();

        $billInfo = BillsHandler::findOne($this->billId);


        $cottageInfo = CottagesHandler::get($this->cottageId);

        $requiredAmount = $billInfo->bill_summ - $billInfo->payed;

        $payWholeness = $amount >= $requiredAmount ? true : false;

        // создам транзакцию для учёта
        $newTransaction = new TransactionsHandler();
        $newTransaction->bill_id = $this->billId;
        $newTransaction->cottage_id = $this->cottageId;
        $newTransaction->summ = $amount;
        $newTransaction->payDate = $this->payCustomDate ? TimeHandler::getCustomTimestamp($this->payCustomDate) : $dealTime;
        $newTransaction->bankDate = $this->getCustomDate ? TimeHandler::getCustomTimestamp($this->getCustomDate) : $dealTime;
        $newTransaction->transaction_reason = 'Оплата по счёту #' . $this->billId;
        $newTransaction->save();

        if (!empty($this->bankTransactionId)) {
            $bankTransaction = BankTransactionsHandler::get($this->bankTransactionId);
            if (!empty($bankTransaction->bounded_transaction_id)) {
                throw new ExceptionWithStatus('Банковская транзакция уже связана со счётом');
            }
            $bankTransaction->bounded_transaction_id = $newTransaction->id;
            $bankTransaction->save();
        }
        $dividedAmount = 0;
        // разберу категории
        if (!empty($this->membership)) {
            foreach ($this->membership as $key => $value) {
                $toPay = CashHandler::fromRubles($value);
                if ($value > 0) {
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataMembershipHandler::findOne(BillMembershipHandler::findOne($key)->membership_data_id);
                    $payed = PayedMembershipHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if (!empty($payed)) {
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if ($payedAmount == $periodInfo->total_pay) {
                            die('П');
                        }
                    } else {
                        // проверю, что оплаченная сумма не больше необходимой
                        if ($toPay > $periodInfo->total_pay) {
                            die('переплата');
                        }
                        // создам оплату членских взносов
                        $newPayedMembership = new PayedMembershipHandler();
                        $newPayedMembership->transaction_id = $newTransaction->id;
                        $newPayedMembership->quarter = $periodInfo->quarter;
                        $newPayedMembership->summ = $toPay;
                        $newPayedMembership->pay_date = $dealTime;
                        $newPayedMembership->period_id = $periodInfo->id;
                        $newPayedMembership->cottage_id = $this->cottageId;
                        $newPayedMembership->bill_id = $this->billId;
                        $newPayedMembership->save();
                        $periodInfo->payed_summ = $toPay;
                        if ($periodInfo->total_pay == $toPay) {
                            $periodInfo->is_full_payed = 1;
                        } else {
                            $periodInfo->is_partial_payed = 1;
                        }
                        $periodInfo->save();
                    }
                }
            }
        }

        if (!empty($this->power)) {
            foreach ($this->power as $key => $value) {
                $toPay = CashHandler::fromRubles($value);
                if ($toPay > 0) {
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataPowerHandler::findOne(BillPowerHandler::findOne($key)->power_data_id);
                    $payed = PayedPowerHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if (!empty($payed)) {
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        // проверю, что сумма оплаты меньше или равна необходимой сумме оплаты
                        $requiredPowerAmount = $periodInfo->total_pay;
                        $leftToPay = $requiredPowerAmount - $payedAmount;
                        if ($toPay > $leftToPay) {
                            $powerOut = $toPay - $leftToPay;

                            // создам оплату электроэнергии
                            $newPayedPower = new PayedPowerHandler();
                            $newPayedPower->transaction_id = $newTransaction->id;
                            $newPayedPower->month = $periodInfo->month;
                            $newPayedPower->counter_id = $periodInfo->counter_id;
                            $newPayedPower->summ = $leftToPay;
                            $newPayedPower->pay_date = $dealTime;
                            $newPayedPower->period_id = $periodInfo->id;
                            $newPayedPower->cottage_id = $this->cottageId;
                            $newPayedPower->bill_id = $this->billId;
                            $newPayedPower->save();
                            $periodInfo->payed_summ += $leftToPay;
                            $periodInfo->is_full_payed = 1;
                            $periodInfo->save();
                            // остальное отправлю на депозит
                            $depositTransaction = new DepositHandler();
                            $depositTransaction->cottage_number = $this->cottageId;
                            $depositTransaction->bill_id = $this->billId;
                            $depositTransaction->transaction_id = $newTransaction->id;
                            $depositTransaction->destination = 'in';
                            $depositTransaction->summ_before = $cottageInfo->deposit;
                            $cottageInfo->deposit += $powerOut;
                            $depositTransaction->summ_after = $cottageInfo->deposit;
                            $depositTransaction->pay_date = $newTransaction->bankDate;
                            $depositTransaction->summ = $powerOut;
                            $depositTransaction->description = 'Излишняя оплата электроэнергиии за период #' . $periodInfo->id . ', транзакция #' . $newTransaction->id;
                            $depositTransaction->save();
                            $cottageInfo->save();
                            continue;
                        }
                    }
                    // проверю, что оплаченная сумма не больше необходимой
                    if ($toPay > $periodInfo->total_pay) {
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
                    $newPayedPower->cottage_id = $this->cottageId;
                    $newPayedPower->bill_id = $this->billId;
                    $newPayedPower->save();
                    $periodInfo->payed_summ += $toPay;
                    if ($periodInfo->total_pay == $toPay) {
                        $periodInfo->is_full_payed = 1;
                    } else {
                        $periodInfo->is_partial_payed = 1;
                    }
                    $periodInfo->save();
                }
            }
        }
        if (!empty($this->target)) {
            foreach ($this->target as $key => $value) {
                $toPay = CashHandler::fromRubles($value);
                if ($value > 0) {
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataTargetHandler::findOne(BillTargetHandler::findOne($key)->year_id);
                    $payed = PayedTargetHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if (!empty($payed)) {
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if ($payedAmount == $periodInfo->total_pay) {
                            die('Доработать излишнюю оплату целевых взносов');
                        }
                    }
                    // проверю, что оплаченная сумма не больше необходимой
                    if ($toPay > $periodInfo->total_pay) {
                        die('переплата');
                    }
                    // создам оплату членских взносов
                    $newPayedTarget = new PayedTargetHandler();
                    $newPayedTarget->transaction_id = $newTransaction->id;
                    $newPayedTarget->year = $periodInfo->year;
                    $newPayedTarget->summ = $toPay;
                    $newPayedTarget->pay_date = $dealTime;
                    $newPayedTarget->period_id = $periodInfo->id;
                    $newPayedTarget->cottage_id = $this->cottageId;
                    $newPayedTarget->bill_id = $this->billId;
                    $newPayedTarget->save();
                    $periodInfo->payed_summ += $toPay;
                    if ($periodInfo->total_pay == $toPay) {
                        $periodInfo->is_full_payed = 1;
                    } else {
                        $periodInfo->is_partial_payed = 1;
                    }
                    $periodInfo->save();
                }
            }
        }
        if (!empty($this->single)) {
            foreach ($this->single as $key => $value) {
                $toPay = CashHandler::fromRubles($value);
                if ($value > 0) {
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = DataSingleHandler::findOne(BillSingleHandler::findOne($key)->single_id);
                    $payed = PayedSingleHandler::find()->where(['period_id' => $periodInfo->id])->all();
                    if (!empty($payed)) {
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if ($payedAmount == $periodInfo->total_pay) {
                            die('Доработать излишнюю оплату целевых взносов');
                        }
                    }
                    // проверю, что оплаченная сумма не больше необходимой
                    if ($toPay > $periodInfo->total_pay) {
                        die('переплата');
                    }
                    // создам оплату членских взносов
                    $newPayedSingle = new PayedSingleHandler();
                    $newPayedSingle->transaction_id = $newTransaction->id;
                    $newPayedSingle->pay_id = $periodInfo->id;
                    $newPayedSingle->summ = $toPay;
                    $newPayedSingle->pay_date = $dealTime;
                    $newPayedSingle->cottage_id = $this->cottageId;
                    $newPayedSingle->bill_id = $this->billId;
                    $newPayedSingle->save();
                    $periodInfo->payed_summ += $toPay;
                    if ($periodInfo->total_pay == $toPay) {
                        $periodInfo->is_full_payed = 1;
                    } else {
                        $periodInfo->is_partial_payed = 1;
                    }
                    $periodInfo->save();
                }

            }
        }
        if (!empty($this->fines)) {
            foreach ($this->fines as $key => $value) {
                $toPay = CashHandler::fromRubles($value);
                if ($value > 0) {
                    $dividedAmount += $toPay;
                    // проверю, не проводилась ли оплата по этому периоду, если она проводилась и сумма оплаты больше необходимой- вместо оплаты зачислю средства на депозит участка
                    $periodInfo = FinesHandler::findOne(BillFinesHandler::findOne($key)->fines_id);
                    $payed = PayedFinesHandler::find()->where(['fines_id' => $periodInfo->id])->all();
                    if (!empty($payed)) {
                        $payedAmount = 0;
                        foreach ($payed as $payedItem) {
                            $payedAmount += $payedItem->summ;
                        }
                        if ($payedAmount == $periodInfo->summ) {
                            die('Доработать излишнюю оплату целевых взносов');
                        }
                    }
                    // проверю, что оплаченная сумма не больше необходимой
                    if ($toPay > $periodInfo->summ) {
                        die('переплата');
                    }
                    // создам оплату членских взносов
                    $newPayedFines = new PayedFinesHandler();
                    $newPayedFines->transaction_id = $newTransaction->id;
                    $newPayedFines->fines_id = $periodInfo->id;
                    $newPayedFines->summ = $toPay;
                    $newPayedFines->pay_date = $dealTime;
                    $newPayedFines->cottage_id = $this->cottageId;
                    $newPayedFines->bill_id = $this->billId;
                    $newPayedFines->save();
                    $periodInfo->payed_summ += $toPay;
                    if ($periodInfo->summ == $toPay) {
                        $periodInfo->is_full_payed = 1;
                    } else {
                        $periodInfo->is_partial_payed = 1;
                    }
                    $periodInfo->save();
                }
            }
        }

        if (!$payWholeness && $dividedAmount != $amount) {
            $transaction->rollbackTransaction();
            return ['info' => 'Распределены не все средства'];
        }
        if ($payWholeness) {
            if ($amount > $requiredAmount) {
                // зачислю переплаченную сумму на депозит
                $difference = $amount - $dividedAmount;
                $depositTransaction = new DepositHandler();
                $depositTransaction->cottage_number = $this->cottageId;
                $depositTransaction->bill_id = $this->billId;
                $depositTransaction->transaction_id = $newTransaction->id;
                $depositTransaction->destination = 'in';
                $depositTransaction->summ_before = $cottageInfo->deposit;
                $cottageInfo->deposit += $difference;
                $depositTransaction->summ_after = $cottageInfo->deposit;
                $depositTransaction->pay_date = $newTransaction->bankDate;
                $depositTransaction->summ = $difference;
                $depositTransaction->description = 'Зачисление по счёту ' . $this->billId;
                $depositTransaction->save();
                $cottageInfo->save();
            }
            $billInfo->is_full_payed = 1;
            $billInfo->is_partial_payed = 0;
        } else {
            $billInfo->is_full_payed = 0;
            $billInfo->is_partial_payed = 1;
        }
        $billInfo->payed += $amount;
        $billInfo->save();
        if ($this->notify) {
            EmailHandler::sendTransactionInfo($newTransaction->id);
        }
        $transaction->commitTransaction();
        return ['status' => 1, 'message' => 'Средства успешно распределены'];
    }
}