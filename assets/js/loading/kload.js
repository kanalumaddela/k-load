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

/**
 * Set the innerText of the elements matching the selector.
 *
 * @param {string} selector
 * @param {string|number} text
 */
const text = function (selector, text) {
    [].forEach.call(getElements(selector), function (elem) {
        elem.innerText = text;
    });
};

var KLoad = {
    loadingHooks: {},
    hookList: {},
    data: {
        demoMode: false,
        volume: 15,
        files: {
            downloaded: 1,
            needed: 1,
            total: 1,
        },

    }
};

KLoad.setVolume = function (volume) {
    KLoad.data.volume = volume;
}

KLoad.getVolume = function () {
    return KLoad.data.volume;
}

KLoad.http = function (route, callback, method) {
    const xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            callback(this.response);
        }
    };

    if (typeof method === 'undefined') {
        method = 'GET';
    }

    xmlhttp.open(method.toUpperCase(), route, true);
    xmlhttp.send();
};

/**
 * Shuffle/randomize an array.
 * {@link https://www.frankmitchell.org/2015/01/fisher-yates/}
 *
 * @param {Array} array
 * @returns {Array}
 */
KLoad.shuffle = function (array) {
    var i, j = 0, temp = null;

    for (i = array.length - 1; i > 0; i -= 1) {
        j = Math.floor(Math.random() * (i + 1));
        temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
};

KLoad.addHook = function (event, name, func) {
    if (typeof KLoad.loadingHooks[event] === 'undefined') {
        KLoad.loadingHooks[event] = {};
    }

    if (typeof KLoad.hookList[name] !== 'undefined' && KLoad.hookList[name] !== event) {
        console.error('Dupliate hook name `' + name + '` which currently exists for event `' + KLoad.hookList[name] + '`')
    }

    KLoad.loadingHooks[event][name] = func;
    KLoad.hookList[name] = event;
}

KLoad.removeHook = function (name) {
    if (typeof KLoad.hookList[name] === 'undefined') {
        return;
    }

    delete KLoad.loadingHooks[KLoad.hookList[name]][name];
    delete KLoad.hookList[name];
}

KLoad.deleteHook = function () {
    KLoad.removeHook.apply(null, arguments);
}

KLoad.fireHook = function (event) {
    if (typeof KLoad.loadingHooks[event] === 'undefined') {
        console.error('No hooks defined for `' + event + '`');
        return;
    }

    var hooksToFire = Object.entries(KLoad.loadingHooks[event]);

    for (var i = 0; i < hooksToFire.length; i++) {
        var args = Array.prototype.slice.call(arguments);
        args.shift();

        hooksToFire[i][1].apply(null, args);
    }
}

KLoad.init = function () {
    window.GameDetails = function (servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language) {
        KLoad.fireHook('GameDetails', servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language)
    }

    window.SetFilesTotal = function (total) {
        KLoad.fireHook('SetFilesTotal', total);
    }

    window.SetFilesNeeded = function (needed) {
        KLoad.fireHook('SetFilesNeeded', needed);
    }

    window.DownloadingFile = function (fileName) {
        KLoad.fireHook('DownloadingFile', fileName);
    }

    window.SetStatusChanged = function (status) {
        KLoad.fireHook('SetStatusChanged', status);
    }
}

KLoad.addHook('GameDetails', 'KLoad.GameDetails', function (servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language) {
    KLoad.data.volume = volume;

    text('.server-name', servername);
    text('.server-url', serverurl);
    text('.map', mapname);
    text('.mapname', mapname);
    text('.max-players', maxplayers);
    text('.steamid', steamid);
    text('.gamemode', gamemode);
    text('.volume', volume);
    text('.language', language);
});
KLoad.addHook('SetFilesTotal', 'KLoad.SetFilesTotal', function (total) {
    KLoad.data.files.total = total;

    text('.files-total', total);
});
KLoad.addHook('SetFilesNeeded', 'KLoad.SetFilesNeeded', function (needed) {
    KLoad.data.files.needed = needed;

    text('.files-needed', needed);
});
KLoad.addHook('DownloadingFile', 'KLoad.DownloadingFile', function (file) {
    KLoad.data.files.downloaded++;

    if ((KLoad.data.files.needed <= 0 || KLoad.data.files.downloaded >= KLoad.data.files.needed) && !KLoad.data.demoMode) {
        SetFilesNeeded(KLoad.data.files.downloaded + 1);
    }

    text('.files-downloading', file);
    text('.files-downloaded', KLoad.data.files.downloaded);
});