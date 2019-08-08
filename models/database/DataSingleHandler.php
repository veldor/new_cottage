<?php


namespace app\models\database;


use app\models\utils\CashHandler;
use app\models\utils\LogHandler;
use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $cottage_number [int(10) unsigned]  Идентификатор участка
 * @property int $filling_date [bigint(20)]  Дата заполнения
 * @property string $pay_description Назначение платежа
 * @property int $total_pay [bigint(20)]  Общая сумма оплаты
 * @property int $payed_summ [bigint(20)]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1)]  Платёж частично погашен
 * @property bool $is_full_payed [tinyint(1)]  Платёж полностью погашен
 * @property int $pay_up_date [bigint(20) unsigned]  Крайняя дата оплаты
 */

class DataSingleHandler extends ActiveRecord
{

    const SCENARIO_ADD = 'add';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_ADD => ['cottage_number', 'total_pay', 'pay_description'],
        ];
    }

    public function rules()
    {
        return[
            [['cottage_number', 'total_pay', 'pay_description'], 'required', 'on' => self::SCENARIO_ADD],
        ];
    }

    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "data_single";
    }

    /**
     * @param int $cottageId
     * @return DataSingleHandler[]
     */
    public static function getDuties(int $cottageId)
    {
        return self::find()->where(['cottage_number' => $cottageId, 'is_full_payed' => 0])->all();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function createPay()
    {
        if($this->total_pay > 0){
            $this->total_pay = CashHandler::fromRubles($this->total_pay);
            $this->filling_date = time();
            $this->pay_description = addslashes($this->pay_description);
            $this->save();

            LogHandler::writeLog(LogHandler::CHANGE_FINES_LOG, "участку {$this->cottage_number} выставлен разовый платёж на сумму , цель платежа: " . $this->pay_description);

            $script = "<script>makeInformerModal('Успешно', 'Зарегистрирован разовый платёж на сумму " . CashHandler::toRubles($this->total_pay) . "')</script>";
            return ['status' => 1, 'action' => "$script"];
        }
        return ['error' => 'Сумма платежа должна быть больше 0'];
    }
}