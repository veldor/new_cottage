<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 12:48
 */

namespace app\models;

use app\models\database\CottagesHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DepositHandler;
use app\models\database\DiscountHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\TransactionsHandler;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use DateTime;
use Exception;
use InvalidArgumentException;
use yii\base\Model;

class Search extends Model
{
    const SCENARIO_BILLS_SEARCH = 'bills-search';
    public $startDate;
    public $finishDate;
    public $searchType;
    public $searchTypeList = ['routine' => 'Обычный', 'summary' => 'Суммарный', 'report' => 'Отчёт'];

    public function scenarios(): array
    {
        return [
            self::SCENARIO_BILLS_SEARCH => ['startDate', 'finishDate', 'searchType'],
        ];
    }

    public function rules(): array
    {
        return [
            [['startDate', 'finishDate', 'searchType'], 'required'],
            [['startDate', 'finishDate'], 'date', 'format' => 'y-M-d'],
            ['searchType', 'in', 'range' => ['routine', 'summary', 'report']]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'startDate' => 'Начало периода',
            'finishDate' => 'Конец периода',
            'searchType' => 'Тип отчёта',
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function doSearch(): array
    {
        $start = new DateTime('0:0:00' . $this->startDate);
        $finish = new DateTime('23:59:50' . $this->finishDate);
        $interval = ['start' => $start->format('U'), 'finish' => $finish->format('U')];
        switch ($this->searchType) {
            case 'summary' :
                return $this->getSummary($interval);
            case 'report':
                return $this->getReport($interval);
            case 'routine':
                return $this->getTransactions($interval);
        }
        throw new InvalidArgumentException("Неверный тип отчёта");
    }

    private function getSummary($interval): array
    {
        $totalPowerSumm = 0;
        $totalMemSumm = 0;
        $totalTargetSumm = 0;
        $totalSingleSumm = 0;
        $totalFinesSumm = 0;
        $discountsSumm = 0;
        $toDepositSumm = 0;
        $fromDepositSumm = 0;

        // найду транзакции за день
        $results = TransactionsHandler::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        if (!empty($results)) {
            foreach ($results as $result) {
                // найду все сущности, привязанные к транзакции
                $powers = PayedPowerHandler::findAll(['transaction_id' => $result->id]);
                $memberships = PayedMembershipHandler::findAll(['transaction_id' => $result->id]);
                $targets = PayedTargetHandler::findAll(['transaction_id' => $result->id]);
                $singles = PayedSingleHandler::findAll(['transaction_id' => $result->id]);
                $fines = PayedFinesHandler::findAll(['transaction_id' => $result->id]);
                $discounts = DiscountHandler::findAll(['transaction_id' => $result->id]);
                $toDeposit = DepositHandler::findAll(['transaction_id' => $result->id, 'destination' => 'in']);
                $fromDeposit = DepositHandler::findAll(['transaction_id' => $result->id, 'destination' => 'out']);

                if (!empty($powers)) {
                    foreach ($powers as $p) {
                        $totalPowerSumm += $p->summ;
                    }
                }
                if (!empty($memberships)) {
                    foreach ($memberships as $p) {
                        $totalMemSumm += $p->summ;
                    }
                }
                if (!empty($targets)) {
                    foreach ($targets as $p) {
                        $totalTargetSumm += $p->summ;
                    }
                }
                if (!empty($singles)) {
                    foreach ($singles as $p) {
                        $totalSingleSumm += $p->summ;
                    }
                }
                if (!empty($fines)) {
                    foreach ($fines as $p) {
                        $totalFinesSumm += $p->summ;
                    }
                }
                if (!empty($discounts)) {
                    foreach ($discounts as $p) {
                        $discountsSumm += $p->summ;
                    }
                }
                if (!empty($toDeposit)) {
                    foreach ($toDeposit as $p) {
                        $toDepositSumm += $p->summ;
                    }
                }
                if (!empty($fromDeposit)) {
                    foreach ($fromDeposit as $p) {
                        $fromDepositSumm += $p->summ;
                    }
                }
            }
            $content = "<table class='table table-striped'><thead><th>Электроэнергия</th><th>Членские</th><th>Целевые</th><th>Разовые</th><th>Пени</th><th>С депозита</th><th>На депозит</th><th>Скидка</th></thead><tbody>";

            $content .= "<tr><td>" . CashHandler::toRubles($totalPowerSumm) . "</td><td>" . CashHandler::toRubles($totalMemSumm) . "</td><td>" . CashHandler::toRubles($totalTargetSumm) . "</td><td>" . CashHandler::toRubles($totalSingleSumm) . "</td><td>" . CashHandler::toRubles($totalFinesSumm) . "</td><td>" . CashHandler::toRubles($fromDepositSumm) . "</td><td>" . CashHandler::toRubles($toDepositSumm) . "</td><td>" . CashHandler::toRubles($discountsSumm) . "</td></tr></tbody></table>";
            $total = $totalSingleSumm + $totalPowerSumm + $totalTargetSumm + $totalFinesSumm + $totalMemSumm - $discountsSumm - $fromDepositSumm + $toDepositSumm;
            return ['status' => 1, 'data' => $content, 'totalSumm' => $total];
        }
        return ['status' => 1, 'data' => "<h2 class='text-center'>Платежей за данный период не было</h2>", 'totalSumm' => 0];
    }

    /**
     * @param $interval
     * @return array
     * @throws Exception
     */
    private function getReport($interval)
    {
        $wholePower = 0;
        $wholeTarget = 0;
        $wholeMembership = 0;
        $wholeFines = 0;
        $wholeSumm = 0;
        $fullSumm = 0;
        $wholeDeposit = 0;
        // найду транзакции за день по банковскому отчёту
        $transactions = TransactionsHandler::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        if (!empty($transactions)) {
            $content = [];
            foreach ($transactions as $transaction) {
                $wholeSumm += $transaction->summ;
                $fullSumm += $transaction->summ;
                $date = TimeHandler::timestampToDate($transaction->bankDate);
                // получу оплаченные сущности
                $powers = PayedPowerHandler::findAll(['transaction_id' => $transaction->id]);
                $memberships = PayedMembershipHandler::findAll(['transaction_id' => $transaction->id]);
                $targets = PayedTargetHandler::findAll(['transaction_id' => $transaction->id]);
                $singles = PayedSingleHandler::findAll(['transaction_id' => $transaction->id]);
                $fines = PayedFinesHandler::findAll(['transaction_id' => $transaction->id]);
                $discounts = DiscountHandler::findAll(['transaction_id' => $transaction->id]);
                $toDeposit = DepositHandler::findAll(['transaction_id' => $transaction->id, 'destination' => 'in']);
                $fromDeposit = DepositHandler::findAll(['transaction_id' => $transaction->id, 'destination' => 'out']);
                if (!empty($memberships)) {
                    $memSumm = 0;
                    $memList = '';
                    foreach ($memberships as $membership) {
                        $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';

                        $memSumm += $membership->summ;
                        $wholeMembership += $membership->summ;
                    }
                    $memSumm = CashHandler::toRubles($memSumm);
                } else {
                    $memList = '--';
                    $memSumm = '--';
                }
                if (!empty($powers)) {
                    $powCounterValue = '';
                    $powUsed = '';
                    $powSumm = 0;
                    foreach ($powers as $power) {
                        // найду данные о показаниях
                        $powData = DataPowerHandler::findOne($power->period_id);
                        if (empty($powData)) {
                            echo 'p' . $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                            die;
                        }
                        $powCounterValue .= $power->month . ': ' . $powData->new_data . '<br/>';
                        $powUsed .= $powData->difference . '<br/>';
                        $powSumm += $power->summ;
                        $wholePower += $power->summ;
                    }
                    $powSumm = CashHandler::toRubles($powSumm);
                } else {
                    $powCounterValue = '--';
                    $powUsed = '--';
                    $powSumm = '--';
                }
                if (!empty($targets)) {
                    $tarSumm = 0;
                    $tarList = '';
                    foreach ($targets as $target) {
                        $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                        $tarSumm += $target->summ;
                        $wholeTarget += $target->summ;
                    }
                    $tarSumm = CashHandler::toRubles($tarSumm);
                } else {
                    $tarList = '--';
                    $tarSumm = '--';
                }
                if (!empty($singles)) {
                    $singleSumm = 0;
                    $singleList = '';
                    foreach ($singles as $single) {
                        $singleList .= CashHandler::toRubles($single->summ) . '<br/>';
                        $singleSumm += $single->summ;
                    }
                    $singleSumm = CashHandler::toRubles($singleSumm);
                } else {
                    $singleSumm = '--';
                    $singleList = '--';
                }
                if (!empty($fines)) {
                    $finesSumm = 0;
                    $finesList = '';
                    foreach ($fines as $fine) {
                        // найду информацию о пени
                        $fineInfo = Table_penalties::findOne($fine->fines_id);
                        $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                        $finesSumm += $fine->summ;
                        $wholeFines += $fine->summ;
                    }
                    $finesSumm = CashHandler::toRubles($finesSumm);
                } else {
                    $finesSumm = '--';
                    $finesList = '--';
                }
                if (!empty($discounts)) {
                    $discountSumm = 0;
                    foreach ($fromDeposit as $item) {
                        $discountSumm += $item->summ;
                    }
                } else {
                    $discountSumm = '--';
                }
                $fromDepositSumm = 0;
                if (!empty($fromDeposit)) {
                    foreach ($fromDeposit as $item) {
                        $fromDepositSumm += $item->summ;
                        $wholeDeposit -= $item->summ;
                    }
                }
                $toDepositSumm = 0;
                if (!empty($toDeposit)) {
                    foreach ($toDeposit as $item) {
                        $toDepositSumm += $item->summ;
                        $wholeDeposit += $item->summ;
                    }
                }
                $cottageInfo = CottagesHandler::get($transaction->cottage_id);
                $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm);
                $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->bill_id}</td><td class='cottage-number-cell'>{$cottageInfo->cottage_number}</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$discountSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->summ) . "</td></tr>";

            }
            $content[] = "<tr><td class='date-cell'>Итого</td><td class='bill-id-cell'></td><td class='cottage-number-cell'></td><td class='quarter-cell'></td><td class='mem-summ-cell'>" . CashHandler::toRubles($wholeMembership) . "</td><td class='pow-values'></td><td class='pow-total'></td><td class='pow-summ'>" . CashHandler::toRubles($wholePower) . "</td><td class='target-by-years-cell'></td><td class='target-total'>" . CashHandler::toRubles($wholeTarget) . "</td><td></td><td></td><td></td><td>" . CashHandler::toRubles($wholeFines) . "</td><td></td><td>" . CashHandler::toRubles($wholeDeposit) . "</td><td>" . CashHandler::toRubles($wholeSumm) . "</td></tr>";
            return ['status' => 1, 'data' => $content, 'totalSumm' => $fullSumm];
        } else {
            return ['status' => 1, 'data' => "<h2 class='text-center'>Платежей за данный период не было</h2>", 'totalSumm' => 0];
        }
    }

    /**
     * @param $interval
     * @return array
     * @throws Exception
     */
    private function getTransactions($interval): array
    {
        $results = TransactionsHandler::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        if (!empty($results)) {
            $totalSumm = 0;
            $content = "<table class='table table-striped'><thead><th>Дата платежа</th><th>Транзакция</th><th>Счёт</th><th>Участок</th><th>Сумма</th></thead><tbody>";
            $data = self::handleTransactions($results);
            $content .= $data['text'];
            $totalSumm += $data['summ'];
            $content .= "</tbody></table>";
            return ['status' => 1, 'data' => $content, 'totalSumm' => $totalSumm];
        } else {
            return ['status' => 1, 'data' => "<h2>Транзакций за период не было</h2>", 'totalSumm' => 0];
        }
    }

    /**
     * @param TransactionsHandler[] $results
     * @return array
     * @throws Exception
     */
    private static function handleTransactions(array $results)
    {
        $totalSumm = 0;
        $text = '';
        if (!empty($results)) {
            foreach ($results as $result) {
                $totalSumm += $result->summ;
                $date = TimeHandler::timestampToDate($result->bankDate);
                $summ = CashHandler::toRubles($result->summ);
                $cottageInfo = CottagesHandler::get($result->cottage_id);
                $text .= "<tr><td>$date</td><td><a target='_blank' href='/transaction/show/{$result->id}'>{$result->id}</a></td><td><a target='_blank' href='/bill/show/{$result->bill_id}'>{$result->bill_id}</a></td><td><a target='_blank' href='/cottage/show/{$cottageInfo->cottage_number}'>{$cottageInfo->cottage_number}</a></td></td><td><b class='text-info'>{$summ}</b></td></tr>";

            }
        }
        return ['text' => $text, 'summ' => $totalSumm];

    }
}