<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $bill_id [int(10) unsigned]  Идентификатор счёта
 * @property int $power_data_id [int(10) unsigned]  Идентификатор месяца оплаты
 * @property int $start_summ [int(10) unsigned]  Сумма к оплате
 */

class BillPowerHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "bill_power";
    }
}