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

const api = function (method, query, callback) {
    const xmlhttp = new XMLHttpRequest();
    const url = site.path + '/api/' + method + '/' + query;

    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            const data = JSON.parse(this.response);
            callback(data);
        }
    };

    xmlhttp.open("GET", url, true);
    xmlhttp.send();
};

/**
 * Remove all children from a parent element.
 */
const clearChildren = function (parent) {
    while (parent.firstChild) {
        parent.removeChild(parent.firstChild);
    }
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
 * Set the src of the elements matching the selector
 *
 * @param {string} selector
 * @param {string} src
 */
const image = function (selector, src) {
    [].forEach.call(getElements(selector), function (elem) {
        elem.src = src;
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
 * @param volume
 */
function GameDetails(servername, serverurl, mapname, maxplayers, steamid, gamemode, volume) {
    if (demoInterval != null) {
        resetDemoMode();
    }

    if (forcedGamemode.length > 0) {
        gamemode = forcedGamemode;
    }

    if (volume) {
        console.log('client volume: ' + volume);
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
    setMessages(gamemode);
    setMusic(gamemode);

    setRules(gamemode, rules_per_page);
    setStaff(gamemode, staff_per_page);

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

    text('.file-downloading', file);
    text('.files-downloaded', files.downloaded);
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
    if ((files.needed <= 0 || files.downloaded >= files.needed) && !inDemoMode) {
        files.needed = files.downloaded + 1;
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

function checkGamemode(gamemode, list)  {
    var gamemodeExists = gamemode in list;
    const globalExists = 'global' in list;

    if (!gamemodeExists && globalExists) {
        gamemode = 'global';
        gamemodeExists = true;
    }

    return gamemodeExists ? gamemode : null;
}

/**
 * Messages
 */
var messageInterval, messageCounter = 0, messagesActive = false;
document.head.appendChild(elem('style', {innerHTML: '.messages{-webkit-transition-duration: '+(messages.fade/1000)+'s}'}));
function setMessages(gamemode) {
    gamemode = checkGamemode(gamemode, messages.list);

    if (gamemode) {
        messagesActive = true;
        text('.messages', '');

        var gmMessages = messages.list[gamemode];

        if (messages.random) {
            shuffle(gmMessages);
        }
        const message_elems = getElements('.messages');

        [].forEach.call(message_elems, function (item) {
            item.addEventListener('webkitTransitionEnd', function () {
                const opacity = window.getComputedStyle(this, null).getPropertyValue('opacity');

                if (opacity === '0') {
                    if (!messagesActive) {
                        item.innerText = '';
                        messageCounter = 0;
                    } else {
                        messageCounter++;

                        if (messageCounter >= gmMessages.length) {
                            messageCounter = 0;
                        }

                        item.innerText = gmMessages[messageCounter];
                        item.style.opacity = '1';
                    }
                }

                if (opacity === '1') {
                    setTimeout(function () {
                        item.style.opacity  = '0';
                    }, messages.duration);
                }
            });

            item.innerText = gmMessages[messageCounter];
            item.style.opacity = 0;
            item.style.opacity = 1;
        });
    }
}

/**
 * Rules
 */
const rules_block = document.getElementById('k-load-rules');
var rulesInterval, ruleBlockCounter = 0;

function setRules(gamemode, perPage) {
    if ('list' in rules === false) {
        rules = {
            duration: 10000,
            list: rules
        }
    }

    gamemode = checkGamemode(gamemode, rules.list);

    if (gamemode && rules_block) {
        clearInterval(rulesInterval);
        clearChildren(rules_block);

        const gmRules = rules.list[gamemode];
        const num_blocks = Math.ceil(gmRules.length / perPage);
        var ruleCount = 0;


        for (var i = 0; i < num_blocks; i++) {
            var children = [];

            for (var x = 0; x < perPage; x++) {
                if (ruleCount < gmRules.length) {
                    children.push(
                        elem('div', {className: 'k-load-rule', innerText: gmRules[ruleCount]})
                    );

                    ruleCount++;
                }
            }

            rules_block.appendChild(
                elem('div', {id: 'k-load-rule-block-' + i, className: 'k-load-rule-block'}, children)
            );
        }

        rules_block.childNodes[ruleBlockCounter].style.display = 'block';
        rules_block.childNodes[ruleBlockCounter].classList.add('active');

        if (num_blocks > 1) {
            rulesInterval = setInterval(function () {
                rules_block.childNodes[ruleBlockCounter].classList.remove('active');
                setTimeout(function () {
                    rules_block.childNodes[ruleBlockCounter].style.display = 'none';

                    var nextSib = rules_block.childNodes[ruleBlockCounter].nextSibling;

                    if (nextSib) {
                        ruleBlockCounter++;
                    } else {
                        ruleBlockCounter = 0;
                        nextSib = rules_block.childNodes[ruleBlockCounter];
                    }

                    nextSib.style.display = 'block';
                    nextSib.classList.add('active');
                }, staff_fade_delay);
            }, rules.duration);
        }
    }
}

/**
 * Staff
 */
const staff_block = document.getElementById('k-load-staff');
var staffInterval, staffBlockCounter = 0, staffActive = false;

function setStaff(gamemode, perPage) {
    if ('list' in staff === false) {
        staff = {
            duration: 5000,
            list: staff
        }
    }

    gamemode = checkGamemode(gamemode, staff.list);

    if (gamemode && staff_block) {
        clearInterval(staffInterval);
        clearChildren(staff_block);

        staffActive = true;

        const gmStaff = staff.list[gamemode];
        const num_blocks = Math.ceil(gmStaff.length / perPage);
        var staffCount = 0;
        var steamids = [];


        for (var i = 0; i < num_blocks; i++) {
            var children = [];

            for (var x = 0; x < perPage; x++) {
                if (staffCount < gmStaff.length) {
                    var staff_member = gmStaff[staffCount];
                    steamids.push(staff_member.steamid);

                    children.push(
                        elem('div', {className: 'k-load-staff'}, [
                            elem('img', {
                                className: 'avatar avatar-'+staff_member.steamid
                            }),
                            elem('div', {className: 'k-load-staff-info'}, [
                                elem('span', {
                                    className: 'k-load-staff--name username-' + staff_member.steamid,
                                    innerText: staff_member.steamid
                                }),
                                elem('span', {className: 'k-load-staff--rank', innerText: staff_member.rank})
                            ])
                        ])
                    );

                    staffCount++;
                }
            }

            var tmp_staff_block = elem('div', {id: 'k-load-staff-block-' + i, className: 'k-load-staff-block'}, children);
            staff_block.appendChild(tmp_staff_block);
        }

        api('players', steamids.join(','), fixStaff);

        staff_block.childNodes[staffBlockCounter].style.display = 'block';
        staff_block.childNodes[staffBlockCounter].classList.add('active');

        if (num_blocks > 1) {
            staffInterval = setInterval(function () {
                staff_block.childNodes[staffBlockCounter].classList.remove('active');
                const tmpCounter = staffBlockCounter;
                setTimeout(function () {
                    staff_block.childNodes[tmpCounter].style.display = 'none';

                    var nextSib = staff_block.childNodes[tmpCounter].nextSibling;

                    if (nextSib) {
                        staffBlockCounter = tmpCounter+1;
                    } else {
                        staffBlockCounter = 0;
                        nextSib = staff_block.childNodes[staffBlockCounter];
                    }

                    nextSib.style.display = 'block';
                    setTimeout(function () {
                        nextSib.classList.add('active');
                    }, 25);
                }, staff_fade_delay);
            }, staff.duration);
        }
    }
}

function fixStaff(data) {
    if (data.success) {
        data = data.data;

        data.forEach(function(row) {
            text('.username-'+row.steamid, row.personaname);
            image('.avatar-'+row.steamid, row.avatarmedium);
        });
    }

    staff_block.childNodes[staffBlockCounter].style.display = 'block';
    staff_block.childNodes[staffBlockCounter].classList.add('active');
}


/**
 * Backgrounds
 */
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
if (backgrounds.enable) {
    document.head.appendChild(backgroundCss);
}
document.head.appendChild(backgroundCssRatioFix);

function fixBackground(elem) {
    var windowRatio = window.innerWidth / window.innerHeight;

    if (windowRatio > 1) { // wide screen

    } else if (windowRatio < 1) { // tall screen

    } else { // square

    }
}

function proccessBackgroundElem(elem) {
    var imageRatio = elem.naturalWidth / elem.naturalHeight;

    if (imageRatio > 1) { // wide image

    } else if (imageRatio < 1) { // tall image

    } else { // square

    }
}

/**
 * Fix background sizing to avoid "bars"  on either top/bottom or left/right.
 */
const fixBackgrounds = debounce(function () {
    var activeBg = backgroundsHtml.getElementsByClassName('k-load-background active');

    if (activeBg.length === 0) {
        return;
    }

    activeBg = activeBg[0];

    console.log('test');

    console.log(activeBg);

    fixBackgroundSizing(activeBg);
    return;


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
}, 100);

function fixBackgroundSizing(elem) {
    var windowRatio = window.innerWidth / window.innerHeight;
    var imageRatio = elem.naturalWidth / elem.naturalHeight;

    console.log(elem);

    var multiplier = 0;

    if (elem.naturalWidth > elem.naturalHeight && window) {
        console.log('change multiplier');

        multiplier = window.innerHeight / elem.naturalHeight;

        console.log(multiplier);
    } else {
        multiplier = window.innerWidth / elem.naturalWidth;
    }

    elem.height = elem.naturalHeight * multiplier;
    elem.width = elem.naturalWidth * multiplier;
}

fixBackgrounds();
window.addEventListener('resize', fixBackgrounds);

/**
 * Configure backgrounds to be used for the given gamemode.
 *
 * @param {string} gamemode
 */
function setBackgrounds(gamemode) {
    gamemode = checkGamemode(gamemode, backgrounds.list);

    if (!backgrounds.enable || backgroundsActive || !gamemode) {
        clearBackgrounds();

        if (!backgrounds.enable || !gamemode) {
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
    clearChildren(backgroundsHtml);

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

        bgElem.addEventListener('resize', function () {
            console.log('resize');
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
        if (tmpCounter === 0) {
            backgroundsHtml.childNodes[tmpCounter].classList.add('active');

            if (backgroundsAdded.length !== 1) {
                backgroundsHtml.childNodes[backgroundsAdded.length - 1].classList.remove('active');
            }
        } else {
            backgroundsHtml.childNodes[tmpCounter - 1].classList.remove('active');
            backgroundsHtml.childNodes[tmpCounter].classList.add('active');
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
var inDemoMode = false;
function demoMode() {
    inDemoMode = true;

    SetFilesNeeded(100);
    // SetFilesNeeded(Math.floor(Math.random() * 100) + 50);
    GameDetails('Demo Server', window.location.href, 'demo_map_name', 24, '76561198152390718', 'demo');

    demoInterval = setInterval(function () {
        if (files.downloaded >= files.needed) {
            files.downloaded = 0;
        }

        DownloadingFile('example/folder/file-' + str_random_v2() + '.ext');
    }, 125);
}

/**
 * Stop demo mode.
 */
function resetDemoMode() {
    inDemoMode = false;
    files.downloaded = 0;
    files.needed = 1;
    messagesActive = backgroundsActive = false;
    SetStatusChanged('');
    setDownloadProgress(0, false);

    clearInterval(rulesInterval);
    clearInterval(staffInterval);
    clearInterval(demoInterval);
}

/**
 * Music/Youtube
 */
var audio, yt_player, music_counter = 0;
var yt_list = youtube.list.slice(0);
const music_block = document.getElementById('music-block');

function loadYoutubeAPI() {
    if (typeof yt_player === 'undefined') {
        document.body.appendChild(elem('script', {src: 'https://www.youtube.com/iframe_api'}));
    }
}

function onYouTubeIframeAPIReady() {
    document.body.appendChild(elem('div', {id: 'youtube_player'}));
    yt_player = new YT.Player('youtube_player', {
        height: '0',
        width: '0',
        playerVars: {
            autoplay: 0,
            controls: 0,
            fs: 0,
            iv_load_policy: 3,
            modestbranding: 1,
            showinfo: 0
        },
        events: {
            'onReady': onYTMusicPlayerReady,
            'onStateChange': onMusicPlayerStateChange
        }
    });
}

function onYTMusicPlayerReady(event) {
    youtube.index = 0;
    event.target.setVolume(0);
    event.target.cueVideoById(yt_list[youtube.index], 0, "small");
}

function onMusicPlayerStateChange(event) {
    if (event.data === YT.PlayerState.CUED) {
        event.target.playVideo();
    }
    if (event.data === YT.PlayerState.PLAYING) {
        audioFadeIn();
        updatePlaying(event.target.getVideoData());
    }
    if (event.data === YT.PlayerState.ENDED) {
        youtube.index++;
        if (youtube.index >= yt_list.length) {
            youtube.index = 0
        }
        event.target.setVolume(0);
        event.target.cueVideoById(yt_list[youtube.index]);
    }
}

function updatePlaying(data) {
    if (data.title.length > 0 && data.title.length !== '') {
        text('.youtube-playing-author', data.author);
        text('.youtube-playing-title', data.title);
    }
}

var tmpAudioFade;
function audioFadeIn() {
    switch (music.source) {
        case 'youtube':
            if (yt_player.getVolume() < music.volume) {
                yt_player.setVolume(yt_player.getVolume() + 1);
                setTimeout(audioFadeIn, 50);
            }
            break;
        case 'files':
            if (audio.volume < music.volumeDecimal) {
                audio.volume = Math.round((audio.volume + .01) * 100) / 100;
                setTimeout(audioFadeIn, 50);
            }
            break;
    }
}

function audioFadeOut() {
    switch (music.source) {
        case 'youtube':
            if (yt_player.getVolume() > 0) {
                yt_player.setVolume(yt_player.getVolume() - 1);
                setTimeout(audioFadeOut, 50);
            }
            break;
        case 'files':
            if (audio.volume > 0) {
                audio.volume = Math.round((audio.volume - .01) * 100) / 100;
                setTimeout(audioFadeOut, 50);
            }
            break;
    }
}

function setMusic(gamemode) {
    gamemode = checkGamemode(gamemode, music.order);

    if (gamemode && music.enable) {
        var music_list = music.order[gamemode];

        if (music.random) {
            shuffle(yt_list);
            shuffle(music_list);
        }

        music.volumeDecimal = music.volume / 100;

        switch (music.source) {
            case 'youtube':
                if (yt_list.length > 0) {
                    loadYoutubeAPI();
                    if (music_block) {
                        music_block.classList.add('fade-in');
                    }
                }
                break;
            case 'files':
                if (music_list.length > 0) {
                    if (music_block) {
                        music_block.classList.add('fade-in');
                    }

                    audio = new Audio(site.url + '/data/music/' + music_list[music_counter]);
                    audio.volume = 0;
                    audio.load();

                    audio.addEventListener('canplay', function () {
                        var aud = this;

                        // setTimeout(function () {
                        //     var audioPromise = aud.play();
                        //
                        //     var tmpSong = music_list[music_counter];
                        //     musicName = tmpSong.replace('.ogg', '');
                        //
                        //     if (audioPromise !== undefined) {
                        //         audioPromise.then(_ => {
                        //
                        //             updatePlaying({title: musicName, author: ''});
                        //
                        //             audioFadeIn();
                        //         }).catch(error => {
                        //             updatePlaying({title: 'Failed to play: ' + musicName, author: ''});
                        //         });
                        //     }
                        //
                        // }, 250);
                    });

                    audio.addEventListener('timeupdate', function () {
                        if (isNaN(audio.duration)) {
                            return;
                        }
                        if (audio.duration - audio.currentTime <= 1) {
                            audioFadeOut();
                        }
                    });

                    audio.addEventListener('ended', function () {
                        this.volume = 0;
                        music_counter += 1;

                        if (music_counter >= music_list.length) {
                            music_counter = 0;
                        }

                        var tmpSong = music_list[music_counter];

                        musicName = tmpSong.replace('.ogg', '');

                        updatePlaying({title: musicName, author: ''});

                        this.src = site.url + '/data/music/' + tmpSong;
                        this.load();
                    });
                }
        }
    }
}

/**
 * If we're not in game, demo the loading screen.
 */
if (!isGmod) {
    demoMode();
}
