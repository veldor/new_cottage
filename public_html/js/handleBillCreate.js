billToolbar = $('<div id="createBillToolbar"><button id="sendBillForm" class="btn btn-success">Создать счёт</button> <span class="text-default">Стоимость: <b id="billCostViewer"></b></span> <span class="text-default">С депозита: <b id="billUsedDepositViewer">0 руб.</b></span> <span class="text-default">Скидка: <b id="billDiscountViewer"></b></span> <span class="text-default">Итог: <b id="billFinalCostViewer"></b></span></div>');
$('body').append(billToolbar);
//
$('div.modal-dialog').css({'margin-bottom': '70px'});
billModal = $('div.modal');

function handleBillCreate(answer) {
    if(answer['error']){
        makeInformer('danger', 'Ошибка', answer['error']);
    }
    if(answer['message']){
        makeInformerModal(answer['title'], answer['message']);
    }
}

// Отправлю форму при нажатии на кнопку отправки
billToolbar.find('#sendBillForm').on('click.send', function () {
    sendAjax('post', '/bill/create', handleBillCreate, billModal.find('form').eq(0), true);
});

billCostViewer = billToolbar.find('#billCostViewer');
billFinalCostViewer = billToolbar.find('#billFinalCostViewer');
discountViewer = billToolbar.find('#billDiscountViewer');
depositViewer = billToolbar.find('#billUsedDepositViewer');
// найду активаторы платежей
payActivators = billModal.find('input.pay-activator');
discountInput = billModal.find('#discountInput');
discountInput.on('input.recalculate', function () {
    recalculateBillCost();
});
discountViewer.html("<b>0,00 руб.</b>");
depositInput = billModal.find('#depositInput');
if (depositInput.length === 1) {
    depositViewer.html(roundRubles(depositInput.val()) + ' руб.');
    depositInput.on('input.recalculate', function () {
        depositViewer.html(roundRubles($(this).val()) + ' руб.');
        recalculateBillCost();
    });
}
payActivators.on('click.switch', function () {
    let payInput = billModal.find('input[name="' + $(this).attr('data-for') + '"]');
    if ($(this).prop('checked')) {
        // активирую платёж
        payInput.prop('disabled', false);
    } else {
        // закрываю платёж
        payInput.prop('disabled', true);
    }
    recalculateBillCost();
});

billModal.on('hidden.bs.modal.bill', function () {
    billToolbar.remove();
    $('script#billScript').remove();
});

function recalculateBillCost() {
    let summ = 0;
    let total = 0;
    let discountSumm = 0;
    let depositSumm = 0;
    let inputs = billModal.find('input.bill-pay').not(':disabled');
    inputs.each(function () {
        summ += toSumm($(this).val());
    });
    total += summ;
    if (!discountInput.prop('disabled')) {
        let used = toSumm(discountInput.val());
        if (!used) {
            used = 0;
        }
        if (used > total) {
            discountInput.val(total);
            discountSumm = total;
            makeInformer('warning', 'Сумма скидки скорректирована', 'Сумма скидки уменьшена до стоимости платежа');
        } else {
            discountSumm = used;
        }
        discountViewer.text(roundRubles(discountSumm) + ' руб.');
    }
    total -= discountSumm;
    if (depositInput.length === 1 && !depositInput.prop('disabled')) {
        let max = depositInput.attr('data-available');
        let used = toSumm(depositInput.val());
        if (used > max) {
            makeInformer('warning', 'Проверьте сумму оплаты с депозита', 'Сумма больше остатка на депозите. Доступно ' + max + ' руб.');
        } else if (used > total) {
            depositInput.val(total);
            depositSumm = total;
            makeInformer('warning', 'Сумма оплаты с депозита скорректирована', 'Сумма оплаты с депозита уменьшена до стоимости платежа');
        } else {
            depositSumm = used;
            depositViewer.text(used + ' руб.');
        }
    }
    else{
        depositViewer.text("0,00 руб.");
    }
    total -= depositSumm;
    billCostViewer.text(roundRubles(summ) + ' руб.');
    billFinalCostViewer.text(roundRubles(total) + ' руб.');
}

billModal.find('input.bill-pay').on('input.recalculate', function () {
    recalculateBillCost();
});
recalculateBillCost();