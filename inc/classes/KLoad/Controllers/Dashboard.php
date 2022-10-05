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

namespace KLoad\Controllers;

use Illuminate\Database\Eloquent\Builder;
use KLoad\Exceptions\InvalidToken;
use KLoad\Facades\Cache;
use KLoad\Facades\Config;
use KLoad\Facades\Session;
use KLoad\Helpers\Util;
use KLoad\Http\RedirectResponse;
use KLoad\Models\Setting;
use KLoad\Models\User;
use KLoad\View\LoadingView;
use function array_intersect;
use function array_keys;
use function array_merge;
use function file_get_contents;
use function get_defined_vars;
use function header;
use function json_encode;
use function KLoad\flash;
use function KLoad\redirect;
use function md5;
use function preg_match;
use const KLoad\APP_ROOT;
use const KLoad\APP_ROUTE_URL;

class Dashboard extends BaseController
{
    protected static string $templateFolder = 'controllers/dashboard';

    public function index()
    {
        if (!$this->user['admin']) {
            exit();
//            return $this->settings();
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
        $userCount = number_format(User::count());

        return $this->view('index', get_defined_vars());
    }

    /**
     * @throws InvalidToken
     *
     * @return RedirectResponse
     */
    public function indexPost(): RedirectResponse
    {
        $this->validateCsrf();

        $post = $this->request->request;

        if ($post->has('theme') && $this->user['super']) {
            $theme = $post->get('theme');

            if (LoadingView::themeExists($theme) && $theme !== Config::get('loading_theme')) {
                Config::set('loading_theme', $theme);
                Config::save();
                flash('success', 'Theme has been changed to `'.$theme.'`');
            }

            if (!LoadingView::themeExists($theme)) {
                Session::error('Theme: '.$theme.' does not exist');
            }
        }

        if ($post->has('community_name') && $this->can('community_name')) {
            Setting::where('name', 'community_name')->update(['value' => $post->get('community_name')]);
            flash('success', 'Community name has been updated');
        }

        if ($post->has('backgrounds') && $this->can('backgrounds')) {
            $backgrounds = $post->get('backgrounds');
            $backgrounds['enable'] = isset($backgrounds['enable']) ? (int) $backgrounds['enable'] : 0;
            $backgrounds['random'] = isset($backgrounds['random']) ? (int) $backgrounds['random'] : 0;
            $backgrounds['duration'] = isset($backgrounds['duration']) ? (int) $backgrounds['duration'] : 5000;
            $backgrounds['fade'] = isset($backgrounds['fade']) ? (int) $backgrounds['fade'] : 750;

            Setting::where('name', 'backgrounds')->update(['value' => json_encode($backgrounds)]);
            flash('success', 'Background settings have been saved');
        }

        if ($post->has('music') && $this->can('music')) {
            $musicPost = $post->get('music');
            $musicPost['enable'] = isset($musicPost['enable']) ? (int) $musicPost['enable'] : 0;
            $musicPost['random'] = isset($musicPost['random']) ? (int) $musicPost['random'] : 0;
            $musicPost['volume'] = isset($musicPost['volume']) ? (int) $musicPost['volume'] : 15;
            $musicPost['use_player_volume'] = isset($musicPost['use_player_volume']) ? (int) $musicPost['use_player_volume'] : false;

            $music = Setting::where('name', 'music')->first();

            Setting::where('name', 'music')->update(['value' => json_encode(array_merge($music->value, $musicPost))]);
            flash('success', 'Music settings have been saved');
        }

        return redirect(APP_ROUTE_URL.'/dashboard');
    }

    public function settingsRedirect()
    {
        return redirect(APP_ROUTE_URL.'/dashboard/my-settings', 301);
    }

    public function mySettings()
    {
        $data = [
            'themes' => LoadingView::getThemes(true),
        ];

        return $this->view('my-settings', array_merge($data, User::findBySteamid(Session::user()['steamid'])->only('settings')));
    }

    public function mySettingsPost()
    {
        $settings = Setting::whereIn('name', ['backgrounds', 'music', 'youtube'])->get()->pluck('value', 'name')->toArray();
        $settings['youtube'] = array_merge($settings['youtube'], $settings['music']);

        unset($settings['music'], $settings['youtube']['source'], $settings['youtube']['order'], $settings['youtube']['list']);

        $post = $this->request->request->all();
        unset($post['_csrf']);

        if (!isset($post['theme'])) {
            $post['theme'] = LoadingView::getTheme();
        }

        $post['backgrounds']['enable'] = !isset($post['backgrounds']['enable']) ? 0 : (int) $post['backgrounds']['enable'];
        $post['backgrounds']['random'] = !isset($post['backgrounds']['random']) ? 0 : (int) $post['backgrounds']['random'];
        $post['backgrounds']['duration'] = (int) $post['backgrounds']['duration'];
        $post['backgrounds']['fade'] = (int) $post['backgrounds']['fade'];
        $post['youtube']['enable'] = !isset($post['youtube']['enable']) ? 0 : (int) $post['youtube']['enable'];
        $post['youtube']['random'] = !isset($post['youtube']['random']) ? 0 : (int) $post['youtube']['random'];
        $post['youtube']['display_videos'] = !isset($post['youtube']['display_videos']) ? 0 : (int) $post['youtube']['display_videos'];

        $post['youtube']['list'] = array_filter(array_map(function ($val) {
            return Util::YouTubeID($val);
        }, $post['youtube']['list']));

        $post = array_merge($settings, $post);
        $update = [];

        unset($post['custom_css']);

        $update['settings'] = $post;

        User::where('steamid', $this->user['steamid'])->update($update);

        flash('success', 'Your settings have been saved!');

        return redirect(APP_ROUTE_URL.'/dashboard/my-settings');
    }

    public function users()
    {
        /**
         * @var Builder $users
         */
        $users = User::select('id', 'name', 'steamid', 'steamid2');

        if ($this->request->get('search')) {
            $query = $this->request->get('search');
            $users->where('name', 'like', '%'.$query.'%')
                ->orWhere('steamid', '=', $query)
                ->orWhere('steamid2', '=', $query)
                ->orWhere('steamid3', '=', $query);
        }

        $users = $users->paginate(20);

        $steamids = $users->pluck('steamid')->implode(',');

        $data = [
            'users'         => $users,
            'usersPageList' => Util::paginateFix($users),
            'steamInfo'     => Cache::remember('steaminfo-users-'.md5($steamids), 3600, function () use ($steamids) {
                return empty($data = Util::getPlayersInfo($steamids, true)) ? null : $data;
            }),
            'query' => $query ?? null,
        ];

        return $this->view('users', $data);
    }

    public function profile(int $id)
    {
        $player = User::findOrFail($id);

        $steamid = $player->steamid;

        $steamInfo = Cache::remember('steaminfo-user-'.$steamid, 3600, function () use ($steamid) {
            return empty($data = Util::getPlayersInfo($steamid, true)) ? null : $data;
        });

        return $this->view('profile', get_defined_vars());
    }

    public function userOldRoute($steamid)
    {
        $player = User::findBySteamid($steamid);

        $steamid = $player->steamid;

        $steamInfo = Cache::remember('steaminfo-user-'.$steamid, 3600, function () use ($steamid) {
            return empty($data = Util::getPlayersInfo($steamid, true)) ? null : $data;
        });

        return $this->view('profile', get_defined_vars());
    }

    public function getUserBackground($steamid)
    {
        $url = Cache::remember('steam-bg-'.$steamid, 3600, function () use ($steamid) {
            $regex = "/no_header *?profile_page *?has_profile_background *?.*\n\t *?style=\"background-image: *?url\( *?\n?'(https?:\/\/.*.jpg)/m";
            $steamProfile = file_get_contents('https://steamcommunity.com/profiles/'.$steamid);

            preg_match($regex, $steamProfile, $matches);

            return $matches[1] ?? 'https://community.cloudflare.steamstatic.com/public/images/profile/2020/bg_dots.png';
        });

        header('Location: '.$url, true, 302);
        exit();
    }
}
