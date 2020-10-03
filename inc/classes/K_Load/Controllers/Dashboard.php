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

namespace K_Load\Controllers;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Facades\Config;
use K_Load\Facades\DB;
use K_Load\Facades\Session;
use K_Load\Models\Setting;
use K_Load\Models\User;
use K_Load\View\LoadingView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function array_intersect;
use function array_keys;
use function array_merge;
use function dd;
use function dump;
use function get_defined_vars;
use function is_array;
use function json_encode;
use function K_Load\redirect;
use const K_Load\APP_ROUTE_URL;

class Dashboard extends BaseController
{
    protected static $templateFolder = 'controllers/dashboard';

    public function index()
    {
        if (!$this->user['admin']) {
            return redirect(APP_ROUTE_URL.'/settings');
        }

        $settings = [
            'backgrounds',
            'community_name',
            'music',
        ];

        if (!$this->user['super']) {
            $settings = array_intersect($settings, array_keys($this->user['perms']));
        }

        $settings = Setting::whereIn('name', $settings)->get()->pluck('value', 'name');
        $themes = LoadingView::getThemes();
        $loading_theme = Config::get('loading_theme');

        return $this->view('index', get_defined_vars());
    }

    public function indexPost()
    {
        $this->validateCsrf();

        $post = $this->request->request;

        if ($post->has('theme') && $this->user['super']) {
            $theme = $post->get('theme');

            if (LoadingView::themeExists($theme) && $theme !== Config::get('loading_theme')) {
                Config::set('loading_theme', $theme);
                Config::save();
            }

            if (!LoadingView::themeExists($theme)) {
                Session::error('Theme: '.$theme.' does not exist');
            }
        }

        if ($post->has('community_name') && $this->can('community_name')) {
            Setting::where('name', 'community_name')->update(['value' => $post->get('community_name')]);
        }

        if ($post->has('backgrounds') && $this->can('backgrounds')) {
            $backgrounds = $post->get('backgrounds');
            $backgrounds['enable'] = isset($backgrounds['enable']) ? (int) $backgrounds['enable'] : 0;
            $backgrounds['random'] = isset($backgrounds['random']) ? (int) $backgrounds['random'] : 0;
            $backgrounds['duration'] = isset($backgrounds['duration']) ? (int) $backgrounds['duration'] : 5000;
            $backgrounds['fade'] = isset($backgrounds['fade']) ? (int) $backgrounds['fade'] : 750;

            Setting::where('name', 'backgrounds')->update(['value' => json_encode($backgrounds)]);
        }

        if ($post->has('music') && $this->can('music')) {
            $musicPost = $post->get('music');
            $musicPost['enable'] = isset($musicPost['enable']) ? (int) $musicPost['enable'] : 0;
            $musicPost['random'] = isset($musicPost['random']) ? (int) $musicPost['random'] : 0;
            $musicPost['volume'] = isset($musicPost['volume']) ? (int) $musicPost['volume'] : 15;

            $music = Setting::where('name', 'music')->first();

            dd($music);

            Setting::where('name', 'music')->update(['value' => json_encode(array_merge($music->value, $musicPost))]);
        }


        return new RedirectResponse(APP_ROUTE_URL.'/dashboard');
    }

    public function settings()
    {
        $post = $this->http->request;

        if ($post->has('save')) {
            $updated = User::update($_SESSION['steamid'], $post->all());
            $alert = $updated ? 'Your settings have been saved' : 'Failed to save, contact the owner to check the data/logs if necessary';

            if ($updated) {
                Cache::remove('loading-screen-'.$_SESSION['steamid']);
            }

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/settings');
        }

        $data = [
            'user' => [
                'css' => User::get($_SESSION['steamid'], 'custom_css'),
            ],
        ];

        if (is_array($data['user']['css'])) {
            $data['user']['css'] = null;
        }

        return self::view('settings', $data);
    }

    public function users()
    {
        $data = [
            'users' => User::paginate(25),
        ];

        dump($data);
        dd(DB::connection()->getQueryLog());

        return self::view('users', $data);
    }

    public function user($steamid)
    {
        if (isset($_SESSION['steamid']) && count($_POST) > 0) {
            User::action($_POST['player'], $_POST);
        }

        $data['player'] = User::get($steamid, ...array_merge(['custom_css'], User::$columns));

        if ($data['player'] !== false && count($data['player']) > 0) {
            $data['player']['settings'] = json_decode($data['player']['settings'], true);

            //var_dump($data);die();

            return self::view('profile', $data);
        } else {
            Util::redirect('/dashboard/users');
        }
    }
}
