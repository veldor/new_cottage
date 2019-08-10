<?php


namespace app\models\database;


use app\models\exceptions\ExceptionWithStatus;
use app\models\selection_classes\ActivatorAnswer;
use app\models\utils\CashHandler;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $bill_id [int(10) unsigned]  Идентификатор счёта
 * @property string $destination [enum('in', 'out')]  Зачисление\списание
 * @property int $summ [bigint(20) unsigned]  Сумма операции
 * @property int $summ_before [bigint(20) unsigned]  Значение депозита участка до операции
 * @property int $summ_after [bigint(20) unsigned]  Значение депозита участка после операции
 * @property int $pay_date [bigint(20) unsigned]  Дата операции
 * @property string $description Дополнительная информация
 * @property int $transaction_id [int(10) unsigned]  Идентификатор транзакции
 */
class DepositHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "deposit_io";
    }

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public static function cottageInfo($id)
    {
        $cottageInfo = CottagesHandler::get($id);
        $answer = new ActivatorAnswer();
        $answer->status = 1;
        $answer->header = 'Сведения о движениях по депозиту';
        $depositInfo = self::find()->where(['cottage_number' => $cottageInfo->id])->all();
        if (empty($depositInfo)) {
            $answer->view = '<h2 class="text-center">Ничего не найдено</h2>';
        } else {
            $answer->view = '<table class="table table-striped"><tr><th>Тип</th><th>Сумма</th><th>Транзакция</th><th>Примечание</th></tr>';

            foreach ($depositInfo as $item) {
                $answer->view .= "<tr><td>" . ($item->destination == 'in' ? '<b class="text-success">Пополнение</b>' : '<b class="text-warning">Списание</b>') . "</td><td>" . CashHandler::toRubles($item->summ) . "</td><td><a href='/transaction/show/{$item->transaction_id}'>{$item->transaction_id}</a></td><td>{$item->description}</td></tr>";
            }

            $answer->view .= '</table>';
        }
        return $answer->return();
    }
}