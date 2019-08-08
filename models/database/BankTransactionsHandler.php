<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use yii\db\ActiveRecord;

/**
 *
 * @property int $bank_operation_id [bigint(20) unsigned]  Уникальный код операции в
 * @property string $pay_date [char(10)]  Дата платежа
 * @property string $pay_time [char(8)]  Время платежа
 * @property string $filial_number [varchar(100)]  Номер отделения
 * @property string $handler_number [varchar(100)]  Номер кассира/УС/СБОЛ
 * @property string $account_number [varchar(100)]  Лицевой счет
 * @property string $fio [varchar(200)]  Фамилия, Имя, Отчество
 * @property string $address [varchar(200)]  Адрес
 * @property string $payment_period [varchar(100)]  Период оплаты
 * @property string $payment_summ [varchar(100)]  Сумма операции
 * @property string $transaction_summ [varchar(100)]  Сумма перевода
 * @property string $commission_summ [varchar(100)]  Сумма комиссии банку
 * @property int $bounded_transaction_id [int(10) unsigned]  Идентификатор платежа в системе
 * @property string $real_pay_date [char(10)]  Истинная дата платежа
 */

class BankTransactionsHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "bank_invoices";
    }

    /**
     * @param $bankTransactionId
     * @return BankTransactionsHandler
     * @throws ExceptionWithStatus
     */
    public static function get($bankTransactionId)
    {
        $transaction = self::findOne($bankTransactionId);
        if(!empty($transaction)){
            return $transaction;
        }
        throw new ExceptionWithStatus('Не найдена банковская транзакция');
    }
}