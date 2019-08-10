<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection HtmlUnknownAnchorTarget */

use app\assets\CottageShowAsset;
use app\models\CottageInfo;
use app\models\database\ContactsHandler;
use app\models\database\CottagesHandler;
use app\models\database\DataMembershipHandler;
use app\models\database\DataPowerHandler;
use app\models\database\DataTargetHandler;
use app\models\exceptions\ExceptionWithStatus;
use app\models\utils\CashHandler;
use app\models\utils\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

/* @var $this View */
/** @var CottageInfo $info */

CottageShowAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = $info->cottageInfo->cottageInfo->cottage_number . ' участок';
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="active in"><a href="#main_tab" data-toggle="tab">Информация</a></li>
    <li><a href="#contacts" data-toggle="tab">Контакты</a></li>
    <li><a href="#power" data-toggle="tab">Электроэнергия</a></li>
    <li><a href="#membership" data-toggle="tab">Членские</a></li>
    <li><a href="#target" data-toggle="tab">Целевые</a></li>
    <li><a href="#single" data-toggle="tab">Разовые</a></li>
    <li><a href="#bills" data-toggle="tab">Счета</a></li>
    <li><a href="#fines" data-toggle="tab">Пени</a></li>
</ul>

<h1 class="text-center">Участок <?= $info->cottageInfo->cottageInfo->cottage_number ?>
    (#<?= $info->cottageInfo->cottageInfo->id ?>)</h1>
<div class="input-group margened col-sm-2 col-lg-offset-5"><label for="goToCottageInput"></label><input type="text"
                                                                                                        id="goToCottageInput"
                                                                                                        class="form-control"
                                                                                                        value="<?= $info->cottageInfo->cottageInfo->cottage_number ?>"><span
            class="input-group-btn"><button class="btn btn-default" type="button" id="goToCottageActivator"><span
                    class="glyphicon glyphicon-play"></span></button></span></div>

<!-- Tab panes -->
<div class="tab-content">
    <div class="tab-pane active in" id="main_tab">
        <table class='table table-hover table-striped table-condensed'>
            <tr>
                <td class="caption" colspan="4">Информация о взносах</td>
            </tr>
            <?php

            if ($info->cottageInfo->cottageInfo->is_individual_tariff) {
                $text = "<b class='text-warning'>Индивидуальный</b>";
            } else {
                $text = "<b class='text-success'>Общий</b>";
            }
            echo "<tr>
                    <td>Тариф</td>
                    <td>$text</td>
                </tr>";
            if ($info->cottageInfo->cottageInfo->is_power) {
                // информация об электроэнергии
                $text = $info->cottageInfo->fullPowerDuty > 0 ? "<a href='#power' class='btn btn-danger emulate-tab'>Задолженность " . CashHandler::toRubles($info->cottageInfo->fullPowerDuty) . "</a>" : "<a id='powerLink' href='#power' class='btn btn-success emulate-tab'>Оплачено</a>";
                echo "<tr>
                    <td>Электроэнергия</td>
                    <td>$text</td>
                </tr>";
            } else {
                echo "
                    <tr>
                    <td>Электроэнергия</td>
                    <td>Не оплачивается</td>
                    </tr>
                ";
            }
            if ($info->cottageInfo->cottageInfo->is_membership) {
                // информация об членских взносах
                $text = $info->cottageInfo->fullMembershipDuty > 0 ? "<a href='#membership' class='btn btn-danger emulate-tab'>Задолженность " . CashHandler::toRubles($info->cottageInfo->fullMembershipDuty) . "</a>" : "<a href='#membership' class='btn btn-success emulate-tab'>Оплачено</a>";
                echo "<tr>
                    <td>Членские взносы</td>
                    <td>$text</td>
                </tr>";
            } else {
                echo "
                    <tr>
                    <td>Членские взносы</td>
                    <td>Не оплачиваются</td>
                    </tr>
                ";
            }
            if ($info->cottageInfo->cottageInfo->is_target) {
                // информация об целевых взносах
                $text = $info->cottageInfo->fullTargetDuty > 0 ? "<a href='#target' class='btn btn-danger emulate-tab'>Задолженность " . CashHandler::toRubles($info->cottageInfo->fullTargetDuty) . "</a>" : "<a href='#target' class='btn btn-success emulate-tab'>Оплачено</a>";
                echo "<tr>
                    <td>Целевые взносы</td>
                    <td>$text</td>
                </tr>";
            } else {
                echo "
                    <tr>
                    <td>Целевые взносы</td>
                    <td>Не оплачиваются</td>
                    </tr>
                ";
            }
            // информация об разовых взносах
            $text = $info->cottageInfo->fullSingleDuty > 0 ? "<a href='#single' class='btn btn-danger emulate-tab'>Задолженность " . CashHandler::toRubles($info->cottageInfo->fullSingleDuty) . "</a>" : "<a href='#single' class='btn btn-success emulate-tab'>Оплачено</a>";
            echo "<tr>
                    <td>Разовые взносы</td>
                    <td>$text</td>
                </tr>";
            // пени
            $finesSumm = 0;
            if (!empty($info->finesData)) {
                $finesSumm = 0;
                foreach ($info->finesData as $fine) {
                    if ($fine->is_enabled) {
                        $finesSumm += $fine->summ - $fine->payed_summ;
                    }
                }
                $text = "<a id='finesLink' href='#fines' class='btn btn-danger emulate-tab'>Задолженность " . CashHandler::toRubles($finesSumm) . "</a>";
            } else {
                $text = "<a href='#fines' class='btn btn-success emulate-tab'>Задолженностей нет</a>";
            }
            echo "<tr>
                    <td>Пени</td>
                    <td>$text</td>
                </tr>";
            $fullDebt = $info->cottageInfo->fullPowerDuty + $info->cottageInfo->fullMembershipDuty + $info->cottageInfo->fullSingleDuty + $info->cottageInfo->fullTargetDuty + $finesSumm;
            if ($fullDebt > 0) {
                $text = '<b class="text-danger">' . CashHandler::toRubles($fullDebt) . '</b>';
            } else {
                $text = '<b class="text-success">Отсутствует</b>';
            }
            echo " <tr>
                    <td>Общая задолженность</td>
                    <td><b id='fullDutyText' class='text-danger'>" . $text . "</b></td>
                </tr>";
            ?>
            <tr>
                <td class="caption" colspan="4">Информация об участке</td>
            </tr>
            <?php
            // обозначу тип участка
            if ($info->cottageInfo->cottageInfo->is_additional) {
                if ($info->cottageInfo->cottageInfo->is_different_owner) {
                    $text = '<b class="text-info">Дополнительный с отдельным владельцем</b>';
                } else {
                    $mainCottageNumber = CottagesHandler::getNumberById($info->cottageInfo->cottageInfo->main_cottage_id);
                    $text = '<b class="text-info">Дополнительный к участку <a href="/cottage/show/' . $mainCottageNumber . '">' . $mainCottageNumber . '</a></b>';
                }
            } else {
                $additionalCottage = CottagesHandler::getAdditionalCottage($info->cottageInfo->cottageInfo->id);
                if (!empty($additionalCottage)) {
                    $text = '<b class="text-info">Основной, имеется дополнительный <a href="/cottage/show/' . $additionalCottage->cottage_number . '">' . $additionalCottage->cottage_number . '</a></b>';
                } else {
                    $text = '<b class="text-info">Основной</b>';
                }

            }

            echo "<tr>
                        <td>Тип участка</td>
                        <td>$text</td>
                       </tr>";
            // площадь участка
            echo "<tr>
                        <td>Площадь участка</td>
                        <td><b class='text-success' id='squareWrapper'>{$info->cottageInfo->cottageInfo->square} м<sup>2</sup></b> <button class='btn btn-primary pull-right control-container control-element hidden' data-type='edit-cottage' data-action='change-square' data-cottage-id='{$info->cottageInfo->cottageInfo->id}'><span class='glyphicon glyphicon-pencil'></span></button></td>
                       </tr>";
            if (!$info->cottageInfo->cottageInfo->is_additional || $info->cottageInfo->cottageInfo->is_different_owner) {
                // депозит участка
                echo "<tr>
                        <td>Депозит участка</td>
                        <td><a href='#' class='activator' data-action='/info/deposit/{$info->cottageInfo->cottageInfo->id}'><b id='depositWrapper' class='text-success'>" . CashHandler::toRubles($info->cottageInfo->cottageInfo->deposit) . "</b></a> <button class='btn btn-primary pull-right control-container control-element hidden' data-type='edit-cottage' data-action='change-deposit' data-cottage-id='{$info->cottageInfo->cottageInfo->id}'><span class='glyphicon glyphicon-pencil'></span></button></td>
                       </tr>";
            }
            echo "<tr>
                        <td>Справка о праве собственности</td>
                        <td>" . ($info->cottageInfo->cottageInfo->is_have_property_rights ? '<b id="haveRightsData" class="text-success">В наличии</b>' : '<b id="haveRightsData" class="text-danger">Отсутствует</b>') . " <button class='btn btn-primary control-container pull-right hidden control-element'  data-type='edit-cottage' data-action='switch-rights' data-cottage-id='{$info->cottageInfo->cottageInfo->id}'><span class='glyphicon glyphicon-transfer'></span></button></td>
                       </tr>";
            $hidden = '';
            if (!$info->cottageInfo->cottageInfo->is_have_property_rights) {
                $hidden = 'hidden';
            }
            echo "<tr id='rightsDataContainer' class='$hidden'>
                        <td>Данные о праве собственности</td>
                        <td><div id='rightsDataWrapper'>{$info->cottageInfo->cottageInfo->property_data}</div><button class='btn btn-primary control-container pull-right hidden control-element'  data-type='edit-cottage' data-action='change-rights' data-cottage-id='{$info->cottageInfo->cottageInfo->id}'><span class='glyphicon glyphicon-pencil'></span></button></td>
                       </tr>";
            echo "<tr>
                        <td>Наличие данных для реестра</td>
                        <td>" . ($info->cottageInfo->cottageInfo->is_cottage_register_data ? '<b id="haveRegisterData" class="text-success">В наличии</b>' : '<b id="haveRegisterData" class="text-danger">Отсутствуют</b>') . " <button class='btn btn-primary control-container pull-right hidden control-element'  data-type='edit-cottage' data-action='switch-register' data-cottage-id='{$info->cottageInfo->cottageInfo->id}'><span class='glyphicon glyphicon-transfer'></span></button></td>
                       </tr>";
            if ($info->cottageInfo->cottageInfo->is_cottage_register_data) {
                echo "<tr>
                        <td>Данные реестра</td>
                        <td>{$info->cottageInfo->cottageInfo->register_data}</td>
                       </tr>";
            }
            ?>
        </table>
    </div>
    <div class="tab-pane" id="contacts">
        <?php
        try {
            echo ContactsHandler::getContactsTable($info->cottage->id);
        } catch (ExceptionWithStatus $e) {
        }
        ?>
    </div>
    <div class="tab-pane" id="power">
        <?php
            echo "<table class='table table-hover table-striped table-condensed'>
                        <caption>Счётчики электрэнергии</caption>
                        <tr>
                            <th>Номер</th>
                            <th>Последние показания</th>
                            <th>Активен</th>
                            <th>Действия</th>
                        </tr>
                    ";
        if (!empty($info->powerCounters)) {
            // покажу список счётчиков с возможностью управления
            foreach ($info->powerCounters as $counter) {
                $activity = $counter->is_active ? "<span class='text-success'>Да</span>" : "<span class='text-warning'>Нет</span>";
                $onOffBtn = $counter->is_active ? "<a class='btn btn-warning tooltiped control-element' data-toggle='tooltip' data-placement='auto' title='Деактивировать' data-type='custom-edit' data-action='counter/disable' data-id='{$counter->id}' data-send-post='1'><span class='glyphicon glyphicon-off'></span></a>" : "<a class='btn btn-success tooltiped control-element' data-toggle='tooltip' data-placement='auto' title='Активировать'  data-type='custom-edit' data-action='counter/enable' data-id='{$counter->id}' data-send-post='1'><span class='glyphicon glyphicon-off'></span></a>";
                echo "<tr>
                            <td>{$counter->id}</td>
                            <td>{$counter->last_data}</td>
                            <td>$activity</td>
                            <td>
                                $onOffBtn
                                <a class='btn btn-danger tooltiped control-element' data-toggle='tooltip' data-placement='auto' title='Удалить' data-type='custom-edit' data-action='counter/delete' data-id='{$counter->id}' data-send-post='1'><span class='glyphicon glyphicon-trash'></span></a>
                            </td>
                       </tr>";
            }
            }
            echo "</table>";

            echo "<div class='text-center'><a class='btn btn-default control-element'  data-type='custom-edit' data-action='counter/add' data-id='{$info->cottage->id}'><span class='text-success'><span class='glyphicon glyphicon-plus'></span> Добавить счётчик</span></a></div>";
        if (!empty($info->powerData)) {
            echo "<table class='table table-hover table-striped table-condensed'>
                        <caption>Показания</caption>
                        <tr>
                            <th>Месяц</th>
                            <th>ID</th>
                            <th>Старт</th>
                            <th>Финиш</th>
                            <th>Сумма</th>
                            <th>Оплачено</th>
                        </tr>
                    ";
            foreach ($info->powerData as $item) {

                $textColor = $item->is_partial_payed ? 'color-info' : $item->is_full_payed ? 'text-success' : 'text-warning';

                echo "
                            <tr id='power_item_{$item->id}'>
                                <td><a class='activator' href='#' data-action='/info/power/{$item->id}'>{$item->month}</a></td>
                                <td>{$item->counter_id}</td>
                                <td>{$item->old_data} кВт·ч</td>
                                <td>{$item->new_data} кВт·ч</td>
                                <td><b class='text-danger'>" . CashHandler::toRubles($item->total_pay) . "</b></td>
                                <td><b class='$textColor'>" . CashHandler::toRubles($item->payed_summ) . "</b></td>
                                <td>
                                    <div class='btn-group'>
                                      <button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>Действия   <span class='caret'></span></button>
                                      <ul class='dropdown-menu' role='menu'>
                                        <li><a class='activator' data-action='/power/change/$item->id' href='#'>Изменить данные</a></li>
                                        <li><a class='post-activator' data-type='edit-data' data-action='/power/delete/{$item->id}' href='#'>Удалить данные</a></li>
                                      </ul>
                                    </div>
                                </td>
                            </tr>
                    ";
            }
            echo "</table>";
        } else {
            echo "<h2>Данных по электроэнергии не найдено</h2>";
        }
        ?>
    </div>
    <div class="tab-pane" id="membership">

        <?php

        if($info->cottage->is_membership){
            echo "<div class='text-center margened'><a class='btn btn-default control-element'  data-type='custom-edit' data-action='membership/change-period' data-id='{$info->cottage->id}'><span class='text-success'><span class='glyphicon glyphicon-pencil'></span> Изменить период оплаты</span></a></div>";

            if (!empty($info->membershipData)) {
                echo "<table class='table table-hover table-striped table-condensed'>
                        <tr>
                            <th>Квартал</th>
                            <th>Площадь</th>
                            <th>Сумма</th>
                            <th>Оплачено</th>
                            <th>Долг</th>
                        </tr>
                    ";
                foreach ($info->membershipData as $item) {

                    $textColor = $item->is_partial_payed ? 'color-info' : $item->is_full_payed ? 'text-success' : 'text-warning';
                    $duty = $item->total_pay - $item->payed_summ;
                    $dutyText = '<b class="text-success">0</b>';
                    if ($duty > 0) {
                        $dutyText = "<b class='text-danger'>" . CashHandler::toRubles($duty) . "</b>";
                    }

                    echo "
                            <tr>
                                <td>{$item->quarter}</td>
                                <td>{$item->square}</td>
                                <td><b class='text-danger'>" . CashHandler::toRubles($item->total_pay) . "</b></td>
                                <td><b class='$textColor'>" . CashHandler::toRubles($item->payed_summ) . "</b></td>
                                <td>$dutyText</td>
                                <td>
                                    <div class='btn-group'>
                                      <button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>Действия   <span class=\"caret\"></span></button>
                                      <ul class='dropdown-menu' role='menu'>
                                        <li><a class='control-element' data-type='custom-edit' data-action='membership/change' data-id='{$item->id}'>Изменить</a></li>
                                      </ul>
                                    </div>
                                </td>
                            </tr>
                    ";
                }
                echo "</table>";
            } else {
                echo "<h2>Данных по членским взносам не найдено</h2>";
            }
        }
        else{
            echo "<div class='text-center'><h2 class='text-info'>Членские взносы не оплачиваются</h2></div>";
            echo "<div class='text-center'><button class='btn btn-default btn-lg text-info activator' data-action='/cottage/switch-membership/{$info->cottage->id}'><span class='glyphicon glyphicon-ok text-success'></span><span class='text-success'> Оплачивать целевые взносы</span></button></div>";
        }

        ?>
    </div>
    <div class="tab-pane" id="target">
        <?php
        if (!empty($info->targetData)) {
            echo "<table class='table table-hover table-striped table-condensed'>
                        <tr>
                            <th>Год</th>
                            <th>Сумма</th>
                            <th>Оплачено</th>
                        </tr>
                    ";
            foreach ($info->targetData as $item) {

                $textColor = $item->is_partial_payed ? 'color-info' : $item->is_full_payed ? 'text-success' : 'text-warning';

                echo "
                            <tr>
                                <td>{$item->year}</td>
                                <td><b class='text-danger'>" . CashHandler::toRubles($item->total_pay) . "</b></td>
                                <td><b class='$textColor'>" . CashHandler::toRubles($item->payed_summ) . "</b></td>
                                <td>
                                    <div class=\"btn-group\">
                                      <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\">Действия   <span class=\"caret\"></span></button>
                                      <ul class=\"dropdown-menu\" role=\"menu\">
                                        <li><a href=\"#\">Действие</a></li>
                                        <li><a href=\"#\">Другое действие</a></li>
                                        <li><a href=\"#\">Что-то иное</a></li>
                                        <li class=\"divider\"></li>
                                        <li><a href=\"#\">Отдельная ссылка</a></li>
                                      </ul>
                                    </div>
                                </td>
                            </tr>
                    ";
            }
            echo "</table>";
        } else {
            echo "<h2>Данных по целевым взносам не найдено</h2>";
        }
        ?>
    </div>
    <div class="tab-pane" id="single">
        <?php
        echo "<div class='col-sm-12 text-center'><a href='#' class='btn btn-default control-element' data-type='custom-edit' data-action='single/add' data-id='{$info->cottage->id}'><span class='text-info glyphicon glyphicon-plus'></span> Добавить платёж</a></div>";
        if (!empty($info->singleData)) {
            echo "<div class='col-sm-12 margened'><table class='table table-hover table-striped table-condensed '>
                        <tr>
                            <th>Цель</th>
                            <th>Сумма</th>
                            <th>Оплачено</th>
                        </tr>
                    ";
            foreach ($info->singleData as $item) {

                $textColor = $item->is_partial_payed ? 'color-info' : $item->is_full_payed ? 'text-success' : 'text-warning';

                echo "
                            <tr>
                                <td>{$item->pay_description}</td>
                                <td><b class='text-danger'>" . CashHandler::toRubles($item->total_pay) . "</b></td>
                                <td><b class='$textColor'>" . CashHandler::toRubles($item->payed_summ) . "</b></td>
                                <td>
                                    <div class=\"btn-group\">
                                      <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\">Действия   <span class=\"caret\"></span></button>
                                      <ul class=\"dropdown-menu\" role=\"menu\">
                                        <li><a href=\"#\">Действие</a></li>
                                        <li><a href=\"#\">Другое действие</a></li>
                                        <li><a href=\"#\">Что-то иное</a></li>
                                        <li class=\"divider\"></li>
                                        <li><a href=\"#\">Отдельная ссылка</a></li>
                                      </ul>
                                    </div>
                                </td>
                            </tr>
                    ";
            }
            echo "</table></div>";
        } else {
            echo "<div class='col-sm-12'><h2 class='text-center text-success'>Данных по разовым взносам не найдено</h2></div>";
        }
        ?>
    </div>
    <div class="tab-pane" id="bills">
        <?php
        // покажу список счетов
        if (!empty($info->bills)) {
            echo "<table class='table table-hover table-striped table-condensed'>
                        <tr>
                            <th>Номер</th>
                            <th>Сумма</th>
                            <th>Оплачено</th>
                        </tr>
                    ";
            foreach ($info->bills as $billInfo) {

                $bill = $billInfo->bill;

                $textColor = $bill->is_partial_payed ? 'color-info' : $bill->is_full_payed ? 'text-success' : 'text-warning';

                echo "
                        <tr>
                            <td><a class='cursor-pointer' target='_blank' href='/bill/show/{$bill->id}'>{$bill->id}</a></td>
                            <td><b class='text-danger'>" . CashHandler::toRubles($bill->bill_summ) . "</b></td>
                            <td><b class='$textColor'>" . CashHandler::toRubles($bill->payed) . "</b></td>
                            <td>
                                <div class='btn-group'>
                                      <a target='_blank' class='btn btn-success' href='/pay/bill/{$bill->id}'>Оплата</a>
                                      <button type='button' class='btn btn-success dropdown-toggle' data-toggle='dropdown'>
                                     <span class='caret'></span>
                                     <span class='sr-only'>Меню с переключением</span>
                                     </button>
                                      <ul class='dropdown-menu' role='menu'>
                                        <li><a href=\"#\">Действие</a></li>
                                        <li><a href=\"#\">Другое действие</a></li>
                                        <li><a href=\"#\">Что-то иное</a></li>
                                        <li class=\"divider\"></li>
                                        <li><a href=\"#\">Отдельная ссылка</a></li>
                                      </ul>
                                    </div>
                            </td>
                        </tr>
                    ";
                if (!empty($billInfo->transactions)) {
                    // найдены транзакции по счёту
                    echo "<tr><td colspan='4'><table id='table_bill_{$bill->id}' class='table table-hover table-striped table-condensed table-in-output'><caption class='cursor-pointer' data-toggle=\"collapse\" data-parent=\"#table_bill_{$bill->id}\" href=\"#transactions_{$bill->id}\">Транзакции по счёту {$billInfo->bill->id}</caption><tbody id='transactions_{$bill->id}'  class=\"panel-collapse collapse\">";
                    foreach ($billInfo->transactions as $transaction) {
                        try {
                            echo "<tr>
                                    <td> <a target='_blank' href='/transaction/show/{$transaction->id}'>Транзакция #{$transaction->id}</a></td>
                                    <td>" . TimeHandler::timestampToDate($transaction->bankDate) . "</td>
                                    <td><b class='text-success'>" . CashHandler::toRubles($transaction->summ) . "</b></td>
                                </tr>";
                        } catch (Exception $e) {
                            throw $e;
                        }
                    }
                    echo "</tbody></table></td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<h2>Счетов по участку не найдено</h2>";
        }
        ?>
    </div>
    <div class="tab-pane" id="fines">
        <?php
        if (!empty($info->finesData)) {
            echo "<table class='table table-condensed table-hover'><tr><th>Тип</th><th>Период</th><th>Начислено</th><th>Оплачено</th><th>Дней</th><th>В день</th></tr>";
            $period = null;
            foreach ($info->finesData as $item) {
                switch ($item->pay_type) {
                    case 'power':
                        $type = 'Электроэнергия';
                        $period = DataPowerHandler::findOne(['id' => $item->period_id])->month;
                        break;
                    case 'membership':
                        $type = 'Членские взносы';
                        $period = DataMembershipHandler::findOne(['id' => $item->period_id])->quarter;
                        break;
                    case 'target':
                        $period = DataTargetHandler::findOne(['id' => $item->period_id])->year;
                        $type = 'Целевые взносы';
                        break;
                }
                if ($item->payed_summ === $item->summ) {
                    $text = "text-success";
                } else {
                    $text = "text-danger";
                }
                // расчитаю количество дней, за которые начисляются пени
                $dayDifference = TimeHandler::checkDayDifference($item->payUpLimit);
                $daySumm = $item->summ / (int)$dayDifference;
                if ($item->is_enabled) {
                    $controlItem = "<a href='#' id='fines_{$item->id}_enable' data-action='/fines/enable/{$item->id}' class='btn btn-default activator hidden'><span class='glyphicon glyphicon-plus text-success'></span></a><a href='#' id='fines_{$item->id}_disable' data-action='/fines/disable/{$item->id}' class='btn btn-default activator'><span class='glyphicon glyphicon-minus text-danger'></span></a>";
                } else {
                    $controlItem = "<a href='#' id='fines_{$item->id}_enable' data-action='/fines/enable/{$item->id}' class='btn btn-default activator'><span class='glyphicon glyphicon-plus text-success'></span></a><a href='#' id='fines_{$item->id}_disable' data-action='/fines/disable/{$item->id}' class='btn btn-default activator hidden'><span class='glyphicon glyphicon-minus text-danger'></span></a>";
                }
                if ($item->is_locked) {
                    $lockItem = "<a href='#' id='fines_{$item->id}_unlock' data-action='/unlock-fine/{$item->id}' class='btn btn-default activator'><span class='glyphicon glyphicon-lock text-danger'></span></a><a href='#' id='fines_{$item->id}_lock' class='btn btn-default control-element hidden' data-type='custom-edit' data-action='lock-fine' data-id='{$item->id}'><span class='glyphicon glyphicon-lock text-success'></span></a>";
                } else {
                    $lockItem = "<a href='#' id='fines_{$item->id}_unlock' data-action='/unlock-fine/{$item->id}' class='btn btn-default activator hidden'><span class='glyphicon glyphicon-lock text-danger'></span></a><a href='#' id='fines_{$item->id}_lock' class='btn btn-default control-element' data-type='custom-edit' data-action='lock-fine' data-id='{$item->id}'><span class='glyphicon glyphicon-lock text-success'></span></a>";
                }
                echo "<tr><td>$type</td><td>{$period}</td><td><b id='fines_{$item->id}_summ' class='text-info'>" . CashHandler::toRubles($item->summ) . "</b></td><td><b class='$text'>" . CashHandler::toRubles($item->payed_summ) . "</b></td><td>$dayDifference дней</td><td>" . CashHandler::toRubles($daySumm) . "</td><td>$controlItem $lockItem</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h2 class='text-center'>Просроченных задолженностей по участку не найдено</h2>";
        }
        ?>
    </div>
</div>
<div id="toolbar">
    <div class="btn-group pull-left">
        <button type="button" class="btn btn-default editModeActivator">Редактирование</button>
        <button type="button" class="btn btn-default control-element" data-type='payment-actions'
                data-action='create-bill' data-cottage-id='<?= $info->cottageInfo->cottageInfo->id ?>'>Выставить счёт
        </button>
        <?php
        if ($info->cottage->is_power) {
            echo "<button class='btn-default btn control-element' data-type='payment-actions' data-action='fill-energy' data-cottage-id='{$info->cottageInfo->cottageInfo->id}'  data-container='body' data-toggle='popover' data-placement='auto' data-trigger='hover' data-content='Заполнить показания потреблённой электроэнергии'><span class='glyphicon glyphicon-flash text-danger'></span></button>";
        }
        ?>
        <div class="btn-group dropup">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">Разное <span
                        class="caret"></span></button>
            <ul class="dropdown-menu" role="menu">
                <?php
                if (!CottagesHandler::haveAdditional($info->cottage->id) && !$info->cottage->is_additional) {
                    echo '<li><a class="post-activator" data-action="/cottage/create-additional/' . $info->cottage->id . '" href="#">Зарегистрировать дополнительный участок</a></li>';
                }
                if ($info->cottage->is_individual_tariff) {
                    echo '<li><a class="post-activator" data-action="/cottage/switch-individual/' . $info->cottage->id . '" href="#">Использовать общий тариф</a></li>';
                } else {
                    echo '<li><a class="post-activator" data-action="/cottage/switch-individual/' . $info->cottage->id . '" href="#">Использовать индивидуальный тариф</a></li>';
                }
                ?>
                <li class="divider"></li>
                <li><a href="#">Отдельная ссылка</a></li>
            </ul>
        </div>
    </div>
</div>
<div id="ajaxJsContainer" class="hidden"></div>