$(function () {
    let billStatus = $('#bill-status');
    let totalPayInput = $('#pay-payedsumm');
    let distributeInputs = $('.distributed-summ-input');
    let summToPay = totalPayInput.attr('data-max-summ') * 1;
    let undistributedAmount = 0;
    totalPayInput.on('change', function () {
        let value = $(this).val() * 100;
        // если введённая сумма больше необходимого- зачислю сдачу на депозит. Если меньше- посчитаю частичную
        // оплату. Если равна- счёт полностью оплачен
        if (value > summToPay) {
            // перечислю остаток на депозит
            let difference = toRubles(value - summToPay);
            billStatus.html('Счёт будет полностью оплачен. ' + difference + ' руб. будет зачислено на депозит.');
            // помещу в поля ввода детализации максимальные значения
            distributeInputs.each(function () {
                $(this).val(toRubles($(this).attr('data-max-summ')));
            });
            $('.field-pay-todeposit').removeClass('hidden');
        } else if (value === summToPay) {
            billStatus.html('Счёт будет полностью оплачен');
            $('.field-pay-todeposit').addClass('hidden');
            // помещу в поля ввода детализации максимальные значения
            distributeInputs.each(function () {
                $(this).val(toRubles($(this).attr('data-max-summ')));
            });
        } else {
            billStatus.html('Сумма меньше необходимой. Нужно распределить ' + toRubles(value) + ' руб.');
            undistributedAmount = value;
            distributeInputs.val(0).attr('data-previous-value', 0);
        }
    });

    let distributeActivators = $('.all-distributed-button');
    distributeActivators.on('click.fill', function () {
        // найду поле ввода, в которое будет распределяться сумма
        let targetInput = $(this).parent().find('input');
        let amount = toRubles(targetInput.attr('data-max-summ'));
        if (targetInput.attr('id') === 'pay-payedsumm') {
            targetInput.val(amount);
        } else {
            let previousVal = targetInput.val();
            if (previousVal) {
                undistributedAmount += roundRubles(previousVal * 100) * 1;
            }
            let amount = targetInput.attr('data-max-summ') * 100;
            // если значение суммы больше оставшихся нераспределённых средств- она проставляется как значение поля
            // и вычитается из общей суммы. Если меньше- общая сумма приравнивается к нулю и выставляется в значение поля
            if (amount >= undistributedAmount) {
                targetInput.val(toRubles(undistributedAmount));
                targetInput.attr('data-previous-value', undistributedAmount);
                undistributedAmount = 0;
            } else {
                targetInput.val(toRubles(amount));
                undistributedAmount -= amount;
                targetInput.attr('data-previous-value', amount);
            }
            if (undistributedAmount === 0) {
                billStatus.html('Сумма меньше необходимой. Распределена полностью');
            } else {
                billStatus.html('Сумма меньше необходимой. Нужно распределить ' + toRubles(undistributedAmount) + ' руб.');
            }
        }
        targetInput.trigger('change');
    });

    distributeInputs.on('input.change', function () {
        // проверю, есть ли предыдущее значение
        let previous = $(this).attr('data-previous-value');
        if (previous) {
            undistributedAmount += previous * 1;
        }
        let value = $(this).val() * 100;
        let currentAmount = undistributedAmount - value;
        if (currentAmount >= 0) {
            undistributedAmount = currentAmount;
            $(this).attr('data-previous-value', value);
        } else {
            $(this).val(0);
            $(this).attr('data-previous-value', 0);
            makeInformer('warning', 'Не получится', 'Число больше нераспределённой суммы');
        }
        if (undistributedAmount === 0) {
            billStatus.html('Сумма меньше необходимой. Распределена полностью');
        } else {
            billStatus.html('Сумма меньше необходимой. Нужно распределить ' + toRubles(undistributedAmount) + ' руб.');
        }
    });
    let submitBtn = $('#submitForm');
    submitBtn.on('click.submit', function () {
        sendAjax('post', '/pay/bill', simpleAnswerHandler, $('form#payBill'), true);
    });

    // обработаю добавление номера банковской транзакции
    let bankIdInput = $('input#pay-banktransactionid');
    let bankTransactionInfoDiv = $('div#bankTransactionInfo');
    bankIdInput.on('change.testTransaction', function () {
        if (bankIdInput.val()) {
            sendAjax('get', '/bank-transaction/get/' + bankIdInput.val(), function (answer) {
                if (answer) {
                    if (answer.bounded_transaction_id) {
                        bankTransactionInfoDiv.html('<h2 class="text-center text-warning margened">Транзакция уже связана со счётом</h2>');
                        totalPayInput.val('').prop('readonly', false);
                        totalPayInput.trigger('change');
                        $('input#payCustomDate').val('');
                        $('input#getCustomDate').val('');
                    } else {
                        bankTransactionInfoDiv.html('<table class="table"><caption>Информация о транзакции</caption><tbody><tr><td>Номер участка</td><td>' + answer.account_number + '</td></tr><tr><td>Плательщик</td><td>' + answer.fio + '</td></tr><tr><td>Цель</td><td>' + answer.address + '</td></tr></tbody></table>');
                        totalPayInput.val(toRubles(answer.payment_summ)).prop('readonly', true);
                        totalPayInput.trigger('change');
                        $('input#payCustomDate').val(swapDate(answer.real_pay_date));
                        $('input#getCustomDate').val(swapDate(answer.pay_date));
                    }
                } else {
                    bankTransactionInfoDiv.html('<h2 class="text-center text-warning margened">Информация о транзакции не найдена</h2>');
                    totalPayInput.val('').prop('readonly', false);
                    totalPayInput.trigger('change');
                    $('input#payCustomDate').val('');
                    $('input#getCustomDate').val('');
                }
            });
        }
    });
    if (bankIdInput.val()) {
        bankIdInput.trigger('change');
    }
});