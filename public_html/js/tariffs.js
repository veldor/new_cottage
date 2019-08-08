$(function () {
    handle();
});

function handle() {
    $('.control-element').on('click.ask', function () {
       let type = $(this).attr('data-type');
       let period = $(this).attr('data-period');
       sendAjax('get', '/tariffs/details/' + type + '/' + period, simpleModalHandler);
    });
}