<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $transaction_id [int(10) unsigned]  Идентификатор транзакции
 * @property int $pay_id [bigint(20) unsigned]  Идентификатор разового платежа
 * @property int $summ [bigint(20) unsigned]  Сумма оплаты
 * @property int $pay_date [bigint(20) unsigned]  Дата оплаты
 * @property int $cottage_id [int(10) unsigned]  Идентификатор участка
 * @property int $bill_id [int(10) unsigned]
 */

class PayedSingleHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "payed_single";
    }
}