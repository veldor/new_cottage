<?php


namespace app\models\database;

use app\models\CottageInfo;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\FineDetails;
use app\models\utils\CashHandler;
use app\models\utils\LogHandler;
use app\models\utils\TimeHandler;
use Exception;
use Yii;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Номер участка
 * @property string $pay_type [enum('membership', 'power', 'target')]  Тип взноса
 * @property int $period_id [int(10) unsigned]  Период оплаты
 * @property int $payUpLimit [bigint(20) unsigned]  Крайняя дата оплаты
 * @property int $summ [int(10) unsigned]  Начисленная сумма
 * @property int $payed_summ [int(10) unsigned]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1)]  Чатично оплачено
 * @property bool $is_full_payed [tinyint(1)]  Полностью оплачено
 * @property bool $is_enabled [tinyint(1)]  Активность пени
 * @property bool $is_locked [tinyint(1) unsigned]  Сумма зафиксирована
 */

class FinesHandler extends ActiveRecord
{
    private const PERCENT = 0.5;
    private const START_POINT = 1561939201;

    public static $types = ['power' => 'Электроэнергия', 'membership' => 'Членские взносы', 'target' => 'Целевые взносы'];

    public static function getPeriod(string $pay_type, int $period_id)
    {
        switch ($pay_type){
            case 'membership':
                return TimeHandler::getFullFromShotQuarter(DataMembershipHandler::findOne($period_id)->quarter);
            case 'target':
                return DataTargetHandler::findOne($period_id)->year . ' год';
            case 'power':
                return TimeHandler::getFullFromShotMonth(DataPowerHandler::findOne($period_id)->month);
        }
    }

