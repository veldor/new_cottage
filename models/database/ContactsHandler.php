<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\ContactInfo;
use app\models\utils\GrammarHandler;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор контакта
 * @property int $cottage_id [int(10) unsigned]  Идентификатор участка
 * @property string $contact_name [varchar(200)]  ФИО контакта
 * @property string $contact_address Почтовый адрес контакта
 * @property string $contact_description Информация о контакте
 * @property bool $is_owner [tinyint(1)]  Является ли контакт владельцем
 * @property bool $is_active [tinyint(1)]  Является ли контакт активным
 */
class ContactsHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "contacts";
    }

    public static function checkOwners($id)
    {
        return self::find()->where(['cottage_id' => $id, 'is_owner' => 1, 'is_active' => 1])->count() > 1;
    }

    /**
     * @param $cottageId
     * @return string
     * @throws ExceptionWithStatus
     */
    public static function getContactsTable($cottageId)
    {
        $text = '';
        $cottageInfo = CottagesHandler::findOne($cottageId);
        if (empty($cottageInfo)) {
            throw new ExceptionWithStatus('Участок не найден');
        }
        // найду список контактов
        // сведения о контактах
        // если участок главный- ищу контакты с id участка, если дополнительный без отдельного владельца- контакты главного участка, если дополнительный с отдельным- контакты по id дополнительного участка
        if($cottageInfo->is_additional){
            if($cottageInfo->is_different_owner){
                $contacts = ContactsHandler::find()->where(['cottage_id' => $cottageInfo->id, 'is_active' => 1])->orderBy('is_owner DESC')->all();
            }
        }
        else{
            $contacts = ContactsHandler::find()->where(['cottage_id' => $cottageInfo->id, 'is_active' => 1])->orderBy('is_owner DESC')->all();
        }
        if(!empty($contacts)){
            foreach ($contacts as $contact) {
                $contactInfo = new ContactInfo();
                $contactInfo->contact = $contact;
                // найду номера телефонов и почты
                $contactInfo->phones = PhonesHandler::find()->where(['contact_id' => $contact->id])->orderBy('is_main DESC')->all();
                $contactInfo->emails = EmailsHandler::find()->where(['contact_id' => $contact->id])->orderBy('is_main DESC')->all();
                $contactsList[] = $contactInfo;
            }
        }
        if (!empty($contactsList)) {
            $text .= "<table class='table table-hover table-striped table-condensed'>
                        <tr>
                            <th>ФИО</th>
                            <th>Статус</th>
                            <th>Телефон</th>
                            <th>Почта</th>
                            <th>Информация</th>
                        </tr>
                    ";
            foreach ($contactsList as $contact) {
                if ($contact->contact->is_owner) {
                    $status = '<b class="text-success">Владелец</b>';
                } else {
                    $status = '<b class="text-info">Контакт</b>';
                }
                if (!empty($contact->phones)) {
                    $phonesText = '';
                    foreach ($contact->phones as $phone) {
                        $tooltip = '';
                        if (!empty($phone->phone_description)) {
                            $tooltip .= "data-toggle='tooltip' data-placement='auto' title='$phone->phone_description''";
                        }
                        $phonesText .= "<div id='wrapper_phone_{$phone->id}'>
                                        <div class='btn-group  control-container hidden margened'>
                                                  <button id='change_phone_{$phone->id}' type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>$phone->phone_number <span class='glyphicon glyphicon-cog'></span></button>
                                                  <ul class='dropdown-menu' role='menu'>
                                                    <li><a class='control-element' href='#' data-type='edit-contact' data-action='change-phone' data-phone-id='{$phone->id}'>Изменить</a></li>
                                                    <li><a class='control-element' href='#' data-type='edit-contact' data-action='delete-phone' data-phone-id='{$phone->id}'>Удалить</a></li>
                                                  </ul>
                                                </div>
                                        <a id='show_phone_{$phone->id}' $tooltip class='editable has-tooltip' href='tel:{$phone->phone_number}'>{$phone->phone_number}</a></div>";
                    }
                } else {
                    $phonesText = '<div class=" empty-fill"><b class="text-warning">Отсутствует</b></div>';
                }
                $phonesText .= "<a data-type='edit-contact' data-action='add-phone' data-contact-id='{$contact->contact->id}' href='#' class='control-element btn btn-info control-container hidden add-block'><span class='glyphicon glyphicon-plus'></span> добавить номер</a>";
                if (!empty($contact->emails)) {
                    $emailsText = '';
                    foreach ($contact->emails as $email) {
                        $tooltip = '';
                        if (!empty($email->email_description)) {
                            $tooltip .= "data-toggle='tooltip' data-placement='auto' title='$email->email_description''";
                        }
                        $emailsText .= "<div id='wrapper_mail_{$email->id}'>
                                        <div class='btn-group  control-container hidden margened'>
                                                  <button id='change_mail_{$email->id}' type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>$email->email_address <span class='glyphicon glyphicon-cog'></span></button>
                                                  <ul class='dropdown-menu' role='menu'>
                                                    <li><a class='control-element' href='#' data-type='edit-contact' data-action='change-mail' data-mail-id='{$email->id}'>Изменить</a></li>
                                                    <li><a class='control-element' href='#' data-type='edit-contact' data-action='delete-mail' data-mail-id='{$email->id}'>Удалить</a></li>
                                                  </ul>
                                                </div>
                                        <a id='show_mail_{$email->id}' $tooltip class='editable has-tooltip' href='mailto:{$email->email_address}'>{$email->email_address}</a></div>";
                    }
                } else {
                    $emailsText = '<div class=" empty-fill"><b class="text-warning">Отсутствует</b></div>';
                }
                $emailsText .= "<a data-type='edit-contact' data-action='add-mail' data-contact-id='{$contact->contact->id}' href='#' class='control-element btn btn-info control-container hidden add-block'><span class='glyphicon glyphicon-plus'></span> добавить почту</a>";
                $infoText = '';
                // проверю наличие почтового адреса и прочих заметок
                if (!empty($contact->contact->contact_address)) {
                    $infoText .= 'Почтовый адрес: ' . GrammarHandler::clearAddress($contact->contact->contact_address) . '<br/>';
                }
                if (!empty($contact->contact->contact_description)) {
                    $infoText .= 'Дополнительная информация: ' . $contact->contact->contact_description;
                }
                $text .= "
                    <tr id='contact_container_{$contact->contact->id}'>
                        <td>
                            {$contact->contact->contact_name}
                            <div class='btn-group control-container hidden'>
                                                  <button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>Редактировать <span class='glyphicon glyphicon-cog'></span></button>
                                                  <ul class='dropdown-menu' role='menu'>
                                                    <li><a class='control-element' href='#' data-type='edit-contact' data-action='change-contact' data-contact-id='{$contact->contact->id}'>Изменить</a></li>
                                                    <li><a class='control-element' href='#' data-type='edit-contact' data-action='delete-contact' data-contact-id='{$contact->contact->id}'>Удалить</a></li>
                                                  </ul>
                                                </div>
                        </td>
                        <td>{$status}</td>
                        <td id='phone_container_{$contact->contact->id}'>{$phonesText}</td>
                        <td id='email_container_{$contact->contact->id}'>{$emailsText}</td>
                        <td>{$infoText}</td>
                    </tr>
                ";
            }
            $text .= "</table>";
            $text .= "<a href='#' data-type='edit-contact' data-action='add-contact' data-cottage-id='{$cottageInfo->id}'  class='control-element btn btn-info control-container hidden'><span class='glyphicon glyphicon-plus'></span> добавить контакт</a>";
        } elseif ($cottageInfo->is_additional && !$cottageInfo->is_different_owner) {
            $text .= "<p>Контакты вы можете посмотреть на <a href='/cottage/show/{$cottageInfo->main_cottage_id}#contacts'>странице основного участка</a></p>";
            $text .= "<a href='#' data-type='edit-contact' data-action='add-contact' data-cottage-id='{$cottageInfo->id}'  class='control-element btn btn-info control-container hidden'><span class='glyphicon glyphicon-plus'></span> добавить контакт</a>";
        } else {
            $text .= "<h1>Контакты не зарегистрированы.</h1>";
            $text .= "<a href='#' data-type='edit-contact' data-action='add-contact' data-cottage-id='{$cottageInfo->id}'  class='control-element btn btn-info control-container hidden'><span class='glyphicon glyphicon-plus'></span> добавить контакт</a>";
        }
        return $text;
    }

    /**
     * @param int $id
     * @return ContactsHandler[]
     */
    public static function getAllContacts(int $id)
    {
        return self::find()->where(['cottage_id' => $id])->all();
    }
}