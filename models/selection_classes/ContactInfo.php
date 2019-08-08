<?php


namespace app\models\selection_classes;


use app\models\database\ContactsHandler;
use app\models\database\EmailsHandler;
use app\models\database\PhonesHandler;

/**
 *
 * @property ContactsHandler $contact Информация о контакте
 * @property PhonesHandler[] $phones Информация о номерах телефонов
 * @property EmailsHandler[] $emails Информация о почте
 */
class ContactInfo
{
    /**
     * @var ContactsHandler
     */
    public $contact;
    /**
     * @var PhonesHandler[]
     */
    public $phones;
    /**
     * @var EmailsHandler[]
     */
    public $emails;
}