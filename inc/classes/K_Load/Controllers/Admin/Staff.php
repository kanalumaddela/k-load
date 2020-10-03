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

namespace K_Load\Controllers\Admin;

use Exception;
use K_Load\Controllers\AdminController;
use K_Load\User;
use K_Load\Util;

class Staff extends AdminController
{
    public static $templateFolder = 'admin/staff';

    public function index()
    {
        User::validatePerm('staff');

        if (isset($_POST['save']) && isset($_POST['staff'])) {
            $_POST['staff']['duration'] = (int) $_POST['staff']['duration'] ?? 5000;

            if (isset($_POST['staff']['list']) && is_array($_POST['staff']['list'])) {
                $friendlyNames = array_values(self::$gamemodes);
                $friendlyNamesLowercase = array_map('strtolower', $friendlyNames);
                foreach ($_POST['staff']['list'] as $gamemode => $ranks) {
                    if (($index = array_search(strtolower($gamemode), $friendlyNamesLowercase)) !== false) {
                        unset($_POST['staff']['list'][$gamemode]);
                        $gamemode = array_search($friendlyNames[$index], self::$gamemodes);
                        Util::flash('alerts', 'Gamemode "'.$friendlyNames[$index].'" given, fixed to proper name: "'.$gamemode.'"');
                    }

                    $_POST['staff']['list'][$gamemode] = array_values($ranks);
                }
            } else {
                $_POST['staff']['list'] = [];
            }

            $success = Util::updateSetting(['staff'], [$_POST['staff']], $_POST['csrf']);

            $alert = ($success ? 'Staff have been saved' : 'Failed to save, please try again');

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/admin/staff');
        }

        $tmpStaff = json_decode(Util::getSetting('staff')['staff'], true);

        if (!isset($tmpStaff['duration'])) {
            $tmpStaff = [
                'duration' => 5000,
                'list'     => $tmpStaff,
            ];

            $success = Util::updateSetting(['staff'], [$tmpStaff], null, true);

            if (!$success) {
                throw new Exception('Failed to implement new staff data. Please check the mysql error logs in data/logs/mysql');
            }
        }

        $data = [
            'settings' => Util::getSetting('staff'),
        ];
        $data['settings']['staff'] = json_decode($data['settings']['staff'], true);

        return self::view('index', $data);
    }
}
