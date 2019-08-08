<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $year [bigint(20)]  Год
 * @property int $square [int(11)]  Расчётная площадь
 * @property int $total_pay [bigint(20)]  Общая сумма оплаты
 * @property int $payed_summ [bigint(20)]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1)]  Год частично оплачен
 * @property bool $is_full_payed [tinyint(1)]  Год полностью оплачен
 * @property bool $is_individual_tariff [tinyint(1)]  Активность индивидуального тарифа
 * @property int $individual_pay_for_field [bigint(20) unsigned]  Индивидуально с сотки
 * @property int $individual_pay_for_cottage [bigint(20) unsigned]  Индивидуально с участка
 * @property int $pay_up_date [bigint(20) unsigned]  Крайняя дата оплаты
 */

class DataTargetHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "data_target";
    }

    /**
     * @param int $cottageId
     * @return DataTargetHandler[]
     */
    public static function getDuties(int $cottageId)
    {
        return self::find()->where(['cottage_number' => $cottageId, 'is_full_payed' => 0])->all();
    }
}