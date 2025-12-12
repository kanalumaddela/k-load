/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

/**
 * Set the innerText of the elements matching the selector.
 *
 * @param {string} selector
 * @param {string|number} text
 * @param {boolean} raw
 */
const text = function (selector, text, raw = false) {
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
});

KLoad.on('SetFilesNeeded', function (needed) {
    KLoad.data.files.needed = needed;
    text('.files-needed', needed);
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

KLoad.Backgrounds = {
    init: function (backgrounds, options) {
        this.setBackgrounds(backgrounds);
        this.setOptions(options);
        this.setupCss();
        this.setupContainer();
        this.data = {
            currentIndex: -1,
            backgroundChildren: 0,
            activeBackgrounds: [],
            timer: null,
        }

        if (backgrounds && backgrounds.length) {
            this.start();
        }

        return this;
    },

    start: function () {
        [].forEach.call(this.backgrounds, function (url) {
            this.container.appendChild(this.createBackground(url))
        }.bind(this));

        this.data.activeBackgrounds = this.backgrounds;
        this.data.backgroundChildren = this.container.querySelectorAll('.bg-item');

        this.nextBackground();

        this.data.timer = setInterval(function () {
            this.nextBackground();
        }.bind(this), this.options.duration);
    },

    nextBackground: function () {
        this.data.currentIndex++;

        if (this.data.currentIndex >= this.data.activeBackgrounds.length) {
            this.data.currentIndex = 0;
        }

        const indx = this.data.currentIndex;

        this.data.backgroundChildren[indx].style.opacity = '1';

        if (indx === 0) {
            this.data.backgroundChildren[this.data.activeBackgrounds.length - 1].style.opacity = '0';
        } else {
            this.data.backgroundChildren[indx - 1].style.opacity = '0';
        }

        return this;
    },

    setupContainer: function () {
        this.container = document.querySelector('.k-load-background-container');

        if (!this.container) {
            window.document.body.innerHTML += '<div class="k-load-background-container"></div>';
            this.container = document.querySelector('.k-load-background-container');
        }

        return this;
    },

    setupCss: function () {
        window.document.body.innerHTML += '<style>body > .k-load-background-container {z-index: -9999;pointer-events: none;position: fixed;top: 0;left: 0;width: 100%;height: 100%;}.k-load-background-container > .bg-item {z-index: -9998;position: absolute;width: 100%;height: 100%;background-size: cover;background-position: center;opacity: 0;-webkit-transition: opacity ' + (Math.round(this.options.fade / 1000 * 100) / 100) + 's ease-in;}</style>';

        return this;
    },

    setBackgrounds: function (backgrounds) {
        if (!backgrounds) {
            backgrounds = [];
        }

        this.backgrounds = backgrounds;

        return this;
    },

    getBackgrounds: function () {
        return this.backgrounds;
    },

    createBackground: function (imageUrl) {
        const div = document.createElement('div');

        div.className = 'bg-item';
        div.style.backgroundImage = 'url(' + imageUrl + ')';

        return div;
    },

    setOptions: function (options) {
        if (!options) {
            options = {};
        }

        this.options = {
            random: options.hasOwnProperty('random') ? options.random : true,
            fade: options.hasOwnProperty('fade') ? options.fade : 750,
            duration: options.hasOwnProperty('duration') ? options.duration : 5 * 1000,
        }

        return this;
    },

    getOptions: function () {
        return this.options;
    }
}