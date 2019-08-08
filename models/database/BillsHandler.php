<?php


namespace app\models\database;


use app\models\BankDetails;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\BillInfo;
use app\models\selection_classes\TransactionInfo;
use app\models\utils\CashHandler;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $time_create [bigint(20) unsigned]  Дата создания счёта
 * @property int $from_deposit [bigint(20) unsigned]  К оплате с депозита
 * @property int $discount [bigint(20) unsigned]  Сумма скидки
 * @property int $bill_summ [bigint(20) unsigned]  Сумма к оплате
 * @property int $payed [bigint(20) unsigned]  Оплаченная сумма
 * @property bool $is_full_payed [tinyint(1)]  Счёт полностью погашен
 * @property bool $is_partial_payed [tinyint(1)]  Счёт частично погашен
 * @property bool $is_closed [tinyint(1)]  Активность счёта
 * @property int $payerId [int(10) unsigned]  Идентификатор плательщика
 * @property bool $is_email_sended [tinyint(1) unsigned]  Отправлено письмо
 * @property bool $is_invoice_printed [tinyint(1) unsigned]  Распечатана квитанция
 * @property bool $is_undistributed [tinyint(1) unsigned]  Имеются нераспределённые средства
 */

class BillsHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "bills";
    }

    /**
     * @param $id
     * @param $bankId
     * @return BillInfo
     * @throws ExceptionWithStatus
     */
    public static function getBillInfo($id, $bankId = null)
    {
        $billInfo = new BillInfo();
        if(!empty($bankId)){
            $billInfo->bankTransaction = BankTransactionsHandler::findOne($bankId);
        }
        $billInfo->bill = self::findOne($id);
        $billInfo->cottage = CottagesHandler::get($billInfo->bill->cottage_number);
        $billInfo->billFines = BillFinesHandler::find()->where(['bill_id' => $id])->all();
        $billInfo->billPower = BillPowerHandler::find()->where(['bill_id' => $id])->all();
        $billInfo->billMembership = BillMembershipHandler::find()->where(['bill_id' => $id])->all();
        $billInfo->billTarget = BillTargetHandler::find()->where(['bill_id' => $id])->all();
        $billInfo->billSingle = BillSingleHandler::find()->where(['bill_id' => $id])->all();
        $transactions = TransactionsHandler::find()->where(['bill_id' => $id])->all();
        $billInfo->transactions = [];
        if(!empty($transactions)){
            foreach ($transactions as $transaction) {
                $transactionInfo = new TransactionInfo();
                $transactionInfo->transaction = $transaction;
                $transactionInfo->payedFines = PayedFinesHandler::find()->where(['bill_id' => $transaction->id])->all();
                $transactionInfo->payedPower = PayedPowerHandler::find()->where(['bill_id' => $transaction->id])->all();
                $transactionInfo->payedMembership = PayedMembershipHandler::find()->where(['bill_id' => $transaction->id])->all();
                $transactionInfo->payedTarget = PayedTargetHandler::find()->where(['bill_id' => $transaction->id])->all();
                $transactionInfo->payedSingle = PayedSingleHandler::find()->where(['bill_id' => $transaction->id])->all();
                $billInfo->transactions[] = $transactionInfo;
            }
        }
        return $billInfo;
    }

    /**
     * @param $billId
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function getBankInfo($billId)
    {
        $billInfo = self::getBillInfo($billId);
        $bankDetails = new BankDetails();
        // добавлю в детали имя плательщика
        $bankDetails->lastName = ContactsHandler::findOne($billInfo->bill->payerId)->contact_name;

        $purposeText = 'Оплата ';

        if (!empty($billInfo->billPower)) {
            $purposeText .= 'электроэнергии,';
        }
        if (!empty($billInfo->billMembership)) {
            $purposeText .= ' членских взносов,';
        }
        if (!empty($billInfo->billTarget)) {
            $purposeText .= ' целевых взносов,';
        }
        if (!empty($billInfo->billSingle)) {
            $purposeText .= ' разных взносов,';
        }
        if (!empty($billInfo->billFines)) {
            $purposeText .= ' пени,';
        }

        $bankDetails->purpose = substr($purposeText, 0, strlen($purposeText) - 1) . ' по сч. № ' . $billInfo->bill->id;
        $bankDetails->summ = CashHandler::toRubles($billInfo->bill->bill_summ - $billInfo->bill->discount - $billInfo->bill->from_deposit);
        $bankDetails->cottageNumber = $billInfo->cottage->cottage_number;
        return ['billInfo' => $billInfo, 'bankInfo' => $bankDetails];
    }

    /**
     * @param int $bill_id
     * @return BillsHandler
     * @throws ExceptionWithStatus
     */
    public static function get(int $bill_id)
    {
        $bill = self::findOne($bill_id);
        if(empty($bill)){
            throw new ExceptionWithStatus('Счёт не найден');
        }
        return $bill;
    }
}