<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\TimeHandler;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property string $month [char(7)]  Месяц оплаты
 * @property int $power_limit [int(10) unsigned]  Льготный лимит
 * @property int $power_cost [bigint(20) unsigned]  Льготная стоимость 1 кв.ч. электроэнергии
 * @property int $power_overcost [bigint(20) unsigned]  Стоимость 1 кв.ч. электроэнергии
 * @property int $pay_up_date [bigint(20) unsigned]  Крайний день оплаты
 * @property int $search_timestamp [bigint(20) unsigned]  Дата для выборок
 */
class TariffPowerHandler extends ActiveRecord
{
    public $energy;

    const SCENARIO_MASS_FILL = 'mass_fill';

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['id', 'month', 'power_limit', 'power_cost', 'power_overcost', 'pay_up_date', 'search_timestamp'],
            self::SCENARIO_MASS_FILL => ['energy'],
        ];
    }

    // имя таблицы

    /**
     * @return string
     */
    public static function tableName()
    {
        return "tariffs_power";
    }

    /**
     * Верну список месяцев с незаполненным тарифом
     * @param $period
     * @return array
     */
    public static function getFillingList($period)
    {
        $forFill = [];
        $months = TimeHandler::getMonthsList($period);
        foreach ($months as $key => $value) {
            // если тариф не заполнен- помещаю месяц в список для заполнения
            $tariff = TariffPowerHandler::findOne(['month' => $key]);
            if (empty($tariff)) {
                $forFill[] = $key;
            }
        }
        return $forFill;
    }

    /**
     * @return string
     * @throws ExceptionWithStatus
     */
    public function massSave()
    {
        $transaction = new DbTransaction();
        foreach ($this->energy as $month => $properties) {
            // проверю, если месяц уже заполнен- ничего не делаю
            $tariff = TariffPowerHandler::findOne(['month' => $month]);
            if (empty($tariff)) {
                $newTariff = new TariffPowerHandler();
                $newTariff->month = $month;
                $newTariff->power_limit = $properties['limit'];
                $newTariff->power_cost = CashHandler::fromRubles($properties['cost']);
                $newTariff->power_overcost = CashHandler::fromRubles($properties['overcost']);
                $newTariff->pay_up_date = TimeHandler::getPayUpMonth($month);
                $newTariff->search_timestamp = TimeHandler::getMonthTimestamp($month);
                $newTariff->save();
            }
        }
        $transaction->commitTransaction();
        return '<script>window.close();</script>';
    }
}