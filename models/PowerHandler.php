<?php


namespace app\models;


use app\models\database\CottagesHandler;
use app\models\database\DataPowerHandler;
use app\models\database\RegistredCountersHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\PowerPeriodInfo;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\EmailHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\LogHandler;
use Exception;
use yii\base\Model;

class PowerHandler extends Model
{
    const SCENARIO_FILL = 'fill';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_FILL => ['cottageId', 'month', 'data', 'notify'],
        ];
    }

    public $cottageId;
    public $month;
    public $data;
    public $notify = 1;
    /**
     * @var DataPowerHandler[]
     */
    public $lastData;

    /**
     * @return bool
     */
    public function fill()
    {
        // получу список активных счётчиков

        $counters = RegistredCountersHandler::find()->where(['cottage_id' => $this->cottageId, 'is_active' => 1])->all();
        if (!empty($counters)) {
            foreach ($counters as $counter) {
                $info = new PowerPeriodInfo();
                $info->counter = $counter;
                $info->lastData = DataPowerHandler::find()->where(['counter_id' => $counter->id])->orderBy('search_timestamp DESC')->one();
                $this->lastData[] = $info;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function insertData()
    {
        $transaction = new DbTransaction();
        // внесу показания
        if (!empty($this->month)) {
            foreach ($this->month as $key => $value) {
                // найду счётчик
                $counter = RegistredCountersHandler::findOne($key);
                if (empty($counter)) {
                    return ['info' => 'Счётчик не найден'];
                }
                if (!$counter->is_active) {
                    return ['info' => 'Счётчик не обслуживается'];
                }
                $data = $this->data[$key];
                if (!empty($data)) {
                    if ($data < $counter->last_data) {
                        return ['error' => 'Показания должны быть больше ' . $counter->last_data];
                    }
                    // получу тариф на месяц
                    $difference = $data - $counter->last_data;
                    try {
                        $data = DataPowerHandler::insertPower($value, $difference, $counter);
                        if (!empty($this->notify)) {
                            EmailHandler::notify($counter->cottage_id, 'Внесены показания электроэнергии', "
                            <h2>Добрый день, %USERNAME%!</h2>
                            <p>
                            Внесены показания электроэнергии для участка <b class='text-info'>" . CottagesHandler::getNumberById($this->cottageId) . "</b>.<br/>
                            Предыщущие показания: <b class='text-info'>{$data->old_data} " . GrammarHandler::KILOWATT . "</b>,
                            <br/> внесённые показания: <b class='text-info'>{$data->new_data} " . GrammarHandler::KILOWATT . "</b><br/>
                            Итого израсходовано электроэнергии: <b class='text-info'>{$data->difference}  " . GrammarHandler::KILOWATT . "</b><br/>
                            Стоимость израсходованной электроэнергии: <b class='text-danger'>" . CashHandler::toRubles($data->total_pay) . "</b>
                            </p>");
                        }
                        // запишу в лог
                        LogHandler::writeLog(LogHandler::CHANGE_POWER_LOG,
                            "Внесены показания электроэнергии для участка " . CottagesHandler::getNumberById($this->cottageId) . ".
                            Предыщущие показания: {$data->old_data} " . GrammarHandler::KILOWATT . ", внесённые показания: {$data->new_data}" . GrammarHandler::KILOWATT . "<br/>
                            Итого израсходовано электроэнергии: {$data->difference}" . GrammarHandler::KILOWATT . "<br/>
                            Стоимость израсходованной электроэнергии: " . CashHandler::toRubles($data->total_pay));
                    } catch (ExceptionWithStatus $e) {
                        if ($e->getCode() === 2) {
                            return ['action' => "<script>makeInformer('warning', 'Не заполнены тарифные ставки', '<a class=\"btn btn-info\" href=\"/tariffs/fill\" target=\"_blank\">Заполнить тариф</a>');</script>"];
                        } else {
                            return ['action' => "<script>makeInformer('danger', 'Ошибка', '{$e->getMessage()}');</script>"];
                        }
                    }
                    $counter->last_data = $data->new_data;
                    $counter->save();
                }
            }
            $transaction->commitTransaction();
            return ['action' => "<script>modal.modal('hide');makeInformerModal('Успех', 'Операция выполнена');</script>"];
        } else {
            return ['info' => 'Не выбран месяц'];
        }
    }
}