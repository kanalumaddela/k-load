/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

var csrf = $('meta[name="csrf-token"]').attr('content');
const elem = (tag, attrs, ...children) => {
    let parent = document.createElement(tag);
    Object.keys(attrs).forEach(function (key) {
        if (key in parent) {
            parent[key] = attrs[key];
        } else {
            parent.setAttribute(key, attrs[key]);
        }
    });
    // Object.keys(attrs).forEach(key => elem[key] = attrs[key]); <-- original

    children.forEach(child => {
        if (typeof child === "string") {
            child = document.createTextNode(child);
        }
        parent.appendChild(child);
    });
    return parent;
};

const lang = (key, default_phrase) => {
    return language[key] ? language_fallback[key] : (default_phrase ? default_phrase : '');
};

/**
 * Generate a random string.
 *
 * @returns {string}
 */
const str_random = function () {
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
};

/**
 * Debounce function to prevent excessive calls.
 * {@link  https://davidwalsh.name/javascript-debounce-function}
 *
 * @param func
 * @param wait
 * @param immediate
 * @returns {Function}
 */
const debounce = function (func, wait, immediate) {
    var timeout;
    return function () {
        var context = this, args = arguments;
        var later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

function toast(message = '', time = 5000, css = '') {
    if (typeof M !== 'undefined') {
        M.toast({
            html: message,
            displayLength: time,
            classes: css,
            activationPercent: .6
        });
    } else {
        window.alert(message);
    }
}

function addElem() {
    $('.add-elem').click(function () {
        var type = $(this).data('type');
        var parent = $(this).data('parent');
        var parent_dom = $(this).prev(parent);
        if (parent_dom.length === 0) {
            parent_dom = $.find(parent);
            if (parent_dom.length > 1) {
                parent_dom = parent_dom[0];
            }
        }
        var func = window['createElem_' + type];
        var callback = window['createElemCallback_' + type];
        if (typeof func === 'function' && typeof parent !== 'undefined') {
            let child = func();
            $(parent_dom).append(child);
            if (typeof callback === 'function') {
                callback(child);
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

function userAction(data, refresh, callback) {
    $.post(site.current, data, function (response) {
        console.log(response);
        toast(response.message);
        if (response.success) {
            if (refresh !== false) {
                toast('Refreshing the page...');
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }

            if (response.csrf) {
                csrf = response.csrf;
            }
        }
        if (callback) {
            callback(response);
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
if (alert !== '') {
    toast(alert, 5000);
}

if (alerts.length > 0) {
    alerts.forEach((alert) => {
        toast(alert.message, alert.duration, alert.css);
    })
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