    public function scenarios()
    {
        return [self::SCENARIO_DEFAULT => ['id', 'summ']];
    }

    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "fines";
    }

    /**
     * @param string $cottageNumber
     * @throws Exception
     */
    public static function check(string $cottageNumber)
    {
        // получу неплаченные задолженности по электроэнергии, где сумма платежа больше 0
        $powers = DataPowerHandler::find()->where(['cottage_number' => $cottageNumber, 'is_full_payed' => 0])->andWhere(['>', 'total_pay', 0])->all();
        if(!empty($powers)){
            foreach ($powers as $power) {
                // получу дату оплаты долга
                if ($power->pay_up_date < self::START_POINT) {
                    $payUp = self::START_POINT;
                }
                else{
                    $payUp = $power->pay_up_date;
                }
                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня

                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                $dayDifference = TimeHandler::checkDayDifference($payUp);
                if($dayDifference > 0){
                    // если есть частичные оплаты по данному периоду- буду расчитывать сумму с учётом уменьшения общей стоимости
                    if($power->is_partial_payed){
                        // найду платежи по счёту
                        $pays = PayedPowerHandler::find()->where(['period_id' => $power->id])->orderBy('pay_date')->all();
                        if(!empty($pays)){
                            // пересчитаю данные- в период оплаты начинаю считать по сниженной стоимости
                            $previousDate = $payUp;
                            $summ = $power->total_pay;
                            $finesSumm = 0;
                            foreach ($pays as $pay) {
                                // оплата может быть до дня расчёта пени, проверю
                                $dayDifference = TimeHandler::checkDayDifference($previousDate, $pay->pay_date);
                                if($dayDifference > 0){
                                    // посчитаю сумму пени
                                    // получу стоимость одного дня просрочки
                                    $fines = CashHandler::countPercent($summ, self::PERCENT);
                                    $finesSumm += $dayDifference * $fines;
                                }
                                $previousDate = $pay->pay_date;
                                $summ -= $pay->summ;
                            }
                            // теперь найду разницу между последней оплатой и настоящим временем
                            $dayDifference = TimeHandler::checkDayDifference($previousDate);
                            if($dayDifference > 0){
                                $fines = CashHandler::countPercent($summ, self::PERCENT);
                                $finesSumm += $dayDifference * $fines;
                            }
                            // сохраню данные
                            if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $power->id])){
                                $existentFine = new FinesHandler();
                                $existentFine->cottage_number = $cottageNumber;
                                $existentFine->pay_type = 'power';
                                $existentFine->period_id = $power->id;
                                $existentFine->payUpLimit = $payUp;
                                $existentFine->payed_summ = 0;
                                $existentFine->is_full_payed = 0;
                                $existentFine->is_partial_payed = 0;
                                $existentFine->is_enabled = 1;
                            }
                            if(!$existentFine->is_locked){
                                $existentFine->summ = $finesSumm;
                                $existentFine->save();
                            }
                        }
                    }
                    else{
                        // если уже есть данные- обновлю, если нет- создам заново
                        // получу стоимость одного дня просрочки
                        $fines = CashHandler::countPercent($power->total_pay, self::PERCENT);
                        $totalFine = $dayDifference * $fines;
                        // сохраню данные
                        if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $power->id])){
                            $existentFine = new FinesHandler();
                            $existentFine->cottage_number = $cottageNumber;
                            $existentFine->pay_type = 'power';
                            $existentFine->period_id = $power->id;
                            $existentFine->payUpLimit = $payUp;
                            $existentFine->payed_summ = 0;
                            $existentFine->is_full_payed = 0;
                            $existentFine->is_partial_payed = 0;
                            $existentFine->is_enabled = 1;
                        }
                        if(!$existentFine->is_locked){
                            $existentFine->summ = $totalFine;
                            $existentFine->save();
                        }
                    }
                }
            }
        }
        // получу неплаченные задолженности по членским взносам
        $memberships = DataMembershipHandler::find()->where(['cottage_number' => $cottageNumber, 'is_full_payed' => 0])->andWhere(['>', 'total_pay', 0])->all();
        if(!empty($memberships)){
            foreach ($memberships as $membership) {
                // получу дату оплаты долга
                if ($membership->pay_up_date < self::START_POINT) {
                    $payUp = self::START_POINT;
                }
                else{
                    $payUp = $membership->pay_up_date;
                }
                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                $dayDifference = TimeHandler::checkDayDifference($payUp);
                if($dayDifference > 0){
                    // если есть частичные оплаты по данному периоду- буду расчитывать сумму с учётом уменьшения общей стоимости
                    if($membership->is_partial_payed){
                        // найду платежи по счёту
                        $pays = PayedMembershipHandler::find()->where(['period_id' => $membership->id])->orderBy('pay_date')->all();
                        if(!empty($pays)){
                            // пересчитаю данные- в период оплаты начинаю считать по сниженной стоимости
                            $previousDate = $payUp;
                            $summ = $membership->total_pay;
                            $finesSumm = 0;
                            foreach ($pays as $pay) {
                                // оплата может быть до дня расчёта пени, проверю
                                $dayDifference = TimeHandler::checkDayDifference($previousDate, $pay->pay_date);
                                if($dayDifference > 0){
                                    // посчитаю сумму пени
                                    // получу стоимость одного дня просрочки
                                    $fines = CashHandler::countPercent($summ, self::PERCENT);
                                    $finesSumm += $dayDifference * $fines;
                                }
                                $previousDate = $pay->pay_date;
                                $summ -= $pay->summ;
                            }
                            // теперь найду разницу между последней оплатой и настоящим временем
                            $dayDifference = TimeHandler::checkDayDifference($previousDate);
                            if($dayDifference > 0){
                                $fines = CashHandler::countPercent($summ, self::PERCENT);
                                $finesSumm += $dayDifference * $fines;
                            }
                            // сохраню данные
                            if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $membership->id])){
                                $existentFine = new FinesHandler();
                                $existentFine->cottage_number = $cottageNumber;
                                $existentFine->pay_type = 'membership';
                                $existentFine->period_id = $membership->id;
                                $existentFine->payUpLimit = $payUp;
                                $existentFine->payed_summ = 0;
                                $existentFine->is_full_payed = 0;
                                $existentFine->is_partial_payed = 0;
                                $existentFine->is_enabled = 1;
                            }
                            if(!$existentFine->is_locked){
                                $existentFine->summ = $finesSumm;
                                $existentFine->save();
                            }
                        }
                    }
                    else{
                        // если уже есть данные- обновлю, если нет- создам заново
                        // получу стоимость одного дня просрочки
                        $fines = CashHandler::countPercent($membership->total_pay, self::PERCENT);
                        $totalFine = $dayDifference * $fines;
                        // сохраню данные
                        if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $membership->id])){
                            $existentFine = new FinesHandler();
                            $existentFine->cottage_number = $cottageNumber;
                            $existentFine->pay_type = 'membership';
                            $existentFine->period_id = $membership->id;
                            $existentFine->payUpLimit = $payUp;
                            $existentFine->payed_summ = 0;
                            $existentFine->is_full_payed = 0;
                            $existentFine->is_partial_payed = 0;
                            $existentFine->is_enabled = 1;
                        }
                        if(!$existentFine->is_locked){
                            $existentFine->summ = $totalFine;
                            $existentFine->save();
                        }
                    }
                }
            }
        }
        // получу неплаченные задолженности по целевым взносам
        $targets = DataTargetHandler::find()->where(['cottage_number' => $cottageNumber, 'is_full_payed' => 0])->andWhere(['>', 'total_pay', 0])->all();
        if(!empty($targets)){
            foreach ($targets as $target) {
                // получу дату оплаты долга
                if ($target->pay_up_date < self::START_POINT) {
                    $payUp = self::START_POINT;
                }
                else{
                    $payUp = $target->pay_up_date;
                }

                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                $dayDifference = TimeHandler::checkDayDifference($payUp);
                if($dayDifference > 0){
                    // если есть частичные оплаты по данному периоду- буду расчитывать сумму с учётом уменьшения общей стоимости
                    if($target->is_partial_payed){
                        // найду платежи по счёту
                        $pays = PayedTargetHandler::find()->where(['period_id' => $target->id])->orderBy('pay_date')->all();
                        if(!empty($pays)){
                            // пересчитаю данные- в период оплаты начинаю считать по сниженной стоимости
                            $previousDate = $payUp;
                            $summ = $target->total_pay;
                            $finesSumm = 0;
                            foreach ($pays as $pay) {
                                // оплата может быть до дня расчёта пени, проверю
                                $dayDifference = TimeHandler::checkDayDifference($previousDate, $pay->pay_date);
                                if($dayDifference > 0){
                                    // посчитаю сумму пени
                                    // получу стоимость одного дня просрочки
                                    $fines = CashHandler::countPercent($summ, self::PERCENT);
                                    $finesSumm += $dayDifference * $fines;
                                }
                                $previousDate = $pay->pay_date;
                                $summ -= $pay->summ;
                            }
                            // теперь найду разницу между последней оплатой и настоящим временем
                            $dayDifference = TimeHandler::checkDayDifference($previousDate);
                            if($dayDifference > 0){
                                $fines = CashHandler::countPercent($summ, self::PERCENT);
                                $finesSumm += $dayDifference * $fines;
                            }
                            // сохраню данные
                            if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $target->id])){
                                $existentFine = new FinesHandler();
                                $existentFine->cottage_number = $cottageNumber;
                                $existentFine->pay_type = 'target';
                                $existentFine->period_id = $target->id;
                                $existentFine->payUpLimit = $payUp;
                                $existentFine->payed_summ = 0;
                                $existentFine->is_full_payed = 0;
                                $existentFine->is_partial_payed = 0;
                                $existentFine->is_enabled = 1;
                            }
                            if(!$existentFine->is_locked){
                                $existentFine->summ = $finesSumm;
                                $existentFine->save();
                            }
                        }
                        else{
                            // платёж частично оплачен но не в программе
                            $summ = $target->total_pay - $target->payed_summ;
                            // теперь найду разницу между последней оплатой и настоящим временем
                            $dayDifference = TimeHandler::checkDayDifference($payUp);
                            $fines = CashHandler::countPercent($summ, self::PERCENT);
                            $finesSumm = $dayDifference * $fines;
                            // сохраню данные
                            if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $target->id])){
                                $existentFine = new FinesHandler();
                                $existentFine->cottage_number = $cottageNumber;
                                $existentFine->pay_type = 'target';
                                $existentFine->period_id = $target->id;
                                $existentFine->payUpLimit = $payUp;
                                $existentFine->payed_summ = 0;
                                $existentFine->is_full_payed = 0;
                                $existentFine->is_partial_payed = 0;
                                $existentFine->is_enabled = 1;
                            }
                            if(!$existentFine->is_locked){
                                $existentFine->summ = $finesSumm;
                                $existentFine->save();
                            }
                        }
                    }
                    else{
                        // если уже есть данные- обновлю, если нет- создам заново
                        // получу стоимость одного дня просрочки
                        $fines = CashHandler::countPercent($target->total_pay, self::PERCENT);
                        $totalFine = $dayDifference * $fines;
                        // сохраню данные
                        if(!$existentFine = FinesHandler::findOne(['cottage_number' => $cottageNumber, 'period_id' => $target->id])){
                            $existentFine = new FinesHandler();
                            $existentFine->cottage_number = $cottageNumber;
                            $existentFine->pay_type = 'target';
                            $existentFine->period_id = $target->id;
                            $existentFine->payUpLimit = $payUp;
                            $existentFine->payed_summ = 0;
                            $existentFine->is_full_payed = 0;
                            $existentFine->is_partial_payed = 0;
                            $existentFine->is_enabled = 1;
                        }
                        if(!$existentFine->is_locked){
                            $existentFine->summ = $totalFine;
                            $existentFine->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $finesId
     * @return array
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public static function disableFine($finesId)
    {
        $fine = self::findOne($finesId);
        if (empty($fine)) {
            return ['status' => 2, 'message' => 'Пени за данный период не найдены'];
        }
        $fine->is_enabled = 0;
        $fine->save();
        $summ = self::getFinesSumm($fine->cottage_number);
        $js = "<script>$('#fines_{$fine->id}_enable').removeClass('hidden');$('#fines_{$fine->id}_disable').addClass('hidden');$('#finesSumm').html('" . CashHandler::toRubles($summ) . "');$('#finesLink').html('" . CashHandler::toRubles(self::getFinesSumm($fine->cottage_number)) . "');$('#fullDutyText').html('" . CashHandler::toRubles(CottageInfo::getFullCottageDebt($fine->cottage_number)) . "');</script>";

        $details = self::getFinesDetail($fine);
        LogHandler::writeLog(LogHandler::CHANGE_FINES_LOG, "участку {$fine->cottage_number} не расчитываются пени за {$details->type} {$details->period}");

        return ['status' => 1, 'message' => 'Пени за период не расчитываются' . $js];
    }

    /**
     * @param $finesId
     * @return array
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public static function enableFine($finesId)
    {
        $fine = self::findOne($finesId);
        if (empty($fine)) {
            return ['status' => 2, 'message' => 'Пени за данный период не найдены'];
        }
        $fine->is_enabled = 1;
        $fine->save();
        $summ = self::getFinesSumm($fine->cottage_number);
        $js = "<script>$('#fines_{$fine->id}_enable').addClass('hidden');$('#fines_{$fine->id}_disable').removeClass('hidden');$('#finesSumm').html('" . CashHandler::toRubles($summ) . "');$('#finesLink').html('" . CashHandler::toRubles(self::getFinesSumm($fine->cottage_number)) . "');$('#fullDutyText').html('" . CashHandler::toRubles(CottageInfo::getFullCottageDebt($fine->cottage_number)) . "');</script>";

        $details = self::getFinesDetail($fine);
        LogHandler::writeLog(LogHandler::CHANGE_FINES_LOG, "участку {$fine->cottage_number} расчитываются пени за {$details->type} {$details->period}");
        return ['status' => 1, 'message' => 'Пени за период расчитываются' . $js];
    }

    public static function getFinesSumm($cottageId)
    {
        $summ = 0;
        $fines = self::find()->where(['cottage_number' => $cottageId])->all();
        if (!empty($fines)) {
            foreach ($fines as $fine) {
                if ($fine->is_enabled)
                    $summ += $fine->summ - $fine->payed_summ;
            }
        }
        return $summ;
    }
    public static function lockFine($finesId)
    {
        $info = self::findOne($finesId);
        $view = Yii::$app->controller->renderAjax('/fines/lock', ['info' => $info]);
        return ['status' => 1, 'html' => $view, 'title' => "Зафиксировать цену пени"];
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function lock()
    {
        if($this->summ > 0){
            // найду пени
            $info = self::findOne($this->id);
            $info->summ = CashHandler::fromRubles($this->summ);
            $info->is_locked = 1;
            $info->save();
            $script = "<script>$('#fines_{$info->id}_summ').html('" . CashHandler::toRubles($info->summ) . "');$('div.modal').modal('hide');$('#fines_{$info->id}_unlock').removeClass('hidden');$('#fines_{$info->id}_lock').addClass('hidden');$('#finesLink').html('" . CashHandler::toRubles(self::getFinesSumm($info->cottage_number)) . "');$('#fullDutyText').html('" . CashHandler::toRubles(CottageInfo::getFullCottageDebt($info->cottage_number)) . "');</script>";

            $details = self::getFinesDetail($info);
            LogHandler::writeLog(LogHandler::CHANGE_FINES_LOG, "участку {$info->cottage_number} зафиксирована сумма пени за {$details->type} {$details->period} на " . CashHandler::toRubles($info->summ));
            return ['status' => 1, 'info' => "Сумма пени зафиксирована$script"];
        }
        return ['error' => 'Сумма платежа должна быть больше 0'];
    }

    /**
     * @param $finesId
     * @return array
     * @throws Exception
     */
    public static function unlock($finesId)
    {
        $info = self::findOne($finesId);
        $info->is_locked = 0;
        self::count($info);
        $info->save();
        $script = "<script>$('#fines_{$info->id}_summ').html('" . CashHandler::toRubles($info->summ) . "');$('#fines_{$info->id}_unlock').addClass('hidden');$('#fines_{$info->id}_lock').removeClass('hidden');$('#finesLink').html('" . CashHandler::toRubles(self::getFinesSumm($info->cottage_number)) . "');$('#fullDutyText').html('" . CashHandler::toRubles(CottageInfo::getFullCottageDebt($info->cottage_number)) . "');</script>";

        $details = self::getFinesDetail($info);
        LogHandler::writeLog(LogHandler::CHANGE_FINES_LOG, "участку {$info->cottage_number}  сумма пени за {$details->type} {$details->period} расчитывается по обычно");
        return ['status' => 1, 'message' => "Сумма пени считается по обычным принципам$script"];
    }

    /**
     * @param $fine FinesHandler
     * @throws Exception
     */
    private static function count($fine){
        /** @var DataTargetHandler|DataMembershipHandler|TariffPowerHandler $result */
        $result = null;
        switch ($fine->pay_type){
            case 'membership':
                $result = DataMembershipHandler::findOne($fine->period_id);
                break;
            case 'power':
                $result = DataPowerHandler::findOne($fine->period_id);
                break;
            case 'target':
                $result = DataTargetHandler::findOne($fine->period_id);
                break;
        }
        $dayDifference = TimeHandler::checkDayDifference($fine->payUpLimit);
        if($result->is_partial_payed){
            die('расчёт пени по частично оплаченным счетам в разработке');
        }
        $fines = CashHandler::countPercent($result->total_pay, self::PERCENT);
        $fine->summ = $dayDifference * $fines;
    }

    /**
     * @param $fine FinesHandler
     * @return FineDetails
     */
    private static function getFinesDetail($fine){
        $details = new FineDetails();
        switch ($fine->pay_type){
            case 'membership':
                $result = DataMembershipHandler::findOne($fine->period_id);
                $details->type = 'Членские взносы';
                $details->period = $result->quarter;
                break;
            case 'power':
                $result = DataPowerHandler::findOne($fine->period_id);
                $details->type = 'Электроэнергия';
                $details->period = $result->month;
                break;
            case 'target':
                $result = DataTargetHandler::findOne($fine->period_id);
                $details->type = 'Целевые взносы';
                $details->period = $result->year;
                break;
        }
        return $details;
    }

}