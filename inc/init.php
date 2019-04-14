<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

// important constants
define('IS_FORWARDED', isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']));
define('IS_HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' : false));
define('APP_HOST', (IS_HTTPS ? 'https://' : 'http://').$_SERVER['HTTP_HOST']);

define('APP_DOMAIN', strtok($_SERVER['HTTP_HOST'], ':'));
define('APP_PORT', !IS_FORWARDED ? (int) $_SERVER['SERVER_PORT'] : 80);

$dir_self = dirname($_SERVER['PHP_SELF']);
$dir_request = dirname($_SERVER['REQUEST_URI']);

if ($dir_request == '\\' || $dir_request == '/') {
    $dir_request = '';
}
if ($dir_self == '\\' || $dir_self == '/') {
    $dir_self = '';
}

define('APP_PATH', str_replace('/index.php'.$dir_request, '', $dir_self));
define('APP_URL', APP_HOST.APP_PATH);
define('APP_URL_CURRENT', APP_HOST.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

/***  E P I C  C O D E  ***/

// sessions
if (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || strpos($_SERVER['REQUEST_URI'], '/install') !== false) {
    session_name('K-Load');
    /*
        sessions on actual loading screen increase load times, dumb fix
    */
    if (strpos($_SERVER['REQUEST_URI'], '/install') !== false) {
        session_set_cookie_params(0, APP_PATH.'/install', APP_DOMAIN, IS_HTTPS, true);
    } else {
        session_set_cookie_params(0, APP_PATH.'/dashboard', APP_DOMAIN, IS_HTTPS, true);
    }
    session_start();
}

// autoloading
function autoload_classes($class_name)
{
    $class_name = (strpos($class_name, '\\') !== false ? str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php' : $class_name.'.class.php');

    $file = __DIR__.'/classes/'.$class_name;
    $file_mod = __DIR__.'/classes/mods/'.$class_name;

    if (file_exists($file_mod) || file_exists($file)) {
        $load_file = $file;
        if (file_exists($file_mod)) {
            $load_file = (!file_exists($file) ? $file_mod : $file);
            if (file_exists($file)) {
                $load_file = filemtime($file) < filemtime($file_mod) ? $file_mod : $file;
            }
        }
        require_once $load_file;
    }
}

spl_autoload_register('autoload_classes');
require_once APP_ROOT.'/vendor/autoload.php';

// make it easier to call classes
use J0sh0nat0r\SimpleCache\Drivers\File;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Constants;
use K_Load\Lang;
use K_Load\Routes;
use K_Load\Template;
use K_Load\User;
use K_Load\Util;
use kanalumaddela\SteamLogin\SteamLogin;
use Phroute\Phroute\Dispatcher;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

Constants::init();

// exception handler
function kload_exception_handler($exception)
{
    $cloner = new VarCloner();
    $dumper = new HtmlDumper();

    $code = $exception->getCode();
    $message = $exception->getMessage();
    $trace = $exception->getTrace();

    Util::log('exception', 'Code: '.$code.' - '.$message, true);
    Util::log('exception', "\t\t".'Stack Trace: ', true);
    foreach ($trace as $file => $info) {
        Util::log('exception', "\t\t\t[".($file ?? 'N/A').'] - '.$info['function'].' '.($info['file'] ?? '<unknown file>').' on line '.($info['line'] ?? '<unknown>'), true);
    }

    $twig_loader = new FilesystemLoader(APP_ROOT.'/themes/default/pages');

    $twig_env_params = [
        'auto_reload' => true,
        'debug'       => true,
    ];
    $twig = new Environment($twig_loader, $twig_env_params);
    $twig->addExtension(new DebugExtension());

    $data = [
        'assets' => APP_PATH.'/assets',
        'error'  => [
            'code'    => $code,
            'message' => $message,
            'trace'   => DEBUG ? $dumper->dump($cloner->cloneVar($trace), true) : '',
        ],
    ];
    $data['time'] = round((microtime(true) - APP_START) * 1000, 3);

    $twig->load('error.twig')->display($data);
    die();
}

set_exception_handler('kload_exception_handler');

// make some new directions
Util::mkDir(APP_ROOT.'/data/logs', true);
Util::mkDir(APP_ROOT.'/data/music');
Util::mkDir(APP_ROOT.'/data/users');

// steam auth and api stuff
$steamLogin_options = [
    'debug'   => DEBUG,
    'return'  => APP_URL_CURRENT,
    'method'  => 'xml',
    'session' => [
        'enable' => false,
    ],
];

$steamLogin = new SteamLogin($steamLogin_options);

if (SteamLogin::validRequest()) {
    $player = $steamLogin->getPlayer();
    Steam::Session($player->steamid);
}

// logging
if (ENABLE_LOG) {
    Util::log();
}

// caching
if (!ENABLE_CACHE || isset($_GET[CLEAR_CACHE])) {
    if (file_exists(APP_ROOT.'/data/cache')) {
        Util::rmDir(APP_ROOT.'/data/cache');
    }
}
$cache = new \J0sh0nat0r\SimpleCache\Cache(File::class, ['dir' => APP_ROOT.'/data/cache']);
Cache::bind($cache);

// get config if exists
if (file_exists(APP_ROOT.'/data/config.php')) {
    $config = include APP_ROOT.'/data/config.php';
    Steam::Key($config['apikeys']['steam']);
    Database::connect($config['mysql']);
}

// check if installed
if (!Util::installed() && strpos($_SERVER['REQUEST_URI'], '/test/') === false) {
    if (strpos($_SERVER['REQUEST_URI'], '/install') === false) {
        if (isset($_SESSION['id'])) {
            session_destroy();
        }
        Util::redirect('/install');
    }
    include __DIR__.'/install.php';
    die();
}

if (isset($_SESSION['steamid']) && Util::installed()) {
    if (!User::validateCSRF($_SESSION['steamid'], User::getCSRF($_SESSION['steamid']))) {
        User::refreshCSRF($_SESSION['steamid']);
    }

    if (User::isSuper($_SESSION['steamid']) || ENABLE_REGISTRATION) {
        if (!isset($_SESSION['id'])) {
            User::add($_SESSION['steamid']);
            User::session($_SESSION['steamid']);
        }
    } else {
        echo 'Registration is not allowed.';
        die();
    }
    if (strpos($_SERVER['REQUEST_URI'], 'api') === false) {
        if (User::isBanned($_SESSION['steamid'])) {
            echo "You're banned";
            die();
        }
    }

    $_SESSION['perms'] = array_fill_keys(json_decode(User::getInfo($_SESSION['steamid'], 'perms')), 1);
}

Lang::init(APP_LANGUAGE);

function lang()
{
    return call_user_func_array([Lang::class, 'get'], func_get_args());
}

// routing
$routes = (ENABLE_CACHE ? Cache::remember('routes', 86400, [Routes::class, 'get']) : Routes::get());
$dispatcher = new Dispatcher($routes);
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
