<?php


namespace app\models;


use app\models\database\TariffMembershipHandler;
use app\models\database\TariffPowerHandler;
use app\models\database\TariffTargetHandler;
use app\models\selection_classes\TariffsInfo;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use DateTime;
use Exception;
use Yii;
use yii\base\Model;


class TariffsHandler extends Model
{
    const SCENARIO_SEARCH_TARIFFS = 'search_tariffs';
    const SCENARIO_FILL = 'fill';
    /**
     * @var string
     */
    public $unfilled;
    /**
     * @var TariffPowerHandler
     */
    public $lastFilledPower;
    /**
     * @var TariffMembershipHandler
     */
    public $lastFilledMembership;
    /**
     * @var TariffTargetHandler
     */
    public $lastFilledTarget;

    public function scenarios(): array
    {
        return [
            self::SCENARIO_SEARCH_TARIFFS => ['startDate', 'finishDate'],
            self::SCENARIO_FILL => ['startDate', 'finishDate', 'membership', 'target', 'energy'],
        ];
    }

    public $startDate;
    public $finishDate;

    public $membership;
    public $target;
    public $energy;

    public function rules(): array
    {
        return [
            [['startDate', 'finishDate'], 'required', 'on' => self::SCENARIO_SEARCH_TARIFFS],
        ];
    }

    /**
     * @return TariffsInfo
     */
    public static function getCurrent()
    {
        $tariffs = new TariffsInfo();
        $prevMonth = TimeHandler::getNeighborMonth(TimeHandler::getCurrentMonth(), '-1');
        $tariffs->powerTariffs[$prevMonth] = TariffPowerHandler::find()->where(['month' => $prevMonth])->one();
        $currentQuarter= TimeHandler::getCurrentQuarter();
        $tariffs->membershipTariffs[$currentQuarter] = TariffMembershipHandler::find()->where(['quarter' => $currentQuarter])->one();
        $currentYear = TimeHandler::getCurrentYear();
        $tariffs->targetTariffs[$currentYear] = TariffTargetHandler::find()->where(['year' => $currentYear])->one();
        /** @var TariffsInfo $tariffs */
        return $tariffs;
    }

    /**
     * @throws Exception
     */
    public function fillTariffs()
    {
        $start = new DateTime('0:0:00' . $this->startDate);
        $finish = new DateTime('23:59:50' . $this->finishDate);
        $interval = ['start' => $start->format('U'), 'finish' => $finish->format('U')];

        $tariffs = new TariffsInfo();
        $tariffs->powerTariffs = TariffPowerHandler::find()->where(['>=', 'search_timestamp', $interval['start']])->andWhere(['<=', 'search_timestamp', $interval['finish']])->all();
        $tariffs->membershipTariffs = TariffMembershipHandler::find()->where(['>=', 'search_timestamp', $interval['start']])->andWhere(['<=', 'search_timestamp', $interval['finish']])->all();
        $tariffs->targetTariffs = TariffTargetHandler::find()->where(['>=', 'search_timestamp', $interval['start']])->andWhere(['<=', 'search_timestamp', $interval['finish']])->all();
        /** @var TariffsInfo $tariffs */
        return $tariffs;
    }

    public function getLastFilled()
    {
        $this->lastFilledPower = TariffPowerHandler::find()->orderBy('month DESC')->one();
        $this->lastFilledMembership = TariffMembershipHandler::find()->orderBy('quarter DESC')->one();
        $this->lastFilledTarget = TariffTargetHandler::find()->orderBy('year DESC')->one();
    }

    public function saveTariffs()
    {
        if(!empty($this->energy)){
            foreach ($this->energy as $key => $value) {
                // проверю, не заполнен ли уже месяц
                if(TariffPowerHandler::find()->where(['month' => $key])->count() == 0){
                    if(!empty($value['limit']) && !empty($value['cost']) && !empty($value['overcost'])){
                        $newTariff = new TariffPowerHandler();
                        $newTariff->month = $key;
                        $newTariff->power_limit = (int)$value['limit'];
                        $newTariff->power_cost = CashHandler::fromRubles($value['cost']);
                        $newTariff->power_overcost = CashHandler::fromRubles($value['overcost']);
                        $newTariff->pay_up_date = TimeHandler::getPayUpMonth($key);
                        $newTariff->search_timestamp = TimeHandler::getMonthTimestamp($key);
                        $newTariff->save();

                        Yii::$app->session->addFlash('success', 'Зарегистрирован тариф электроэнергии на ' . TimeHandler::getFullFromShotMonth($key));
                    }
                }
            }
        }
        if(!empty($this->membership)){
            foreach ($this->membership as $key => $value) {
                // проверю, не заполнен ли уже квартал
                if(TariffMembershipHandler::find()->where(['quarter' => $key])->count() == 0){
                    if(!empty($value['fixed']) && !empty($value['float'])){
                        $newTariff = new TariffMembershipHandler();
                        $newTariff->quarter = $key;
                        $newTariff->pay_for_cottage = CashHandler::fromRubles($value['fixed']);
                        $newTariff->pay_for_meter = CashHandler::fromRubles($value['float']);
                        $newTariff->pay_up_date = TimeHandler::getPayUpQuarter($key);
                        $newTariff->search_timestamp = TimeHandler::getMonthTimestamp(TimeHandler::getQuarterFirstMonth($key));
                        $newTariff->save();

                        Yii::$app->session->addFlash('success', 'Зарегистрирован тариф членских взносов на ' . TimeHandler::getFullFromShotQuarter($key));
                    }
                }
            }
        }
        if(!empty($this->target)){
            foreach ($this->target as $key => $value) {
                // проверю, не заполнен ли уже месяц
                if(TariffTargetHandler::find()->where(['year' => $key])->count() == 0){
                    if(!empty($value['fixed']) && !empty($value['float']) && !empty($value['payUp'])){
                        $newTariff = new TariffTargetHandler();
                        $newTariff->year = $key;
                        $newTariff->pay_for_cottage = CashHandler::fromRubles($value['fixed']);
                        $newTariff->pay_for_meter = CashHandler::fromRubles($value['float']);
                        $newTariff->pay_description = $value['description'] ?? '';
                        $newTariff->pay_up_date = TimeHandler::dateToTimestamp($value['payUp']);
                        $newTariff->search_timestamp = TimeHandler::getYearTimestamp($key);
                        $newTariff->save();
                        Yii::$app->session->addFlash('success', 'Зарегистрирован тариф целевых взносов на ' . $key . ' год');
                    }
                }
            }
        }
    }
}