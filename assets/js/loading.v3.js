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

class KLoad {
    static gamemodes = {
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

    constructor(config = {}) {
        this.config = config;

        this.files = {
            downloaded: 0,
            needed: 0,
            total: 0,
        }

        this.customGamemodes = {};
        this.hooks = {};
    }

    defineGamemode({id, friendlyName}) {
        this.customGamemodes[id] = friendlyName;
    }

    defineGamemodes(arr) {
        const cnt = arr.length;

        for (let i = 0; i < cnt; i++) {
            this.defineGamemode(arr[i]);
        }
    }

    init() {
        function GameDetails(servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language) {
            if (this.config.automatic) {

            }
            this.GameDetails(servername, serverurl, mapname, maxplayers, steamid, gamemode, volume, language)
        }

        function SetFilesTotal(total) {
            this.SetFilesTotal(total);
        }

        function SetFilesNeeded(needed) {
            this.SetFilesNeeded(needed);
        }

        function DownloadingFile(fileName) {
            this.DownloadingFile(fileName);
        }

        function SetStatusChanged(status) {
            this.SetStatusChanged(status);
        }
    }

    addHook(event, callback) {

    }
}

