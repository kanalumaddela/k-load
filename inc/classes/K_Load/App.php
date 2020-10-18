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

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use K_Load\Cache\Cache;
use K_Load\Cache\KDriver;
use K_Load\Container\DefinitionAggregate;
use K_Load\Exceptions\HttpException;
use K_Load\Facades\DB;
use K_Load\Helpers\Util;
use K_Load\Models\Device;
use K_Load\Models\Setting;
use K_Load\Models\User;
use K_Load\Routing\Router;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function array_merge;
use function count;
use function date;
use function dd;
use function define;
use function defined;
use function error_reporting;
use function file_exists;
use function header;
use function ini_set;
use function is_array;
use function is_null;
use function key;
use function parse_url;
use function str_replace;
use function stripos;
use function strpos;
use function strtoupper;
use function substr;
use function time;
use function trigger_error;
use function var_dump;
use const E_ALL;
use const PHP_URL_PATH;

function defineConstant($name, $value)
{
    $constant = __NAMESPACE__.'\\'.strtoupper($name);

    if (!defined($constant)) {
        define($constant, $value);
    }

    return $constant;
}

class App
{
    /**
     * @var \League\Container\Container
     */
    protected static $container;

    public static function getCopyright()
    {
        $date = 'date';

        return <<<copyright
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-{$date('Y')} Maddela
 * @license   MIT
 */
copyright;
    }

    public static function init()
    {
        static::checkRequirements();

        Constants::init();

        if (DEBUG) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

//        \Sentry\init(['dsn' => 'https://0bdc6629de78435f807c56358e3cdbae@o259687.ingest.sentry.io/1455550']);
        static::determineCurrentRoute();

        if (!static::isInstalled()) {
            if (APP_CURRENT_ROUTE !== '/install') {
                header('Location: '.$_SERVER['SCRIPT_NAME'].'?/install', true, 302);
            }

            var_dump('do install shit here');
            exit();
        }

        static::bootContainer();
        static::runConversion();

        if (stripos(APP_CURRENT_ROUTE, '/dashboard') !== false) {
            static::$container->share(Session::class, ($session = new Session()))->addTags('Session', 'session');
            static::$container->share(SteamLogin::class, ($steamLogin = new SteamLogin([
                'debug'   => DEBUG,
                'return'  => APP_CURRENT_URL,
                //                'method'  => \K_Load\Facades\Config::has('apikeys.steam'),
                'session' => [
                    'enable' => false,
                ],
            ])))->addTags('SteamLogin', 'steamLogin');
            static::$container->share(Cookie::class, ($cookie = new Cookie([
                'prefix' => 'kload_',
                'domain' => APP_DOMAIN,
                'path'   => APP_PATH,
                'secure' => IS_HTTPS,
            ])))->addTags('Cookie', 'cookie');

            static::createCsrf();

            if (SteamLogin::validRequest()) {
                $player = $steamLogin->getPlayer();

                $session->set('steamlogin', (array) $player);
                $session->regenerate();

                header('Location: '.$_GET['openid_return_to'] ?? APP_ROUTE_URL.'/dashboard', true, 302);
                exit();
            }

            $steamid = $session->get('user.steamid', $session->get('steamlogin.steamid'));
//            $steamid = '76561198152390718';
            $user = !empty($steamid) ? User::findBySteamid($steamid) : null;

            // user doesn't exist
            if (is_null($user) && !empty($steamid)) {
                $settings = Setting::whereIn('name', ['backgrounds', 'music'])->get()->toArray();
                $youtube = $settings[1]['value'];
                $youtube['list'] = [];
                unset($youtube['source'], $youtube['order']);

                $player = SteamLogin::convert($steamid);

                $user = new User([
                    'steamid'  => $player->steamid,
                    'steamid2' => $player->steamid2,
                    'steamid3' => $player->steamid3,
                    'admin'    => User::isSuper($player->steamid),
                    'perms'    => [],
                    'settings' => [
                        'theme'       => static::get('config')->get('loading_theme', 'default'),
                        'backgrounds' => $settings[0]['value'],
                        'youtube'     => $youtube,
                    ],
                ]);

                $user->save();
            }

            // we have a user
            if ($user) {
                if (!$session->has('user.avatar')) {
                    $player = SteamLogin::userInfo($user->steamid);

                    if (!empty($user->name)) {
                        dd('test');
                        exit();

                        $user->name = $player->name;
                        $user->save();
                    }
                }

                $user = $user->toArray();

                $user['super'] = User::isSuper($user['steamid']);
                if ($user['super'] && !$user['admin']) {
                    $user['admin'] = true;
                }

                $fixedPerms = [];
                foreach ($user['perms'] as $perm) {
                    $fixedPerms[$perm] = true;
                }

                $user['perms'] = $fixedPerms;

                if (isset($player)) {
                    $user = array_merge($user, (array) $player);
                }

                $session->set('user', $user);
            }

            unset($user, $fixedPerms, $perm, $player, $steamid, $youtube, $settings);
        }

        Router::init();
    }

