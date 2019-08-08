<?php


namespace app\models;


use app\models\database\CottagesHandler;
use app\models\selection_classes\FullCottageInfo;
use yii\base\Model;

/**
 *
 * @property FullCottageInfo[] $cottagesInfo Информация об участках
 */

class MainView extends Model
{
    public $cottagesInfo;

    public function __construct($config = [])
    {
        parent::__construct($config);
        // получу все зарегистрированные участки
        $cottages = CottagesHandler::find()->all();
        if(!empty($cottages)){
            // отсортирую список по номерам участков
            usort($cottages, function($a,$b){
                return ((int)$a->cottage_number > (int)$b->cottage_number);
            });
            foreach ($cottages as $cottage) {
                $this->cottagesInfo[] = CottageInfo::fillCottageInfo($cottage);
            }
        }
    }
}