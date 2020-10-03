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

class Backgrounds extends AdminController
{
    public static $templateFolder = 'admin/backgrounds';

    public function index()
    {
        User::validatePerm('backgrounds');

        if (count($_POST) === 3 && count(array_diff(array_keys($_POST), ['csrf', 'type', 'background'])) === 0) {
            $messages = [
                'Background deleted successfully',
                'Failed to delete background',
            ];

            User::validateCSRF($_SESSION['steamid'], $_POST['csrf']);

            if (substr($_POST['background'], 0, 1) === '/') {
                $location = APP_ROOT.str_replace('/', DIRECTORY_SEPARATOR, $_POST['background']);
            } else {
                $location = APP_ROOT.'/assets/img/backgrounds/'.$_POST['background'];
            }

            if (file_exists($location)) {
                if (is_dir($location)) {
                    $messages = [
                        'Gamemode backgrounds deleted successfully',
                        'Failed to delete gamemode backgrounds',
                    ];
                    Util::rmDir($location);
                } else {
                    unlink($location);
                }
            }

            $deleted = !file_exists($location);

            $data = [
                'success' => $deleted,
                'message' => $deleted ? $messages[0] : $messages[1].', refresh or try again',
            ];

            if ($deleted) {
                Util::log('action', $_SESSION['steamid'].' deleted '.$_POST['background']);
                User::refreshCSRF($_SESSION['steamid']);
                $data['csrf'] = User::getCSRF($_SESSION['steamid']);
            }

            Util::json($data, true);
        }

        if (isset($_POST['save']) && isset($_SESSION['steamid'])) {
            $_POST['backgrounds']['enable'] = (isset($_POST['backgrounds']['enable']) ? (int) $_POST['backgrounds']['enable'] : 0);
            $_POST['backgrounds']['random'] = (isset($_POST['backgrounds']['random']) ? (int) $_POST['backgrounds']['random'] : 0);
            $_POST['backgrounds']['duration'] = (isset($_POST['backgrounds']['duration']) && $_POST['backgrounds']['duration'] != 0) ? (int) $_POST['backgrounds']['duration'] : 8000;
            $_POST['backgrounds']['fade'] = (isset($_POST['backgrounds']['duration']) && $_POST['backgrounds']['fade'] != 0) ? (int) $_POST['backgrounds']['fade'] : 750;

            $success = Util::updateSetting(['backgrounds'], [$_POST['backgrounds']], $_POST['csrf']);

            $alert = ($success ? 'Background settings have been saved' : 'Failed to save, please try again and check the data/logs if necessary');

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/admin/backgrounds');
        }

        $data = [
            'settings'            => Util::getSetting('backgrounds'),
            'upload_requirements' => [
                'max_uploads' => (int) ini_get('max_file_uploads'),
                'file_size'   => ini_get('upload_max_filesize'),
            ],
        ];
        $data['settings']['backgrounds'] = json_decode($data['settings']['backgrounds'], true);

        $data['backgrounds'] = [];
        $bgGamemodes = glob(APP_ROOT.sprintf('%sassets%simg%sbackgrounds%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($bgGamemodes as $gamemode) {
            $bgImages = glob($gamemode.DIRECTORY_SEPARATOR.'*.{jpg,png}', GLOB_BRACE);

            if (count($bgImages) === 0) {
                continue;
            }

            $images = [];

            foreach ($bgImages as $image) {
                $images[] = [
                    'src'  => APP_PATH.str_replace(DIRECTORY_SEPARATOR, '/', str_replace(APP_ROOT, '', $image)),
                    'name' => basename($image),
                ];
            }

            $gamemode = explode(DIRECTORY_SEPARATOR, $gamemode);
            $gamemode = end($gamemode);
            $data['backgrounds'][$gamemode] = $images;
        }

        return self::view('index', $data);
    }

    public function backgroundsUpload()
    {
        if (!isset($_SESSION['steamid'])) {
            Util::redirect('/dashboard/admin/backgrounds');
        }

        if (!isset($_FILES['bg-files']) || !User::can('backgrounds')) {
            Util::redirect('/dashboard/admin/backgrounds');
        }

        User::validateCSRF($_SESSION['steamid'], $_POST['csrf']);

        $gamemode = $_POST['gamemode'] ?? 'global';
        $gamemode = preg_replace('/[^a-zA-Z0-9]+/', '', $gamemode);

        $bgFolder = APP_ROOT.'/assets/img/backgrounds/'.$gamemode;
        Util::mkDir($bgFolder);

        $message = 'No images provided';
        $failed = false;

        if (($count = count($_FILES['bg-files']['name'])) > 0) {
            $filesUploaded = 0;
            $files = $_FILES['bg-files'];

            foreach ($files['name'] as $index => $name) {
                switch ($files['error'][$index]) {
                    case UPLOAD_ERR_NO_FILE:
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                    case UPLOAD_ERR_INI_SIZE:
                        Util::log('error', $name.' failed to upload: filesize limit exceeded');
                        break;
                    case UPLOAD_ERR_OK:
                        $fileinfo = new finfo(FILEINFO_MIME_TYPE);

                        if (!in_array($type = $fileinfo->file($files['tmp_name'][$index]), ['image/png', 'image/jpg', 'image/jpeg'])) {
                            Util::log('error', 'Invalid filetype uploaded for '.$name.'. '.$type.' given.');
                            break;
                        }

                        $filename = strtolower(preg_replace('/[[:^print:]]/', '', $name));

                        $success = move_uploaded_file($files['tmp_name'][$index], $bgFolder.'/'.$filename);

                        if ($success === false && $failed === false) {
                            $failed = true;
                        }

                        if ($success) {
                            Util::log('action', $name.' was uploaded by '.$_SESSION['steamid']);
                            $filesUploaded++;
                        } else {
                            Util::log('error', $_SESSION['steamid'].' tried to upload '.$name.'. Make sure the proper write permissions are give ');
                        }
                        break;
                    default:
                        Util::log('error', $name.' failed to upload: Unknown error, check your upload_max_filesize and post_max_size in your php.ini');
                        break;
                }
            }

            $message = $filesUploaded.' out of '.$count.' backgrounds were uploaded.'.(!$failed ? ' Check the data/logs/error folder for more info.' : '');
        }

        Util::flash('alert', $message);
        Util::redirect('/dashboard/admin/backgrounds');
    }
}
