// переключатель режима редактирования
let editMode = false;
let jsContainer = $('#ajaxJsContainer');
let modal;

function switchEditMode(activator) {
    if (editMode) {
        activator.text('Редактирование');
        // скрою элементы управления
        $('.control-container').addClass('hidden');
        $('.editable').removeClass('hidden');
    } else {
        activator.text('Завершить редактирование');
        // покажу элементы управления
        $('.control-container').removeClass('hidden');
        $('.editable').addClass('hidden');
    }
    editMode = !editMode;
}

function handleChange(answer) {
    // если действие успешно- выполню скрипт, пришедший с сервера
    if (answer['action']) {
        jsContainer.html(answer['action']);
        $('.has-tooltip').tooltip();
    } else if (answer['info']) {
        makeInformer('info', 'Информация', answer['info']);
    } else if (answer['error']) {
        makeInformer('warning', 'Ошибка', answer['error']);
    } else {
        makeInformer('warning', 'Ошибка', 'Неверный ответ сервера');
    }
}

function handleEditForm(answer) {
    if(answer['note']){
        modal = makeInformerModal(answer['title'], answer['html']);
    }
    else if (answer['info']) {
        makeInformer('info', 'Информация', answer['info']);
    } else if (answer['error']) {
        makeInformer('warning', 'Ошибка', answer['error']);
    } else if (answer['html']) {
        modal = makeModal(answer['title'], answer['html']);
        let form = modal.find('form');
        form.on('submit.send', function (e) {
            e.preventDefault();
            // отправлю форму на сохранение своим путём
            let url = form.attr('action');
            sendAjax('post', url, handleChange, form, true);
        })
    } else {
        makeInformer('warning', 'Ошибка', 'Неверный ответ сервера');
    }
}

function handleEdit(element) {
    element.off('click.startEdit');
    element.on('click.startEdit', function (e) {
        e.preventDefault();
        // получу тип действия
        let type = element.attr('data-type');
        let action = element.attr('data-action');
        let id;
        let url;
        if (type === 'edit-contact') {
            // получу идентификатор контакта
            id = element.attr('data-contact-id');
            // получу действие

            if(action === 'delete-mail' || action === 'change-mail'){
                id = element.attr('data-mail-id');
            }
            else if(action === 'delete-phone' || action === 'change-phone'){
                id = element.attr('data-phone-id');
            }
            else if(action === 'add-contact'){
                id = element.attr('data-cottage-id');
            }
            url = '/cottage/edit/' + type + '/' + action + '/' + id;
        }
        else if(type === 'edit-cottage'){
            id = element.attr('data-cottage-id');
            url = '/cottage/edit/' + type + '/' + action + '/' + id;
        }
        else if(type === 'custom-edit'){
            id = element.attr('data-id');
            url = '/' +  action + '/' + id;
        }
        else{
            id = element.attr('data-cottage-id');
            url = '/' + type + '/' + action + '/' + id;
        }

        if(element.attr('data-send-post')){
            sendAjax('post', url, handleEditForm);
        }
        else{
            // отправлю запрос на получение формы
            sendAjax('get', url, handleEditForm);}
    })
}

function handleEditing() {
    let editActivators = $('.control-element');
    // привяжу к каждому элементу обработчик
    editActivators.each(function () {
        handleEdit($(this));
    });
}

function handle() {
    // активирую навигацию по табам
    enableTabNavigation();
    handleEditing();
    // активирую всплывающие подсказки
    $('.has-tooltip').tooltip();
    $('.has-popover').popover();

    // обработка редактирования информации
    const editModeActivator = $('.editModeActivator');
    editModeActivator.on('click.switchEdit', function (e) {
        e.preventDefault();
        switchEditMode($(this));
    });
    // активирую переход к участку по ссылке
    $('#goToCottageActivator').on('click.go', function () {
       location.replace('/cottage/show/' + $('#goToCottageInput').val());
    });
    $('#goToCottageInput').on('keypress.go', function (e) {
        if(e.charCode === 13){
            location.replace('/cottage/show/' + $('#goToCottageInput').val());
        }
    });
}

$(function () {
    handle();
});