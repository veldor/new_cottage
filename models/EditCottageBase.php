<?php


namespace app\models;


use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataTargetHandler;
use app\models\database\DepositHandler;
use app\models\database\TariffMembershipHandler;
use app\models\database\TariffTargetHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\Calculator;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\DifferentUtils;
use app\models\utils\LogHandler;
use app\models\utils\TimeHandler;
use Exception;
use yii\base\Model;

class EditCottageBase extends Model
{
// СЦЕНАРИИ
    const SCENARIO_SWITCH_REGISTER = 'switch_register';
    const SCENARIO_SWITCH_RIGHTS = 'switch_rights';
    const SCENARIO_CHANGE_RIGHTS = 'change_rights';
    const SCENARIO_CHANGE_DEPOSIT = 'change_deposit';
    const SCENARIO_CHANGE_SQUARE = 'change_square';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_SWITCH_REGISTER => ['cottageId'],
            self::SCENARIO_SWITCH_RIGHTS => ['cottageId'],
            self::SCENARIO_CHANGE_RIGHTS => ['cottageId', 'propertyData'],
            self::SCENARIO_CHANGE_DEPOSIT => ['cottageId', 'is_register_deposit', 'deposit', 'description'],
            self::SCENARIO_CHANGE_SQUARE => ['cottageId', 'square', 'changeDate'],
        ];
    }

    /**
     * @var CottagesHandler
     */
    public $cottageInfo;
    /**
     * @var string
     */
    public $propertyData;
    /**
     * @var int
     */
    public $cottageId;
    /**
     * @var string
     */
    public $deposit;
    /**
     * @var bool
     */
    public $is_register_deposit;
    /**
     * @var string
     */
    public $description;
    /**
     * @var int
     */
    public $square;
    /**
     * @var string
     */
    public $changeDate;

    public function attributeLabels(): array
    {
        return [
            'propertyData' => 'Данные права собственности',
            'cottageId' => 'Идентификатор участка',
            'deposit' => 'Значение депозита',
            'is_register_deposit' => 'Регистрация изменения депозита',
            'description' => 'Примечание',
        ];
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
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function switchRegister()
    {
        $transaction = new DbTransaction();
        try {
            $this->fillCottageId($this->cottageId);
            $this->cottageInfo->is_cottage_register_data = !$this->cottageInfo->is_cottage_register_data;
            $this->cottageInfo->save();
            LogHandler::writeLog(LogHandler::CHANGE_BASE_LOG, "Участку {$this->cottageInfo->cottage_number} изменена информация о наличии данных для реестра на {$this->cottageInfo->is_cottage_register_data}");
            if ($this->cottageInfo->is_cottage_register_data) {
                $jsAction = '$("#haveRegisterData").removeClass("text-danger").addClass("text-success").text("В наличии")';
            } else {
                $jsAction = '$("#haveRegisterData").addClass("text-danger").removeClass("text-success").text("Отсутствуют")';
            }
            $transaction->commitTransaction();
            return ['action' => "<script>{$jsAction};modal.modal('hide');makeInformer('success', 'Успех', 'Наличие данных для реестра изменено');</script>"];
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function switchRights()
    {
        $transaction = new DbTransaction();
        try {
            $this->fillCottageId($this->cottageId);
            $this->cottageInfo->is_have_property_rights = !$this->cottageInfo->is_have_property_rights;
            $this->cottageInfo->save();
            LogHandler::writeLog(LogHandler::CHANGE_BASE_LOG, "Участку {$this->cottageInfo->cottage_number} изменена информация о наличии данных права собственности на {$this->cottageInfo->is_have_property_rights}");
            if ($this->cottageInfo->is_have_property_rights) {
                $jsAction = '$("#haveRightsData").removeClass("text-danger").addClass("text-success").text("В наличии");$("#rightsDataContainer").removeClass("hidden")';
            } else {
                $jsAction = '$("#haveRightsData").addClass("text-danger").removeClass("text-success").text("Отсутствует");$("#rightsDataContainer").addClass("hidden")';
            }
            $transaction->commitTransaction();
            return ['action' => "<script>{$jsAction};modal.modal('hide');makeInformer('success', 'Успех', 'Наличие данных о праве собственности изменено');</script>"];
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillPropertyData($id)
    {
        $this->fillCottageId($id);
        $this->propertyData = $this->cottageInfo->property_data;
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function changeRights()
    {
        $transaction = new DbTransaction();
        try {
            $this->fillCottageId($this->cottageId);
            $this->cottageInfo->property_data = trim($this->propertyData);
            $this->cottageInfo->save();
            LogHandler::writeLog(LogHandler::CHANGE_BASE_LOG, "Участку {$this->cottageInfo->cottage_number} изменена информация о данных права собственности на {$this->cottageInfo->property_data}");
            $jsAction = '$("#rightsDataWrapper").text("' . $this->cottageInfo->property_data . '")';
            $transaction->commitTransaction();
            return ['action' => "<script>{$jsAction};modal.modal('hide');makeInformer('success', 'Успех', 'Наличие данных о праве собственности изменено');</script>"];
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public function fillDeposit($id)
    {
        $this->fillCottageId($id);
        $this->deposit = CashHandler::toMathRubles($this->cottageInfo->deposit);
    }

    /**
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function changeDeposit()
    {
        $transaction = new DbTransaction();
        try {
            $this->fillCottageId($this->cottageId);
            if($this->deposit < 0){
                return ['info' => 'Значение должно быть больше нуля'];
            }
            $oldDepositSumm = $this->cottageInfo->deposit;
            $this->cottageInfo->deposit = CashHandler::fromRubles($this->deposit);
            if($this->is_register_deposit){
                $depositIo = new DepositHandler();
                $difference = $oldDepositSumm - $this->cottageInfo->deposit;
                $depositIo->cottage_number = $this->cottageId;
                $depositIo->summ_before = $oldDepositSumm;
                $depositIo->summ_after = $this->cottageInfo->deposit;
                $depositIo->pay_date = time();
                if($difference > 0){
                    // из депозита вычиталось значение
                    $depositIo->destination = 'out';
                    $depositIo->summ = $difference;
                    $depositIo->save();
                }
                elseif($difference < 0){
                    $depositIo->destination = 'in';
                    $depositIo->summ = abs($difference);
                    $depositIo->save();
                }
            }
            $this->cottageInfo->save();
            LogHandler::writeLog(LogHandler::CHANGE_BASE_LOG, "Участку {$this->cottageInfo->cottage_number} изменена сумма депозита с {$oldDepositSumm} на {$this->cottageInfo->deposit} c примечанием '{$this->description}'");
            $transaction->commitTransaction();
            $jsAction = '$("#depositWrapper").html("' . CashHandler::toRubles( $this->cottageInfo->deposit) . '")';
            return ['action' => "<script>{$jsAction};modal.modal('hide');makeInformer('success', 'Успех', 'Сумма депозита изменена');</script>"];
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function fillSquare($id)
    {
        $this->fillCottageId($id);
        $this->square = $this->cottageInfo->square;
        $this->changeDate = TimeHandler::timestampToShortDate(time());
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function changeSquare()
    {
        $transaction = new DbTransaction();
        try {
            $changesList = "<table class='table table-striped table-condensed'>";
            $this->fillCottageId($this->cottageId);
            // получу дату изменения
            $date = $this->changeDate;
            if(!empty($date)){
                $timestamp = TimeHandler::dateToTimestamp($this->changeDate);
            }
            else{
                $timestamp = 0;
            }
            // найду все платежи, в расчёте которых учавствует площадь, начиная с этой даты
            // членские
            $membershipRates = DataMembershipHandler::find()->where(['cottage_number' => $this->cottageId])->andWhere(['>=' , 'search_timestamp', $timestamp])->all();
            // получу все тарифы в массиве
            $tariffs = TariffMembershipHandler::find()->orderBy('quarter')->all();
            /** @var TariffMembershipHandler[] $tariffs */
            $tariffs = DifferentUtils::sortArray($tariffs, 'quarter');
            foreach ($membershipRates as $membershipRate) {
                $cost = 0;
                $difference = 0;
                // если учавствует оплата по площади- посчитаю разницу в оплате
                if(!empty($membershipRate->individual_pay_for_field)){
                    $difference = $this->square - $membershipRate->square;
                    $cost = $membershipRate->individual_pay_for_field;
                }
                else{
                    if($tariffs[$membershipRate->quarter]->pay_for_meter > 0){
                        $difference = $this->square - $membershipRate->square;
                        $cost = $tariffs[$membershipRate->quarter]->pay_for_meter;
                    }
                }
                if($cost > 0){
                    $summ =  (int)($difference * ($cost / 100));
                    if($summ < 0){
                        // зачислю излишек на депозит
                        $newDeposit = new DepositHandler();
                        $newDeposit->cottage_number = $this->cottageId;
                        $newDeposit->summ = abs($summ);
                        $newDeposit->destination = 'in';
                        $newDeposit->summ_before = $this->cottageInfo->deposit;
                        $this->cottageInfo->deposit += $newDeposit->summ;
                        $newDeposit->summ_after = $this->cottageInfo->deposit;
                        $newDeposit->description = "Излишки по членским взносам за {$membershipRate->quarter} всвязи с изменением площади участка с {$membershipRate->square} на {$this->square}";
                        $newDeposit->pay_date = time();
                        $newDeposit->save();
                    }
                }
                $membershipRate->square = $this->square;
                // пересчитаю расценки
                if($membershipRate->is_individual_tariff){
                    $membershipRate->total_pay = Calculator::calculateWithSquare($membershipRate->square, $membershipRate->individual_pay_for_field, $membershipRate->individual_pay_for_cottage);
                }
                else{
                    $membershipRate->total_pay = Calculator::calculateWithSquare($membershipRate->square, $tariffs[$membershipRate->quarter]->pay_for_meter, $tariffs[$membershipRate->quarter]->pay_for_cottage);
                }
                if($membershipRate->payed_summ >= $membershipRate->total_pay){
                    $membershipRate->is_full_payed = 1;
                    $membershipRate->is_partial_payed = 0;
                }
                else{
                    $membershipRate->is_full_payed = 0;
                    if($membershipRate->payed_summ > 0){
                        $membershipRate->is_partial_payed = 0;
                    }
                }
                $membershipRate->save();
                if($cost != 0){
                    $changesList .= "<tr><td>Членские взносы</td><td>{$membershipRate->quarter}</td><td>" . CashHandler::toRubles($cost) . "</td></tr>";
                }
            }
            // целевые
            $targetRates = DataTargetHandler::find()->where(['cottage_number' => $this->cottageId])->andWhere(['>=' , 'year', TimeHandler::getYearFromTimestamp($timestamp)])->all();
            // получу все тарифы в массиве
            $tariffs = TariffTargetHandler::find()->orderBy('year')->all();
            /** @var TariffTargetHandler[] $tariffs */
            $tariffs = DifferentUtils::sortArray($tariffs, 'year');
            foreach ($targetRates as $targetRate) {
                $cost = 0;
                $difference = 0;
                // если учавствует оплата по площади- посчитаю разницу в оплате
                if(!empty($targetRate->individual_pay_for_field)){
                    $difference = $this->square - $targetRate->square;
                    $cost = $targetRate->individual_pay_for_field;
                }
                else{
                    if($tariffs[$targetRate->year]->pay_for_meter > 0){
                        $difference = $this->square - $targetRate->square;
                        $cost = $tariffs[$targetRate->year]->pay_for_meter;
                    }
                }
                if($cost > 0){
                    $summ =  (int)($difference * ($cost / 100));
                    if($summ < 0){
                        // зачислю излишек на депозит
                        $newDeposit = new DepositHandler();
                        $newDeposit->cottage_number = $this->cottageId;
                        $newDeposit->summ = abs($summ);
                        $newDeposit->destination = 'in';
                        $newDeposit->summ_before = $this->cottageInfo->deposit;
                        $this->cottageInfo->deposit += $newDeposit->summ;
                        $newDeposit->summ_after = $this->cottageInfo->deposit;
                        $newDeposit->description = "Излишки по целевым взносам за {$targetRate->year} всвязи с изменением площади участка с {$targetRate->square} на {$this->square}";
                        $newDeposit->pay_date = time();
                        $newDeposit->save();
                    }
                }
                $targetRate->square = $this->square;
                // пересчитаю расценки
                if($targetRate->is_individual_tariff){
                    $targetRate->total_pay = Calculator::calculateWithSquare($targetRate->square, $targetRate->individual_pay_for_field, $targetRate->individual_pay_for_cottage);
                }
                else{
                    $targetRate->total_pay = Calculator::calculateWithSquare($targetRate->square, $tariffs[$targetRate->year]->pay_for_meter, $tariffs[$targetRate->year]->pay_for_cottage);
                }
                if($targetRate->payed_summ >= $targetRate->total_pay){
                    $targetRate->is_full_payed = 1;
                    $targetRate->is_partial_payed = 0;
                }
                else{
                    $targetRate->is_full_payed = 0;
                    if($targetRate->payed_summ > 0){
                        $targetRate->is_partial_payed = 0;
                    }
                }
                if($cost != 0){
                    $changesList .= "<tr><td>Членские взносы</td><td>{$targetRate->year}</td><td>" . CashHandler::toRubles($cost) . "</td></tr>";
                }
                $targetRate->save();
            }
            $this->cottageInfo->square = $this->square;
            $this->cottageInfo->save();
            $changesList .= "</table>";
            $transaction->commitTransaction();
            return ['action' => "<script>modal.modal('hide');makeInformerModal('Площадь участка успешно изменена', \"<h2>Список изменений</h2>{$changesList}\");</script>"];
        } catch (ExceptionWithStatus $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }
}