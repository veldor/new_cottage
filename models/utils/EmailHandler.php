<?php


namespace app\models\utils;


use app\models\database\BillsHandler;
use app\models\database\ContactsHandler;
use app\models\database\CottagesHandler;
use app\models\database\EmailsHandler;
use app\models\database\SendMailsHandler;
use app\models\database\TransactionsHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\BillInfo;
use app\models\selection_classes\FileAttachment;
use app\priv\Info;
use Exception;
use Yii;
use yii\base\Model;

class EmailHandler extends Model
{

    /**
     * @param int $cottage_id
     * @param string $title
     * @param string $text
     * @param FileAttachment $attachment
     * @throws ExceptionWithStatus
     */
    public static function notify(int $cottage_id, string $title, string $text, $attachment = null)
    {
        // получу список владельцев
        $cottageInfo = CottagesHandler::get($cottage_id);
        if($cottageInfo->is_additional && !$cottageInfo->is_different_owner){
            $cottageInfo = CottagesHandler::get($cottageInfo->main_cottage_id);
        }
        // найду все контакты участка
        $contacts = ContactsHandler::getAllContacts($cottageInfo->id);
        if(!empty($contacts)){
            foreach ($contacts as $contact) {
                self::directNotify($contact, $title, $text, $attachment);
            }
        }
    }

    /**
     * @param ContactsHandler $contact
     * @param string $title
     * @param string $text
     * @param FileAttachment $attachment
     * @throws ExceptionWithStatus
     */
    public static function directNotify(ContactsHandler $contact, string $title, string $text, $attachment = null)
    {
        // вставлю текст в шаблон
        $body = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => $text]);
        // обработаю лексемы
        $body = GrammarHandler::handleLexemes($body, $contact);
        // найду почтовые адреса контакта
        $mails = EmailsHandler::get($contact->id);
        if(!empty($mails)){
            foreach ($mails as $mail) {
                self::send($mail->email_address, $contact->contact_name, $title, $body, $attachment);
            }
        }
    }

    /**
     * @param $address
     * @param $receiverName
     * @param $subject
     * @param $body
     * @param FileAttachment $attachment
     * @throws ExceptionWithStatus
     */
    public static function send($address, $receiverName, $subject, $body, $attachment = null)
    {
        $mail = Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTo(['eldorianwin@gmail.com' => $receiverName]);
        if (!empty($attachment)) {
            $mail->attach($attachment->url, ['fileName' => $attachment->name]);
        }
        try {
            $mail->send();
            $sent = true;
        } catch (Exception $e) {
            // отправка не удалась, переведу сообщение в неотправленные
            $sent = false;
            throw new ExceptionWithStatus($e->getMessage(), 3);
        }
        finally{
            $sentMail = new SendMailsHandler();
            $sentMail->body = $body;
            $sentMail->subject = $subject;
            $sentMail->address = $address;
            $sentMail->is_send = $sent;
            $sentMail->save();
            // добавлю сообщение в список отправленных
        }
    }

    /**
     * @param $billId
     * @throws ExceptionWithStatus
     */
    public static function sendBIllInfo($billId)
    {
        /** @var BillInfo $info */
        $info = BillsHandler::getBillInfo($billId);
        $text =  Yii::$app->controller->renderAjax('bill_information', ['bill_info' => $info]);
        $info = BillsHandler::getBankInfo($billId);
        $invoice =   Yii::$app->controller->renderPartial('/email/bank-invoice-pdf', ['info' => $info]);
        PDFHandler::renderPDF($invoice);
        $attachment = new FileAttachment();
        $attachment->name = "Квитанция на оплату.pdf";
        $attachment->url = str_replace('\\', '/', Yii::getAlias('@app')) . '/public_html/invoice.pdf';
        EmailHandler::notify(74, 'Квитанция на оплату', $text, $attachment);
        unlink($attachment->url);
        $info['billInfo']->bill->is_email_sended = 1;
        $info['billInfo']->bill->save();
    }

    /**
     * @param int $id
     * @throws ExceptionWithStatus
     */
    public static function sendTransactionInfo(int $id)
    {
        $info = TransactionsHandler::getTransactionInfo($id);
        $text =  Yii::$app->controller->renderAjax('transaction_information', ['transaction_info' => $info]);
        EmailHandler::notify($info->cottageInfo->id, 'Подтверждение оплаты', $text);
    }
}