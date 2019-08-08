<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $bill_id [int(10) unsigned]  Идентификатор платежа
 * @property int $summ [bigint(20) unsigned]  Сумма скидки
 * @property string $reason Причина скидки
 * @property int $pay_date [bigint(20) unsigned]  Дата операции
 * @property int $transaction_id [int(10) unsigned]  Идентификатор транзакции
 */

class DiscountHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "discounts";
    }
}