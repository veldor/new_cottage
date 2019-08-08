<?php


namespace app\models;


use app\models\database\BillsHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\FinesHandler;
use app\models\database\RegistredCountersHandler;
use app\models\database\TransactionsHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\BillInfo;
use app\models\selection_classes\ContactInfo;
use app\models\selection_classes\FullCottageInfo;
use app\models\utils\Calculator;
use app\models\utils\TimeHandler;
use yii\base\Model;

class CottageInfo extends Model
{
    /**
     * @var FullCottageInfo
     */
    public $cottageInfo;
    /**
     * @var BillInfo[]
     */
    public $bills;
    /**
     * @var DataPowerHandler[]
     */
    public $powerData;
    /**
     * @var DataMembershipHandler[]
     */
    public $membershipData;
    /**
     * @var DataTargetHandler[]
     */
    public $targetData;
    /**
     * @var DataSingleHandler[]
     */
    public $singleData;
    /**
     * @var ContactInfo[]
     */
    public $contacts;
    /**
     * @var CottagesHandler
     */
    public $cottage;
    /**
     * @var string
     */
    public $mainCottageNumber;
    /**
     * @var FinesHandler[]
     */
    public $finesData;
    /**
     * @var RegistredCountersHandler[]
     */
    public $powerCounters;


    /**
     * CottageInfo constructor.
     * @param string $cottageNumber
     * @param array $config
     * @throws ExceptionWithStatus
     */

    public function __construct($cottageNumber, $config = [])
    {
        parent::__construct($config);
        // найду участок
        if(empty($cottageNumber)){
            throw new ExceptionWithStatus('Не найден номер участка', 2);
        }
        $cottageInfo = CottagesHandler::findByNumber($cottageNumber);
        if(empty($cottageInfo)){
            throw new ExceptionWithStatus('Участок с данным номером не найден', 3);
        }
        $this->cottage = $cottageInfo;
        $this->cottageInfo = self::fillCottageInfo($cottageInfo);
        // найду все счета по данному участку
        $bills = BillsHandler::find()->where(['cottage_number' => $cottageInfo->id])->orderBy('time_create DESC')->all();
        if(!empty($bills)){
            // добавлю информацию о каждом платеже
            foreach ($bills as $bill) {
                $billInfo = new BillInfo();
                $billInfo->bill = $bill;
                // найду транзакции по счёту
                $billInfo->transactions = TransactionsHandler::find()->where(['bill_id' => $bill->id])->orderBy('bankDate DESC')->all();
                $this->bills[] = $billInfo;
            }
        }
        // список данных электроэнергии
        $this->powerData = DataPowerHandler::find()->where(['cottage_number' => $cottageInfo->id])->orderBy('month DESC')->all();
        // список счётчиков электроэнергии
        $this->powerCounters = RegistredCountersHandler::find()->where(['cottage_id' =>$cottageInfo->id])->all();
        // список данных целевых платежей
        $this->membershipData = DataMembershipHandler::find()->where(['cottage_number' => $cottageInfo->id])->orderBy('quarter DESC')->all();
        // список данных целевых платежей
        $this->targetData = DataTargetHandler::find()->where(['cottage_number' => $cottageInfo->id])->orderBy('year DESC')->all();
        // список данных разовых платежей
        $this->singleData = DataSingleHandler::find()->where(['cottage_number' => $cottageInfo->id])->orderBy('filling_date DESC')->all();
        // список пени
        $this->finesData = FinesHandler::find()->where(['cottage_number' => $cottageInfo->id])->all();
    }

    /**
     * @param $cottage CottagesHandler
     * @return FullCottageInfo
     */
    public static function fillCottageInfo($cottage){
        // соберу всю необходимую информацию о участке
        $info = new FullCottageInfo();
        $info->cottageInfo = $cottage;
        // соберу все неоплаченные и частично оплаченные счета
        $info->powerDuties = DataPowerHandler::getDuties($cottage->id);
        $info->membershipDuties = DataMembershipHandler::getDuties($cottage->id);
        $info->targetDuties = DataTargetHandler::getDuties($cottage->id);
        $info->singleDuties = DataSingleHandler::getDuties($cottage->id);
        // общая задолженность по категориям
        $info->fullPowerDuty = Calculator::calculateDebt($info->powerDuties);
        $info->fullMembershipDuty = Calculator::calculateDebt($info->membershipDuties);
        $info->fullTargetDuty = Calculator::calculateDebt($info->targetDuties);
        $info->fullSingleDuty = Calculator::calculateDebt($info->singleDuties);
        // просроченность платежей
        $info->isPowerPayUp = TimeHandler::checkPayUp($info->powerDuties);
        $info->isMembershipPayUp = TimeHandler::checkPayUp($info->membershipDuties);
        $info->isTargetPayUp = TimeHandler::checkPayUp($info->targetDuties);
        $info->isSinglePayUp = TimeHandler::checkPayUp($info->singleDuties);
        return $info;
    }

    /**
     * @param $cottageNumber
     * @return int
     * @throws ExceptionWithStatus
     */
    public static function getFullCottageDebt($cottageNumber){
        // получу информацию об участке
        $info = self::fillCottageInfo(CottagesHandler::get($cottageNumber));
        return $info->fullTargetDuty + $info->fullSingleDuty + $info->fullMembershipDuty + $info->fullPowerDuty + FinesHandler::getFinesSumm($cottageNumber);
    }
}