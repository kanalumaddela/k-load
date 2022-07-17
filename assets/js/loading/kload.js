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
 * Set the innerText of the elements matching the selector.
 *
 * @param {string} selector
 * @param {string|number} text
 * @param {boolean} raw
 */
const text = function (selector, text, raw) {
    [].forEach.call(document.querySelectorAll(selector), function (elem) {
        if (raw) {
            elem.innerHTML = text;
        } else {
            elem.innerText = text;
        }
    });
};

var KLoad = {
    isGmod: navigator.userAgent.toLowerCase().indexOf('valve') !== -1,
    loadingHooks: {},
    events: {},
    data: {
        demoMode: false,
        GameDetails: {},
        volume: 15,
        files: {
            downloaded: 0,
            needed: 1,
            total: 1,
            downloadComplete: false,
        },
    },
    finishedStatuses: [
        'Sending client info...',
        'Client info sent!',
        'Received all Lua files we needed!',
        'No files to request!',
        'Starting Lua...',
    ],
    tmp: {
        ignoreLoadings: false,
        lastFileDownloaded: null,
        statuses: [],
        progressRegex: /(\d+)\/(\d+)/,
    }
};


KLoad.emit = function (event) {
    if (typeof KLoad.events[event] !== 'undefined') {
        const args = [].slice.call(arguments);
        args.shift();

        KLoad.events[event].forEach(function (func) {
            func.apply(null, args);
        });
    }
};

KLoad.on = function (event, func) {
    if (typeof KLoad.events[event] === 'undefined') {
        KLoad.events[event] = [];
    }

    KLoad.events[event].push(func);
};

KLoad.on('GameDetails', function (servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language) {
    KLoad.data.GameDetails.servername = servername;
    KLoad.data.GameDetails.serverurl = serverurl;
    KLoad.data.GameDetails.mapname = mapname;
    KLoad.data.GameDetails.maxplayers = maxplayers;
    KLoad.data.GameDetails.steamid = steamid;
    KLoad.data.GameDetails.gamemode = gamemode;
    KLoad.data.GameDetails.volume = volume;
    KLoad.data.GameDetails.language = language;

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

KLoad.on('SetFilesTotal', function (total) {
    KLoad.data.files.total = total;
    text('.files-total', total);

    console.log('SetFilesTotal: ' + total);
});

KLoad.on('SetFilesNeeded', function (needed) {
    KLoad.data.files.needed = needed;
    text('.files-needed', needed);

    console.log('SetFilesNeeded: ' + needed);
});

KLoad.getProgress = function () {
    const progress = KLoad.data.files.downloaded / KLoad.data.files.needed;

    return {
        progress: KLoad.data.files.downloadComplete ? 1 : progress,
        percentage: KLoad.data.files.downloadComplete ? 100 : Math.round(progress * 100)
    }
}

KLoad.on('DownloadingFile', function (file) {
    KLoad.data.files.downloaded++;

    if ((KLoad.data.files.needed <= 0 || KLoad.data.files.downloaded >= KLoad.data.files.needed) && !KLoad.data.demoMode) {
        SetFilesNeeded(KLoad.data.files.downloaded + 1);
    }

    KLoad.tmp.lastFileDownloaded = file;

    SetStatusChanged('Downloading ' + file);
    text('.files-downloading', file);

    console.log('DownloadingFile: ' + file)

    if (!KLoad.data.files.downloadComplete) {
        KLoad.emit('DownloadProgress', KLoad.getProgress());
    }
});

KLoad.on('DownloadProgress', function (data) {
    text('.files-downloaded', KLoad.data.files.downloaded);
    text('.percentage', data.percentage + '%');

    [].forEach.call(document.querySelectorAll('.loading-bar, .loading-bar--vertical'), function (elem) {
        if (elem.classList.contains('.loading-bar--vertical')) {
            elem.style.height = data.percentage + '%';
        } else {
            elem.style.width = data.percentage + '%';
        }
    });
});

KLoad.on('SetStatusChanged', function (status) {
    if (status.indexOf('Extracting') !== -1 || status.indexOf('Retrieving') !== -1) {
        return;
    }

    const i = status.indexOf('Loading');
    if (i !== -1) {
        if (status.indexOf(KLoad.tmp.lastFileDownloaded) === -1) {
            KLoad.data.files.downloaded++;
            KLoad.emit('DownloadProgress', KLoad.getProgress());
        }

        status = status.substr(i);
    }

    text('.status', status);
    console.log('SetStatusChanged: ' + status)

    if (KLoad.finishedStatuses.indexOf(status) !== -1) {
        KLoad.data.files.downloadComplete = true;

        KLoad.emit('DownloadProgress', {
            progress: 1,
            percentage: 100
        });
    }
});

KLoad.init = function () {
    window.GameDetails = function (servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language) {
        KLoad.emit('GameDetails', servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language)
    };

    window.SetFilesTotal = function (total) {
        KLoad.emit('SetFilesTotal', total);
    };

    window.SetFilesNeeded = function (needed) {
        KLoad.emit('SetFilesNeeded', needed);
    };

    window.DownloadingFile = function (fileName) {
        KLoad.emit('DownloadingFile', fileName);
    };

    window.SetStatusChanged = function (status) {
        KLoad.emit('SetStatusChanged', status);
    };
};