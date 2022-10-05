/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

let csrf = document.head.querySelector('meta[name="csrf-token"]');
if (csrf) {
    csrf = csrf.content;
}

const elem = (tag, attrs, ...children) => {
    let parent = document.createElement(tag);
    Object.keys(attrs).forEach(function (key) {
        if (key in parent) {
            parent[key] = attrs[key];
        } else {
            parent.setAttribute(key, attrs[key]);
        }
    });

    children.forEach(child => {
        if (typeof child === "string") {
            child = document.createTextNode(child);
        }
        parent.appendChild(child);
    });
    return parent;
};

let toastsContainer = document.getElementById('toastMessages');

function createToast(message, type = 'info', duration = 3000) {
    if (typeof message === 'object') {
        message = message.message;
        type = message.type ?? type;
        duration = message.duration ?? duration;
    }

    let toast = elem('div', {classList: 'notification is-' + type, innerText: message},
        elem('button', {classList: 'delete', onclick: dismissToast})
    )

    toastsContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
    }, 15);

    if (duration !== -1 && duration !== Infinity) {
        setTimeout(() => {
            toast.style.opacity = '0';

            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration)
    }
}

function dismissToast() {
    this.parentElement.style.opacity = '0';

    setTimeout(() => {
        this.parentElement.remove();
    }, 300);
}


if (Object.keys(toastMessages).length) {
    for (const [type, messages] of Object.entries(toastMessages)) {
        messages.forEach(message => {
            createToast(message, type);
        });
    }
}

let copyElems = document.querySelectorAll('[data-copy]');
copyElems.forEach(item => {
    item.addEventListener('click', ev => {
        let copy = document.getElementById('copyBox');
        if (ev.target.innerText.length) {
            copy.value = ev.target.innerText;
            copy.select();
            copy.setSelectionRange(0, 99999);

            // document.execCommand("copy");
            navigator.clipboard.writeText(copy.value).then(_ => {
                createToast('Copied!');
            });
        }
    });
});

function addElem(parent, child) {
    if (typeof child === 'function') {
        child = child();
    } else {
        child.cloneNode(true);
    }

    document.getElementById(parent).appendChild(child);
}

function deleteElem(el) {
    let parent = el.closest('.child');

    if (parent) {
        parent.remove();
    }
}