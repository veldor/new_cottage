<?php


namespace app\models\selection_classes;

use app\models\database\BillsHandler;
use app\models\database\TransactionsHandler;

/**
 *
 * @property string $type
 * @property string $period
 */

class FineDetails
{
    public $type;
    public $period;
}