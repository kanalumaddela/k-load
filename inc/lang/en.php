<?php
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

return [
    /*** dashboard specific ***/

    // login/logout
    'login_with_steam' => 'Login with Steam',
    'login' => 'Login',
    'logout' => 'Logout',

    // common words
    'home' => 'Home',
    'dashboard' => 'Dashboard',
    'settings' => 'Settings',
    'my_settings' => 'My Settings',
    'user' => 'User',
    'users' => 'Users',
    'admin' => 'Admin',
    'core' => 'Core',
    'general' => 'General',
    'backgrounds' => 'Backgrounds',
    'messages' => 'Messages',
    'music' => 'Music',
    'rules' => 'Rules',
    'staff' => 'Staff',
    'theme' => 'Theme',
    'themes' => 'Themes',
    'profile' => 'Profile',
    'permissions' => 'Permissions',
    'community_name' => 'Community Name',
    'description' => 'Description',
    'youtube' => 'YouTube',
    'gamemode' => 'Gamemode',

    // common setting phrases
    'close' => 'Close',
    'remove' => 'Remove',
    'save' => 'Save',
    'save_changes' => 'Save Changes',
    'no' => 'No',
    'yes' => 'Yes',
    'enable' => 'Enable',
    'enabled' => 'Enabled',
    'randomize' => 'Randomize',
    'randomized' => 'Randomized',
    'random' => 'Random',
    'volume' => 'Volume',
    'duration' => 'Duration',
    'fade' => 'Fade',
    'yt_videos_to_play' => 'YouTube videos to play',
    'enter_youtube_link' => 'Enter a YouTube link',
    'add_video' => 'Add Video',
    'add_song' => 'Add Song',
    'add_gamemode' => 'Add Gamemode',
    'add_staff_member' => 'Add Staff Member',
    'add_rule' => 'Add Rule',
    'add_message' => 'Add Message',
    'background_duration' => 'Background Duration',
    'background_fade' => 'Background Fade',
    'delete' => 'Delete',
    'enable_question' => 'Enable?',
    'randomize_question' => 'Randomize?',
    'upload' => 'Upload',

    // navigation tooltips
    'my_settings_tooltip' => 'This is for your own (player specific) settings',
    'admin_tooltip' => 'This is for global (all players) settings',

    // steamid
    'steamids' => 'SteamIDs',
    'steamid' => 'SteamID',
    'steamid2' => 'SteamID2',
    'steamid3' => 'SteamID3',

    // gamemode examples
    'gamemodes_examples' => 'Example Gamemodes',
    'gamemode_hint_1' => 'If you do not want to set a gamemode, use `global` as the gamemode to make it the default',
    'gamemode_hint_2' => 'Majority of gamemode ids is the name of the gamemode folder on your server in `garrysmod/gamemodes`',

    // dashboard index
    'welcome_user' => 'Welcome %s',
    'loading_screen_access_help' => 'To preview your customized loading screen, visit:',
    'your_info' => 'Your Info',
    'your_perms' => 'Your Permissions',
    'user_is_super' => 'You are a super admin',

    // my settings page
    'admin_global_settings_notice' => 'To edit the loading screen for all users visit:',

    // users page
    'users_page' => 'Users - Page %s',
    'users_search_placeholder' => 'Enter any type of steamid or a name',
    'search' => 'Search',
    'player' => 'Player',

    // profile page
    'name' => 'Name',
    'loading_screen' => 'Loading Screen',
    'registered' => 'Registered',
    'copy_user_settings' => 'Copy User\'s Settings',

    // admin core
    'config' => 'Config',
    'server_cfg_notice' => 'Your server.cfg you should have this',
    'default_theme' => 'Default Theme',
    'tools' => 'Tools',
    'updates' => 'Updates',
    'current_version' => 'Current Version',
    'updates_available' => [
        'There is %s update available',
        'There are %s updates available',
    ],
    'latest_version_available' => 'Latest Version',
    'refresh_themes' => 'Refresh Themes',
    'clear_cache' => 'Clear Cache',
    'cache_all' => 'All Cache',
    'cache_data' => 'Data Cache',
    'cache_template' => 'Template Cache',
    'recompile_css' => 'Recompile CSS',
    'reset_perms' => 'Reset Perms',
    'steam_api_key' => 'Steam API key',
    'loading_url' => 'Loading URL',
    'version' => 'Version',
    'quick_info' => 'Quick Info',
    'quick_info_copy_notice' => 'Click to copy',
    'quick_settings' => 'Quick Settings',
    'drag_drop_click_upload' => 'Drag and drop or Click to upload',

    // admin general
    'logo' => 'Logo',
    'upload_logo' => 'Upload Logo',
    'gamemode_help' => 'Gamemode Help',

    // admin backgrounds
    'upload_backgrounds' => 'Upload Backgrounds',
    'uploaded_backgrounds' => 'Uploaded Backgrounds',
    'message_duration' => 'Message Duration',
    'message_fade' => 'Message Fade',
    'upload_limits' => 'Upload Limits',
    'upload_limit_notice' => '* Some may not apply to this specific upload, exceeding these limits may cause no files to upload or error',
    'max_uploads' => 'Maximum of %s uploads allowed',
    'true_max_uploads' => 'True maximum of %s uploads allowed using max file size limits',
    'max_file_size' => 'Maximum size per file: %s',
    'max_post_size' => 'Maximum overall upload limit: %s',

    // admin music
    'music_general_options' => 'General Music Options',
    'music_source' => 'Music Source',
    'music_files' => 'Music Files',
    'songs_to_play' => 'Songs to play',
    'save_music' => 'Save Music',
    'save_music_order' => 'Save Music Order',
    'save_youtube' => 'Save YouTube',
    'upload_music' => 'Upload Music',
    'music_upload_notice' => 'When uploading music, the page automatically refreshes to update the list of songs.',
    'music_updated' => 'Music updated',

    // admin messages
    'message_settings' => 'Message Settings',
    'messages_updated' => 'Messages updated',

    // admin rules
    'rule_block_duration' => 'Rule Block Duration',
    'rule_block_duration_hint' => 'Rules are displayed x amount at a time, this is determined by the loading theme. Choose how many millseconds to display each block/section of rules before going on the next one.',
    'rule_type_0' => 'Disable',
    'rule_type_1' => 'Decimal numbers (1, 2, 3, 4)',
    'rule_type_I' => 'Roman numbers, uppercase (I, II, III, IV)',
    'rule_type_i' => 'Roman numbers, lowercase (i, ii, iii, iv)',
    'rule_type_A' => 'Alphabetically ordered list, uppercase (A, B, C, D)',
    'rule_type_a' => 'Alphabetically ordered list, lowercase (a, b, c, d)',

    // admin staff
    'staff_block_duration' => 'Staff Block Duration',
    'staff_block_duration_hint' => 'Staff are displayed x amount at a time, this is determined by the loading theme. Choose how many millseconds to display each block/section of rules before going on the next one.',
    'rank' => 'Rank',
    'staff_updated' => 'Staff Updated',

    /*** loading screen specific ***/

    'progress' => 'Progress',
    'info' => 'Info',
    'information' => 'Information',
    'about_us' => 'About Us',
    'now_playing' => 'Now Playing',
    'max_players' => 'Max Players',
    'map' => 'Map',
    'welcome' => 'Welcome',
    'server_rules' => 'Server Rules',
    'server_info' => 'Server Info',
    'we_are_currently_playing' => 'We are currently playing %s on %s',
    'you_are_now_joining' => 'You are now joining',
    'you_are_now_playing' => 'You are now playing on %s',
];
