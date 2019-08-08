<?php


namespace app\models\utils;


class DifferentUtils
{
    public static function sortArray($array, $field)
    {
        $result = [];

        foreach ($array as $item) {
            $result[$item[$field]] = $item;
        }
        return $result;
    }
}