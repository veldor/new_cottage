<?php


namespace app\models\utils;

use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\PayedFinesHandler;
use app\models\database\PayedMembershipHandler;
use app\models\database\PayedPowerHandler;
use app\models\database\PayedSingleHandler;
use app\models\database\PayedTargetHandler;
use app\models\database\RegistredCountersHandler;
use app\models\database\TransactionsHandler;

class Fix
{
    public static function fix(){
        $payedPower = PayedPowerHandler::find()->all();
        foreach ($payedPower as $item) {
            // найду транзакцию по платежу
            $trans = TransactionsHandler::findOne(['id' => $item->transaction_id]);
            $item->bill_id = $trans->bill_id;
            $counter = RegistredCountersHandler::find()->where(['cottage_id' => $trans->cottage_id])->all();
            foreach ($counter as $counterItem) {
                $item->counter_id = $counterItem->id;
                // найду идентификатор периода
                $period = DataPowerHandler::findOne(['counter_id' => $counterItem->id, 'month' => $item->month]);
                if(!empty($period)){
                    $item->period_id = $period->id;
                    break;
                }
            }
            $item->cottage_id = $trans->cottage_id;
            $item->save();
        }
        $payedMembership = PayedMembershipHandler::find()->all();
        foreach ($payedMembership as $item) {
            $trans = TransactionsHandler::findOne(['id' => $item->transaction_id]);
            $item->bill_id = $trans->bill_id;
            $period = DataMembershipHandler::findOne(['cottage_number' => $trans->cottage_id, 'quarter' => $item->quarter]);
            $item->period_id = $period->id;
            $item->cottage_id = $trans->cottage_id;
            $item->save();
        }
        $payedTarget = PayedTargetHandler::find()->all();
        foreach ($payedTarget as $item) {
            $trans = TransactionsHandler::findOne(['id' => $item->transaction_id]);

            $item->bill_id = $trans->bill_id;
            $period = DataTargetHandler::findOne(['cottage_number' => $trans->cottage_id, 'year' => $item->year]);
            $item->period_id = $period->id;
            $item->cottage_id = $trans->cottage_id;
            $item->save();
        }
        $payedSingle = PayedSingleHandler::find()->all();
        foreach ($payedSingle as $item) {
            $trans = TransactionsHandler::findOne(['id' => $item->transaction_id]);
            $item->bill_id = $trans->bill_id;
            $item->cottage_id = $trans->cottage_id;
            $single = DataSingleHandler::find()->where(['filling_date' => $item->pay_id])->one();
            $item->pay_id = $single->id;
            $item->save();
        }
        $payedFines = PayedFinesHandler::find()->all();
        foreach ($payedFines as $item) {
            $trans = TransactionsHandler::findOne(['id' => $item->transaction_id]);
            $item->bill_id = $trans->bill_id;
            $item->save();
        }
    }
}