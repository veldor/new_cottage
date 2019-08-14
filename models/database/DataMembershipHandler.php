<?php


namespace app\models\database;

use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\ActivatorAnswer;
use app\models\utils\Calculator;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use Yii;
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

    public $firstCountedQuarter;

    const SCENARIO_ENABLE = 'enable';

    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "data_membership";
    }

    public static function switchUse()
    {

    }

    public static function getStartFilling($date){
        $quartersForFill = [];
        // получу список месяцев для заполнения, проверю, заполнены ли тарифы на эти месяцы.
        $quarters = TimeHandler::getQuarterList($date);
        if (!empty($quarters)) {
            // проверю заполненность тарифов
            foreach ($quarters as $key => $value) {
                $tariff = TariffMembershipHandler::findOne(['quarter' => $key]);
                if (empty($tariff)) {
                    return ['error' => 'Не заполнены тарифы.<br><a class="btn btn-info" target="_blank" href="/tariff/fill/membership/' . $key . '">Заполнить тарифы</a>'];
                }
                // добавлю месяц в список
                $quartersForFill[$key] = $tariff;
            }
        } else {
            // отсчёт начнётся с этого месяца
            $tariff = TariffMembershipHandler::findOne(['quarter' => TimeHandler::getCurrentQuarter()]);
            if (empty($tariff)) {
                return ['error' => 'Не заполнены тарифы.<br><a class="btn btn-info" target="_blank" href="/tariff/index' . TimeHandler::getCurrentQuarter() . '">Заполнить тарифы</a>'];
            }
        }
        $answerText = '';
        foreach ($quartersForFill as $quarter => $tariff) {
            $answerText .= "
            <div class='col-sm-8 col-sm-offset-2'><h2 class='text-center text-success'>" . TimeHandler::getFullFromShotQuarter($quarter) . "</h2>
            <div class='form-group'><div class='col-sm-5'><label class='control-label'>Конечные показания</label></div><div class='col-sm-7'><div class='input-group'><input class='form-control' type='number' step='1' name='DataMembershipHandler[quarters][$quarter][finish]'/><span class='input-group-addon'>" . GrammarHandler::KILOWATT . "</span></div></div></div>
            </div>
            ";
        }
        return ['text' => $answerText];
    }

    /**
     * @param $cottageId
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function getSwitchForm($cottageId)
    {
        $cottageInfo = CottagesHandler::get($cottageId);
        if($cottageInfo->is_membership){

        }
        else{
            // верну форму активации оплаты
            $model = new DataMembershipHandler(['scenario' => DataMembershipHandler::SCENARIO_ENABLE, 'cottage_number' => $cottageInfo->id]);
            $answer = new ActivatorAnswer();
            $answer->status = 1;
            $answer->header = "Активация оплаты членских взносов";
            $answer->view = Yii::$app->controller->renderAjax('/indication/change-membership', ['model' => $model]);
            return $answer->return();
        }
    }

    public static function periodInfo($id)
    {
        // найду информацию о периоде
        $info = self::findOne($id);
        $tariff = TariffMembershipHandler::findOne(['quarter' => $info->quarter]);
        $transactions = '';
        $payed = PayedMembershipHandler::find()->where(['period_id' => $info->id])->all();
        if (!empty($payed)) {
            foreach ($payed as $item) {
                $transactions .= '<a href="/transaction/show/' . $item->transaction_id . '">' . $item->transaction_id . '</a> - ' . CashHandler::toRubles($item->summ) . '<br/>';
            }
        }
        $forMeter = $info->is_individual_tariff ? CashHandler::toRubles($info->individual_pay_for_field) : CashHandler::toRubles($tariff->pay_for_meter);
        $forCottage = $info->is_individual_tariff ? CashHandler::toRubles($info->individual_pay_for_cottage) : CashHandler::toRubles($tariff->pay_for_cottage);
        $answer = new ActivatorAnswer();
        $answer->status = 1;
        $answer->header = 'Сведения об членских взносах за ' . TimeHandler::getFullFromShotQuarter($info->quarter);
        $answer->view = "<table class='table table-hover table-striped'>
                            <tr><td>Площадь участка</td><td><b class='text-info'>{$info->square} " . " m<sup>2</sup></b></td></tr>
                            <tr><td>Оплатить до</td><td><b class='text-info'>" . TimeHandler::timestampToDate($tariff->pay_up_date) . "</b></td></tr>
                            <tr><td>Оплата за сотку</td><td><b class='text-info'>$forMeter</b></td></tr>
                            <tr><td>Оплата за участок</td><td><b class='text-info'>$forCottage</b></td></tr>
                            <tr><td>Итого к оплате</td><td><b class='text-danger'>" . CashHandler::toRubles($info->total_pay) . "</b></td></tr>
                            <tr><td>Оплачено ранее</td><td><b class='text-success'>" . CashHandler::toRubles($info->payed_summ) . "</b></td></tr>
                            <tr><td>Детали оплаты</td><td>$transactions</td></tr>
                        </table>";
        return $answer->return();
    }

    /**
     * @param $cottage CottagesHandler
     * @param $tariff TariffMembershipHandler
     * @throws ExceptionWithStatus
     */
    public static function add($cottage, $tariff)
    {
        if ($cottage->square == 0) {
            throw new ExceptionWithStatus('Нулевая площадь участка');
        }
        $newMembershipDebt = new DataMembershipHandler();
        $newMembershipDebt->cottage_number = $cottage->id;
        $newMembershipDebt->quarter = $tariff->quarter;
        $newMembershipDebt->search_timestamp = $tariff->search_timestamp;
        $newMembershipDebt->square = $cottage->square;
        $newMembershipDebt->total_pay = $tariff->pay_for_cottage + $tariff->pay_for_meter / 100 * $cottage->square;
        $newMembershipDebt->pay_up_date = $tariff->pay_up_date;
        $newMembershipDebt->save();
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public static function checkCurrentFilling($id = null)
    {
        $quarter = TimeHandler::getCurrentQuarter();
        if (!empty($id)) {
            $cottage = CottagesHandler::get($id);
            if ($cottage->is_membership) {
                $existentData = DataMembershipHandler::findOne(['cottage_number' => $id, 'quarter' => $quarter]);
                if (empty($existentData)) {
                    // добавлю данные
                    self::insertData($cottage, $quarter);
                }
            }
        } else {
            $cottages = CottagesHandler::findAll(['is_membership' => 1]);
            if (!empty($cottages)) {
                foreach ($cottages as $cottage) {
                    $existentData = DataMembershipHandler::findOne(['cottage_number' => $cottage->id, 'quarter' => $quarter]);
                    if (empty($existentData)) {
                        // добавлю данные
                        self::insertData($cottage, $quarter);
                    }
                }
            }
        }
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['id', 'cottage_number', 'quarter', 'search_timestamp', 'square', 'total_pay', 'payed_summ', 'is_partial_payed', 'is_full_payed', 'is_individual_tariff', 'individual_pay_for_field', 'individual_pay_for_cottage', 'pay_up_date'],
            self::SCENARIO_ENABLE => ['cottage_number', 'firstCountedQuarter']
        ];
    }

    /**
     * @param $cottage CottagesHandler
     * @param $quarter string
     * @throws ExceptionWithStatus
     */
    public static function insertData($cottage, $quarter)
    {
        if ($cottage->square == 0) {
            throw new ExceptionWithStatus('Нулевая площадь участка');
        }
        $tariff = TariffMembershipHandler::findOne(['quarter' => $quarter]);
        if (!empty($tariff)) {
            // проверю, что данные ещё не заполнены
            $oldData = DataMembershipHandler::findOne(['cottage_number' => $cottage->id, 'quarter' => $quarter]);
            if (empty($oldData)) {
                $newData = new DataMembershipHandler();
                $newData->cottage_number = $cottage->id;
                $newData->quarter = $quarter;
                $newData->search_timestamp = $tariff->search_timestamp;
                $newData->square = $cottage->square;
                $newData->pay_up_date = $tariff->pay_up_date;
                $newData->total_pay = Calculator::calculateWithSquare($cottage->square, $tariff->pay_for_meter, $tariff->pay_for_cottage);
                $newData->save();
            }
        }

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
     * @throws ExceptionWithStatus
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

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public function enable()
    {

        $cottageInfo = CottagesHandler::get($this->cottage_number);
        if ($cottageInfo->is_membership) {
            return ['info' => 'Членские взносы уже оплачиваются'];
        }
        // если пуст параметр последнего оплаченного квартала- выдам ошибку, ну нафиг
        if (empty($this->firstCountedQuarter)) {
            return ['info' => 'Не заполнены данные о первом неоплаченном квартале'];
        }
        // если площадь участка равна нулю- предупрежу, что она не верна
        if ($cottageInfo->square == 0) {
            return ['info' => 'Не заполнены данные о площади участка'];
        }
        $transaction = new DbTransaction();
        $quarters = TimeHandler::getQuarterList($this->firstCountedQuarter);
        if (!empty($quarters)) {
            foreach ($quarters as $quarter => $value) {
                $tariff = TariffMembershipHandler::findOne(['quarter' => $quarter]);
                $newData = new DataMembershipHandler();
                $newData->cottage_number = $cottageInfo->id;
                $newData->quarter = $quarter;
                $newData->search_timestamp = TimeHandler::getMonthTimestamp(TimeHandler::getQuarterFirstMonth($quarter));
                $newData->square = $cottageInfo->square;
                $newData->pay_up_date = TimeHandler::getPayUpQuarter($quarter);
                $newData->total_pay = $tariff->pay_for_meter / 100 * $newData->square + $tariff->pay_for_cottage;
                $newData->save();
            }
        }
        $quarter = TimeHandler::getCurrentQuarter();
        // запишу в долги данный квартал
        $tariff = TariffMembershipHandler::findOne(['quarter' => $quarter]);
        $newData = new DataMembershipHandler();
        $newData->cottage_number = $cottageInfo->id;
        $newData->quarter = $quarter;
        $newData->search_timestamp = TimeHandler::getMonthTimestamp(TimeHandler::getQuarterFirstMonth($quarter));
        $newData->square = $cottageInfo->square;
        $newData->pay_up_date = TimeHandler::getPayUpQuarter($quarter);
        $newData->total_pay = $tariff->pay_for_meter / 100 * $newData->square + $tariff->pay_for_cottage;
        $newData->save();
        $cottageInfo->is_membership = 1;
        $cottageInfo->save();
        $transaction->commitTransaction();
        $answer = new ActivatorAnswer();
        $answer->status = 1;
        $answer->header = 'Успех';
        $answer->view = 'Теперь по участку оплачиваются членские взносы';
        return $answer->return();
    }
}