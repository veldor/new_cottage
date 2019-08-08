<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\TimeHandler;
use Exception;
use Throwable;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $counter_id [int(10) unsigned]  Идентификатор счётчика(на случай наличия нескольких на участке)
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property string $month [char(7)]  Месяц оплаты
 * @property int $filling_date [bigint(20) unsigned]  Дата заполнения
 * @property int $old_data [bigint(20) unsigned]  Старые показания
 * @property int $new_data [bigint(20) unsigned]  Новые показания
 * @property int $search_timestamp [bigint(20) unsigned]  Дата для выборок
 * @property int $difference [bigint(20) unsigned]  Потреблённая энергия
 * @property int $total_pay [bigint(20) unsigned]  Общая сумма оплаты
 * @property int $in_limit_data [bigint(20) unsigned]  Потреблённые льготные киловатты
 * @property int $over_limit_data [bigint(20) unsigned]  Потреблённые киловатты
 * @property int $in_limit_pay [bigint(20) unsigned]  Льготная стоимость
 * @property int $over_limit_pay [bigint(20) unsigned]  Стоимость
 * @property bool $is_limit_ignored [tinyint(1) unsigned]  Игнорировать льготный лимит
 * @property int $payed_summ [bigint(20) unsigned]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1) unsigned]  Месяц оплачен частично
 * @property bool $is_full_payed [tinyint(1) unsigned]  Месяц полностью оплачен
 * @property bool $is_active [tinyint(1) unsigned]  Учитывать ли показания по счётчику
 * @property bool $is_individual_tariff [tinyint(1) unsigned]  Активность индивидуального тарифа
 * @property int $individual_limit [bigint(20) unsigned]  Индивидуальный лимит
 * @property int $individual_cost [bigint(20) unsigned]  Индивидуальная льготная стоимость
 * @property int $individual_overcost [bigint(20) unsigned]  Индивидуальная стоимость
 * @property int $pay_up_date [bigint(20) unsigned]  Крайняя дата оплаты
 */
class DataPowerHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "data_power";
    }

    public function scenarios()
    {
        return [self::SCENARIO_DEFAULT => ['id', 'counter_id', 'old_data', 'new_data', 'is_limit_ignored', 'individual_limit', 'individual_cost', 'individual_overcost']];
    }

    /**
     * @param $cottageId
     * @return DataPowerHandler[]
     */
    public static function getDuties($cottageId)
    {
        return self::find()->where(['cottage_number' => $cottageId, 'is_full_payed' => 0])->andWhere(['>', 'difference', 0])->all();
    }

    /**
     * @param $month
     * @param int $difference
     * @param RegistredCountersHandler $counter
     * @return DataPowerHandler
     * @throws Exception
     */
    public static function insertPower($month, int $difference, $counter)
    {
        $transaction = new DbTransaction();
        try {
            // получу тарифы на месяц, если их нет- верну исключение
            $tariff = TariffPowerHandler::findOne(['month' => $month]);
            if (empty($tariff)) {
                throw new ExceptionWithStatus("Тариф не найден", 2);
            }
            // проверю, не заполнены ли уже данные показания для данного счётчика
            if (!empty(DataPowerHandler::findOne(['month' => $month, 'counter_id' => $counter->id]))) {
                throw new ExceptionWithStatus("Данные за этот месяц уже внесены", 3);
            }
            $newRecord = new DataPowerHandler();
            $newRecord->counter_id = $counter->id;
            $newRecord->cottage_number = $counter->cottage_id;
            $newRecord->month = $month;
            $newRecord->filling_date = time();
            $newRecord->old_data = $counter->last_data;
            $newRecord->new_data = $counter->last_data + $difference;
            $newRecord->search_timestamp = TimeHandler::getMonthTimestamp($month);
            $newRecord->pay_up_date = TimeHandler::getPayUpMonth($month);
            $newRecord->difference = $difference;
            if ($difference > $tariff->power_limit) {
                $newRecord->in_limit_data = $tariff->power_limit;
                $newRecord->over_limit_data = $difference - $tariff->power_limit;
            } else {
                $newRecord->in_limit_data = $difference;
            }
            $newRecord->in_limit_pay = $newRecord->in_limit_data * $tariff->power_cost;
            $newRecord->over_limit_pay = $newRecord->over_limit_data * $tariff->power_overcost;
            $newRecord->total_pay = $newRecord->in_limit_pay + $newRecord->over_limit_pay;
            $newRecord->save();
            // обновлю данные счётчика
            $counter->last_data = $newRecord->new_data;
            $transaction->commitTransaction();
            return $newRecord;
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     * @throws Throwable
     */
    public static function deleteIndication($id)
    {
        $transaction = new DbTransaction();
        try {
            // найду показания
            $data = self::findOne($id);
            // платёж не должен участвовать в выставленных счетах и должен быть последним, который вносился
            if (BillPowerHandler::find()->where(['power_data_id' => $data])->count() > 0) {
                return ['info' => 'Невозможно удалить показания: они уже учтены в выставленном счёте'];
            }
            if (self::find()->where(['counter_id' => $data->counter_id])->andWhere(['>', 'month', $data->month])->count() > 0) {
                return ['info' => 'Можно удалить только последние заполненные показания'];
            }
            if(FinesHandler::find()->where(['period_id' => $data->id, 'pay_type' => 'power'])->count() > 0){
                return ['info' => 'Невозможно удалить показания: по ним начислены пени'];
            }
            $counter = RegistredCountersHandler::findOne($data->counter_id);
            $counter->last_data = $data->old_data;
            $counter->save();
            $data->delete();
            $transaction->commitTransaction();
            $script = "<script>makeInformerModal('Успешно', 'Показания удалены')</script>";
            return ['status' => 1, 'message' => 'Показания удалены.' . $script];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

/*    private static function getDebt(int $cottage_number)
    {
        $debt = 0;
        $data = self::getDuties($cottage_number);
        if(!empty($data)){
            foreach ($data as $datum) {
                $debt += $datum->total_pay - $datum->payed_summ;
            }
        }
        return $debt;
    }*/

    /**
     * @return array
     * @throws Exception
     */
    public function changeData()
    {
        $transaction = new DbTransaction();
        try{
            // получу сохранённое значение
            $current = self::findOne($this->id);
            if (self::find()->where(['counter_id' => $current->counter_id])->andWhere(['>', 'month', $current->month])->count() > 0) {
                return ['info' => 'Можно изменить только последние заполненные показания'];
            }
            $cottage = CottagesHandler::findOne($current->cottage_number);
            // Если менялись новые данные
            if($this->new_data < $current->old_data){
                return ['error' => 'Новые показания должны быть больше старых'];
            }
            $current->new_data = abs((int)$this->new_data);
            $current->is_limit_ignored = $this->is_limit_ignored;
            if(!empty($this->individual_limit)){
                $current->individual_limit = (int)$this->individual_limit;
            }
            if(!empty($this->individual_cost)){
                $current->individual_cost = CashHandler::fromRubles($this->individual_cost);
            }
            if(!empty($this->individual_overcost)){
                $current->individual_overcost = CashHandler::fromRubles($this->individual_overcost);
            }
            $difference = $current->new_data - $current->old_data;
            if($difference > 0){
                $tariff = TariffPowerHandler::findOne(['month' => $current->month]);
                // посчитаю сумму в новых реалиях
                if($current->is_limit_ignored){
                    $current->over_limit_data = $difference;
                    $current->in_limit_data = 0;
                }
                else{
                    if($current->individual_limit){
                        $limit = $current->individual_limit;
                    }
                    else{
                        $limit = $tariff->power_limit;
                    }
                    if($difference < $limit){
                        $current->in_limit_data = $difference;
                        $current->over_limit_data = 0;
                    }
                    else{
                        $current->in_limit_data = $limit;
                        $current->over_limit_data = $difference - $limit;
                    }
                }
                if($current->individual_cost){
                    $current->in_limit_pay = $current->in_limit_data * $current->individual_cost;
                }
                else{
                    $current->in_limit_pay = $current->in_limit_data * $tariff->power_cost;
                }
                if($current->individual_overcost){
                    $current->over_limit_pay = $current->over_limit_data * $current->individual_overcost;
                }
                else{
                    $current->over_limit_pay = $current->over_limit_data * $tariff->power_overcost;
                }
                $current->total_pay = $current->in_limit_pay + $current->over_limit_pay;
            }
            else{
                $current->difference = 0;
                $current->total_pay = 0;
                $current->in_limit_data = 0;
                $current->in_limit_pay = 0;
                $current->over_limit_data = 0;
                $current->over_limit_pay = 0;
                $current->is_partial_payed = 0;
                $current->is_full_payed = 1;
                // если была оплата по данному счёту- сумма оплаты зачисляется на депозит
                if($current->payed_summ > 0){
                    $newDeposit = new DepositHandler();
                    $newDeposit->cottage_number = $cottage->id;
                    $newDeposit->destination = 'in';
                    $newDeposit->summ_before = $cottage->deposit;
                    $cottage->deposit += $current->payed_summ;
                    $newDeposit->summ_after = $cottage->deposit;
                    $newDeposit->summ = $current->payed_summ;
                    $newDeposit->pay_date = time();
                    $newDeposit->description = 'Пересчёт показаний электроэнергии по счётчику ' . $current-> counter_id . ' за ' . $current->month;
                    $newDeposit->save();
                    $current->payed_summ = 0;
                    $current->is_partial_payed = 0;
                    $current->is_full_payed = 0;
                }
            }
            $cottage->save();
            $current->save();
            $transaction->commitTransaction();
            return ['action' => '<script>makeInformerModal("Успех", "Показания успешно изменены")</script>'];
        }
        catch (Exception $e){
            $transaction->rollbackTransaction();
            throw $e;
        }
    }
}