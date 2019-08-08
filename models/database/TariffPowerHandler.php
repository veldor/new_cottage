<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property string $month [char(7)]  Месяц оплаты
 * @property int $power_limit [int(10) unsigned]  Льготный лимит
 * @property int $power_cost [bigint(20) unsigned]  Льготная стоимость 1 кв.ч. электроэнергии
 * @property int $power_overcost [bigint(20) unsigned]  Стоимость 1 кв.ч. электроэнергии
 * @property int $pay_up_date [bigint(20) unsigned]  Крайний день оплаты
 * @property int $search_timestamp [bigint(20) unsigned]  Дата для выборок
 */

class TariffPowerHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "tariffs_power";
    }
}