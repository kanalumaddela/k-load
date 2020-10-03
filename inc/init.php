<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2020 Maddela
 * @license   MIT
 */

namespace K_Load;

use J0sh0nat0r\SimpleCache\Cache;
use K_Load\Facades\DB;
use function microtime;
use function print_r;

App::setRoot(dirname(__DIR__));

App::init();

ob_start();

App::dispatch()->send();

if (DEBUG) {
    echo "\n".'<!--'."\n\n";

    echo 'Script Time: '.(microtime(true) - APP_START).'s';

    echo "\n\n\nDB Query Log:\n\n";

    print_r(DB::connection()->getQueryLog());

    echo "\n".'-->';
}

if (ob_get_length() > 0) {
    ob_end_flush();
}

exit();

/**
 * old code below that is never reached :).
 */

/***  E P I C  C O D E  ***/

// sessions
if (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || strpos($_SERVER['REQUEST_URI'], '/install') !== false) {
    session_name('K-Load');
    session_set_cookie_params(0, APP_PATH.(strpos($_SERVER['REQUEST_URI'], '/install') !== false ? '/install' : '/dashboard'), APP_DOMAIN, IS_HTTPS, true);
    session_start();
} else {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

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

// caching
if (!ENABLE_CACHE || isset($_GET[CLEAR_CACHE])) {
    if (file_exists(APP_ROOT.'/data/cache')) {
        Util::rmDir(APP_ROOT.'/data/cache');
    }
}
$cache = new Cache(File::class, ['dir' => APP_ROOT.'/data/cache']);
Cache::bind($cache);

// check if "installed" aka config.php exists
if (!Util::installed() && strpos($_SERVER['REQUEST_URI'], '/test/') === false) {
    if (strpos($_SERVER['REQUEST_URI'], '/install') === false) {
        if (isset($_SESSION['id'])) {
            session_destroy();
        }

        Util::redirect('/install');
    }
    include __DIR__.'/install.php';
    exit();
}

// get config if exists
$config = include APP_ROOT.'/data/config.php';

$db_config = $config['mysql'];
$db_config['username'] = $db_config['user'];
$db_config['password'] = $db_config['pass'];
$db_config['database'] = $db_config['db'];
$db_config['driver'] = 'mysql';
$db_config['charset'] = 'utf8mb4';
$db_config['collation'] = 'utf8mb4_unicode_ci';
$db_config['prefix'] = 'kload_';

$capsule = new Illuminate\Database\Capsule\Manager();

$capsule->addConnection($db_config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    $test = $capsule::getPdo();
    unset($test);
} catch (Exception $e) {
    echo 'K-Load | Failed to connect to mysql server';
    echo '<br>';
    echo '<pre style="padding: 4px 2px;background-color: #000;color: lawngreen">';
    print_r($e->getMessage());
    echo '</pre>';
    if (DEBUG) {
        dump($e);
    }
    exit();
}

Steam::Key($config['apikeys']['steam']);

if (isset($_SESSION['steamid']) && Util::installed()) {
    if (User::isAdmin($_SESSION['steamid']) || ENABLE_REGISTRATION) {
        $user = User::get($_SESSION['steamid']);

        if (empty($user)) {
            $user = User::add($_SESSION['steamid']);
        }

        User::session($user);
    } else {
        echo 'Registration is disabled, please talk to the owner.';
        exit();
    }
    if (strpos($_SERVER['REQUEST_URI'], 'api') === false) {
        if (!DEMO_MODE && User::isBanned($_SESSION['steamid'])) {
            echo "You're banned";
            exit();
        }
    }

    $_SESSION = array_merge($_SESSION, User::get($_SESSION['steamid'], 'admin', 'perms'));
}

Lang::init(APP_LANGUAGE);

require_once __DIR__.'/helpers.php';

// routing
$routes = (ENABLE_CACHE ? Cache::remember('routes', 86400, [Routes::class, 'get']) : Routes::get());
$dispatcher = new Dispatcher($routes);

$exception = null;

$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($response instanceof Response) {
    $response->send();
} elseif (!empty($response)) {
    echo $response;
}

//try {
//    $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
//
//    if ($response instanceof Response) {
//        $response->send();
//    } elseif (!empty($response)) {
//        echo $response;
//    }
//} catch (HttpRouteNotFoundException $e) {
//    if (!empty(pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION))) {
//        header('HTTP/1.0 404 Not Found');
//        die();
//    }
//    $exception = $e;
//} catch (Exception $e) {
//    $exception = $e;
//} finally {
//    $headers_sent = headers_sent();
//
//    if ($headers_sent) {
//        die();
//    }
//
//    if ($exception instanceof Exception) {
//        $sentry_id = Sentry\captureException($exception);
//
//        $errorData = [
//            'sentry_id' => Sentry\captureException($exception),
//            'exception' => $exception,
//        ];
//
//        if ($exception instanceof HttpRouteNotFoundException) {
//            $errorData['code'] = 404;
//        }
//        if ($exception instanceof HttpMethodNotAllowedException) {
//            $errorData['code'] = 405;
//        }
//
//        require_once __DIR__.'/error.php';
//    }
//}
