/*
	K-Load v{{ script_version_name }}
*/
var csrf = $('#csrf').val();
const elem = (tag, attrs, ...children) => {
    const elem = document.createElement(tag);
    Object.keys(attrs).forEach(function (key) {
        if (key in document.createElement(tag)) {
            elem[key] = attrs[key];
        } else {
            elem.setAttribute(key, attrs[key]);
        }
    });
    // {{ user_id sha256 key }}
    // Object.keys(attrs).forEach(key => elem[key] = attrs[key]); <-- original

    children.forEach(child => {
        if (typeof child === "string") {
            child = document.createTextNode(child);
        }
        elem.appendChild(child);
    });
    return elem;
};

function toast(message = '', time = 5000, css = '') {
    if (typeof Materialize !== 'undefined') {
        if (time == -1) {
            Materialize.toast(message, Infinity, css, function () {
                current_message = '';
            });
        } else {
            Materialize.toast(message, time, css, function () {
                current_message = '';
            });
        }
    } else {
        console.log(message);
    }
}

function addElem() {
    $('.add-elem').click(function () {
        var type = $(this).data('type');
        var parent = $(this).data('parent');
        var parent_dom = $(this).prev(parent);
        if (parent_dom.length == 0) {
            parent_dom = $.find(parent);
            if (parent_dom.length > 1) {
                parent_dom = parent_dom[0];
            }
        }
        var func = window['createElem_' + type];
        var callback = window['createElemCallback_' + type];
        if (typeof func === 'function' && typeof parent !== 'undefined') {
            $(parent_dom).append(func());
            if (typeof callback === 'function') {
                callback();
            }
        } else {
            console.log('Function createElem_' + type + '() not found!');
        }
        $('.add-elem').unbind();
        $('.delete-elem').unbind();
        deleteElem();
        addElem();
    });
}

function deleteElem() {
    $('.delete-elem').click(function () {
        $(this).closest('.child').fadeOut(function () {
            this.remove();
        });
    });
}

$('.copy-user').click(function () {
    var type = 'copy';
    var player = $(this).data('steamid');
    var data = {csrf, type, player};
    if (window.confirm('Are you sure you want to copy this user\'s settings?')) {
        userAction(data);
    }
});
$('.ban-user').click(function () {
    var type = 'ban';
    var player = $(this).data('steamid');
    var data = {csrf, type, player};
    if (window.confirm('Are you sure you want to ban this user?')) {
        userAction(data);
    }
});
$('.unban-user').click(function () {
    var type = 'unban';
    var player = $(this).data('steamid');
    var data = {csrf, type, player};
    if (window.confirm('Are you sure you want to unban this user?')) {
        userAction(data);
    }
});

function userAction(data) {
    $.post(site.current, data, function (response) {
        console.log(response);
        toast(response.message);
        if (response.success) {
            toast('Refreshing the page...');
            setTimeout(function () {
                location.reload();
            }, 1000);
        }
    });
}

addElem();
deleteElem();
var css_check = document.getElementById("css");
if (typeof css_check != 'undefined' && css_check != null) {
    var editor = CodeMirror.fromTextArea(document.getElementById("css"), {
        mode: 'css',
        theme: 'material',
        indentUnit: 4,
        indentWithTabs: true,
        lineNumbers: true
    });
    editor.on('change', function (cm) {
        var value = cm.getValue();
        var length = value.length;
        document.getElementById('css').value = value;
        var counter = $('#css').next().next();
        var max_length = $(counter).data('max');
        $(counter).text(length + '/' + max_length);
        if (length > max_length) {
            $(counter).removeClass('success-text').addClass('warning-text');
        } else {
            $(counter).removeClass('warning-text').addClass('success-text');
        }
    });
}
if (alert != '') {
    toast(alert, 5000, 'blue');
}
$('textarea').on('input', function () {
    var length = this.value.length;
    var counter = $(this).next('span.counter');
    if (counter.length === 0) {
        counter = $(this).next().next('span.counter');
    }
    var max_length = $(counter).data('max');
    counter.text(length + '/' + max_length);
    if (length > max_length) {
        counter.removeClass('success-text').addClass('warning-text');
    } else {
        counter.removeClass('warning-text').addClass('success-text');
    }
});
