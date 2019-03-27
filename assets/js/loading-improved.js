const gamemodes = {
    cinema: 'Cinema',
    demo: 'Demo Gamemode',
    darkrp: 'DarkRP',
    deathrun: 'Deathrun',
    jailbreak: 'Jailbreak',
    melonbomber: 'Melon Bomber',
    militaryrp: 'MilitaryRP',
    murder: 'Murder',
    morbus: 'Morbus',
    policerp: 'PoliceRP',
    prophunt: 'Prophunt',
    sandbox: 'Sandbox',
    santosrp: 'SantosRP',
    schoolrp: 'SchoolRP',
    starwarsrp: 'SWRP',
    swrp: 'SWRP',
    stopitslender: 'Stop it Slender',
    slashers: 'Slashers',
    terrortown: 'TTT'
};

var files = {
    downloaded: 0,
    needed: 1,
    total: 1
};

const finishedStatuses = [
    'Sending client info...',
    'Client info sent!',
    'Received all Lua files we needed!',
    'No files to request!',
    'Starting Lua...',
];

// misc variables
var currentProgress, demoInterval;
var isGmod = navigator.userAgent.toLowerCase().indexOf('valve') !== -1;

/**
 * Generate a HTMLElement with the given attributes and optional children.
 *
 * @param {string} tag
 * @param {object} attrs
 * @param {array=} children
 * @returns {HTMLElement}
 */
const elem = function (tag, attrs, children) {
    const elem = document.createElement(tag);

    Object.keys(attrs).forEach(function (key) {
        elem.setAttribute(key, attrs[key]);
    });

    if (Array.isArray(children)) {
        children.forEach(function (child) {
            elem.appendChild(child);
        });
    }

    return elem;
};

/**
 * Generate a random string.
 *
 * @returns {string}
 */
const str_random = function () {
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
};

const str_random_v2 = function (length) {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    if (!length) {
        length = 12;
    }

    for (var i = 0; i < length; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
};

/**
 * Return a NodeList matching the given selector.
 *
 * @param {string} selector
 * @returns {NodeListOf<Element>}
 */
const getElements = function (selector) {
    return document.querySelectorAll(selector);
};

/**
 * Set the innerText of the elements matching the selector.
 *
 * @param {string} selector
 * @param {string} text
 */
const text = function (selector, text) {
    [].forEach.call(getElements(selector), function (elem) {
        elem.innerText = text;
    });
};

/**
 * Set the style.width of the elements matching the selector.
 *
 * @param {string} selector
 * @param {string} width
 */
const width = function (selector, width) {
    [].forEach.call(getElements(selector), function (elem) {
        elem.style.width = width;
    });
};

/**
 * gmod js function
 *
 * @param servername
 * @param serverurl
 * @param mapname
 * @param maxplayers
 * @param steamid
 * @param gamemode
 */
function GameDetails(servername, serverurl, mapname, maxplayers, steamid, gamemode) {
    if (demoInterval != null) {
        resetDemoMode();
    }

    if (Object.keys(gamemodes).indexOf(gamemode) !== -1) {
        gamemode = gamemodes[gamemode];
    }

    text('.server-name', servername);
    text('.server-url', serverurl);
    text('.map', mapname);
    text('.mapname', mapname);
    text('.maxplayers', maxplayers);
    text('.max-players', maxplayers);
    text('.steamid', steamid);
    text('.gamemode', gamemode);
    updateProgress();

    if (typeof GameDetails_custom === 'function') {
        GameDetails_custom(servername, serverurl, mapname, maxplayers, steamid, gamemode);
    }
}

/**
 * gmod js function
 *
 * @example 910
 * @param {number} needed
 */
function SetFilesNeeded(needed) {
    files.needed = needed;
    text('.files-needed', files.needed);
}

/**
 * gmod js function
 *
 * @example 913
 * @param {number} total
 */
function SetFilesTotal(total) {
    files.total = total;
    text('.files-total', total);
}

/**
 * gmod js function
 *
 * @param {string} file
 */
function DownloadingFile(file) {
    files.downloaded++;

    text('.files-downloading', files.downloaded);
    SetStatusChanged('Downloading ' + file);
    updateProgress();
}

/**
 * gmod js function
 *
 * @param {string} status
 */
function SetStatusChanged(status) {
    if (finishedStatuses.indexOf(status) !== -1) {
        setDownloadProgress(100);
    }

    if (status.indexOf('Loading') !== -1) {
        files.downloaded++;
        updateProgress();
    }

    text('.status', status);
}

/**
 * Recalculate the progress and set progress.
 */
function updateProgress() {
    if (files.needed <= 0 || files.downloaded >= files.needed) {
        files.needed = files.downloaded + 5;
        SetFilesNeeded(files.needed);
    }

    setDownloadProgress(files.downloaded / files.needed);
}

/**
 * Set the current download progress and update text/loading bars.
 *
 * @param {number} decimal
 */
function setDownloadProgress(decimal, force) {
    decimal = Math.abs(decimal);
    var percentage = decimal * 100;

    if (percentage >= 100) {
        percentage = 100;
    }

    const roundedPercentage = Math.ceil(percentage);

    text('.percentage', percentage + '%');

    if (currentProgress === roundedPercentage && !force) {
        return;
    }

    currentProgress = roundedPercentage;
    text('.progress', roundedPercentage + '%');
    width('.loading-bar', roundedPercentage + '%');

    if (typeof progressCallback === 'function') {
        progressCallback(decimal, percentage, roundedPercentage);
    }
}

/**
 * Create the div container for the backgrounds and insert styles.
 */
function setUpBackgrounds() {
    var backgroundsHtml = elem('div', {id: 'k-load-backgrounds'});

    document.body.appendChild(backgroundsHtml);

    var backgroundCounter = 0;
}

/**
 * Start up demo mode.
 */
function demoMode() {
    files.needed = 100;

    GameDetails('Demo Server', window.location.href, 'demo_map_name', 24, '76561198152390718', 'demo');

    demoInterval = setInterval(function () {
        DownloadingFile('example/folder/file-' + str_random_v2() + '.ext');

        if (files.downloaded >= 100) {
            files.downloaded = 0;
        }
    }, 125);
}

/**
 * Stop demo mode.
 */
function resetDemoMode() {
    files.downloaded = 0;
    files.needed = 1;
    SetStatusChanged('');
    setDownloadProgress(0);

    clearInterval(demoInterval);
}

if (!isGmod) {
    demoMode();
}

if (backgrounds.enable) {
    //setUpBackgrounds();
}