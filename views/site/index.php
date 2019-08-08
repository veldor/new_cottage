<?php

/* @var $this yii\web\View */
/* @var $model MainView */

use app\assets\MainAsset;
use app\models\database\EmailsHandler;
use app\models\MainView;
use app\models\utils\CashHandler;
use nirvana\showloading\ShowLoadingAsset;


ShowLoadingAsset::register($this);
MainAsset::register($this);

$this->title = 'Список участков';
?>
<div class="row">
    <?php
    // отображу список участков
    $counter = 1; // start counter
    if(!empty($model->cottagesInfo)){
        foreach ($model->cottagesInfo as $item) {
            // информация об участке
            $popoverContent = '';
            // получу номер участка
            $cottageNumber = $item->cottageInfo->cottage_number;
            $additionalBlock = "<div class='col-xs-12 additional-block'>";
            // если у участка есть почта
            if(EmailsHandler::isMail($item->cottageInfo->id)){
                $additionalBlock .= "<span class='glyphicon glyphicon-envelope'></span>";
            }
            $additionalBlock .= "</div>";
            $popoverContent .= "<h4>Участок $cottageNumber</h4>";

            $popoverContent .= "Депозит участка: " . CashHandler::toRubles($item->cottageInfo->deposit) . "<br/>";

            $totalDebt = $item->fullPowerDuty + $item->fullMembershipDuty + $item->fullTargetDuty + $item->fullSingleDuty;
            $popoverContent .= "<b class=\"text-info\">Задолженность</b>:&nbsp;<span class=\"text-danger\">" . CashHandler::toRubles($totalDebt) . '</span><br/>';

            if($totalDebt > 0){
                // добавлю информацию о каждом виде задолженности
                if($item->fullPowerDuty > 0){
                    $popoverContent .= '<b class="text-info">Электричество:</b>&nbsp;<span class="text-danger">' . CashHandler::toRubles($item->fullPowerDuty) . '</b>';
                    if($item->isPowerPayUp){
                        $popoverContent .= ' <b class="text-danger">Просрочено</b>';
                    }
                    $popoverContent .= '<br/>';
                }
                if($item->fullMembershipDuty > 0){
                    $popoverContent .= '<b class="text-info">Членские:</b>&nbsp;<span class="text-danger">' . CashHandler::toRubles($item->fullMembershipDuty). '</b>';
                    if($item->isMembershipPayUp){
                        $popoverContent .= ' <b class="text-danger">Просрочено</b>';
                    }
                    $popoverContent .= '<br/>';
                }
                if($item->fullTargetDuty > 0){
                    $popoverContent .= '<b class="text-info">Целевые:</b>&nbsp;<span class="text-danger">' . CashHandler::toRubles($item->fullTargetDuty). '</b>';
                    if($item->isTargetPayUp){
                        $popoverContent .= ' <b class="text-danger">Просрочено</b>';
                    }
                    $popoverContent .= '<br/>';
                }
                if($item->fullSingleDuty > 0){
                    $popoverContent .= '<b class="text-info">Разовые:</b>&nbsp;<span class="text-danger">' . CashHandler::toRubles($item->fullSingleDuty). '</b>';
                    if($item->isSinglePayUp){
                        $popoverContent .= ' <b class="text-danger">Просрочено</b>';
                    }
                    $popoverContent .= '<br/>';
                }


                if($item->isPowerPayUp || $item->isMembershipPayUp || $item->isTargetPayUp || $item->isSinglePayUp){
                    $color = 'btn-danger';
                }
                else{
                    $color = 'btn-warning';
                }
            }
            else{
                $color = 'btn-success';
            }
            if(!$item->cottageInfo->is_additional){// определю, не пропущены ли незарегистрированные участки.
                // Счётчик должен совпадать с номером участка
                ++$counter;
                while($counter <= $cottageNumber){
                    ++$counter;
                    echo "<div class='col-lg-1 col-md-2 col-sm-3 col-xs-4 text-center margened inlined'>empty</div>";
                }
            }
            echo "<div class='col-lg-1 col-md-2 col-sm-3 col-xs-4 text-center margened inlined cottage-container'  data-container='body' data-toggle='popover' data-content='$popoverContent'><a href='/cottage/show/$cottageNumber' class='cottage-button btn $color'>{$cottageNumber}$additionalBlock</a></div>";
        }
    }
    ?>
</div>
