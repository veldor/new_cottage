<?php


namespace app\models;


use app\models\database\ContactsHandler;
use app\models\database\CottagesHandler;
use app\models\database\EmailsHandler;
use app\models\database\PhonesHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\DbTransaction;
use app\models\utils\GrammarHandler;
use app\models\utils\LogHandler;
use app\validators\CheckPhoneNumberValidator;
use Exception;
use Throwable;
use yii\base\Model;

class EditContact extends Model
{
    // СЦЕНАРИИ
    const SCENARIO_ADD_EMAIL = 'add_email';
    const SCENARIO_ADD_PHONE = 'add_phone';
    const SCENARIO_DELETE_EMAIL = 'delete_email';
    const SCENARIO_EDIT_EMAIL = 'edit_email';
    const SCENARIO_DELETE_PHONE = 'delete_phone';
    const SCENARIO_EDIT_PHONE = 'edit_phone';
    const SCENARIO_DELETE_CONTACT = 'delete_contact';
    const SCENARIO_ADD_CONTACT = 'add_contact';
    const SCENARIO_CHANGE_CONTACT = 'change_contact';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_ADD_CONTACT => ['cottageId', 'contactName', 'isOwner', 'makeMain', 'description', 'contactAddressIndex', 'contactAddressTown', 'contactAddressStreet', 'contactAddressBuild', 'contactAddressFlat'],
            self::SCENARIO_CHANGE_CONTACT => ['contactNumber', 'contactName', 'isOwner', 'makeMain', 'contactAddressIndex', 'contactAddressTown', 'contactAddressStreet', 'contactAddressBuild', 'contactAddressFlat', 'description'],
            self::SCENARIO_ADD_EMAIL => ['contactNumber', 'emailAddress', 'description', 'makeMain'],
            self::SCENARIO_ADD_PHONE => ['contactNumber', 'phoneNumber', 'description', 'makeMain'],
            self::SCENARIO_EDIT_EMAIL => ['emailId', 'emailAddress', 'description', 'makeMain'],
            self::SCENARIO_EDIT_PHONE => ['phoneId', 'phoneNumber', 'description', 'makeMain'],
            self::SCENARIO_DELETE_EMAIL => ['emailId'],
            self::SCENARIO_DELETE_PHONE => ['phoneId'],
            self::SCENARIO_DELETE_CONTACT => ['contactNumber'],
        ];
    }

    /**
     * @var CottagesHandler
     */
    public $cottageInfo;

    public $emailId;
    public $phoneId;
    public $contactNumber;
    public $emailAddress;
    public $phoneNumber;
    public $description;
    public $makeMain;

    public $cottageId;
    public $contactName;
    public $isOwner;
    public $contactAddressIndex = ''; // Индекс места проживания
    public $contactAddressTown = ''; // Город проживания
    public $contactAddressStreet = ''; // Улица проживания
    public $contactAddressBuild = ''; // Номер дома
    public $contactAddressFlat = ''; // Номер квартиры


    public function attributeLabels(): array
    {
        return [
            'emailId' => 'Идентификатор адреса почты',
            'isOwner' => 'Это владелец',
            'phoneId' => 'Идентификатор номера телефона',
            'contactNumber' => 'Идентификатор контакта',
            'emailAddress' => 'Адрес почты',
            'phoneNumber' => 'Номер телефона',
            'description' => 'Примечание',
            'makeMain' => 'Назначить основным',
        ];
    }

    public function rules(): array
    {
        return [
            [['contactNumber', 'emailAddress'], 'required', 'on' => self::SCENARIO_ADD_EMAIL],
            [['emailId', 'emailAddress'], 'required', 'on' => self::SCENARIO_EDIT_EMAIL],
            ['emailId', 'required', 'on' => self::SCENARIO_DELETE_EMAIL],
            ['phoneId', 'required', 'on' => self::SCENARIO_DELETE_PHONE],
            ['contactNumber', 'required', 'on' => self::SCENARIO_DELETE_CONTACT],
            ['contactNumber', 'integer', 'min' => 1, 'max' => 300],
            ['emailAddress', 'email'],
            ['phoneNumber', CheckPhoneNumberValidator::class],
            ['description', 'string', 'max' => 500],
        ];
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillContactId($id)
    {
        $contact = ContactsHandler::findOne($id);
        if (empty($contact)) {
            throw new ExceptionWithStatus('Не найден контакт');
        }
        if ($this->scenario === self::SCENARIO_DELETE_CONTACT && $contact->is_owner && !ContactsHandler::checkOwners($id)) {
            // проверю, если контакт владелец- должен остаться хотя бы минимум ещё один после удаления
            throw new ExceptionWithStatus('Невозможно удалить единственного владельца. Сначала добавьте нового.');
        }
        $this->contactNumber = $id;
    }


    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillCottageId($id)
    {
        $cottage = CottagesHandler::findOne($id);
        if (empty($cottage)) {
            throw new ExceptionWithStatus('Не найден адрес участка');
        }
        $this->cottageInfo = $cottage;
        $this->cottageId = $id;
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillEmailId($id)
    {
        $contact = EmailsHandler::findOne($id);
        if (empty($contact)) {
            throw new ExceptionWithStatus('Не найден адрес почты');
        }
        $this->emailId = $id;
    }


    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillPhoneId($id)
    {
        $contact = PhonesHandler::findOne($id);
        if (empty($contact)) {
            throw new ExceptionWithStatus('Не найден номер телефона');
        }
        $this->phoneId = $id;
    }


    /**
     * @return array
     * @throws Exception
     */
    public function saveContact()
    {
        $transaction = new DbTransaction();
        try {
            // найду информацию об участке
            self::fillCottageId($this->cottageId);
            // сохраню контакт
            $contact = new ContactsHandler();
            $contact->cottage_id = $this->cottageId;
            $contact->contact_name = $this->contactName;
            $contact->is_owner = $this->isOwner;
            $contact->contact_description = $this->description;
            $contact->contact_address = trim($this->contactAddressIndex) . "&" . trim($this->contactAddressTown) . "&" . trim($this->contactAddressStreet) . "&" . trim($this->contactAddressBuild) . "&" . trim($this->contactAddressFlat);
            $contact->save();
            if ($contact->is_owner && $this->cottageInfo->is_additional) {
                $this->cottageInfo->is_different_owner = 1;
                $this->cottageInfo->save();
            }
            $transaction->commitTransaction();
            // добавлю контакт в список контактов
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "участку {$this->cottageInfo->cottage_number} добавлен контакт {$contact->contact_name}");
            return ['action' => "<script>" . $this->getContactsTable($contact->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Добавлен новый контакт');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function saveEmail()
    {
        $transaction = new DbTransaction();
        try {
            // проверю, не зарегистрирован ли уже данный адрес
            if (EmailsHandler::find()->where(['contact_id' => $this->contactNumber, 'email_address' => $this->emailAddress])->count()) {
                return ['info' => 'Данный адрес уже зарегистрирован для этого контакта'];
            }
            // если адрес объявлен основным- деактивирую предыдущий
            if ($this->makeMain) {
                $prevMain = EmailsHandler::find()->where(['contact_id' => $this->contactNumber, 'is_main' => 1])->one();
                if (!empty($prevMain)) {
                    $prevMain->is_main = 0;
                    $prevMain->save();
                }
            }
            // регистрирую адрес
            $newEmail = new EmailsHandler();
            $newEmail->email_address = $this->emailAddress;
            $newEmail->email_description = $this->description;
            $newEmail->is_main = $this->makeMain;
            $newEmail->contact_id = $this->contactNumber;
            $newEmail->save();
            $transaction->commitTransaction();
            // логгирую
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Добавлен почтовый адрес {$newEmail->email_address} контакту {$newEmail->contact_id}");
            return ['action' => "<script>" . $this->getContactsTable(ContactsHandler::findOne($newEmail->contact_id)->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Добавлен новый адрес почты');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function deleteEmail()
    {
        // найду адрес почты
        $transaction = new DbTransaction();
        try {
            $mail = EmailsHandler::findOne($this->emailId);
            if (empty($mail)) {
                throw new ExceptionWithStatus('Почта не найдена');
            }
            $mail->delete();
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Удалён почтовый адрес {$mail->email_address} контакта {$mail->contact_id}");
            return ['action' => "<script>" . $this->getContactsTable(ContactsHandler::findOne($mail->contact_id)->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Адрес почты удалён');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function deletePhone()
    {
        // найду номер
        $transaction = new DbTransaction();
        try {
            $phone = PhonesHandler::findOne($this->phoneId);
            if (empty($phone)) {
                throw new ExceptionWithStatus('Номер не найден');
            }
            $phone->delete();
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Удалён номер телефона {$phone->phone_number} контакта {$phone->contact_id}");
            return ['action' => "<script>" . $this->getContactsTable(ContactsHandler::findOne($phone->contact_id)->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Номер телефона удалён');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function deleteContact()
    {
        // найду контакт
        $transaction = new DbTransaction();
        try {
            $contact = ContactsHandler::findOne($this->contactNumber);
            if (empty($contact)) {
                throw new ExceptionWithStatus('Контакт не найден');
            }
            // если это обычный контакт- удаляю его целиком, с почтами и телефонами. Если владелец- проверю, что после удаления останется как минимум ещё один и переведу его в неактивные
            if ($contact->is_owner) {
                if (!ContactsHandler::checkOwners($contact->cottage_id)) {
                    // проверю, если контакт владелец- должен остаться хотя бы минимум ещё один после удаления
                    return ['info' => 'Невозможно удалить единственного владельца. Сначала добавьте нового.'];
                }
                $contact->is_active = 0;
                $contact->save();
            } else {
                $contact->delete();
            }
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Удалён контакт {$contact->contact_name} участка {$contact->cottage_id}");
            return ['action' => "<script>" . $this->getContactsTable($contact->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Контакт удалён');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillEmailInfo($id)
    {
        $info = EmailsHandler::findOne($id);
        if (empty($info)) {
            throw new ExceptionWithStatus('Почта не найдена');
        }
        $this->emailId = $info->id;
        $this->emailAddress = $info->email_address;
        $this->description = $info->email_description;
        $this->makeMain = $info->is_main;
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillPhoneInfo($id)
    {
        $info = PhonesHandler::findOne($id);
        if (empty($info)) {
            throw new ExceptionWithStatus('Почта не найдена');
        }
        $this->phoneId = $info->id;
        $this->phoneNumber = $info->phone_number;
        $this->description = $info->phone_description;
        $this->makeMain = $info->is_main;
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function changeEmail()
    {
        // найду адрес
        // найду адрес почты
        $transaction = new DbTransaction();
        try {
            $mail = EmailsHandler::findOne($this->emailId);
            if (empty($mail)) {
                throw new ExceptionWithStatus('Почта не найдена');
            }
            $oldAddress = $mail->email_address;
            // если новый адрес уже используется у этого контакта
            if (EmailsHandler::find()->where(['email_address' => $this->emailAddress, 'contact_id' => $mail->contact_id])->andWhere(['<>', 'id', $this->emailId])->count()) {
                return ['info' => 'Этот адрес уже зарегистрирован для данного контакта'];
            }
            // если адрес объявлен основным- деактивирую предыдущий
            if ($this->makeMain) {
                $prevMain = EmailsHandler::find()->where(['contact_id' => $this->contactNumber, 'is_main' => 1])->one();
                if (!empty($prevMain)) {
                    $prevMain->is_main = 0;
                    $prevMain->save();
                }
            }
            $mail->email_address = $this->emailAddress;
            $mail->email_description = $this->description;
            $mail->is_main = $this->makeMain;
            $mail->save();
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Изменён адрес почты контакта {$mail->contact_id} с $oldAddress на {$mail->email_address}");
            return ['action' => "<script" . $this->getContactsTable(ContactsHandler::findOne($mail->contact_id)->cottage_id) . "modal.modal('hide');makeInformer('success', 'Успех', 'Адрес почты изменён');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function changePhone()
    {
        // найду номер
        $transaction = new DbTransaction();
        try {
            $phone = PhonesHandler::findOne($this->phoneId);
            if (empty($phone)) {
                throw new ExceptionWithStatus('Номер не найден');
            }
            $oldPhone = $phone->phone_number;
            // если новый номер уже используется у этого контакта
            if (PhonesHandler::find()->where(['phone_number' => $this->phoneNumber, 'contact_id' => $phone->contact_id])->andWhere(['<>', 'id', $this->phoneId])->count()) {
                return ['info' => 'Этот номер уже зарегистрирован для данного контакта'];
            }
            // если адрес объявлен основным- деактивирую предыдущий
            if ($this->makeMain) {
                $prevMain = PhonesHandler::find()->where(['contact_id' => $this->contactNumber, 'is_main' => 1])->one();
                if (!empty($prevMain)) {
                    $prevMain->is_main = 0;
                    $prevMain->save();
                }
            }
            $phone->phone_number = $this->phoneNumber;
            $phone->phone_description = $this->description;
            $phone->is_main = $this->makeMain;
            $phone->save();
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Изменён номер телефона контакта {$phone->contact_id} с $oldPhone на {$phone->phone_number}");

            return ['action' => "<script>" . $this->getContactsTable(ContactsHandler::findOne($phone->contact_id)->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Номер телефона изменён');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function savePhone()
    {
        $transaction = new DbTransaction();
        try {
            // проверю, не зарегистрирован ли уже данный адрес
            if (PhonesHandler::find()->where(['contact_id' => $this->contactNumber, 'phone_number' => $this->phoneNumber])->count()) {
                return ['info' => 'Данный адрес уже зарегистрирован для этого контакта'];
            }
            // если адрес объявлен основным- деактивирую предыдущий
            if ($this->makeMain) {
                $prevMain = PhonesHandler::find()->where(['contact_id' => $this->contactNumber, 'is_main' => 1])->one();
                if (!empty($prevMain)) {
                    $prevMain->is_main = 0;
                    $prevMain->save();
                }
            }
            // регистрирую адрес
            $newPhone = new PhonesHandler();
            $newPhone->phone_number = $this->phoneNumber;
            $newPhone->phone_description = $this->description;
            $newPhone->is_main = $this->makeMain;
            $newPhone->contact_id = $this->contactNumber;
            $newPhone->save();
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "Добавлен номер телефона контакта {$newPhone->contact_id} {$newPhone->phone_number}");
            return ['action' => "<script>" . $this->getContactsTable(ContactsHandler::findOne($newPhone->contact_id)->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Добавлен новый номер телефона');</script>"];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillContactInfo($id)
    {
        $contact = ContactsHandler::findOne($id);
        if (empty($contact)) {
            throw new ExceptionWithStatus('Контакт не найден');
        }
        $this->contactNumber = $contact->id;
        $this->isOwner = $contact->is_owner;
        $this->contactName = $contact->contact_name;
        $this->description = $contact->contact_description;
        // попробую разобрать данные адреса вдадельца
        $addressArray = explode('&', $contact->contact_address);
        if (count($addressArray) === 5) {
            $this->contactAddressIndex = trim($addressArray[0]);
            $this->contactAddressTown = trim($addressArray[1]);
            $this->contactAddressStreet = trim($addressArray[2]);
            $this->contactAddressBuild = trim($addressArray[3]);
            $this->contactAddressFlat = trim($addressArray[4]);
        } else {
            $this->contactAddressTown = GrammarHandler::clearAddress($contact->contact_address);
        }
    }

    /**
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function changeContact()
    {
        $transaction = new DbTransaction();
        try {
        // сохраню контакт
        $contact = ContactsHandler::findOne($this->contactNumber);
        if (empty($contact)) {
            throw new ExceptionWithStatus('Контакт не найден');
        }
        // если есть попытка переделать владельца в обычный контакт- проверю, что останется ещё как минимум один
        if($contact->is_owner && !$this->isOwner && !ContactsHandler::checkOwners($this->contactNumber)){
            throw new ExceptionWithStatus('Чтобы сделать владельца контактным лицом, должен быть как минимум ещё один владелец');
        }

            $contact->contact_name = $this->contactName;
            $contact->is_owner = $this->isOwner;
            $contact->contact_description = $this->description;
            $contact->contact_address = trim($this->contactAddressIndex) . "&" . trim($this->contactAddressTown) . "&" . trim($this->contactAddressStreet) . "&" . trim($this->contactAddressBuild) . "&" . trim($this->contactAddressFlat);
            $contact->contact_description = $this->description;
            $contact->save();
            $transaction->commitTransaction();
            LogHandler::writeLog(LogHandler::CHANGE_INFO_LOG, "изменён контакт {$contact->contact_name}");
            return ['action' => "<script>" . $this->getContactsTable($contact->cottage_id) . ";modal.modal('hide');makeInformer('success', 'Успех', 'Контакт изменён');</script>"];
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $cottageId
     * @return string
     * @throws ExceptionWithStatus
     */
    private function getContactsTable($cottageId){
        $table = ContactsHandler::getContactsTable($cottageId);
        $table = preg_replace( "/\r|\n/", "", $table );
        $table = str_replace('"', "'", $table);
        return "$('div#contacts').html(\"{$table}\");$('.control-container').removeClass('hidden');$('.editable').addClass('hidden');handleEditing();$('.has-tooltip').tooltip()";

    }
}

