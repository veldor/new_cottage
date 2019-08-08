<?php

namespace app\models\utils;

use app\models\database\BillsHandler;
use app\models\database\ContactsHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\EmailsHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\PhonesHandler;
use app\models\database\RegistredCountersHandler;
use app\models\database\TariffMembershipHandler;
use app\models\database\TariffPowerHandler;
use app\models\database\TariffTargetHandler;
use app\models\database\TransactionsHandler;
use app\models\exceptions\ExceptionWithStatus;
use DOMElement;
use yii\base\Model;

class Migration extends Model
{
    public static function migrateCottages()
    {
        // начну с миграции списка участков
        $cottageListText = file_get_contents('Z:/migration/cottages.xml');
        if (!empty($cottageListText)) {
            try {
                $dom = new DomHandler($cottageListText);
                $cottagesList = $dom->query('/cottages/cottage');
                /** @var DOMElement $cottage */
                foreach ($cottagesList as $cottage) {
                    $options = DomHandler::getElemAttributes($cottage);
                    $cottageItem = new CottagesHandler();
                    foreach ($options as $key => $value) {
                        $cottageItem->$key = $value;
                    }
                    $cottageItem->save();
                }
            } catch (ExceptionWithStatus $e) {
                echo $e->getMessage();
            }
        } else {
            echo "Не найден список участков";
        }
    }

