$(function () {
    handle();
});

function handle() {
    enableTabNavigation();
    // при выборе файлов регистра- отправлю форму
    let registryInput = $('#registryInput');
    registryInput.on('change.send', function () {
        if($(this).val()){
            $(this).parents('form').trigger('submit');
        }
    });

    const chainActivators = $('button.chain_bill');
    chainActivators.on('click.chain', function () {
        let supposedBillId = $(this).attr('data-bill-id');
        let bankTransactionId = $(this).attr('data-bank-operation');
        if(supposedBillId){
            confirmChainedBillId(supposedBillId, bankTransactionId);
        }
        else{
            selectBillId(bankTransactionId);
        }
    });

    // добавлю функцию ручной привязки счёта
    let manualChainActivators = $('a.bill-manual-inserted');
    manualChainActivators.on('click.change', function (e) {
        e.preventDefault();
        selectBillId($(this).attr('data-bank-operation'), true);
    });

    function confirmChainedBillId(supposedBillId, bankTransactionId) {
        makeInformerModal("Связка платежа", "Связать платёж со счётом №" + supposedBillId + "?", function () {
            let newWindow = window.open('/pay/bill/' + supposedBillId + '/' + bankTransactionId);
            $(newWindow).on('beforeunload.refresh', function () {
               setTimeout(function () {
                   location.reload();
               }, 2000);
            });
        }, function () {
            selectBillId(bankTransactionId);
        });
    }
    function selectBillId(bankTransactionId, manualChain) {
        // покажу модаль с полем ввода номера счёта
        let modal = null;
        if(manualChain){
            modal = makeModal("Выбор номера транзакции", '<div class="row"><div class="col-sm-12 margened"><div class="col-sm-5"><label for="billId" class="control-label">Номер транзакции</label></div><div class="col-xs-7"><input class="form-control" id="billId" type="text" maxlength="100"></div></div><div class="col-sm-12 margened"><button class="btn btn-success" id="acceptBill">Связать</button></div></div>');
        }
        else{
            modal = makeModal("Выбор номера счёта", '<div class="row"><div class="col-sm-12 margened"><div class="col-sm-5"><label for="billId" class="control-label">Номер счёта</label></div><div class="col-xs-7"><input class="form-control" id="billId" type="text" maxlength="100"></div></div><div class="col-sm-12 margened"><button class="btn btn-success" id="acceptBill">Связать</button></div></div>');
        }

        let acceptButton = modal.find('button#acceptBill');
        let billIdInput = modal.find('input#billId');
        acceptButton.on('click.accept', function () {
            if(billIdInput.val()){
                modal.modal('hide');
                if(manualChain){
                    confirmManualChain(billIdInput.val(),bankTransactionId);
                }
                else{
                    confirmChainedBillId(billIdInput.val(), bankTransactionId);
                }

            }
        });
    }
    function confirmManualChain(billId, bankTransactionId) {
        let url = "/bank-transaction/confirm-manual";
        let attributes = {
            'ComparisonHandler[transactionId]': billId,
            'ComparisonHandler[bankTransactionId]':bankTransactionId,
        };
        sendAjax('post', url, simpleAnswerHandler, attributes);
    }
}