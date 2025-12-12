<?php

/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

namespace KLoad;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use KLoad\Cache\Cache;
use KLoad\Cache\KDriver;
use KLoad\Container\DefinitionAggregate;
use KLoad\Exceptions\HttpException;
use KLoad\Exceptions\InvalidToken;
use KLoad\Facades\DB;
use KLoad\Helpers\Util;
use KLoad\Models\Device;
use KLoad\Models\Setting;
use KLoad\Models\User;
use KLoad\Routing\Router;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PDO;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function kload_error_page;

use const E_ALL;
use const PHP_URL_PATH;

function defineConstant($name, $value)
{
    $constant = __NAMESPACE__.'\\'.\strtoupper($name);

    if (!\defined($constant)) {
        \define($constant, $value);
    }

    return $constant;
}

class App
{
    public static string $version = '2.6.0';
    public static int $versionId = 260;

    /**
     * @var Container
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
 * @copyright Copyright (c) 2018-{$date('Y')} kanalumaddela
 * @license   MIT
 */
copyright;
    }

    public static function init()
    {
        Constants::init();

//        static::setupHandlers();
        static::setupDirectories();

        static::determineCurrentRoute();

        if (!static::isInstalled()) {
            if (APP_CURRENT_ROUTE !== '/install') {
                \header('Location: '.$_SERVER['SCRIPT_NAME'].'?/install', true, 302);
            }

            \var_dump('do install shit here');
            exit;
        }

        if (APP_CURRENT_ROUTE === '/install') {
            echo 'K-Load has already been installed, please visit <a href="'.APP_ROUTE_URL.'/dashboard">'.APP_ROUTE_URL.'/dashboard</a>';
            exit;
        }

        static::bootContainer();
        static::runConversion();

        if (\stripos(APP_CURRENT_ROUTE, '/dashboard') !== false) {
            static::$container->addShared(Session::class, $session = new Session())->addTags('Session', 'session');
            static::$container->addShared(SteamLogin::class, $steamLogin = new SteamLogin([
                'debug'     => DEBUG,
                'return_to' => APP_CURRENT_URL,
                //                'method'  => \K_Load\Facades\Config::has('apikeys.steam'),
                'session' => [
                    'enable' => false,
                ],
            ]))->addTags('SteamLogin', 'steamLogin');
            static::$container->addShared(Cookie::class, $cookie = new Cookie([
                'prefix' => 'kload_',
                'domain' => APP_DOMAIN,
                'path'   => APP_PATH,
                'secure' => IS_HTTPS,
            ]))->addTags('Cookie', 'cookie');

            $session->generateCsrf();

            if (SteamLogin::validRequest()) {
                $player = $steamLogin->getPlayer();

                $session->set('steamlogin', $player);
                $session->regenerate();

                \header('Location: '.$_GET['openid_return_to'] ?? APP_ROUTE_URL.'/dashboard', true, 302);
                exit;
            }

            $steamid = $session->get('user.steamid', $session->get('steamlogin.steamid'));
//            $steamid = '76561198152390718';
//            $steamid = null;
            $user = !empty($steamid) ? User::findBySteamid($steamid) : null;

            // user doesn't exist
            if (\is_null($user) && !empty($steamid)) {
                $settings = Setting::whereIn('name', ['backgrounds', 'youtube'])->get()->toArray();
                $youtube = $settings[1]['value'];
                $youtube['list'] = [];

                $player = SteamLogin::convert($steamid);

                // {"theme":"default","backgrounds":{"enable":1,"duration":5000,"fade":750,"random":0},"youtube":{"volume":15,"enable":0,"random":0,"list":[]}}

                $user = new User([
                    'steamid'  => $player['steamid'],
                    'steamid2' => $player['steamid2'],
                    'steamid3' => $player['steamid3'],
                    'admin'    => User::isSuper($player['steamid']),
                    'perms'    => [],
                    'settings' => [
                        'theme'       => static::get('config')->get('loading_theme', 'default'),
                        'backgrounds' => $settings[0]['value'],
                        'youtube'     => $youtube,
                    ],
                ]);
            }

            // we have a user
            if ($user) {
                if (!$session->has('user.avatar')) {
                    $player = self::get('steamLogin')->getUserInfo($user->steamid);

                    if (!empty($user->name)) {
                        $user->name = $player['name'];
                    }

                    $avatars = [
                        'large'  => $player['avatarLarge'],
                        'medium' => $player['avatarMedium'],
                        'small'  => $player['avatarSmall'],
                    ];
                }

                if (!$user->exists || $user->wasChanged()) {
                    $user->save();
                }

                $user = $user->toArray();

                $user['avatars'] = $session->get('user.avatars', $avatars ?? []);
                $user['avatar'] = $session->get('user.avatar', $avatars['large'] ?? []);

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
                    $user = \array_merge($user, (array) $player);
                }

                $session->set('user', $user);
            }

            unset($user, $fixedPerms, $perm, $player, $steamid, $youtube, $settings);
        }

        Router::init();
    }

    private static function setupHandlers()
    {
        if (DEV) {
            return;
        }

        \set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext = null) {
            kload_error_page([
                'type'    => 'error',
                'code'    => $errno,
                'file'    => $errfile,
                'line_no' => $errline,
                'message' => $errstr,
                'raw'     => \func_get_args(),
            ]);

            exit;
        }, E_ALL);

        \set_exception_handler(function (Exception $exception) {
            kload_error_page([
                'type'    => 'exception',
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line_no' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'raw'     => $exception,
            ]);

            exit;
        });
    }

    /**
     * @throws Exception
     */
    private static function setupDirectories(): void
    {
        Util::mkDir(APP_ROOT.'/assets/img/logos');
        Util::mkDir(APP_ROOT.'/data/logs', true);
        Util::mkDir(APP_ROOT.'/data/music');
        Util::mkDir(APP_ROOT.'/data/uploads');
    }

    private static function determineCurrentRoute(): void
    {
        $current_route = '/';
        $route_query_string = false;

        if (isset($_SERVER['PATH_INFO'])) {
            $current_route = $_SERVER['PATH_INFO'];
        } else {
            // check query string
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'][0] === '/') { // && preg_match('/(\/[\w]*)+/', $_SERVER['QUERY_STRING'], $query_matches, PREG_UNMATCHED_AS_NULL) === 1
                $current_route = \key($_GET);
                $route_query_string = true;
            } elseif (\strpos($parse_url = \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/index.php') === false) { // isset($_SERVER['QUERY_STRING']) && substr($_SERVER['QUERY_STRING'], 0, 1) !== '/' &&
                $current_route = $parse_url;
                unset($parse_url);
            }
        }

        $current_route = \rtrim(\str_replace(APP_PATH, '', $current_route), '/');

        defineConstant('app_current_route', $current_route);
        defineConstant('app_route_url', $route_query_string ? APP_URL.'/index.php?' : APP_URL);
        defineConstant('app_current_url', APP_ROUTE_URL.APP_CURRENT_ROUTE);
    }

    public static function isInstalled(): bool
    {
//        return false;
        return \file_exists(APP_ROOT.'/data/config.php');
    }

    public static function bootContainer(): void
    {
        $container = new Container(new DefinitionAggregate())->defaultToShared(true);

        $container->addShared(Request::class, $request = Request::createFromGlobals())->addTags('Request', 'request');
        $container->addShared(Cache::class, $cache = new Cache(KDriver::class))->addTags('Cache', 'cache');
        $container->addShared(Config::class, $config = new Config(APP_ROOT.'/data/config.php'))->addTags('Config', 'config');
        $container->addShared(Lang::class, $lang = new Lang(APP_LANGUAGE))->addTags('Lang', 'lang');

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

            unset($dbs, $name);
        }

        if (DEBUG) {
            $capsule->getConnection()->enableQueryLog();
        }

        $container->addShared(Capsule::class, $capsule)->addTags('DB', 'db', 'database');

        $container->delegate(new ReflectionContainer());

        static::$container = $container;

        Paginator::currentPageResolver(static function () use ($request) {
            return $request->query->get('page', 1);
        });

        Paginator::currentPathResolver(static function () {
            return APP_CURRENT_ROUTE;
        });
    }

    private static function runConversion(): void
    {
        if (\file_exists(APP_ROOT.'/inc/migrations')) {
            $pdo = DB::connection()->getPdo();

            $triggers = $pdo->query('SHOW TRIGGERS like \'kload_sessions\'')->fetchAll(PDO::FETCH_ASSOC);

            if (\count($triggers) > 0) {
                $queries = [
                    'drop trigger if exists csrf_fix_insert',
                    'drop trigger if exists csrf_fix_update',
                ];

                foreach ($queries as $query) {
                    $pdo->exec($query);
                }
            }

            $tmpConfig = include APP_ROOT.'/data/config.php';
            if (\is_array($tmpConfig['dashboard_theme'])) {
                $tmpConfig['dashboard_theme'] = 'new';
                Config::saveConfig(APP_ROOT.'/data/config.php', $tmpConfig, false);
            }

            unset($query, $pdo, $queries, $triggers, $tmpConfig);

            Util::rmDir(APP_ROOT.'/inc/migrations');
        }
    }

    public static function get(string $id, bool $new = false)
    {
        $r = static::$container->get($id, $new);

        return \is_array($r) ? $r[0] : $r;
    }

    public static function setRoot(string $dir): void
    {
        if (!\file_exists($dir)) {
            \trigger_error('Cannot set root directory as `'.$dir.'`. Does not exist.', E_USER_ERROR);
        }

        defineConstant('app_root', $dir);
    }

    /**
     * @param string|null $route
     *
     * @throws Exception
     *
     * @return Response
     */
    public static function dispatch(?string $route = null): Response
    {
        try {
            $response = Router::dispatch(!empty($route) ? $route : APP_CURRENT_ROUTE);
        } catch (Exception $e) {
            if (DEBUG) {
                throw $e;
            }

            $response = new Response($e->getMessage())->setStatusCode(500);

            if ($e instanceof \Phroute\Phroute\Exception\HttpException) {
                switch (\get_class($e)) {
                    case HttpRouteNotFoundException::class:
                        $response->setStatusCode(404);
                        break;
                    case HttpMethodNotAllowedException::class:
                        $response->setStatusCode(405);
                        break;
                }
            }

            if ($e instanceof HttpException) {
                if ($e instanceof InvalidToken) {
                    $response = redirect(APP_CURRENT_URL)->withError($e->getMessage())->withInputs();
                } else {
                    $response->setStatusCode($e->getStatusCode());
                }
            }
        }

        if (!$response instanceof Response) {
            if (\is_array($response) || $response instanceof Model) {
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
            'expires'   => \date('Y-m-d H:i:s', \time() + (60 * 60 * 24)),
        ]);

        \KLoad\Facades\Cookie::set('device_id', $device_id);
        \KLoad\Facades\Cookie::set('csrf', $token, null, true);
        \KLoad\Facades\Session::set('csrf', $token);

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
