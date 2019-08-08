<?php


namespace app\models\utils;


use app\models\database\ContactsHandler;

class GrammarHandler
{

    public const COTTAGE_PERSONALS_PRESET = '%USERNAME%';
    public const COTTAGE_FULL_PERSONALS_PRESET = '%FULLUSERNAME%';
    public const COTTAGE_NUMBER_PRESET = '%COTTAGENUMBER%';
    public const REGISTRIATION_INFO_PRESET = '%REGINFO%';
    public const DUTY_INFO_PRESET = '%DUTYINFO%';

    const KILOWATT = 'кВт⋅ч';
    const RUBLE = '‎₽';

    public static function clearAddress($address){
        if(!empty($address)){
            // разобью адрес на категории
            $result = explode('&', $address);
            if(count($result) === 5){
                $return = '';
                foreach ($result as $key => $item) {
                    if(!empty($item)){
                        if($key === 3){
                            $return .= 'дом ' . trim($item) . ' ';
                        }
                        elseif($key === 4){
                            $return .= 'квартира ' . trim($item) . ' ';
                        }
                        else{
                            $return .= trim($item) . ' ';
                        }
                    }
                }
                return trim($return);
            }
            return trim($address);
        }
        return null;
    }

    public static function handleLexemes($body, ContactsHandler $contact)
    {
        // заменю вхождения имени
        if(strpos($body, self::COTTAGE_PERSONALS_PRESET)){
            $personals = self::handlePersonals($contact->contact_name);
            $body = str_replace(self::COTTAGE_PERSONALS_PRESET, $personals, $body);
        }
        return $body;
    }

    /**
     * @param string $contact_name
     * @return string
     */
    private static function handlePersonals(string $contact_name)
    {
        if($data = self::personalsToArray($contact_name)){
            if (is_array($data)){
                return "{$data['name']} {$data['fname']}";
            }
            else{
                return $data;
            }

        }
        return $contact_name;
    }

    /**
     * @param string $string
     * @return array|string
     */
    public static function personalsToArray(string $string){
        // извлекаю имя и отчество из персональных данных
        $result = explode(' ', $string);
        if(count($result) === 3){
            return ['lname' => $result[0], 'name' => $result[1], 'fname' => $result[2]];
        }
        return $string;
    }
    public static function clearNumber($num){
        return urlencode($num);
    }
}