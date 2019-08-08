<?php


namespace app\models;


use app\models\database\BankTransactionsHandler;
use app\models\database\TransactionsHandler;

class ComparisonHandler extends Model
{

    const SCENARIO_MANUAL_COMPARISON = 'manual comparison';
    public $bankTransactionId;
    public $transactionId;


    public function scenarios(): array
    {
        return [
            self::SCENARIO_MANUAL_COMPARISON => ['bankTransactionId', 'transactionId'],
        ];
    }

    /**
     * @return array
     * @throws exceptions\ExceptionWithStatus
     */
    public function manualCompare()
    {
        $transactionInfo = TransactionsHandler::get($this->transactionId);
        $bankTransactionInfo = BankTransactionsHandler::get($this->bankTransactionId);
        $bankTransactionInfo->bounded_transaction_id = $transactionInfo->id;
        $bankTransactionInfo->save();
        return ['status' => 1, 'message' => "Транзакции успешно связаны"];
    }

}