<?php

use app\models\PowerHandler;
use app\models\selection_classes\PowerPeriodInfo;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use kartik\switchinput\SwitchInput;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model PowerHandler */
$form = ActiveForm::begin(['id' => 'fillPower', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/payment-actions/fill-energy/' . $model->cottageId]]);

echo $form->field($model, 'cottageId', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);

echo "<div class='row'>";
if(!empty($model->lastData)){
    /** @var PowerPeriodInfo $item */
    foreach ($model->lastData as $item) {
        if(!empty($item->lastData)){
            if ($item->lastData->month === TimeHandler::getCurrentMonth()) {
                // данные заполнены
                echo "<div class='col-sm-12'><h2 class='text-center'>Данные счетчика №{$item->counter->id} заполнены</h2></div>";
            } else {
                echo "<h2 class='text-center'>Данные счетчика №{$item->counter->id}</h2>";
                $currentMonth = TimeHandler::getCurrentMonth();
                if ($item->lastData->month < TimeHandler::getNeighborMonth(TimeHandler::getCurrentMonth(), -1)) {
                    $prevMonth = TimeHandler::getNeighborMonth($currentMonth, -1);
                    echo "<div class='col-sm-12 form-group'><div class='col-sm-5'><label class='control-label' for=''>Месяц</label></div><div class='col-sm-7'><div class='btn-group-vertical'><a class='btn btn-default'><label><input type='radio' class='form-control' name='PowerHandler[month][{$item->lastData->counter_id}]' value='{$prevMonth}'/> " . TimeHandler::getFullFromShotMonth($prevMonth) . "</label></a><a class='btn btn-default'><label><input type='radio' class='form-control' name='PowerHandler[month][{$item->lastData->counter_id}]' value='{$currentMonth}'/> " . TimeHandler::getFullFromShotMonth($currentMonth) . "</label></a></div></div></div>";
                } else {
                    echo "<div class='col-sm-12 form-group margened'><div class='col-sm-5'><label class='control-label' for=''>Месяц</label></div><div class='col-sm-7'><div class='btn-group-vertical'><a class='btn btn-default'><label><input type='radio' class='form-control' name='PowerHandler[month][{$item->lastData->counter_id}]' value='{$currentMonth}' checked/> " . TimeHandler::getFullFromShotMonth($currentMonth) . "</label></a></div></div></div>";
                }
                echo "<div class='col-sm-12 form-group margened'><div class='col-sm-5'><label class='control-label' for=''>Показания</label></div><div class='col-sm-7'><input type='number' step='1' class='control-label' name='PowerHandler[data][{$item->lastData->counter_id}]'/><div class='hint-block'>Предыдущие показания: {$item->lastData->new_data} " . GrammarHandler::KILOWATT . "</div></div></div>";
            }
        }
        else{
            echo "<h2 class='text-center'>Данные счетчика №{$item->counter->id}</h2>";
            $currentMonth = TimeHandler::getCurrentMonth();
            // данные счётчика ни разу не заполнялись
            $prevMonth = TimeHandler::getNeighborMonth($currentMonth, -1);
            echo "<div class='col-sm-12 form-group'><div class='col-sm-5'><label class='control-label' for=''>Месяц</label></div><div class='col-sm-7'><div class='btn-group-vertical'><a class='btn btn-default'><label><input type='radio' class='form-control' name='PowerHandler[month][{$item->counter->id}]' value='{$prevMonth}'/> " . TimeHandler::getFullFromShotMonth($prevMonth) . "</label></a><a class='btn btn-default'><label><input type='radio' class='form-control' name='PowerHandler[month][{$item->counter->id}]' value='{$currentMonth}'/> " . TimeHandler::getFullFromShotMonth($currentMonth) . "</label></a></div></div></div>";
            echo "<div class='col-sm-12 form-group margened'><div class='col-sm-5'><label class='control-label' for=''>Показания</label></div><div class='col-sm-7'><input type='number' step='1' class='control-label' name='PowerHandler[data][{$item->counter->id}]'/><div class='hint-block'>Предыдущие показания: {$item->counter->last_data} " . GrammarHandler::KILOWATT . "</div></div></div>";
        }
    }

    try {
        echo $form->field($model, 'notify', ['template' =>
            '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-12 margened']])->widget(SwitchInput::class, [
            'type' => SwitchInput::CHECKBOX,
            'pluginOptions' => [
                'onText'=>'Да',
                'offText'=>'Нет',
                'handleWidth'=>20,
            ]
        ])
            ->label('Оповестить по электронной почте');

    } catch (Exception $e) {
        echo $e->getMessage();
        die('i broke');
    }
    echo "<div class='col-sm-12 form-group margened text-center'>" . Html::submitButton('Сохранить', ['class' => 'btn btn-success']) . "</div>";
}


echo "</div>";
ActiveForm::end();

