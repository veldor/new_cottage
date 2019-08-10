<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\DbTransaction;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use Exception;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_id [int(10) unsigned]  Идентификатор участка
 * @property int $last_data [bigint(20) unsigned]  Последние зарегистрированные показания
 * @property bool $is_active [tinyint(1)]  Счётчик активен
 * @property string $counter_serial [varchar(100)]  Серийный номер счётчика
 */
class RegistredCountersHandler extends ActiveRecord
{
    public $firstCountedMonth;
    public $counterSerial;
    public $startValue;
    public $months;

    const SCENARIO_REGISTER_COUNTER = 'register_counter';

    public static function getStartFilling($date)
    {
        $monthsForFill = [];
        // получу список месяцев для заполнения, проверю, заполнены ли тарифы на эти месяцы.
        $months = TimeHandler::getMonthsList($date);
        if (!empty($months)) {
            // проверю заполненность тарифов
            foreach ($months as $key => $value) {
                $tariff = TariffPowerHandler::findOne(['month' => $key]);
                if (empty($tariff)) {
                    return ['error' => 'Не заполнены тарифы.<br><a class="btn btn-info" target="_blank" href="/tariff/fill/power/' . $key . '">Заполнить тарифы</a>'];
                }
                // добавлю месяц в список
                $monthsForFill[$key] = $tariff;
            }
        } else {
            // отсчёт начнётся с этого месяца
            $tariff = TariffPowerHandler::findOne(['month' => TimeHandler::getCurrentMonth()]);
            if (empty($tariff)) {
                return ['error' => 'Не заполнены тарифы.<br><a class="btn btn-info" target="_blank" href="/tariff/index' . TimeHandler::getCurrentMonth() . '">Заполнить тарифы</a>'];
            }
        }
        $answerText = '';
        foreach ($monthsForFill as $month => $tariff) {
            $answerText .= "
            <div class='col-sm-8 col-sm-offset-2'><h2 class='text-center text-success'>" . TimeHandler::getFullFromShotMonth($month) . "</h2>
            <div class='form-group'><div class='col-sm-5'><label class='control-label'>Конечные показания</label></div><div class='col-sm-7'><div class='input-group'><input class='form-control' type='number' step='1' name='RegistredCountersHandler[months][$month][finish]'/><span class='input-group-addon'>" . GrammarHandler::KILOWATT . "</span></div></div></div>
            </div>
            ";
        }
        return ['text' => $answerText];
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_REGISTER_COUNTER => ['cottage_id', 'firstCountedMonth', 'counterSerial', 'startValue', 'months'],
            self::SCENARIO_DEFAULT => ['id', 'cottage_id', 'last_data', 'counter_serial', 'is_active']
        ];
    }
    // имя таблицы

    /**
     * @return string
     */
    public static function tableName()
    {
        return "registered_counters";
    }


    public static function disable($id)
    {
        $counter = self::findOne($id);
        $counter->is_active = 0;
        $counter->save();
        return ['title' => "Успешно", 'html' => 'Счётчик деактивирован', 'note' => 1];
    }

    /**
     * @param $id
     * @return array
     * @throws ExceptionWithStatus
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function deleteItem($id)
    {
        $transaction = new DbTransaction();
        $counter = self::findOne($id);
        if (DataPowerHandler::find()->where(['counter_id' => $counter->id])->count() > 0) {
            return ['info' => 'Невозможно удалить показания: по счётчику внесены показания. Сначала удалите все внесённые показания'];
        }
        $counter->delete();
        $transaction->commitTransaction();
        return ['title' => "Успешно", 'html' => 'Счётчик удалён', 'note' => 1];
    }

    public static function enable($id)
    {
        $counter = self::findOne($id);
        $counter->is_active = 1;
        $counter->save();
        return ['title' => "Успешно", 'html' => 'Счётчик активирован', 'note' => 1];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function register()
    {
        $transaction = new DbTransaction();
        if (empty($this->startValue) && $this->startValue !== '0') {
            return ['error' => 'Не заполнены начальные показания счётчика'];
        }
        // проверю наличие участка
        $cottageInfo = CottagesHandler::get($this->cottage_id);
            $counter = new RegistredCountersHandler(['scenario' => RegistredCountersHandler::SCENARIO_REGISTER_COUNTER]);
            $counter->cottage_id = $this->cottage_id;
        $counter->last_data = (int)$this->startValue;
        $counter->counter_serial = $this->counter_serial;
            $counter->is_active = 1;
            $counter->save();
        if (!empty($this->firstCountedMonth)) {
            $startValue = (int)$this->startValue;
            foreach ($this->months as $month => $lastData) {
                if ((int)$lastData['finish'] < $startValue) {
                    return ['error' => 'Неверно заполнены конечные показания счётчика за ' . TimeHandler::getFullFromShotMonth($month)];
                }
                // внесу показания в базу
                $difference = (int)$lastData['finish'] - $startValue;
                DataPowerHandler::insertPower($month, $difference, $counter);
                $startValue = (int)$lastData['finish'];
            }
        }
        $cottageInfo->is_power = 1;
        $cottageInfo->save();
        $transaction->commitTransaction();
            return ['status' => 1, 'action' => '<script>makeInformerModal("Успех", "Счётчик зарегистрирован");</script>'];
    }
}