    public static function migrateContacts()
    {
        $transaction = new DbTransaction();
        try {
            $contactsListText = file_get_contents('Z:/migration/contacts.xml');
            if (!empty($contactsListText)) {
                $dom = new DomHandler($contactsListText);
                $contactsList = $dom->query('/contacts/contact');
                /** @var DOMElement $cottage */
                foreach ($contactsList as $contact) {
                    $options = DomHandler::getElemAttributes($contact);
                    $contactItem = new ContactsHandler();
                    foreach ($options as $key => $value) {
                        if ($key === 'cottage_id') {
                            $cottageInfo = CottagesHandler::find()->where(['cottage_number' => $value])->one();
                            $contactItem->cottage_id = $cottageInfo->id;
                        } elseif ($key === 'contact_address') {
                            $contactItem->contact_address = urldecode($value);
                        } else {
                            $contactItem->$key = $value;
                        }
                    }
                    $contactItem->save();
                }
                $transaction->commitTransaction();
            } else {
                echo "Не найден список контактов";
            }
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migratePhones()
    {
        $transaction = new DbTransaction();
        try {
            $phonesListText = file_get_contents('Z:/migration/phones.xml');
            $phonesDom = new DomHandler($phonesListText);
            $phonesList = $phonesDom->query('/phones/phone');
            foreach ($phonesList as $phone) {
                // обработаю список телефонов
                $phoneItem = new PhonesHandler();
                // определю владельца телефона
                $options = DomHandler::getElemAttributes($phone);
                // получу номер участка
                $cottageId = CottagesHandler::find()->where(['cottage_number' => $options['cottage']])->one()->id;
                // получу имя контакта
                $contactId = ContactsHandler::find()->where(['cottage_id' => $cottageId, 'is_owner' => $options['is_main']])->one()->id;
                $phoneItem->contact_id = $contactId;
                $phoneItem->phone_number = $options['number'];
                $phoneItem->is_main = 1;
                $phoneItem->phone_description = '';
                $phoneItem->save();
            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migrateEmails()
    {
        $transaction = new DbTransaction();
        try {
            $emailsListText = file_get_contents('Z:/migration/emails.xml');
            $emailsDom = new DomHandler($emailsListText);
            $emailsList = $emailsDom->query('/emails/email');
            foreach ($emailsList as $email) {
                // обработаю список телефонов
                $emailItem = new EmailsHandler();
                // определю владельца телефона
                $options = DomHandler::getElemAttributes($email);
                // получу номер участка
                $cottageId = CottagesHandler::find()->where(['cottage_number' => $options['cottage']])->one()->id;
                // получу имя контакта
                $contactId = ContactsHandler::find()->where(['cottage_id' => $cottageId, 'is_owner' => $options['is_main']])->one()->id;
                $emailItem->contact_id = $contactId;
                $emailItem->email_address = $options['email'];
                $emailItem->is_main = 1;
                $emailItem->email_description = '';
                $emailItem->save();
            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migrateTariffs()
    {
        $transaction = new DbTransaction();
        try {
            $tariffsPowerText = file_get_contents('Z:/migration/tariff_power.xml');
            $tariffsTargetText = file_get_contents('Z:/migration/tariff_target.xml');
            $tariffsMembershipText = file_get_contents('Z:/migration/tariff_membership.xml');
            $powerDom = new DomHandler($tariffsPowerText);
            $targetDom = new DomHandler($tariffsTargetText);
            $membershipDom = new DomHandler($tariffsMembershipText);

            $powersList = $powerDom->query('/powers/power_item');
            foreach ($powersList as $item) {
                $options = DomHandler::getElemAttributes($item);
                $tariffItem = new TariffPowerHandler();
                foreach ($options as $key => $value) {
                    $tariffItem->$key = $value;
                }
                $tariffItem->save();
            }
            $membershipsList = $membershipDom->query('/memberships/membership');
            foreach ($membershipsList as $item) {
                $options = DomHandler::getElemAttributes($item);
                $tariffItem = new TariffMembershipHandler();
                foreach ($options as $key => $value) {
                    $tariffItem->$key = $value;
                }
                $tariffItem->save();
            }
            $targetsList = $targetDom->query('/targets/target');
            foreach ($targetsList as $item) {
                $options = DomHandler::getElemAttributes($item);
                $tariffItem = new TariffTargetHandler();
                foreach ($options as $key => $value) {
                    $tariffItem->$key = $value;
                }
                $tariffItem->search_timestamp = TimeHandler::getYearTimestamp($tariffItem->year);
                $tariffItem->save();
            }

            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migratePowerData()
    {
        $transaction = new DbTransaction();
        try {
            $text = file_get_contents('Z:/migration/data_power.xml');
            $dom = new DomHandler($text);
            $elements = $dom->query('/powers/power_data');
            echo count($elements);
            foreach ($elements as $element) {
                $options = DomHandler::getElemAttributes($element);
                // найду идентификатор участка
                $cottageId = CottagesHandler::find()->where(['cottage_number' => $options['cottage_number']])->one()->id;
                // найду зарегистрированный на участке счётчик
                $counter = RegistredCountersHandler::find()->where(['cottage_id' => $cottageId, 'is_active' => 1])->one();
                $counterInfo = null;
                if (empty($counter)) {
                    // зарегистрирую новый счётчик
                    $newCounter = new RegistredCountersHandler();
                    $newCounter->cottage_id = $cottageId;
                    $newCounter->is_active = 1;
                    $newCounter->last_data = $options['new_data'];
                    $newCounter->save();
                    $counterInfo = $newCounter;
                } else {
                    // зарегистрирован один счётчик
                    $counterInfo = $counter;
                }
                $data = new DataPowerHandler();
                foreach ($options as $key => $value) {
                    if ($key === 'new_data') {
                        // проверю, что новые данные больше или равны старым. Если они меньше- это другой счётчик, регистрирую его
                        if ($value >= $counterInfo->last_data) {
                            $counterInfo->last_data = $value;
                            $counterInfo->save();
                        } else {
                            $counterInfo->is_active = 0;
                            $counterInfo->save();
                            // зарегистрирую новый счётчик
                            $newCounter = new RegistredCountersHandler();
                            $newCounter->cottage_id = $cottageId;
                            $newCounter->is_active = 1;
                            $newCounter->last_data = $value;
                            $newCounter->save();
                            $counterInfo = $newCounter;
                        }
                        $data->counter_id = $counterInfo->id;
                        $data->new_data = $value;
                    } elseif ($key === 'cottage_number') {
                        $data->cottage_number = $cottageId;
                    } else {
                        $data->$key = $value;
                    }
                }
                $data->save();

            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }

    }

    public static function migrateMembershipData()
    {
        $transaction = new DbTransaction();
        try {
            $text = file_get_contents('Z:/migration/data_membership.xml');
            $dom = new DomHandler($text);
            $elements = $dom->query('/memberships/membership');
            foreach ($elements as $element) {
                $data = new DataMembershipHandler();
                $options = DomHandler::getElemAttributes($element);
                foreach ($options as $key => $value) {
                    if ($key === 'cottage_number') {
                        // найду идентификатор участка
                        $cottageId = CottagesHandler::find()->where(['cottage_number' => $value])->one()->id;
                        $data->cottage_number = $cottageId;
                    } elseif ($key === 'quarter') {
                        $data->quarter = $value;
                        $data->pay_up_date = TimeHandler::getPayUpQuarter($value);
                    } else {
                        $data->$key = $value;
                    }
                }
                $data->save();
            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migrateTargetData()
    {
        $transaction = new DbTransaction();
        try {
            $text = file_get_contents('Z:/migration/data_target.xml');
            $dom = new DomHandler($text);
            $elements = $dom->query('/targets/target');
            foreach ($elements as $element) {
                $data = new DataTargetHandler();
                $options = DomHandler::getElemAttributes($element);
                foreach ($options as $key => $value) {
                    if ($key === 'cottage_number') {
                        // найду идентификатор участка
                        $cottageId = CottagesHandler::find()->where(['cottage_number' => $value])->one()->id;
                        $data->cottage_number = $cottageId;
                    } elseif ($key === 'year') {
                        $data->year = $value;
                        $data->pay_up_date = TariffTargetHandler::find()->where(['year' => $value])->one()->pay_up_date;
                    } else {
                        $data->$key = $value;
                    }
                }
                $data->save();
            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migrateSingleData()
    {
        $transaction = new DbTransaction();
        try {
            $text = file_get_contents('Z:/migration/data_singles.xml');
            $dom = new DomHandler($text);
            $elements = $dom->query('/singles/single');
            foreach ($elements as $element) {
                $data = new DataSingleHandler();
                $options = DomHandler::getElemAttributes($element);
                foreach ($options as $key => $value) {
                    if ($key === 'cottage_number') {
                        // найду идентификатор участка
                        $cottageId = CottagesHandler::find()->where(['cottage_number' => $value])->one()->id;
                        $data->cottage_number = $cottageId;
                    } elseif ($key === 'pay_description') {
                        $data->pay_description = urldecode($value);
                    } else {
                        $data->$key = $value;
                    }
                }
                $data->save();
            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }

    public static function migratePayments()
    {
        $bills = file_get_contents('Z:/migration/bills.xml');
        $transactions = file_get_contents('Z:/migration/transactions.xml');
        $payedMembership = file_get_contents('Z:/migration/payed_membership.xml');
        $payedPower = file_get_contents('Z:/migration/payed_power.xml');
        $payedTarget = file_get_contents('Z:/migration/payed_target.xml');
        $payedSingle = file_get_contents('Z:/migration/payed_single.xml');
        $transaction = new DbTransaction();
        try {
            $billsDom = new DomHandler($bills);
            $transactionsDom = new DomHandler($transactions);
            $payedPowerDom = new DomHandler($payedPower);
            $payedMembershipDom = new DomHandler($payedMembership);
            $payedTargetsDom = new DomHandler($payedTarget);
            $payedSingleDom = new DomHandler($payedSingle);
            $existentBills = $billsDom->query('/bills/bill');

            foreach ($existentBills as $existentBill) {
                // получу старый идентификатор платжа
                $billItem = new BillsHandler();
                $options = DomHandler::getElemAttributes($existentBill);
                $oldCottageNumber = $options['cottage_number'];
                $billItem->cottage_number = CottagesHandler::getIdByNumber($oldCottageNumber)->id;
                $billItem->time_create = $options['creation_time'];
                $billItem->from_deposit = $options['from_deposit'];
                $billItem->discount = $options['discount'];
                $billItem->bill_summ = $options['bill_summ'];
                $billItem->payed = $options['payed'];
                $billItem->is_full_payed = $options['full_payed'];
                $billItem->is_partial_payed = $options['partial_payed'];
                // переформирую содержимое платежа
                $billContentDom = new DomHandler(urldecode($options['bill_content']));
                $billContent = '<payment>';
                // найду id дополнительного участка, если он есть
                $additionalCottage = CottagesHandler::getAdditionalCottage($billItem->cottage_number);
                // найду платежи за электричество, если они есть
                $powerPayments = $billContentDom->query('/payment/power/month');
                if ($powerPayments->count() > 0) {
                    $billContent .= '<power>';
                    foreach ($powerPayments as $powerPayment) {
                        /** @var DOMElement $powerPayment */
                        $date = $powerPayment->getAttribute('date');
                        $powerPaymentId = DataPowerHandler::find()->where(['cottage_number' => $billItem->cottage_number, 'month' => $date])->one()->id;
                        $billContent .= '<month id="' . $powerPaymentId . '"/>';
                    }
                    $billContent .= '</power>';
                }
                // найду платежи за членские взносы, если они есть
                $membershipPayments = $billContentDom->query('/payment/membership/quarter');
                if ($membershipPayments->count() > 0) {
                    $billContent .= '<membership>';
                    foreach ($membershipPayments as $membershipPayment) {
                        /** @var DOMElement $membershipPayment */
                        $date = $membershipPayment->getAttribute('date');
                        $membershipPayment = DataMembershipHandler::find()->where(['cottage_number' => $billItem->cottage_number, 'quarter' => $date])->one();
                        if (!empty($membershipPayment)) {
                            $billContent .= '<quarter id="' . $membershipPayment->id . '"/>';
                        }

                    }
                    $billContent .= '</membership>';
                }
                // найду платежи за целевые взносы, если они есть
                $targetPayments = $billContentDom->query('/payment/target/pay');
                if ($targetPayments->count() > 0) {
                    $billContent .= '<target>';
                    foreach ($targetPayments as $targetPayment) {
                        /** @var DOMElement $targetPayment */
                        $date = $targetPayment->getAttribute('year');
                        $targetPayment = DataTargetHandler::find()->where(['cottage_number' => $billItem->cottage_number, 'year' => $date])->one();
                        if (!empty($targetPayment)) {
                            $billContent .= '<year id="' . $targetPayment->id . '"/>';
                        }
                    }
                    $billContent .= '</target>';
                }
                // найду платежи за разовые взносы, если они есть
                $singlePayments = $billContentDom->query('/payment/single/pay');
                if ($singlePayments->count() > 0) {
                    $billContent .= '<single>';
                    foreach ($singlePayments as $singlePayment) {
                        /** @var DOMElement $singlePayment */
                        $date = $singlePayment->getAttribute('time');
                        $singlePayment = DataSingleHandler::find()->where(['cottage_number' => $billItem->cottage_number, 'filling_date' => $date])->one();
                        if (!empty($singlePayment)) {
                            $billContent .= '<pay id="' . $singlePayment->id . '"/>';
                        }
                    }
                    $billContent .= '</single>';
                }

                if (!empty($additionalCottage)) {
                    // если присутствует дополнительный участок- обработаю дополнительные платежи
                    // найду платежи за электричество, если они есть
                    $powerPayments = $billContentDom->query('/payment/additional_power/month');
                    if ($powerPayments->count() > 0) {
                        $billContent .= '<additional_power>';
                        foreach ($powerPayments as $powerPayment) {
                            /** @var DOMElement $powerPayment */
                            $date = $powerPayment->getAttribute('date');
                            $powerPaymentId = DataPowerHandler::find()->where(['cottage_number' => $additionalCottage->cottage_number, 'month' => $date])->one()->id;
                            $billContent .= '<month id="' . $powerPaymentId . '"/>';
                        }
                        $billContent .= '</additional_power>';
                    }
                    // найду платежи за членские взносы, если они есть
                    $membershipPayments = $billContentDom->query('/payment/additional_membership/quarter');
                    if ($membershipPayments->count() > 0) {
                        $billContent .= '<additional_membership>';
                        foreach ($membershipPayments as $membershipPayment) {
                            /** @var DOMElement $membershipPayment */
                            $date = $membershipPayment->getAttribute('date');
                            $membershipPayment = DataMembershipHandler::find()->where(['cottage_number' => $additionalCottage->cottage_number, 'quarter' => $date])->one();
                            if (!empty($membershipPayment)) {
                                $billContent .= '<quarter id="' . $membershipPayment->id . '"/>';
                            }

                        }
                        $billContent .= '</additional_membership>';
                    }
                    // найду платежи за целевые взносы, если они есть
                    $targetPayments = $billContentDom->query('/payment/additional_target/pay');
                    if ($targetPayments->count() > 0) {
                        $billContent .= '<additional_target>';
                        foreach ($targetPayments as $targetPayment) {
                            /** @var DOMElement $targetPayment */
                            $date = $targetPayment->getAttribute('year');
                            $targetPayment = DataTargetHandler::find()->where(['cottage_number' => $additionalCottage->cottage_number, 'year' => $date])->one();
                            if (!empty($targetPayment)) {
                                $billContent .= '<year id="' . $targetPayment->id . '"/>';
                            }
                        }
                        $billContent .= '</additional_target>';
                    }
                }

                $billContent .= '</payment>';
                $billItem->bill_content = $billContent;
                $billItem->save();
                $oldBillId = $options['bill_id'];
                // найду транзакции по данному платежу
                $chainedTransactions = $transactionsDom->query('/transactions/transaction[@bill_id="' . $oldBillId . '"][@cottage_number="' . $oldCottageNumber . '"]');
                foreach ($chainedTransactions as $chainedTransaction) {
                    $transactionOptions = DomHandler::getElemAttributes($chainedTransaction);
                    $transaction_item = new TransactionsHandler();
                    $transaction_item->cottage_id = $billItem->cottage_number;
                    $transaction_item->summ = $transactionOptions['summ'];
                    $transaction_item->date = $transactionOptions['date'];
                    $transaction_item->bill_id = $billItem->id;
                    $transaction_item->save();
                }
                if (!empty($transaction_item)) {
                    // теперь заполню сведения об оплаченных периодах
                    $payed_powers = $payedPowerDom->query('/powers/power_item[@bill_id="' . $oldBillId . '"][@cottage_number="' . $oldCottageNumber . '"]');
                    $payed_memberships = $payedMembershipDom->query('/memberships/membership[@bill_id="' . $oldBillId . '"][@cottage_number="' . $oldCottageNumber . '"]');
                    $payed_targets = $payedTargetsDom->query('/targets/target[@bill_id="' . $oldBillId . '"][@cottage_number="' . $oldCottageNumber . '"]');
                    $payed_singles = $payedSingleDom->query('/singles/single[@bill_id="' . $oldBillId . '"][@cottage_number="' . $oldCottageNumber . '"]');
                    if ($payed_powers->length > 0) {
                        foreach ($payed_powers as $payed_power) {
                            $options = DomHandler::getElemAttributes($payed_power);
                            $item = new PayedPowerHandler();
                            $item->bill_id = $billItem->id;
                            $item->transaction_id = $transaction_item->id;
                            $item->month = $options['month'];
                            $item->summ = $options['summ'];
                            $item->pay_date = $options['pay_date'];
                            $item->save();
                        }
                    }
                    if ($payed_memberships->length > 0) {
                        foreach ($payed_memberships as $payed_membership) {
                            $options = DomHandler::getElemAttributes($payed_membership);
                            $item = new PayedMembershipHandler();
                            $item->bill_id = $billItem->id;
                            $item->transaction_id = $transaction_item->id;
                            $item->quarter = $options['month'];
                            $item->summ = $options['summ'];
                            $item->pay_date = $options['pay_date'];
                            $item->save();
                        }
                    }
                    if ($payed_targets->length > 0) {
                        foreach ($payed_targets as $payed_target) {
                            $options = DomHandler::getElemAttributes($payed_target);
                            $item = new PayedTargetHandler();
                            $item->bill_id = $billItem->id;
                            $item->transaction_id = $transaction_item->id;
                            $item->year = $options['month'];
                            $item->summ = $options['summ'];
                            $item->pay_date = $options['pay_date'];
                            $item->save();
                        }
                    }
                    if ($payed_singles->length > 0) {
                        foreach ($payed_singles as $payed_single) {
                            $options = DomHandler::getElemAttributes($payed_single);
                            $item = new PayedSingleHandler();
                            $item->bill_id = $billItem->id;
                            $item->transaction_id = $transaction_item->id;
                            $item->pay_id = $options['month'];
                            $item->summ = $options['summ'];
                            $item->pay_date = $options['pay_date'];
                            $item->save();
                        }
                    }
                }
            }
            $transaction->commitTransaction();
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            echo "Ошибка: " . $e->getMessage();
        }
    }
}