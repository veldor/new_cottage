<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $bill_id [int(10) unsigned]  Идентификатор счёта
 * @property string $destination [enum('in', 'out')]  Зачисление\списание
 * @property int $summ [bigint(20) unsigned]  Сумма операции
 * @property int $summ_before [bigint(20) unsigned]  Значение депозита участка до операции
 * @property int $summ_after [bigint(20) unsigned]  Значение депозита участка после операции
 * @property int $pay_date [bigint(20) unsigned]  Дата операции
 * @property string $description Дополнительная информация
 * @property int $transaction_id [int(10) unsigned]  Идентификатор транзакции
 */

class DepositHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "deposit_io";
    }
}