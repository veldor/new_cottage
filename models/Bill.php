<?php


namespace app\models;


use app\models\database\BillFinesHandler;
use app\models\database\BillMembershipHandler;
use app\models\database\BillPowerHandler;
use app\models\database\BillsHandler;
use app\models\database\BillSingleHandler;
use app\models\database\BillTargetHandler;
use app\models\database\ContactsHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataSingleHandler;
use app\models\database\DataTargetHandler;
use app\models\database\DepositHandler;
use app\models\database\DiscountHandler;
use app\models\database\FinesHandler;
use app\models\database\RegistredCountersHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\CashHandler;
use app\models\utils\DbTransaction;
use app\models\utils\DifferentUtils;
use app\models\utils\EmailHandler;
use Exception;
use yii\base\Model;

class Bill extends Model
{
// СЦЕНАРИИ
    const SCENARIO_CREATE = 'create';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CREATE => ['cottageId', 'targetOwner', 'deposit', 'discount', 'discountReason', 'power', 'membership', 'target', 'single', 'fines', 'notify', 'print'],
        ];
    }

    public $cottageId;
    public $targetOwner;
    public $deposit;
    public $discount;
    public $discountReason;
    public $power;
    public $membership;
    public $target;
    public $single;
    public $fines;

    public $notify = 0;
    public $print = 0;
    /**
     * @var FinesHandler[]
     */
    public $finesData;
    /**
     * @var CottagesHandler
     */
    public $cottageInfo;
    /**
     * @var array
     */
    public $ownersList;
    /**
     * @var array
     */
    public $powerData;
    /**
     * @var DataMembershipHandler[]
     */
    public $membershipData;
    /**
     * @var DataTargetHandler[]
     */
    public $targetData;
    /**
     * @var DataSingleHandler[]
     */
    public $singleData;

    /**
     * @throws ExceptionWithStatus
     */
    public function fill()
    {
        $this->cottageInfo = CottagesHandler::findOne($this->cottageId);
        $cottageId = null;
        // получу список владельцев
        if ($this->cottageInfo->is_additional) {
            if ($this->cottageInfo->is_different_owner) {
                $cottageId = $this->cottageId;
            } else {
                $cottageId = $this->cottageInfo->main_cottage_id;
            }
        } else {
            $cottageId = $this->cottageId;
        }
        $owners = ContactsHandler::find()->where(['cottage_id' => $cottageId, 'is_owner' => 1, 'is_active' => 1])->all();
        if (empty($owners)) {
            throw new ExceptionWithStatus('Не найдены владельцы участка. Перед выставлением счёта зарегистрируйте хотя бы одного.');
        }
        $ownersList = [];
        foreach ($owners as $owner) {
            $ownersList[$owner->id] = $owner->contact_name;
        }
        $this->ownersList = $ownersList;

        // электроэнергия
        if ($this->cottageInfo->is_power) {
            $powerData = [];
            // найду зарегистрированные счётчики электроэнергии
            $powerCounters = RegistredCountersHandler::find()->where(['cottage_id' => $this->cottageInfo->id, 'is_active' => 1])->all();
            foreach ($powerCounters as $powerCounter) {
                // найду неоплаченные платежи по данному счётчику
                $pays = DataPowerHandler::find()->where(['counter_id' => $powerCounter->id, 'is_full_payed' => 0])->all();
                if (!empty($pays)) {
                    $pays = DifferentUtils::sortArray($pays, 'month');
                    $powerData[$powerCounter->id] = $pays;
                }
            }
            $this->powerData = $powerData;
        }
        // членские взносы
        if ($this->cottageInfo->is_membership) {
            $this->membershipData = DataMembershipHandler::find()->where(['cottage_number' => $this->cottageId, 'is_full_payed' => 0])->orderBy('quarter')->all();
        }
        // целевые взносы
        if ($this->cottageInfo->is_target) {
            $this->targetData = DataTargetHandler::find()->where(['cottage_number' => $this->cottageId, 'is_full_payed' => 0])->orderBy('year')->all();
        }
        // разовые взносы
        $this->singleData = DataSingleHandler::find()->where(['cottage_number' => $this->cottageId, 'is_full_payed' => 0])->orderBy('filling_date')->all();
        $this->finesData = FinesHandler::find()->where(['cottage_number' => $cottageId])->all();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function createBill()
    {
        $transaction = new DbTransaction();
        try {
            $fullSumm = 0;
            // Создам новый счёт
            $newBill = new BillsHandler();
            $newBill->time_create = time();
            // проверю зависимости
            $cottageInfo = CottagesHandler::findOne($this->cottageId);
            $mainCottage = null;
            if ($cottageInfo->is_additional && !$cottageInfo->is_different_owner) {
                $cottageInfo = CottagesHandler::findOne($cottageInfo->main_cottage_id);
            } else {
                $newBill->cottage_number = $this->cottageId;
            }
            $additionalCottageInfo = CottagesHandler::getAdditionalCottage($cottageInfo);
            $newBill->cottage_number = $cottageInfo->id;
            $newBill->payerId = $this->targetOwner;
            $newBill->save();
            if (!empty($this->deposit)) {
                // конвертирую
                $this->deposit = CashHandler::fromRubles($this->deposit);
                // проверю, что на счету участка достаточно средств
                $newBill->from_deposit = $this->deposit;
                if ($cottageInfo->deposit < $newBill->from_deposit) {
                    $transaction->rollbackTransaction();
                    return ['error' => 'Недостаточно средств на депозите'];
                }
                $depositRegistration = new DepositHandler();
                $depositRegistration->cottage_number = $cottageInfo->id;
                $depositRegistration->bill_id = $newBill->id;
                $depositRegistration->destination = 'out';
                $depositRegistration->summ = $newBill->from_deposit;
                $depositRegistration->summ_before = $cottageInfo->deposit;
                $cottageInfo->deposit -= $newBill->from_deposit;
                $cottageInfo->save();
                $depositRegistration->summ_after = $cottageInfo->deposit;
                $depositRegistration->description = "Использование депозита для оплаты членских взносов";
                $depositRegistration->save();
            }
            if (!empty($this->discount)) {
                // конвертирую
                $this->discount = CashHandler::fromRubles($this->discount);
                // зарегистрирую скидку
                $discountRegistration = new DiscountHandler();
                $discountRegistration->cottage_number = $cottageInfo->id;
                $discountRegistration->bill_id = $newBill->id;
                $discountRegistration->summ = $this->discount;
                $discountRegistration->reason = addslashes($this->discountReason);
                $discountRegistration->save();
            }
            // теперь перехожу к регистрации платежей
            if (!empty($this->power)) {
                foreach ($this->power as $counter => $value) {
                    foreach ($value as $month => $powerData) {
                        // проверю цену
                        $amount = CashHandler::fromRubles($powerData['value']);
                        // найду месяц оплаты
                        $powerMonthData = DataPowerHandler::findOne($month);
                        // проверю, что период относится к данному участку или к дополнительному
                        if ($powerMonthData->cottage_number == $cottageInfo->id || (!empty($additionalCottageInfo) && $powerMonthData->cottage_number == $additionalCottageInfo->id)) {
                            $requiredAmount = $powerMonthData->total_pay - $powerMonthData->payed_summ;
                            if ($amount > $requiredAmount) {
                                $transaction->rollbackTransaction();
                                return ['error' => 'Сумма по месяцу #' . $powerMonthData->id . 'превышает необходимую'];
                            }
                            $newBillPower = new BillPowerHandler();
                            $newBillPower->bill_id = $newBill->id;
                            $newBillPower->power_data_id = $powerMonthData->id;
                            $newBillPower->start_summ = $amount;
                            $newBillPower->save();
                            $fullSumm += $amount;
                        } else {
                            $transaction->rollbackTransaction();
                            return ['error' => 'Месяц #' . $powerMonthData->id . 'не относится к данному участку'];
                        }
                    }
                }
            }
            if (!empty($this->membership)) {
                foreach ($this->membership as $id => $value) {
                    // проверю цену
                    $amount = CashHandler::fromRubles($value['value']);
                    // найду квартал оплаты
                    $membershipData = DataMembershipHandler::findOne($id);
                    // проверю, что период относится к данному участку или к дополнительному
                    if ($membershipData->cottage_number == $cottageInfo->id || $membershipData->cottage_number == $cottageInfo->main_cottage_id) {
                        $requiredAmount = $membershipData->total_pay - $membershipData->payed_summ;
                        if ($amount > $requiredAmount) {
                            $transaction->rollbackTransaction();
                            return ['error' => 'Сумма по кварталу #' . $membershipData->id . 'превышает необходимую'];
                        }
                        $newBillMembership = new BillMembershipHandler();
                        $newBillMembership->bill_id = $newBill->id;
                        $newBillMembership->membership_data_id = $membershipData->id;
                        $newBillMembership->start_summ = $amount;
                        $newBillMembership->save();
                        $fullSumm += $amount;
                    } else {
                        $transaction->rollbackTransaction();
                        return ['error' => 'Квартал #' . $membershipData->id . 'не относится к данному участку'];
                    }
                }
            }
            if (!empty($this->target)) {
                foreach ($this->target as $id => $value) {
                    // проверю цену
                    $amount = CashHandler::fromRubles($value['value']);
                    // найду год оплаты
                    $targetData = DataTargetHandler::findOne($id);
                    // проверю, что период относится к данному участку или к дополнительному
                    if ($targetData->cottage_number == $cottageInfo->id || $targetData->cottage_number == $cottageInfo->main_cottage_id) {
                        $requiredAmount = $targetData->total_pay - $targetData->payed_summ;
                        if ($amount > $requiredAmount) {
                            $transaction->rollbackTransaction();
                            return ['error' => 'Сумма по году #' . $targetData->id . 'превышает необходимую'];
                        }
                        $newBillTarget = new BillTargetHandler();
                        $newBillTarget->bill_id = $newBill->id;
                        $newBillTarget->year_id = $targetData->id;
                        $newBillTarget->start_summ = $amount;
                        $newBillTarget->save();
                        $fullSumm += $amount;
                    } else {
                        $transaction->rollbackTransaction();
                        return ['error' => 'Год #' . $targetData->id . 'не относится к данному участку'];
                    }
                }
            }
            if (!empty($this->single)) {
                foreach ($this->single as $id => $value) {
                    // проверю цену
                    $amount = CashHandler::fromRubles($value['value']);
                    // найду год оплаты
                    $singleData = DataSingleHandler::findOne($id);
                    // проверю, что период относится к данному участку или к дополнительному
                    if ($singleData->cottage_number == $cottageInfo->id || $singleData->cottage_number == $cottageInfo->main_cottage_id) {
                        $requiredAmount = $singleData->total_pay - $singleData->payed_summ;
                        if ($amount > $requiredAmount) {
                            $transaction->rollbackTransaction();
                            return ['error' => 'Сумма по разовому платежу #' . $singleData->id . 'превышает необходимую'];
                        }
                        $newBillTarget = new BillSingleHandler();
                        $newBillTarget->bill_id = $newBill->id;
                        $newBillTarget->single_id = $singleData->id;
                        $newBillTarget->start_summ = $amount;
                        $newBillTarget->save();
                        $fullSumm += $amount;
                    } else {
                        $transaction->rollbackTransaction();
                        return ['error' => 'Разовый платёж #' . $singleData->id . 'не относится к данному участку'];
                    }
                }
            }
            if (!empty($this->fines)) {
                foreach ($this->fines as $id => $value) {
                    // проверю цену
                    $amount = CashHandler::fromRubles($value['value']);
                    // найду год оплаты
                    $finesData = FinesHandler::findOne($id);
                    // проверю, что период относится к данному участку или к дополнительному
                    if ($finesData->cottage_number == $cottageInfo->id || $finesData->cottage_number == $cottageInfo->main_cottage_id) {
                        $requiredAmount = $finesData->summ - $finesData->payed_summ;
                        if ($amount > $requiredAmount) {
                            $transaction->rollbackTransaction();
                            return ['error' => 'Сумма по пени #' . $finesData->id . 'превышает необходимую (' . $amount . ' => ' . $requiredAmount . ')'];
                        }
                        $newBillFines = new BillFinesHandler();
                        $newBillFines->bill_id = $newBill->id;
                        $newBillFines->fines_id = $finesData->id;
                        $newBillFines->start_summ = $amount;
                        $newBillFines->save();
                        $fullSumm += $amount;
                    } else {
                        $transaction->rollbackTransaction();
                        return ['error' => 'Пени #' . $finesData->id . 'не относится к данному участку'];
                    }
                }
            }
            $newBill->from_deposit = $this->deposit ?? 0;
            $newBill->discount = $this->discount ?? 0;
            if($newBill->from_deposit > 0 || $newBill->discount > 0){
                $newBill->is_undistributed = 1;
            }
            $newBill->bill_summ = $fullSumm;
            $newBill->payed = $newBill->from_deposit + $newBill->discount;
            //todo сделать проверку нераспределённых среств и напоминать о необходимости распределения
            $newBill->save();
            if ($this->notify) {
                EmailHandler::sendBIllInfo($newBill->id);
            }
            $action = '';
            if ($this->print) {
                $action = "<script>let win = window.open('/print/bill/{$newBill->id}', '_blank');win.focus();</script>";
            }
            $transaction->commitTransaction();
            return ['title' => "Счёт $newBill->id создан.", 'message' => 'Просмотреть информацию о счёте вы можете в разделе "Счета"' . $action];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }
}