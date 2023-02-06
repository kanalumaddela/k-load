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
use KLoad\Controllers\AdminController;
use KLoad\Http\RedirectResponse;
use KLoad\Models\Setting;
use KLoad\Traits\UpdateSettings;
use Symfony\Component\HttpFoundation\Response;
use function KLoad\redirect;

class Staff extends AdminController
{
    use UpdateSettings;

    protected static array $defaultData = [
        'enable' => false,
        'list' => [],
    ];

    public function index(): Response
    {
        $this->authorize('staff');

        $settings = Setting::where('name', 'staff')->pluck('value', 'name');

        return $this->view('index', get_defined_vars());
    }

    public function indexPost(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('staff');

        $staff = static::$defaultData;
        $post = $this->getPost()->get('staff');
        $redirect = redirect(static::getRoute());

        $staff['enable'] = (bool)($post['enable'] ?? false);

        if (isset($post['list'])) {
            foreach ($post['list'] as $gamemode => $list) {
                if (count($list['steamids']) !== count($list['ranks'])) {
                    return $redirect->withError('Number of steamids and ranks do not equal each other')->withInputs();
                }

                $fixed = [];

                foreach ($list['steamids'] as $i => $steamid) {
                    if (empty($steamid)) {
                        continue;
                    }

                    $fixed[] = [
                        'steamid' => $steamid,
                        'rank' => $list['ranks'][$i],
                    ];
                }

                $staff['list'][strtolower($gamemode)] = $fixed;
            }
        }


        try {
            $this->updateSetting('staff', $staff);

            return $redirect;
        } catch (Exception) {
            return $redirect->withInputs();
        }
    }
}