<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property string $year [char(6)]  Год оплаты
 * @property int $pay_for_meter [bigint(20) unsigned]  Оплата за кв. метр
 * @property int $pay_for_cottage [bigint(20) unsigned]  Оплата за участок
 * @property int $pay_up_date [bigint(20) unsigned]  Крайний день оплаты
 * @property int $search_timestamp [bigint(20) unsigned]  Дата для выборок
 * @property string $pay_description Назначение платежа
 */

class TariffTargetHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "tariffs_target";
    }
}