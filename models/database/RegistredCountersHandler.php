<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\DbTransaction;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_id [int(10) unsigned]  Идентификатор участка
 * @property int $last_data [bigint(20) unsigned]  Последние зарегистрированные показания
 * @property bool $is_active [tinyint(1)]  Счётчик активен
 */

class RegistredCountersHandler extends ActiveRecord
{
    const SCENARIO_REGISTER_COUNTER = 'register_counter';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_REGISTER_COUNTER => ['cottage_id', 'last_data'],
            self::SCENARIO_DEFAULT => ['id', 'cottage_id', 'last_data', 'is_active']
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
        if(DataPowerHandler::find()->where(['counter_id' => $counter->id])->count() > 0){
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

    public function register()
    {
        // проверю наличие участка
        if(CottagesHandler::check($this->cottage_id)){
            $counter = new RegistredCountersHandler(['scenario' => RegistredCountersHandler::SCENARIO_REGISTER_COUNTER]);
            $counter->cottage_id = $this->cottage_id;
            $counter->last_data = (int) $this->last_data;
            $counter->is_active = 1;
            $counter->save();
            return ['status' => 1, 'action' => '<script>makeInformerModal("Успех", "Счётчик зарегистрирован");</script>'];
        }
    }
}