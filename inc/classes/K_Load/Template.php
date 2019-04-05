<?php

namespace K_Load;

use Steam;
use Twig\Markup;

class Template
{
    private static $data = [];

    public static $theme = 'default';

    /**
     * @var \Twig_Environment
     */
    private static $twig;
    private static $twig_loader;
    private static $twig_env_params;

    public static function init()
    {
        global $steamLogin;

        $theme_loc = APP_ROOT.'/themes/'.self::$theme.'/pages';
        $theme_loc_fallback = APP_ROOT.'/themes/default/pages';

        self::$twig_loader = new \Twig\Loader\FilesystemLoader($theme_loc);

        self::$twig_loader->addPath((\file_exists($theme_loc.'/dashboard') ? $theme_loc.'/dashboard' : $theme_loc_fallback.'/dashboard'), 'dashboard');
        self::$twig_loader->addPath((\file_exists($theme_loc.'/admin') ? $theme_loc.'/admin' : $theme_loc_fallback.'/admin'), 'admin');
        self::$twig_loader->addPath((\file_exists($theme_loc.'/partials') ? $theme_loc.'/partials' : $theme_loc_fallback.'/partials'), 'partials');
        self::$twig_loader->addPath(APP_ROOT.'/themes/default/pages/loading', 'loading');

        self::$twig_env_params = [
            'auto_reload' => true,
            'debug'       => DEBUG,
            'cache'       => (ENABLE_CACHE ? APP_ROOT.'/data/cache/templates' : false),
        ];
        self::$twig = new \Twig\Environment(self::$twig_loader, self::$twig_env_params);

        if (DEBUG) {
            self::$twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        $function = new \Twig\TwigFunction('csrf', function () {
            return new Markup('<input id="csrf" type="hidden" name="csrf" value="'.User::getCSRF($_SESSION['steamid']).'">', 'utf8');
        });
        self::$twig->addFunction($function);

        $function = new \Twig\TwigFunction('theme_asset', function ($file) {
            return APP_PATH.'/themes/'.self::$theme.'/assets/'.\ltrim($file, '/');
        });
        self::$twig->addFunction($function);
        $function = new \Twig\TwigFunction('asset', function ($file) {
            return APP_PATH.'/assets/'.\ltrim($file, '/');
        });
        self::$twig->addFunction($function);


        $site_urls = [
            'host'    => APP_HOST,
            'path'    => APP_PATH,
            'url'     => APP_URL,
            'current' => APP_URL_CURRENT,
        ];

        self::$twig->addGlobal('site', $site_urls);

        self::$data = [
            'assets'       => APP_PATH.'/assets',
            'assets_theme' => APP_PATH.'/themes/'.self::$theme.'/assets',
            'login_url'    => $steamLogin->getLoginURL(),
            'site_json'    => \json_encode($site_urls),
            'cache_buster' => \bin2hex(\random_bytes(3)),
        ];
    }

    public static function theme($theme = null)
    {
        if (isset($theme) && \file_exists(APP_ROOT.'/themes/'.$theme)) {
            self::$theme = $theme;
        }

        return self::$theme;
    }

    public static function isDasbhoardTheme($name)
    {
        $theme = APP_ROOT.'/themes/'.$name.'/pages';

        return \file_exists($theme.'/dashboard') && \file_exists($theme.'/admin');
    }

    public static function isLoadingTheme($name)
    {
        return \file_exists(APP_ROOT.'/themes/'.$name.'/pages/loading.twig');
    }

    public static function dashboardThemes()
    {
        $list = [];
        $themes = \glob(APP_ROOT.\sprintf('%sthemes%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($themes as $location) {
            $tmp = \explode(DIRECTORY_SEPARATOR, $location);
            $name = \end($tmp);
            $location .= '/pages';
            if (\file_exists($location.'/dashboard') && \file_exists($location.'/admin')) {
                $list[] = $name;
            }
        }

        return $list;
    }

    public static function loadingThemes($all = false)
    {
        global $config;

        $list = [];
        $themes = \glob(APP_ROOT.\sprintf('%sthemes%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($themes as $location) {
            $tmp = \explode(DIRECTORY_SEPARATOR, $location);
            $name = \end($tmp);
            if (\file_exists($location.'/pages/loading.twig')) {
                if ($all || in_array($name, $config['loading_themes'])) {
                    $previews = \glob($location.DIRECTORY_SEPARATOR.'*.{jpg,png}', GLOB_BRACE);
                    \usort($previews, function ($a, $b) {
                        return \filemtime($a) - \filemtime($b);
                    });
                    $preview = \count($previews) > 0 ? \str_replace(APP_ROOT, APP_PATH, $previews[0]) : null;

                    $list[] = [
                        'name'    => $name,
                        'preview' => $preview ?? APP_PATH.'/assets/img/theme.jpg',
                    ];
                }
            }
        }

        return $list;
    }

    public static function render($template, array $data = [], $dontBuild = false)
    {
        if (!isset($data['alert'])) {
            if (isset($_SESSION['flash']['alert'])) {
                $data['alert'] = $_SESSION['flash']['alert'];
                unset($_SESSION['flash']['alert']);
            }
        }

        if (!$dontBuild) {
            self::buildData($data);
            $data = self::$data;
        }

        self::$twig->load($template)->display($data);
    }

    public static function renderReturn($template, array $data = [], $dontBuild = false)
    {
        if (!$dontBuild) {
            self::buildData($data);
            $data = self::$data;
        }

        return self::$twig->render($template, $data);
    }

    public static function buildData(array $data = [])
    {
        if (isset($_SESSION['steamid']) && !isset($_GET['steamid']) && (APP_URL.'/') != APP_URL_CURRENT) {
            self::$data['themes'] = self::loadingThemes(User::isSuper($_SESSION['steamid']));
            self::$data['user'] = $_SESSION;
            self::$data['user']['admin'] = User::isSuper($_SESSION['steamid']) ? 1 : (int) User::getInfo($_SESSION['steamid'], 'admin');
            self::$data['user']['super'] = User::isSuper($_SESSION['steamid']);
            self::$data['user']['perms'] = \array_fill_keys(\array_keys(\array_flip(\json_decode(User::getInfo($_SESSION['steamid'], 'perms'), true))), 1);
            if (self::$data['user']['perms'] != $_SESSION['perms']) {
                $_SESSION['perms'] = self::$data['user']['perms'];
            }
            self::$data['csrf'] = '<input id="csrf" type="hidden" name="csrf" value="'.User::getCSRF($_SESSION['steamid']).'">';
        }
        self::$data = self::$data + $data;
    }

    public static function getData()
    {
        self::buildData();

        return self::$data;
    }

    public static function setData(array $data)
    {
        self::$data = $data;
    }
}
