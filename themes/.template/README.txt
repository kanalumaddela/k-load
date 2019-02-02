HOW TO

1. Copy the .template folder inside the themes/ folder and rename to what you choose (lowercase only, no spaces)
2. Open up the pages/loading.twig file and insert any html to be visible inside the {% block body %}
3. CSS & JS go in their respective blocks and these blocks exist, so the core K-Load files can be loaded on top of the theme such as the global JS

USAGE

Twig Variables - <p>{{variable}}</p>
CSS Classes - <p class="something">hello</p>


/*** AVAILABLE CSS CLASSES ***/

overlay - add this is a div with no content to place a dark overlay
	dark - darker overlay
	dots - repeated pattern of square dots
	vertical - repeated pattern of vertical lines
	horizontal - repeated pattern of horizontal lines
	diagonal-left - repeated pattern of diagonal lines from left to right, bottom to top
	diagonal-right - repeated pattern of diagonal lines from right to left, bottom to top

plz-hide - hides the element completely

fucking-hide - forces the element to be hidden using !important

avatar - to be applied to user avatars, sets a max-width of 175px
	medium - sets a max-width of 125px
	small - sets a max-width of 75px
		extra - sets a max-width of 25px
	cirlce - sets a border-radius of 50% for circle avatars
	shadow - sets a box-shadow on the image

loading-bar - add to a div to create a loading bar, width is automatically handled by the JS

video-background - add this when creating a video background, makes the video full screen


/*** CSS CLASSES CONTROLLED BY JS ***/

downloading - displays what file is currently being downloaded

gamemode - displays the current gamemode

map - displays the current map name

max-players - displays the max players allowed on the server

messages - automatically inserts / fades in messages defined in K-Load

progress - displays the download progress as a percentage

server-name - displays the name of the connecting server

status - the current client connecting status

youtube-playing-title - currently playing song
youtube-playing-author - channel of the currently playing song



/*** IMPORTANT  VARIABLES ***/

{{assets}} - path relative to the 'assets' folder in the root of k-load
		e.g: /k-load/assets
		usage: <link rel="stylesheet" href="{{assets}}/css/file.css" /> or <script src="{{assets}}/js/file.js"></script>

{{assets_theme}} - same as the above except it is the path to the theme specific assets
		e.g.: /k-load/themes/themes/assets
		usage: same as above ^

{{site}} - array of urls related to the site
	{{site.host}} - domain of the site with the protocol
			e.g: https://demo.maddela.org

	{{site.path}} - absolute path to k-load from the root of the domain
			e.g.: /k-load

	{{site.url}} - site.host + site.path combined to form the full url
			e.g.: https://demo.maddela.org/k-load

	{{site.current}} - the current url you are on for the site
			e.g.: https://demo.maddela.org/k-load/

{{site_json}} - an json encoded string of the above array ^

{{cache_buster}} - randomly generated 6 character string mainly use to prevent caching so you don't have to restart gmod to see changes
			e.g.: 1432a4



/*** LOADING SCREEN VARIABLES ***/

{{backgrounds}} - json encoded string of array of backgrounds found in assets/img/backgrounds, already loaded in js

{{settings}} - array of settings for k-load
	{{backgrounds}} - json encoded string of background settings, already loading in js
			e.g.: {"enable":1,"random":1,"duration":10000,"fade":750}

	{{community_name}} - community name
			e.g.: My DarkRP Commmunity

	{{description}} - commnunity description
			e.g.: we're a fun loving community blah blah blah players

	{{messages}} - json encoded string of message settings
			e.g.: {"duration":3000,"fade":500}

	{{rules}} - json_encoded string of rules, already loaded in js

	{{staff}} - json_encoded string of staff members, already loaded in js

	{{youtube}} - json_encoded string of youtube settings, already loaded in js


{{map}} - the current map passed in &mapname=%m



/*** USER VARIABLES ***/

{{user}} - array of details for a user
	{{id}} - user's id in the database if they've registered
			e.g.: 1

	{{name}} - the user's name in the database
			e.g.: kanalumaddela

	{{steamid}} - steamid of the user (64 bit)
			e.g.: 76561198152390718

	{{steamid2}} - steamid2 of the user (32 bit)
			e.g.: STEAM_0:0:96062495

	{{steamid3}} - steamid3 of the user
			e.g.: [U:1:192124990]

	{{settings}} - array of settings the user has
		{{theme}} - the theme they have selected which is automatically the active theme
			e.g.: default

		{{backgrounds}} - json encoded string of background settings whichs overrides settings.backgrounds, already loading in js

		{{youtube}} - json encoded string of youtube settings whichs overrides the settings.youtube, already loading in js

	{{admin}} - tells if the user is an admin 0/1

	{{banned}} - tells if the user is banned through K-Load 0/1

	{{registered}} - timestamp of when the user registered on the K-Load site
			e.g.: 01/23/2018 02:08:23 PM

	{{communityvisibilitystate}} - steam api stuff
	{{profilestate}} - steam api stuff

	{{personaname}} - current name of the user from steam api
			e.g.: kanalumaddela

	{{lastlogoff}} - steam api stuff
	{{commentpermission}} - steam api stuff

	{{profileurl}} - link to the user's steam profile
			e.g.: https://steamcommunity.com/id/kanalumaddela/

	{{avatar}} - link to the user's small avatar
			e.g.: https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/be/bef34ddba1c1baa2818d004059e09e4e6e5bad1f.jpg
			usage: <img class="avatar" src="{{user.avatar}}" />

	{{avatarmedium}} - link to the user's medium avatar
			e.g.: https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/be/bef34ddba1c1baa2818d004059e09e4e6e5bad1f_medium.jpg
			usage: <img class="avatar" src="{{user.avatarmedium}}" />

	{{avatarfull}} - link to the user's large avatar
			e.g.: https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/be/bef34ddba1c1baa2818d004059e09e4e6e5bad1f_full.jpg
			usage: <img class="avatar" src="{{user.avatarfull}}" />