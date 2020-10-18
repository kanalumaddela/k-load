/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2020 Maddela
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
    // Object.keys(attrs).forEach(key => elem[key] = attrs[key]); <-- original

    children.forEach(child => {
        if (typeof child === "string") {
            child = document.createTextNode(child);
        }
        parent.appendChild(child);
    });
    return parent;
};