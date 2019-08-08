$(function () {
    let undistributedAmount = $('#transactionshandler-summ').val() * 100;
    let distributeActivators = $('.all-distributed-button');
    let undistributedSummContainer = $('#undistributedAmountView');

    distributeActivators.on('click.fill', function () {
        // найду поле ввода, в которое будет распределяться сумма
        let targetInput = $(this).parent().find('input.distributed-summ-input');
        // если в поле уже есть значение- перед распределением средств плюсую его к нераспределённой части
        let previousVal = targetInput.val();
        if (previousVal) {
            undistributedAmount += roundRubles(previousVal * 100);
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
        undistributedSummContainer.html(toRubles(undistributedAmount));
    });
    $('#submitForm').on('click.sendForm', function () {
        sendAjax('post', '/bill/distribute', simpleAnswerHandler, $('form#distributeBill'), true);
    });

});