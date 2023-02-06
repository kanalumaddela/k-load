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

/**
 *
 * @param tag
 * @param attrs
 * @param children
 * @returns {*}
 */
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

const debounce = function (func, wait, immediate) {
    let timeout;
    return function () {
        const context = this, args = arguments;
        const later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
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

function determineToastDuration() {

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

    if (typeof parent !== 'string') {
        parent.appendChild(child)
    } else {
        document.querySelector(parent).appendChild(child);
    }
}

function deleteElem(el, selector, requireConfirmation) {
    selector = typeof selector === 'undefined' ? '.child' : selector
    requireConfirmation = typeof requireConfirmation === 'undefined' ? false : requireConfirmation

    let parent = el.closest(selector);

    if (parent) {

        if (requireConfirmation) {
            if (window.confirm('Are you sure you want to delete this?')) {
                parent.remove();
            }
        } else {
            parent.remove();
        }
    }
}

let elemCache = {};

function querySelectorCache(selector) {
    return elemCache.hasOwnProperty(selector) ? elemCache[selector] : document.querySelector(selector);
}

function querySelectorAllCache(selector) {
    return elemCache.hasOwnProperty(selector) ? elemCache[selector] : document.querySelectorAll(selector);
}

const getParent = (el) => {
    return el.closest('.parent');
}

const getChildParent = (el, parent, child) => {
    return el.closest(parent).querySelector(child)
}

const determineGamemode = parent => {
    return parent.querySelector('input.gamemode').value;
}

const checkIfEmptyOrMissingEmptyRow = (el, childToAdd, childSelector) => {
    if (!el.hasChildNodes()) {
        addElem(el, childToAdd)
        return;
    }

    childSelector = typeof childSelector === 'undefined' ? 'input' : childSelector;

    const children = el.querySelectorAll(childSelector);

    if (children[children.length - 1].value.length) {
        addElem(el, childToAdd)
    }
}

let mouseTimeout, mouseDown = false;
const createImageModal = el => {
    let src = el.src;
    let modal = elem('div', {className: 'image-modal'},
        elem('img', {src: src})
    );

    modal.addEventListener('click', ev => {
        modal.remove();
    }, true);

    document.body.append(modal);
};

const themePreviews = document.querySelectorAll('img.theme-preview, .image-preview-modal');
themePreviews.forEach(el => {
    el.addEventListener('mousedown', _ => {
        mouseDown = true;
        mouseTimeout = setTimeout(_ => {
            if (mouseDown) {
                createImageModal(el);
            }
        }, 225);
    });

    el.addEventListener('mouseup', _ => {
        clearTimeout(mouseTimeout);
        mouseDown = false;
    })
})