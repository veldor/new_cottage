<?php


namespace app\models\database;

use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property string $quarter [char(6)]  Квартал
 * @property int $search_timestamp [bigint(20)]  Дата для выборок
 * @property int $square [int(11)]  Расчётная площадь
 * @property int $total_pay [bigint(20)]  Общая сумма оплаты
 * @property int $payed_summ [bigint(20)]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1)]  Квартал частично оплачен
 * @property bool $is_full_payed [tinyint(1)]  Квартал полностью оплачен
 * @property bool $is_individual_tariff [tinyint(1)]  Активность индивидуального тарифа
 * @property int $individual_pay_for_field [bigint(20) unsigned]  Индивидуально с метра
 * @property int $individual_pay_for_cottage [bigint(20) unsigned]  Индивидуально с участка
 * @property int $pay_up_date [bigint(20) unsigned]  Крайняя дата оплаты
 */
class DataMembershipHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "data_membership";
    }

    public function scenarios()
    {
        return [self::SCENARIO_DEFAULT => ['id', 'cottage_number', 'quarter', 'search_timestamp', 'square', 'total_pay', 'payed_summ', 'is_partial_payed', 'is_full_payed', 'is_individual_tariff', 'individual_pay_for_field', 'individual_pay_for_cottage', 'pay_up_date']];
    }

    /**
     * @param int $cottageId
     * @return DataMembershipHandler[]
     */
    public static function getDuties(int $cottageId)
    {
        return self::find()->where(['cottage_number' => $cottageId, 'is_full_payed' => 0])->all();
    }

    /**
     * @return array
     * @throws \app\models\exceptions\ExceptionWithStatus
     */
    public function changeData()
    {
        $transaction = new DbTransaction();
        $current = self::findOne($this->id);
        if(!empty($this->individual_pay_for_cottage) || !empty($this->individual_pay_for_field)){
            $current->is_individual_tariff = 1;
            if(!empty($this->individual_pay_for_cottage)){
                $current->individual_pay_for_cottage = CashHandler::fromRubles($this->individual_pay_for_cottage);
            }
            else{
                $current->individual_pay_for_cottage = null;
            }
            if(!empty($this->individual_pay_for_field)){
                $current->individual_pay_for_field = CashHandler::fromRubles($this->individual_pay_for_field);
            }
            else{
                $current->individual_pay_for_field = null;
            }
        }
        else{
            $current->individual_pay_for_field = null;
            $current->individual_pay_for_cottage = null;
            $current->is_individual_tariff = 0;
        }
        // исходя из реалий, пересчитаю стоимость месяца
        $totalPay = 0;
        if($current->is_individual_tariff){
            if(!empty($current->individual_pay_for_field)){
                $totalPay += $current->individual_pay_for_field / 100 * $current->square;
            }
            if(!empty($current->individual_pay_for_cottage)){
                $totalPay += $current->individual_pay_for_cottage;
            }
        }
        else{
            // получу тариф на квартал
            $tariff = TariffMembershipHandler::findOne(['quarter' => $current->quarter]);
            if(!empty($tariff->pay_for_meter)){
                $totalPay += $tariff->pay_for_meter / 100 * $current->square;
            }
            if(!empty($tariff->pay_for_cottage)){
                $totalPay += $tariff->pay_for_cottage;
            }
        }
        $current->total_pay = $totalPay;
        if($current->payed_summ > 0){
            $cottage = CottagesHandler::get($current->cottage_number);
            $newDeposit = new DepositHandler();
            $newDeposit->cottage_number = $current->cottage_number;
            $newDeposit->destination = 'in';
            $newDeposit->summ_before = $cottage->deposit;
            $cottage->deposit += $current->payed_summ;
            $newDeposit->summ_after = $cottage->deposit;
            $newDeposit->summ = $current->payed_summ;
            $newDeposit->pay_date = time();
            $newDeposit->description = 'Пересчёт членских взносов за ' . $current->quarter;
            $newDeposit->save();
            $current->payed_summ = 0;
            $current->is_partial_payed = 0;
            $current->is_full_payed = 0;
            $cottage->save();
        }
        $current->save();
        $transaction->commitTransaction();
        return ['action' => '<script>makeInformerModal("Успех", "Показания успешно изменены")</script>'];
    }
}