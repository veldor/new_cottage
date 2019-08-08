<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $address [varchar(200)]  Адрес почты
 * @property string $subject [varchar(500)]  Тема
 * @property string $body Текст письма
 * @property bool $is_send [tinyint(1)]  Письмо успешно отправлено
 */

class SendMailsHandler extends ActiveRecord
{
    public static function tableName()
    {
        return 'send_emails';
    }
}