    private static function checkRequirements()
    {
        Util::mkDir(APP_ROOT.'/assets/img/backgrounds/global');
        Util::mkDir(APP_ROOT.'/assets/img/logos');
        Util::mkDir(APP_ROOT.'/data/logs', true);
        Util::mkDir(APP_ROOT.'/data/music');
        Util::mkDir(APP_ROOT.'/data/users');
    }

    private static function determineCurrentRoute()
    {
        $current_route = !empty($route) ? $route : '/';
        $route_query_string = false;

        if (isset($_SERVER['PATH_INFO'])) {
            $current_route = $_SERVER['PATH_INFO'];
        } else {
            // check query string
            if (isset($_SERVER['QUERY_STRING']) && substr($_SERVER['QUERY_STRING'], 0, 1) === '/') { // && preg_match('/(\/[\w]*)+/', $_SERVER['QUERY_STRING'], $query_matches, PREG_UNMATCHED_AS_NULL) === 1
                $current_route = key($_GET);
                $route_query_string = true;
            } elseif (strpos(($parse_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/index.php') === false) { // isset($_SERVER['QUERY_STRING']) && substr($_SERVER['QUERY_STRING'], 0, 1) !== '/' &&
                $current_route = $parse_url;
                unset($parse_url);
            }
        }

        $current_route = str_replace(APP_PATH, '', $current_route);

        defineConstant('app_current_route', $current_route);
        defineConstant('app_route_url', $route_query_string ? APP_URL.'/index.php?' : APP_URL);
        defineConstant('app_current_url', APP_ROUTE_URL.APP_CURRENT_ROUTE);
    }

    public static function isInstalled()
    {
//        return false;
        return file_exists(APP_ROOT.'/data/config.php');
    }

    public static function bootContainer()
    {
        $container = (new Container(new DefinitionAggregate()))->defaultToShared(true);

        $container->share(Request::class, ($request = Request::createFromGlobals()))->addTags('Request', 'request');
        $container->share(Cache::class, ($cache = new Cache(KDriver::class)))->addTags('Cache', 'cache');
        $container->share(Config::class, $config = new Config(APP_ROOT.'/data/config.php'))->addTags('Config', 'config');
        $container->share(Lang::class, ($lang = new Lang(APP_LANGUAGE)))->addTags('Lang', 'lang');

        $capsule = new Capsule();
        $dbConfig = [
            'host'      => $config->get('mysql.host', 'localhost'),
            'port'      => $config->get('mysql.port', 3306),
            'username'  => $config->get('mysql.user', 'root'),
            'password'  => $config->get('mysql.pass', ''),
            'database'  => $config->get('mysql.db', 'k-load'),
            'driver'    => $config->get('mysql.db_type', 'mysql'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 'kload_',
        ];

        $capsule->addConnection($dbConfig);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        if ($config->has('databases')) {
            $dbs = $config->get('databases');

            foreach ($dbs as $name => $db) {
                $capsule->addConnection($db, $name);
            }

            unset($dbs);
            unset($name);
            unset($db);
        }

        if (DEBUG) {
            $capsule->getConnection()->enableQueryLog();
        }

        $container->share(Capsule::class, $capsule)->addTags('DB', 'db', 'database');

        $container->delegate(new ReflectionContainer());

        static::$container = $container;
    }

    private static function runConversion()
    {
        if (file_exists(APP_ROOT.'/inc/migrations')) {
            $pdo = DB::connection()->getPdo();

            $triggers = $pdo->query('SHOW TRIGGERS like \'kload_sessions\'')->fetchAll(PDO::FETCH_ASSOC);

            if (count($triggers) > 0) {
                $queries = [
                    'drop trigger if exists csrf_fix_insert',
                    'drop trigger if exists csrf_fix_update',
                ];

                foreach ($queries as $query) {
                    $pdo->exec($query);
                }
            }

            $tmpConfig = include APP_ROOT.'/data/config.php';
            if (is_array($tmpConfig['dashboard_theme'])) {
                $tmpConfig['dashboard_theme'] = 'new';
                Config::saveConfig(APP_ROOT.'/data/config.php', $tmpConfig, false);
            }

            unset($query, $pdo, $queries, $triggers, $tmpConfig);

//            Util::rmDir(APP_ROOT.'/inc/migrations');
        }
    }

    protected static function createCsrf()
    {
        $csrf = \K_Load\Facades\Session::get('csrf', []);

        if (!isset($csrf[APP_CURRENT_ROUTE]) || $csrf[APP_CURRENT_ROUTE]['expires'] <= time()) {
            $csrf[APP_CURRENT_ROUTE] = [
                'token'     => Util::hash(32),
                'expires'   => time() + 3600,
                'last_used' => null,
                'uses'      => 0,
            ];

            \K_Load\Facades\Session::set('csrf', $csrf);
        }
    }

    public static function get(string $id, bool $new = false)
    {
        $r = static::$container->get($id, $new);

        return is_array($r) ? $r[0] : $r;
    }

    public static function setRoot(string $dir)
    {
        if (!file_exists($dir)) {
            trigger_error('Cannot set root directory as `'.$dir.'`. Does not exist.', E_USER_ERROR);
        }

        defineConstant('app_root', $dir);
    }

    /**
     * @param string $route
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function dispatch(string $route = null): Response
    {
        try {
            $response = Router::dispatch(!empty($route) ? $route : APP_CURRENT_ROUTE);
        } catch (Exception $e) {
            dd($e);
            $response = (new Response($e->getMessage()))->setStatusCode(500);

            if ($e instanceof HttpException) {
                $response = (new Response($e->getMessage()))->setStatusCode($e->getStatusCode());
            }
        }

        if (!$response instanceof Response) {
            if (is_array($response) || $response instanceof Model) {
                $response = new JsonResponse($response);
            } else {
                $response = new Response($response);
            }
        }

        $response->prepare(static::get('request'));

        return $response;
    }

    public static function isHttps()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' : false);
    }

    public static function has(string $id)
    {
        return static::$container->has($id);
    }

    protected static function createDeviceId()
    {
        $device_id = Util::hash();
        $token = Util::hash(32);

        $device = Device::create([
            'device_id' => $device_id,
            'token'     => $token,
            'expires'   => date('Y-m-d H:i:s', time() + (60 * 60 * 24)),
        ]);

        \K_Load\Facades\Cookie::set('device_id', $device_id);
        \K_Load\Facades\Cookie::set('csrf', $token, null, true);
        \K_Load\Facades\Session::set('csrf', $token);

        return $device;
    }

    private static function getExtensions(): array
    {
        return [
            [
                'ext' => 'bcmath',
            ],
            'bcmath'   => true,
            'curl'     => true,
            'fileinfo' => false,
            'json'     => true,
        ];
    }
}
