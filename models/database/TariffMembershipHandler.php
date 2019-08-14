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
 * @property string $quarter [char(6)]  Квартал оплаты
 * @property int $pay_for_meter [bigint(20) unsigned]  Оплата за кв. метр
 * @property int $pay_for_cottage [bigint(20) unsigned]  Оплата за участок
 * @property int $pay_up_date [bigint(20) unsigned]  Крайний день оплаты
 * @property int $search_timestamp [bigint(20) unsigned]  Дата для выборок
 * @property bool $is_broadcasted [tinyint(4)]  Выставлены ли счета по тарифу
 */
class TariffMembershipHandler extends ActiveRecord
{

    public $membership;

    // имя таблицы
    const SCENARIO_MASS_FILL = 'membership';

    public static function check()
    {
        $tariff = self::findOne(['quarter' => TimeHandler::getCurrentQuarter()]);
        // проверю заполненность данных за текущий квартал
        if (!$tariff) {
            return ['status' => 1];
        } else {
            // если долги по тарифу не выставлены- выставляю
            if (!$tariff->is_broadcasted) {
                $transaction = new DbTransaction();
                $cottages = CottagesHandler::findAll(['is_membership' => 1]);
                if (!empty($cottages)) {
                    foreach ($cottages as $cottage) {
                        DataMembershipHandler::add($cottage, $tariff);
                    }
                    $tariff->is_broadcasted = 1;
                    $tariff->save();
                }
            }
            return ['status' => 2];
        }
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['id', 'quarter', 'pay_for_meter', 'pay_for_cottage ', 'pay_up_date', 'search_timestamp'],
            self::SCENARIO_MASS_FILL => ['membership'],
        ];
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return "tariffs_membership";
    }

    public static function getFillingList($period)
    {
        $forFill = [];
        $quarters = TimeHandler::getQuarterList($period);
        foreach ($quarters as $key => $value) {
            // если тариф не заполнен- помещаю месяц в список для заполнения
            $tariff = TariffMembershipHandler::findOne(['quarter' => $key]);
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
        foreach ($this->membership as $quarter => $properties) {
            // проверю, если месяц уже заполнен- ничего не делаю
            $tariff = TariffMembershipHandler::findOne(['quarter' => $quarter]);
            if (empty($tariff)) {
                $newTariff = new TariffMembershipHandler();
                $newTariff->quarter = $quarter;
                $newTariff->pay_for_cottage = $properties['cottage'];
                $newTariff->pay_for_meter = CashHandler::fromRubles($properties['meter']);
                $newTariff->pay_up_date = TimeHandler::getPayUpQuarter($quarter);
                $newTariff->search_timestamp = TimeHandler::getMonthTimestamp(TimeHandler::getQuarterFirstMonth($quarter));
                $newTariff->save();
            }
        }
        $transaction->commitTransaction();
        return '<script>window.close();</script>';
    }
}