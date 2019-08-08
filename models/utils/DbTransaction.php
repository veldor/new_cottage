<?php


namespace app\models\utils;


use app\models\exceptions\ExceptionWithStatus;
use Yii;
use yii\db\Exception;

class DbTransaction
{
    /**
     * @var \yii\db\Transaction
     */
    private $transaction;

    public function __construct()
    {
        $db = Yii::$app->db;
        $this->transaction = $db->beginTransaction();
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function commitTransaction(){
        try {
            $this->transaction->commit();
        } catch (Exception $e) {
            throw new ExceptionWithStatus('Ошибка работы с базой данных: ' . $e->getMessage(), 2);
        }
    }

    /**
     *
     */
    public function rollbackTransaction(){
        $this->transaction->rollBack();
    }
}