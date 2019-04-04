<?php

/* displays php errors and allows use of {{ dump() }} in templates */
define('DEBUG', false); // default: false

/* cache data retrieved for faster load times */
define('ENABLE_CACHE', true); // default: true

/* logs all requests, mysql queries, actions performed by admins, etc */
define('ENABLE_LOG', true); // default: true

/* query parameter to force clear all cache , usage ?refresh or &refresh added the url */
define('CLEAR_CACHE', 'refresh'); // default: refresh

/* allow registration */
define('ENABLE_REGISTRATION', true); // default: true

/* override user's theme choice */
define('THEME_OVERRIDE', false); // default: false

/* users per page */
define('USERS_PER_PAGE', 16); // default: 16

/* define owner of script ( ͡° ͜ʖ ͡°) */
define('SCRIPT_OWNER', '{{ user_id }}');
