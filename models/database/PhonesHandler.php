<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $contact_id [int(10) unsigned]  Идентификатор контакта
 * @property string $phone_number [varchar(20)]  Номер телефона
 * @property bool $is_main [tinyint(1)]  Является ли основным
 * @property string $phone_description Комментарий к номеру
 */

class PhonesHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "phones";
    }
}