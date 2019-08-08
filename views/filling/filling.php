<?php

use app\assets\FillingAsset;
use app\assets\MainAsset;
use app\models\Registry;
use app\models\utils\CashHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;
use yii\widgets\ActiveForm;

FillingAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $model Registry */

$this->title = 'Действия';

$tabs = ['bills' => '', 'registry' => 'active in', 'mailing' => ''];

if (!empty($tab)) {
    foreach ($tabs as $key => $value) {
        if ($key === $tab) {
            $tabs[$key] = 'active in';
        } else {
            $tabs[$key] = '';
        }
    }
}

?>

<ul class="nav nav-tabs">
    <li class="<?= $tabs['registry'] ?>"><a href="#registry" data-toggle="tab">Реестр</a></li>
    <li class="<?= $tabs['bills'] ?>"><a href="#bills" data-toggle="tab">Счета</a></li>
    <li class="<?= $tabs['mailing'] ?>"><a href="#mailing" data-toggle="tab">Рассылка</a></li>
</ul>

<div class="tab-pane <?= $tabs['registry'] ?>" id="registry">

    <div class="row margened">
        <?php $form = ActiveForm::begin(['options' => ['id' => 'handleRegistryForm', 'enctype' => 'multipart/form-data',], 'action' => '/filling/registry']);
        echo $form->field($model, 'file[]', ['template' =>
            '<div class="col-sm-6 text-center">{label}{input}
									{error}{hint}</div>'])
            ->fileInput(['class' => 'hidden', 'id' => 'registryInput', 'multiple' => true, 'accept' => 'text/plain'])
            ->label('Выберите файл регистра.', ['class' => 'btn btn-info']);
        ActiveForm::end();
        if (!empty($errorMessage)) {
            echo "<div class='col-sm-12'><b>$errorMessage</b></div>";
        }
        /** @var RegistryInfo $billDetails */
        if (!empty($model->unhandled)) {
            echo "<div class='col-sm-12'><table class='table-condensed table-striped'><tr><th>Дата оплаты</th><th>Время оплаты</th><th>Номер участка</th><th>Сумма платежа</th><th>ФИО плательщика</th><th>№ счёта</th></tr>";
            foreach ($model->unhandled as $item) {
                echo "<tr>
                                <td>{$item->pay_date}</td>
                                <td>{$item->pay_time}</td>
                                <td>{$item->account_number}</td>
                                <td>" . CashHandler::toRubles($item->transaction_summ) . "</td>
                                <td>{$item->fio}</td>
                                <td>" . Registry::getBillId($item->address) . "</td>
                                <td>
                                    <div class='btn-group'>
                                         <button class='chain_bill btn btn-success' data-bank-operation='{$item->bank_operation_id}' data-bill-id='" . Registry::getBillId($item->address) . "'><span class='glyphicon glyphicon-link'></span></button>
                                           <button type=\"button\" class=\"btn btn-success dropdown-toggle\" data-toggle=\"dropdown\">
                                             <span class=\"caret\"></span>
                                             <span class=\"sr-only\">Меню с переключением</span>
                                             </button>
                                              <ul class=\"dropdown-menu\" role=\"menu\">
                                                <li><a class='bill-manual-inserted' data-bank-operation='{$item->bank_operation_id}' href='#'>Внесён вручную</a></li>
                                              </ul>
                                    </div>
                                </td>
                          </tr>
                           ";
            }
            echo "</table></div>";
        }
        ?>
    </div>
</div>
