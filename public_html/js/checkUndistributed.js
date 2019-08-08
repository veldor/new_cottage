
function checkUndistributed() {
    sendSilentAjax('get', '/payments/check-undistributed', function (data) {
        if(data['id']){
            let win = window.open('/bill/distribute/' + data['id'], '_blank');win.focus();
        }
    });
}

$(function () {
    checkUndistributed();
});