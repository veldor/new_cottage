<?php


namespace app\models;


use app\models\database\BankTransactionsHandler;
use app\models\selection_classes\RegistryInfo;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\GrammarHandler;
use Exception;
use yii\base\Model;

class Registry extends Model
{

    public $file;

    const SCENARIO_PARSE = 'parse';
    /**
     * @var Table_bank_invoices[]
     */
    public $unhandled;

    public function scenarios(): array
    {
        return [
            self::SCENARIO_PARSE => ['file'],
        ];
    }

    public function rules(): array
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'txt', 'maxFiles' => 1000],
            [['file'], 'required', 'on' => self::SCENARIO_PARSE],
        ];
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function handleRegistry()
    {
        if ($this->validate()) {
            $billsList = null;
            $date = null;
            $newBillsCount = 0;
            $oldBillsCount = 0;

            foreach ($this->file as $item) {
                $file = $item->tempName;
                $handle = fopen($file, 'r');
                while (($buffer = fgets($handle, 4096)) !== false) {
                    $billInfo = $this->handleBill($buffer);
                    if(!empty($billInfo)){
                        $billsList[] = $billInfo;
                    }
                    else{
                        // проверю на строчку с датой рееста
                        $registerDate = $this->getRegisterDate($buffer);
                        if(!empty($registerDate)){
                            $date = $registerDate;
                        }
                    }
                }
                if (!feof($handle)) {
                    throw new ExceptionWithStatus('Ошибка чтения файла',);
                }
                fclose($handle);
            }

            $newBills = null;

            if(!empty($billsList)){
                $transaction = new DbTransaction();
                try{
                    /** @var RegistryInfo $bill */
                    foreach ($billsList as $bill) {
                        // если в базе данных платежей от сбербанка ещё нет данного- внесу.
                        if(!BankTransactionsHandler::findOne(['bank_operation_id' => $bill->sberBillId])){
                            $invoice = new BankTransactionsHandler();
                            $invoice->bank_operation_id = $bill->sberBillId;
                            $invoice->pay_date = $date;
                            $invoice->pay_time = $bill->payTime;
                            $invoice->filial_number = $bill->departmentNumber;
                            $invoice->handler_number = $bill->handlerNumber;
                            $invoice->account_number = str_replace('№', '', $bill->personalAcc);
                            $invoice->fio = $bill->fio;
                            $invoice->address = $bill->address;
                            $invoice->payment_period = $bill->period;
                            $invoice->payment_summ = $bill->operationSumm;
                            $invoice->transaction_summ = $bill->transactionSumm;
                            $invoice->commission_summ = $bill->commissionSumm;
                            $invoice->real_pay_date = $bill->payDate;
                            $invoice->save();
                            $newBills[] = $bill;
                            $newBillsCount ++;
                        }
                        else{
                            $oldBillsCount++;
                        }
                    }
                    $transaction->commitTransaction();
                }
                catch (Exception $e){
                    $transaction->rollbackTransaction();
                    echo $e->getMessage();
                    die;
                }
                return ['billsList' => $newBills, 'newBillsCount' => $newBillsCount, 'oldBillsCount' => $oldBillsCount];
            }
            throw new ExceptionWithStatus('Не удалось прочитать содержимое файла', 3);
        }
        throw new ExceptionWithStatus('Не могу обработать файл, проверьте, что он правильный', 2);
    }

    private function handleBill($string)
    {
        if (!empty($string)) {
            $details = explode(';', $string);
            if (count($details) === 12) {
                // заполню объект данными о платеже

                $registryInfo = new RegistryInfo();

                $registryInfo->payDate = $details[0];
                $registryInfo->payTime = $details[1];
                $registryInfo->departmentNumber = $details[2];
                $registryInfo->handlerNumber = $details[3];
                $registryInfo->sberBillId = $details[4];
                $registryInfo->personalAcc = GrammarHandler::clearNumber($details[5]);
                $registryInfo->fio = iconv('CP1251', 'UTF-8', $details[6]);
                $registryInfo->address = iconv('CP1251', 'UTF-8', $details[7]);
                $registryInfo->operationSumm = CashHandler::fromRubles($details[8]);
                $registryInfo->transactionSumm = CashHandler::fromRubles($details[9]);
                $registryInfo->commissionSumm = CashHandler::fromRubles($details[10]);
                $registryInfo->period = iconv('CP1251', 'UTF-8', $details[11]);
                if($registryInfo->validate()){
                    return $registryInfo;
                }
                else{
                    throw new ExceptionWithStatus("Ошибка обработки платежа", 3);
                }

            }
        }
        return null;
    }
    private function getRegisterDate($string)
    {
        if (!empty($string)) {
            $details = explode(';', $string);
            if (count($details) === 6) {
                return $details[5];
            }
        }
        return null;
    }

    public function getUnhandled()
    {
        $this->unhandled = BankTransactionsHandler::find()->where(['bounded_transaction_id' => null])->orderBy('pay_date')->all();
    }

    public static function getBillId($string)
    {
        // номер должен быть последним в строке, разделённым пробелом
        $substrs = explode(' ', $string);
        $num = $substrs[count($substrs) - 1];
        if ((int)$num > 0) {
            return $num;
        } else {
            $firstChar = mb_substr($num, 0, 1);
            if ($firstChar === '№') {
                $num = mb_substr($num, 1);
                if ((int)$num > 0) {
                    return $num;
                }
            }
        }
        return null;
    }

}