<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\ActivatorAnswer;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use Yii;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $year [bigint(20)]  Год
 * @property int $square [int(11)]  Расчётная площадь
 * @property int $total_pay [bigint(20)]  Общая сумма оплаты
 * @property int $payed_summ [bigint(20)]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1)]  Год частично оплачен
 * @property bool $is_full_payed [tinyint(1)]  Год полностью оплачен
 * @property bool $is_individual_tariff [tinyint(1)]  Активность индивидуального тарифа
 * @property int $individual_pay_for_field [bigint(20) unsigned]  Индивидуально с сотки
 * @property int $individual_pay_for_cottage [bigint(20) unsigned]  Индивидуально с участка
 * @property int $pay_up_date [bigint(20) unsigned]  Крайняя дата оплаты
 */
class DataTargetHandler extends ActiveRecord
{
    public $targets;

    const SCENARIO_ENABLE = 'enable';

    public static function periodInfo($id)
    {
        // найду информацию о периоде
        $info = self::findOne($id);
        $tariff = TariffTargetHandler::findOne(['year' => $info->year]);
        $transactions = '';
        $payed = PayedTargetHandler::find()->where(['period_id' => $info->id])->all();
        if (!empty($payed)) {
            foreach ($payed as $item) {
                $transactions .= '<a href="/transaction/show/' . $item->transaction_id . '">' . $item->transaction_id . '</a> - ' . CashHandler::toRubles($item->summ) . '<br/>';
            }
        }
        $forMeter = $info->is_individual_tariff ? CashHandler::toRubles($info->individual_pay_for_field) : CashHandler::toRubles($tariff->pay_for_meter);
        $forCottage = $info->is_individual_tariff ? CashHandler::toRubles($info->individual_pay_for_cottage) : CashHandler::toRubles($tariff->pay_for_cottage);
        $answer = new ActivatorAnswer();
        $answer->status = 1;
        $answer->header = 'Сведения об членских взносах за ' . $info->year . ' год';
        $answer->view = "<table class='table table-hover table-striped'>
                            <tr><td>Назначение платежа</td><td><b class='text-info'>{$tariff->pay_description} " . "</b></td></tr>
                            <tr><td>Площадь участка</td><td><b class='text-info'>{$info->square} " . " m<sup>2</sup></b></td></tr>
                            <tr><td>Оплата за сотку</td><td><b class='text-info'>$forMeter</b></td></tr>
                            <tr><td>Оплата за участок</td><td><b class='text-info'>$forCottage</b></td></tr>
                            <tr><td>Итого к оплате</td><td><b class='text-danger'>" . CashHandler::toRubles($info->total_pay) . "</b></td></tr>
                            <tr><td>Оплачено ранее</td><td><b class='text-success'>" . CashHandler::toRubles($info->payed_summ) . "</b></td></tr>
                            <tr><td>Детали оплаты</td><td>$transactions</td></tr>
                        </table>";
        return $answer->return();
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['id', 'cottage_number', 'year', 'search_timestamp', 'square', 'total_pay', 'payed_summ', 'is_partial_payed', 'is_full_payed', 'is_individual_tariff', 'individual_pay_for_field', 'individual_pay_for_cottage', 'pay_up_date'],
            self::SCENARIO_ENABLE => ['cottage_number', 'targets']
        ];
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return "data_target";
    }

    /**
     * @param int $cottageId
     * @return DataTargetHandler[]
     */
    public static function getDuties(int $cottageId)
    {
        return self::find()->where(['cottage_number' => $cottageId, 'is_full_payed' => 0])->all();
    }

    /**
     * @param $cottageId
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function getSwitchForm($cottageId)
    {
        $cottageInfo = CottagesHandler::get($cottageId);
        if ($cottageInfo->is_membership) {

        } else {
            // верну форму активации оплаты
            $model = new DataTargetHandler(['scenario' => DataTargetHandler::SCENARIO_ENABLE, 'cottage_number' => $cottageInfo->id]);
            $tariffs = TariffTargetHandler::find()->all();
            $answer = new ActivatorAnswer();
            $answer->status = 1;
            $answer->header = "Активация оплаты целевых взносов";
            $answer->view = Yii::$app->controller->renderAjax('/indication/change-target', ['model' => $model, 'tariffs' => $tariffs, 'cottageInfo' => $cottageInfo]);
            return $answer->return();
        }
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function enable()
    {
        $transaction = new DbTransaction();
        $cottageInfo = CottagesHandler::get($this->cottage_number);
        foreach ($this->targets as $year => $value) {
            if (!is_int((int)$value)) {
                return ['error' => 'Не заполнены данные за ' . $year . ' год'];
            }
            $tariff = TariffTargetHandler::findOne(['year' => $year]);
            $newDuty = new DataTargetHandler();
            $newDuty->cottage_number = $cottageInfo->id;
            $newDuty->year = $year;
            $newDuty->square = $cottageInfo->square;
            $newDuty->total_pay = $tariff->pay_for_cottage + $tariff->pay_for_meter / 100 * $cottageInfo->square - $value;
            $newDuty->pay_up_date = $tariff->pay_up_date;
            $newDuty->save();
        }
        $cottageInfo->is_target = 1;
        $cottageInfo->save();
        $transaction->commitTransaction();
        return ['status' => 1, 'message' => 'Целевые платежи оплачиваются'];
    }
}