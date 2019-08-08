$(function () {
    handleUtils();
});

function handleUtils() {
// активирую переключатели
    let switchers = $('.switcher');
    switchers.on('change.switch', function () {
        let target = $('#' + $(this).attr('data-switch'));
        if ($(this).prop('checked')) {
            target.removeClass('hidden');
        } else {
            target.addClass('hidden');
        }
    });

    // обработаю заполнение дат
    let dateSelectors = $('.date-selector');
    dateSelectors.on('click.fill', function () {
        let period = $(this).attr('data-period');
        let form = $(this).parents('form').eq(0);
        let start = form.find('.date-start');
        let finish = form.find('.date-finish');

        let date = new Date;
        let day = ('0' + date.getDate()).slice(-2);
        let month = ('0' + (date.getMonth() + 1)).slice(-2);
        let year = date.getFullYear();
        if (period === 'day') {
            start.val(year + '-' + month + '-' + day);
            finish.val(year + '-' + month + '-' + day);
        } else if (period === 'month') {
            start.val(year + '-' + month + '-01');
            finish.val(year + '-' + month + '-' + getLastDayOfMonth(year, month - 1));
        } else if (period === 'year') {
            start.val(year + '-01-01');
            start.trigger('change');
            start.trigger('input');
            start.trigger('blur');
            finish.val(year + '-12-31');
            finish.trigger('change');
        }
    });
    // активирую автозаполнение
    $('.autofill').on('click.autofill', function () {
       let text = $(this).attr('data-fill');
       $(this).parents('.input-group').eq(0).find('input').val(text);
    });
}
function getLastDayOfMonth(y, m) {
    if (m === 1) {
        return y % 4 || (!(y % 100) && y % 400) ? 28 : 29;
    }
    return m === 3 || m === 5 || m === 8 || m === 10 ? 30 : 31;
}