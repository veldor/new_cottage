function makeModal(header, text) {
    if (!text)
        text = '';
    let modal = $('<div class="modal fade mode-choose" data-keyboard="true"><div class="modal-dialog  modal-lg"><div class="modal-content"><div class="modal-header">' + header + '</div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-danger"  data-dismiss="modal" type="button" id="cancelActionButton">Отмена</button></div></div></div>');
    $('body').append(modal);
    ajaxDangerReload();
    modal.modal({
        'keyboard': true,
        'backdrop': 'static',
        'show': true
    });
    modal.on('hidden.bs.modal', function () {
        ajaxNormalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');
    return modal;
}

function makeInformerModal(header, text, acceptAction, declineAction) {
    if (!text)
        text = '';
    let modal = $('<div class="modal fade mode-choose"><div class="modal-dialog text-center"><div class="modal-content"><div class="modal-header"><h3>' + header + '</h3></div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-success" type="button" id="acceptActionBtn">Ок</button></div></div></div>');
    $('body').append(modal).css({'overflow': 'hidden'});
    $('div.modal').css({'overflow': 'scroll'});
    let acceptButton = modal.find('button#acceptActionBtn');
    if (declineAction) {
        let declineBtn = $('<button class="btn btn-warning" role="button">Отмена</button>');
        declineBtn.insertAfter(acceptButton);
        declineBtn.on('click.custom', function () {
            ajaxNormalReload();
            modal.modal('hide');
            declineAction();
        });
    }
    ajaxDangerReload();
    modal.modal({
        keyboard: true,
        backdrop: 'static',
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        ajaxNormalReload();
        modal.remove();
        $('body').css({'overflow': 'auto'});
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');

    acceptButton.on('click', function () {
        ajaxNormalReload();
        modal.modal('hide');
        if (acceptAction) {
            acceptAction();
        } else {
            location.reload();
        }
    });

    return modal;
}

// ========================================================== ИНФОРМЕР
// СОЗДАЮ ИНФОРМЕР
function makeInformer(type, header, body) {
    if (!body)
        body = '';
    const container = $('div#alertsContentDiv');
    const informer = $('<div class="alert-wrapper"><div class="alert alert-' + type + ' alert-dismissable my-alert"><div class="panel panel-' + type + '"><div class="panel-heading">' + header + '<button type="button" class="close">&times;</button></div><div class="panel-body">' + body + '</div></div></div></div>');
    informer.find('button.close').on('click.hide', function (e) {
        e.preventDefault();
        closeAlert(informer)
    });
    container.append(informer);
    showAlert(informer)
}

// ПОКАЗЫВАЮ ИНФОРМЕР
function showAlert(alertDiv) {
    // считаю расстояние от верха страницы до места, где располагается информер
    const topShift = alertDiv[0].offsetTop;
    const elemHeight = alertDiv[0].offsetHeight;
    let shift = topShift + elemHeight;
    alertDiv.css({'top': -shift + 'px', 'opacity': '0.1'});
    // анимирую появление информера
    alertDiv.animate({
        top: 0,
        opacity: 1
    }, 500, function () {
        // запускаю таймер самоуничтожения через 5 секунд
        setTimeout(function () {
            closeAlert(alertDiv)
        }, 5000);
    });

}

// СКРЫВАЮ ИНФОРМЕР
function closeAlert(alertDiv) {
    const elemWidth = alertDiv[0].offsetWidth;
    alertDiv.animate({
        left: elemWidth
    }, 500, function () {
        alertDiv.animate({
            height: 0,
            opacity: 0
        }, 300, function () {
            alertDiv.remove();
        });
    });
}

function serialize(obj) {
    const str = [];
    for (let p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

function ajaxDangerReload() {
    $(window).on('beforeunload.ajax', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function ajaxNormalReload() {
    $(window).off('beforeunload.ajax');
}

function showWaiter() {
    let shader = $('<div class="shader"></div>');
    $('body').append(shader).css({'overflow': 'hidden'});

    $('div.wrap, div.flyingSumm, div.modal').addClass('blured');
    shader.showLoading();
}

function deleteWaiter() {
    $('div.wrap, div.flyingSumm, div.modal').removeClass('blured');
    $('body').css({'overflow': ''});
    let shader = $('div.shader');
    if (shader.length > 0)
        shader.hideLoading().remove();
}

// ajax-запрос
function sendAjax(method, url, callback, attributes, isForm) {
    showWaiter();
    ajaxDangerReload();
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            deleteWaiter();
            ajaxNormalReload();
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            ajaxNormalReload();
            deleteWaiter();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON['message']);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            //callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            deleteWaiter();
            ajaxNormalReload();
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            deleteWaiter();
            ajaxNormalReload();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON.message);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            //callback(false)
        });
    }
}
// ajax-запрос
function sendSilentAjax(method, url, callback, attributes, isForm) {
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON['message']);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            //callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            deleteWaiter();
            ajaxNormalReload();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON.message);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            //callback(false)
        });
    }
}

// навигация по табам
function enableTabNavigation() {
    let url = location.href.replace(/\/$/, "");
    if (location.hash) {
        const hash = url.split("#");
        $('a[href="#' + hash[1] + '"]').tab("show");
        url = location.href.replace(/\/#/, "#");
        history.replaceState(null, null, url);
    }

    $('a[data-toggle="tab"]').on("click", function () {
        console.log('click');
        let newUrl;
        const hash = $(this).attr("href");

        if (hash === "#home") {
            newUrl = url.split("#")[0];
        } else {
            newUrl = url.split("#")[0] + hash;
        }
        history.replaceState(null, null, newUrl);
    });
    $('a.emulate-tab').on('click', function (e) {
        e.preventDefault();
        const hash = $(this).attr("href");
        $('a[data-toggle="tab"][href="' + hash + '"]').click();
    })
}

function toSumm(val) {
    return parseFloat(val);
}
function roundRubles(val) {
    return parseFloat(val).toFixed(2);
}
function toRubles(val) {
    return (val / 100).toFixed(2);
}


// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformerModal("Успешно", message);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
}

function simpleModalHandler(data) {
    if (data.status) {
        if (data.status === 1) {
            return makeModal(data.header, data.view);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
    return null;
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerInformerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformer('success',"Успешно", message);
        }
        else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    }
    else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
}

function stringify(data) {
    if (typeof data === 'string') {
        return data;
    }
    else if (typeof data === 'object') {
        let answer = '';
        for (let i in data) {
            answer += data[i] + '<br/>';
        }
        return answer;
    }
}

function swapDate(date){
    let parts = date.split('-');
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}

$(function () {
// покажу информеры
    $('.control-element').popover();
    $('.tooltiped').tooltip();
    $('.popovered').popover();
// инициализирую активаторы
    let activators = $('.activator');
    activators.on('click.doAction', function (e) {
        e.preventDefault();
        let url = $(this).attr('data-action');
        sendAjax('get', url, simpleModalHandler);
    });
// инициализирую активаторы
    let postActivators = $('.post-activator');
    postActivators.on('click.doAction', function (e) {
        e.preventDefault();
        let url = $(this).attr('data-action');
        sendAjax('post', url, simpleAnswerHandler);
    });
    let printPageActivator = $('#printPageActivator');
    printPageActivator.on('click.print', function () {
        window.print();
    });
});