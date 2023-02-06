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

namespace KLoad\Controllers;

use KLoad\Facades\Cache;
use KLoad\Helpers\Util;
use KLoad\Http\RedirectResponse;
use function KLoad\redirect;
use const KLoad\APP_URL;

class API extends BaseController
{
    public function userInfo($steamid, string $info = null)
    {
        $data = Cache::remember('api-steaminfo-user-'.$steamid, 3600, static function () use ($steamid) {
            return empty($data = Util::getPlayerInfo($steamid)) ? null : $data;
        });

        return !empty($info) ? ($data[$info] ?? null) : $data;
    }

    public function avatar($steamid): RedirectResponse
    {
        $info = $this->userInfo($steamid, 'avatarfull');

        return redirect(!empty($info) ? $info : APP_URL.'/assets/img/avatar.jpg');
    }
}
