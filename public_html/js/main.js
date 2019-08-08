function handle() {
    // найду карточки участков, подключу им функциональность подсказок
    let cottageCards = $('div.cottage-container');
    cottageCards.popover({
        'placement' : 'auto',
        'html': true,
        'trigger': 'hover',
        'delay': {show: 500, hide: 100},
        'title': "Информация об участке"
    });
}

$(function () {
    handle();
});