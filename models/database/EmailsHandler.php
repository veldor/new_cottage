<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $contact_id [int(10) unsigned]  Идентификатор контакта
 * @property string $email_address [varchar(200)]  Адрес почты
 * @property bool $is_main [tinyint(1)]  Является ли основным
 * @property string $email_description Комментарий к почте
 */

class EmailsHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "emails";
    }

    public static function isMail($id)
    {
        $contacts = ContactsHandler::find()->where(['cottage_id' => $id])->all();
        if(!empty($contacts)){
            foreach ($contacts as $contact) {
                if(self::find()->where(['contact_id' => $contact->id])->one()){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param int $id
     * @return EmailsHandler[]
     */
    public static function get(int $id)
    {
        return self::find()->where(['contact_id' => $id])->all();
    }
}