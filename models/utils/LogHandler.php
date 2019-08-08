<?php


namespace app\models\utils;


use yii\base\Model;
use \Exception;

class LogHandler extends Model
{
    // виды логов
    const CHANGE_INFO_LOG = 'info_changes';
    const CHANGE_BASE_LOG = 'cottage_base_changes';
    const CHANGE_POWER_LOG = 'power';
    const CHANGE_FINES_LOG = 'fines';

    /**
     * @param string $logName
     * @param string $text
     * @throws Exception
     */
    public static function writeLog($logName, $text)
    {
        // сформирую имя файла
        $now = time();
        $logName = $logName . '_' . TimeHandler::timestampToShortDate($now) . '.txt';
        file_put_contents('Z:/cottage_logs/' . $logName, TimeHandler::timestampToShortTime($now) . ' ' . $text . "\r\n", FILE_APPEND);
    }
}