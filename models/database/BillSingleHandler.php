<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]  Идентификатор
 * @property int $bill_id [int(10) unsigned]
 * @property int $single_id [int(10) unsigned]
 * @property int $start_summ [int(10) unsigned]
 */

class BillSingleHandler extends ActiveRecord
{
    // имя таблицы
    /**
     * @return string
     */
    public static function tableName()
    {
        return "bill_single";
    }
}