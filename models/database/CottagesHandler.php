<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property string $cottage_number [varchar(10)]  Номер участка
 * @property bool $is_membership [tinyint(1)]  Оплачиваются членские взносы
 * @property int $membership_debt [bigint(20)]  Сумма задолженности по членским взносам
 * @property bool $is_power [tinyint(1)]  Оплачивается электричество
 * @property int $power_debt [bigint(20) unsigned]  Сумма задолженности по электроэнергии
 * @property bool $is_target [tinyint(1)]  Оплачиваются целевые взносы
 * @property int $target_debt [bigint(20)]  Сумма задолженности по целевым взносам
 * @property int $single_debt [bigint(20)]  Сумма задолженности по разовым взносам
 * @property int $square [int(11)]  Площадь участка, кв.м.
 * @property bool $is_have_property_rights [tinyint(1)]  Наличие справки о правах собственности
 * @property bool $is_cottage_register_data [tinyint(1)]  Наличие данных для реестра
 * @property string $property_data Данные права собственности
 * @property string $register_data Данные кадастрового учёта
 * @property bool $is_individual_tariff [tinyint(1)]  Участку подключен индивидуальный тариф
 * @property bool $is_additional [tinyint(1)]  Участок дополнительный
 * @property bool $is_different_owner [tinyint(1)]  Отдельный владелец дополнительного участка
 * @property int $main_cottage_id [int(10) unsigned]  Идентификатор главного участка
 * @property int $deposit [bigint(20) unsigned]  Депозит участка
 */

class CottagesHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "cottages";
    }

    /**
     * @param string $cottageNumber
     * @return CottagesHandler
     */
    public static function getIdByNumber($cottageNumber){
        return self::find()->where(['cottage_number' => $cottageNumber])->one();
    }

    /**
     * @param string $cottage_number
     * @return CottagesHandler
     */
    public static function getAdditionalCottage($cottage_number)
    {
        $cottageInfo = self::findOne($cottage_number);
        return self::find()->where(['main_cottage_id' => $cottageInfo->cottage_number])->one();
    }

    /**
     * @param string $cottageNumber
     * @return CottagesHandler
     */
    public static function findByNumber($cottageNumber)
    {
        return self::find()->where(['cottage_number' => $cottageNumber])->one();
    }

    /**
     * @param int $cottage_id
     * @return string
     */
    public static function getNumberById(int $cottage_id)
    {
        return self::findOne($cottage_id)->cottage_number;
    }

    /**
     * @param int $cottage_id
     * @return CottagesHandler|null
     * @throws ExceptionWithStatus
     */
    public static function get(int $cottage_id)
    {
        $cottage = self::findOne($cottage_id);
        if(empty($cottage)){
            throw new ExceptionWithStatus("Сведения об участке не найдены");
        }
        return $cottage;
    }

    public static function check(int $cottage_id)
    {
        return self::find()->where(['id' => $cottage_id])->count();
    }
}