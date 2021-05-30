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

namespace KLoad\Controllers\Admin;

use Exception;
use KLoad\Controllers\AdminController;
use KLoad\User;
use KLoad\Util;

class Rules extends AdminController
{
    public static $templateFolder = 'admin/rules';

    public function index()
    {
        User::validatePerm('rules');

        if (isset($_POST['save']) && isset($_POST['rules'])) {
            $_POST['rules']['duration'] = (int) $_POST['rules']['duration'] ?? 10000;
            if (!isset($_POST['rules']['list'])) {
                $_POST['rules']['list'] = [];
            }

            $friendlyNames = array_values(self::$gamemodes);
            $friendlyNamesLowercase = array_map('strtolower', $friendlyNames);
            foreach ($_POST['rules']['list'] as $gamemode => $rules) {
                if (($index = array_search(strtolower($gamemode), $friendlyNamesLowercase)) !== false) {
                    unset($_POST['rules']['list'][$gamemode]);
                    $gamemode = array_search($friendlyNames[$index], self::$gamemodes);
                    Util::flash('alerts', 'Gamemode "'.$friendlyNames[$index].'" given, fixed to proper name: "'.$gamemode.'"');
                }

                $_POST['rules']['list'][$gamemode] = array_values($rules);
            }

            $success = Util::updateSetting(['rules'], [$_POST['rules']], $_POST['csrf']);

            $alert = ($success ? 'Rules have been saved' : 'Failed to save, please try again');

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/admin/rules');
        }

        $tmpRules = json_decode(Util::getSetting('rules')['rules'], true);

        if (!isset($tmpRules['duration'])) {
            $tmpRules = [
                'duration' => 10000,
                'list'     => $tmpRules,
            ];

            $success = Util::updateSetting(['rules'], [$tmpRules], null, true);

            if (!$success) {
                throw new Exception('Failed to implement new rules data. Please check the mysql error logs in data/logs/mysql');
            }
        }

        $data = [
            'settings' => Util::getSetting('rules'),
        ];

        $data['settings']['rules'] = json_decode($data['settings']['rules'], true);

        return self::view('index', $data);
    }
}
