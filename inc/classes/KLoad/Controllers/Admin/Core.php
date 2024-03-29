<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2023 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Controllers\Admin;

use Exception;
use KLoad\App;
use KLoad\Controllers\AdminController;
use KLoad\Facades\Config;
use KLoad\Facades\Lang;
use KLoad\Http\RedirectResponse;
use KLoad\View\LoadingView;
use Symfony\Component\HttpFoundation\Response;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function get_defined_vars;
use function json_decode;
use function KLoad\flash;
use function KLoad\redirect;
use function str_contains;
use const KLoad\APP_ROUTE_URL;

class Core extends AdminController
{
    protected static string $templateFolder = 'general';

    protected \KLoad\Config $coreConfig;

    public function boot(): void
    {
        $this->coreConfig = App::get('config');

        parent::boot(); // TODO: Change the autogenerated stub
    }

    public function index(): Response
    {
        $pages = [
            ['icon' => 'fas fa-wrench', 'title' => 'core', 'description' => 'core_desc', 'route' => 'dashboard/admin/core'],
            ['icon' => 'fas fa-sliders-h', 'title' => 'general', 'description' => 'general_desc', 'route' => 'dashboard/admin/general'],
            ['icon' => 'fas fa-images', 'title' => 'backgrounds', 'description' => 'backgrounds_desc', 'route' => 'dashboard/admin/backgrounds'],
            ['icon' => 'fas fa-comment-alt', 'title' => 'messages', 'description' => 'messages_desc', 'route' => 'dashboard/admin/messages'],
            ['icon' => 'fas fa-headphones', 'title' => 'music', 'description' => 'music_desc', 'route' => 'dashboard/admin/music'],
            ['icon' => 'fas fa-list-ol', 'title' => 'rules', 'description' => 'rules_desc', 'route' => 'dashboard/admin/rules'],
            ['icon' => 'fas fa-user-tie', 'title' => 'staff', 'description' => 'staff_desc', 'route' => 'dashboard/admin/staff'],
            ['icon' => 'fas fa-paint-brush', 'title' => 'themes', 'description' => 'themes_desc', 'route' => 'dashboard/admin/themes'],
        ];

        $filtered = [];

        foreach ($pages as $page) {
            if ($this->user['super'] || $this->can($page['title'])) {
                $filtered[] = $page;
            }
        }

        $this->setTitle('admin');

        return $this->view('index', ['pages' => $filtered]);
    }

    public function core(): Response
    {
        $themes = LoadingView::getThemes(true);
        $steamApiKey = Config::get('apikeys.steam');
        $currentTheme = LoadingView::getTheme();

        return $this->view('core', get_defined_vars());
    }

    public function configUpdate(): RedirectResponse
    {
        $this->validateCsrf();

        $post = $this->getPost()->all();

        $key = $post['steam_api_key'];

        $valid = ($error = self::testSteamApiKey($key)) === true;

        if ($valid) {
            $this->coreConfig->set('apikeys.steam', $key);
            $this->coreConfig->save();
        }

        flash($valid ? 'success' : 'danger', $valid ? Lang::get('config_updated', 'Config has been updated!') : $error);

        return redirect(APP_ROUTE_URL.'/dashboard/admin/core');
    }

    private static function testSteamApiKey($key): bool|string
    {
        $url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key='.$key.'&steamids=76561198152390718';

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        $data = curl_exec($curl);
//        $err = curl_error($curl);
//        $errno = curl_errno($curl);
        curl_close($curl);

        if (str_contains($data, 'Please verify your')) {
            return Lang::get('api_key_invalid_error', 'API key invalid, please make sure it is entered correctly.');
        }

        try {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            unset($data);
        } catch (Exception $e) {
            return 'Validate Failed:'.$e->getMessage();
        }

        return true;
    }

    public function themeUpdate(): RedirectResponse
    {
        $this->validateCsrf();

        $post = $this->getPost()->all();

        if (LoadingView::themeExists($post['theme'])) {
            $this->coreConfig->set('loading_theme', $post['theme']);
            $this->coreConfig->save();

            flash('success', Lang::get('theme_updated', 'Default theme has been updated!'));
        }

        return redirect(APP_ROUTE_URL.'/dashboard/admin/core');
    }
}
