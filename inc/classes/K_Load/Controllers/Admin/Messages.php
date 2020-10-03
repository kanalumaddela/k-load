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

use K_Load\Controllers\AdminController;
use K_Load\User;
use K_Load\Util;

class Messages extends AdminController
{
    public static $templateFolder = 'admin/messages';

    public function index()
    {
        User::validatePerm('messages');

        if (isset($_POST['save']) && isset($_POST['messages'])) {
            $_POST['messages']['random'] = isset($_POST['messages']['random']) ? (int) $_POST['messages']['random'] : 0;
            $_POST['messages']['duration'] = isset($_POST['messages']['duration']) ? (int) $_POST['messages']['duration'] : 5000;
            $_POST['messages']['fade'] = (isset($_POST['messages']['fade']) ? (int) $_POST['messages']['fade'] : 500);

            if (!isset($_POST['messages']['list'])) {
                $_POST['messages']['list'] = [];
            }

            $success = Util::updateSetting(['messages'], [$_POST['messages']], $_POST['csrf']);
            $alert = ($success ? 'Messages have been saved' : 'Failed to save, please try again');

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/admin/messages');
        }

        $data = [
            'settings' => Util::getSetting('messages'),
        ];
        $data['settings']['messages'] = json_decode($data['settings']['messages'], true);

        if (!isset($data['settings']['messages']['list'])) {
            $data['settings']['messages']['list'] = [];
            Util::updateSetting(['messages'], [$data['settings']['messages']], null, true);
        }

        return self::view('index', $data);
    }
}
