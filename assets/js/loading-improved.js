/**
 * List of gamemode ids and their respective friendly/well known name.
 */
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
var currentProgress, demoInterval, backgroundImages, backgroundCounter = 0, backgroundsAddedCounter = 0,
    backgroundsActive = false,
    backgroundsAdded = [];
const isGmod = navigator.userAgent.toLowerCase().indexOf('valve') !== -1;

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
        if (key in document.createElement(tag)) {
            elem[key] = attrs[key];
        } else {
            elem.setAttribute(key, attrs[key]);
        }
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

/**
 * {@link https://stackoverflow.com/a/1349426}
 *
 * @param length
 * @returns {string}
 */
const str_random_v2 = function (length) {
    var text = "";
    const possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    if (!length) {
        length = 12;
    }

    for (var i = 0; i < length; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }

    return text;
};

/**
 * Shuffle/randomize an array.
 * {@link https://www.frankmitchell.org/2015/01/fisher-yates/}
 *
 * @param {Array} array
 * @returns {Array}
 */
const shuffle = function (array) {
    var i, j = 0, temp = null;

    for (i = array.length - 1; i > 0; i -= 1) {
        j = Math.floor(Math.random() * (i + 1));
        temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
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
 * @param {string|number} text
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

    var gamemodeFriendly = gamemode;
    if (gamemode in gamemodes) {
        gamemodeFriendly = gamemodes[gamemode];
    }

    text('.server-name', servername);
    text('.server-url', serverurl);
    text('.map', mapname);
    text('.mapname', mapname);
    text('.maxplayers', maxplayers);
    text('.max-players', maxplayers);
    text('.steamid', steamid);
    text('.gamemode', gamemodeFriendly);
    updateProgress();
    setBackgrounds(gamemode);

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

    if (typeof SetFilesNeeded_custom === 'function') {
        SetFilesNeeded_custom(needed);
    }
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

    if (typeof SetFilesTotal_custom === 'function') {
        SetFilesTotal_custom(total);
    }
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

    if (typeof DownloadingFile_custom === 'function') {
        DownloadingFile_custom(file);
    }
}

/**
 * gmod js function
 *
 * @param {string} status
 */
function SetStatusChanged(status) {
    if (finishedStatuses.indexOf(status) !== -1) {
        setDownloadProgress(100, false);
    }

    if (status.indexOf('Loading') !== -1) {
        files.downloaded++;
        updateProgress();
    }

    text('.status', status);

    if (typeof SetStatusChanged_custom === 'function') {
        SetStatusChanged_custom(status);
    }
}

/**
 * Recalculate the progress and set progress.
 */
function updateProgress() {
    if (files.needed <= 0 || files.downloaded >= files.needed) {
        files.needed = files.downloaded + 5;
        SetFilesNeeded(files.needed);
    }

    setDownloadProgress(files.downloaded / files.needed, false);
}

/**
 * Set the current download progress and update text/loading bars.
 *
 * @param {number} decimal
 * @param {boolean} force
 */
function setDownloadProgress(decimal, force) {
    decimal = Math.abs(decimal);
    var percentage = decimal * 100;

    if (percentage >= 100) {
        percentage = 100;
    }

    text('.percentage', percentage + '%');

    var roundedPercentage = Math.round(10 * percentage) / 10;
    roundedPercentage = roundedPercentage.toFixed(1);

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


const backgroundsHtml = elem('div', {id: 'k-load-backgrounds'});
const backgroundCss = elem('style', {
    type: 'text/css',
    innerHTML: '.k-load-background {-webkit-transition-duration: ' + (backgrounds.fade / 1000) + 's !important;}body:before{background-image:none;}'
});
const backgroundCssRatioFix = elem('style', {
    id: 'k-load-bg-css-fix',
    type: 'text/css'
});

document.body.appendChild(backgroundsHtml);
document.head.appendChild(backgroundCss);
document.head.appendChild(backgroundCssRatioFix);

/**
 * Fix background sizing to avoid "bars"  on either top/bottom or left/right.
 */
const fixBackgrounds = debounce(function () {
    const normalRatio = 16 / 9;
    const ratio = window.innerWidth / window.innerHeight;
    const tolerance = normalRatio * 0.08;
    const html = backgroundCssRatioFix.innerHTML;

    if (ratio < normalRatio && html.length !== 47) {
        backgroundCssRatioFix.innerHTML = '.k-load-background {width:initial;height:100%;}';
    }
    if (ratio > normalRatio && html.length !== 32) {
        backgroundCssRatioFix.innerHTML = '.k-load-background {width:125%;}';
    }
    if (ratio >= (normalRatio - tolerance) && ratio <= (normalRatio + tolerance) && html.length !== 0) {
        backgroundCssRatioFix.innerHTML = '';
    }
}, 250);

fixBackgrounds();
window.addEventListener('resize', fixBackgrounds);

/**
 * Configure backgrounds to be used for the given gamemode.
 *
 * @param {string} gamemode
 */
function setBackgrounds(gamemode) {
    var gamemodeExists = gamemode in backgrounds.list;
    const globalExists = 'global' in backgrounds.list;

    if (!gamemodeExists && globalExists) {
        gamemode = 'global';
        gamemodeExists = true;
    }

    if (!backgrounds.enable || backgroundsActive || !gamemodeExists) {
        clearBackgrounds();

        if (!backgrounds.enable || !gamemodeExists) {
            backgroundsActive = false;
            return;
        }
    }

    backgroundImages = backgrounds.list[gamemode].slice(0);
    if (backgrounds.random) {
        shuffle(backgroundImages);
    }

    backgroundsActive = true;

    loadNextBackground();
    nextBackground();
}

/**
 * Remove all backgrounds from container.
 */
function clearBackgrounds() {
    while (backgroundsHtml.firstChild) {
        backgroundsHtml.removeChild(backgroundsHtml.firstChild);
    }

    backgroundsAdded = [];
}

/**
 * Preload the next background so it's ready to be displayed.
 */
function loadNextBackground() {
    if (backgroundsAdded.length === backgroundImages.length) {
        return;
    }

    const bgSrc = backgroundImages[backgroundsAddedCounter];

    if (backgroundsAdded.indexOf(bgSrc) === -1) {
        const bgElem = elem('img', {src: bgSrc, className: 'k-load-background'});
        bgElem.addEventListener('webkitTransitionEnd', function () {
            if (this.classList.contains('active')) {
                loadNextBackground();
            }

            queueBackground(backgrounds.duration);
        });

        backgroundsHtml.appendChild(bgElem);
        backgroundsAdded.push(bgSrc);

        backgroundsAddedCounter++;
    }
}

/**
 * Advance to the next background.
 */
function nextBackground() {
    if (!backgroundsActive) {
        return;
    }

    if (backgroundCounter >= backgroundImages.length) {
        backgroundCounter = 0;
    }

    const tmpCounter = backgroundCounter;

    setTimeout(function () {
        switch (tmpCounter) {
            case 0:
                backgroundsHtml.childNodes[tmpCounter].classList.add('active');

                if (backgroundsAdded.length !== 1) {
                    backgroundsHtml.childNodes[backgroundsAdded.length - 1].classList.remove('active');
                }

                break;
            default:
                backgroundsHtml.childNodes[tmpCounter - 1].classList.remove('active');
                backgroundsHtml.childNodes[tmpCounter].classList.add('active');
                break;
        }

        backgroundCounter = tmpCounter + 1;
    }, 25);
}

/**
 * Queue to display the background in x seconds
 *
 * @param {number} milliseconds
 */
function queueBackground(milliseconds) {
    setTimeout(function () {
        nextBackground();
    }, milliseconds);
}

/**
 * Start up demo mode.
 */
function demoMode() {
    files.needed = Math.floor(Math.random() * 1000) + 100;

    GameDetails('Demo Server', window.location.href, 'demo_map_name', 24, '76561198152390718', 'demo');

    demoInterval = setInterval(function () {
        DownloadingFile('example/folder/file-' + str_random_v2() + '.ext');

        if (files.downloaded >= files.needed) {
            files.downloaded = 0;
        }
    }, 200);
}

/**
 * Stop demo mode.
 */
function resetDemoMode() {
    files.downloaded = 0;
    files.needed = 1;
    backgroundsActive = false;
    SetStatusChanged('');
    setDownloadProgress(0, false);

    clearInterval(demoInterval);
}

/**
 * Music/Youtube
 */
function loadYoutubeAPI() {
    if (typeof yt_player === 'undefined') {
        document.body.appendChild(elem('script', {src: 'https://www.youtube.com/iframe_api'}));
    }
}

var audio, yt_player, music_counter = 0;
var yt_list = youtube.list.slice(0), music_list = music.order.slice(0);

if (music.enable) {
    if (music.random) {
        shuffle(yt_list);
        shuffle(music_list);
    }

    switch (music.source) {
        case 'youtube':
            if (yt_list.length > 0) {
                loadYoutubeAPI();
            }
            break;
        case 'files':
            if (music_list.length > 0) {
                audio = new Audio(site.url + '/data/music/' + music_list[music_counter]);
                audio.volume = music.volume / 100;
                audio.load();

                audio.addEventListener('canplay', function () {
                    this.play();
                });

                audio.addEventListener('ended', function () {
                    music_counter += 1;
                    if (music_counter > music_list.length) {
                        music_counter = 0;
                    }

                    const song = music_list[music_counter];

                    musicName = song.replace('.ogg', '');

                    updatePlaying({title: musicName, author: ''});

                    this.src = site.url + '/data/music/' + song;
                    this.load();
                    this.play();
                });
            }
    }
}

/**
 * If we're not in game, demo the loading screen.
 */
if (!isGmod) {
    demoMode();
}
