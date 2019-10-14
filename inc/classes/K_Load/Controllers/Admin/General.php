<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace K_Load\Controllers\Admin;


use Database;
use J0sh0nat0r\SimpleCache\Cache;
use K_Load\Controllers\AdminController;
use K_Load\Setup;
use K_Load\Template;
use K_Load\Test;
use K_Load\User;
use K_Load\Util;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;

class General extends AdminController
{
    public static $templateFolder = 'admin';

    public function index()
    {
        return self::view('index');
    }

    public function core()
    {
        if (!User::isSuper($_SESSION['steamid'])) {
            Util::flash('alerts', ['message' => 'You are not a super user defined in the data/config.php', 'css' => 'red']);
            Util::redirect('/dashboard/admin');
        }

        $config = include APP_ROOT.'/data/config.php';

        $data = [
            'version'  => Util::version(),
            'updates'  => Setup::getUpdates(),
            'theme'    => $config['loading_theme'] ?? 'default',
            'themes'   => Template::loadingThemes(true),
            'api_keys' => [
                'steam' => $config['apikeys']['steam'],
            ],
        ];
        $data['updates'] = Setup::getUpdates();

        if (isset($_SESSION['steamid']) && User::isSuper($_SESSION['steamid'])) {
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'update_apikeys':
                        if (count($_POST) > 0) {
                            if (isset($_POST['save_apikeys']) && isset($_POST['apikeys'])) {
                                if (count($_POST['apikeys']) > 0) {
                                    if (isset($_POST['apikeys']['steam'])) {
                                        if (Test::steam($_POST['apikeys']['steam'])) {
                                            $config['apikeys'] = $_POST['apikeys'];
                                            array_multisort($config);
                                            Util::createConfig($config);
                                            $data['alert'] = 'API key updated';
                                            $data['api_keys']['steam'] = $config['apikeys']['steam'];
                                        } else {
                                            $data['alert'] = 'API key is invalid, please try again';
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'update_theme':
                        if (count($_POST) > 0) {
                            if (isset($_POST['save_theme']) && isset($_POST['theme'])) {
                                if (Template::isLoadingTheme($_POST['theme'])) {
                                    $config['loading_theme'] = $_POST['theme'];
                                    $data['theme'] = $_POST['theme'];
                                    array_multisort($config);
                                    Util::createConfig($config);
                                    $data['alert'] = 'Theme updated';
                                } else {
                                    $data['alert'] = 'Not a valid theme, please make sure there is a <code>pages/loading</code> in the theme';
                                }
                            }
                        }
                        break;
                    case 'update':
                        if ($data['updates']['amount'] > 0) {
                            Setup::update();
                            $data['version'] = Util::version();
                            $data['updates'] = Setup::getUpdates();

                            $themes = Template::loadingThemes(true);
                            $config['loading_themes'] = array_column($themes, 'name');

                            Util::createConfig($config);

                            $data['alert'] = 'An update was attempted, please make sure everything is working and check the logs';
                        } else {
                            $data['alert'] = 'There are no updates';
                        }
                        break;
                    case 'refresh_themes':
                        $themes = Template::loadingThemes(true);
                        $config['loading_themes'] = array_column($themes, 'name');
                        Util::createConfig($config);
                        $data['alert'] = 'Themes have been refreshed and added to config';
                        break;
                    case 'clear_cache':
                        if (file_exists(APP_ROOT.'/data/cache')) {
                            Util::rmDir(APP_ROOT.'/data/cache');
                            Util::log('action', 'Attempted to clear all cache');
                        }
                        $data['alert'] = !file_exists(APP_ROOT.'/data/cache') ? 'All cache has been deleted' : 'Failed to clear all cache';
                        break;
                    case 'clear_cache_data':
                        Util::log('action', 'Attempted to clear cached data');
                        $data['alert'] = Cache::clear() ? 'Cached data has been cleared' : 'Failed to delete cached data';
                        break;
                    case 'clear_cache_template':
                        if (file_exists(APP_ROOT.'/data/cache/templates')) {
                            Util::rmDir(APP_ROOT.'/data/cache/templates');
                            Util::log('action', 'Attempted to clear template cache');
                        }
                        $data['alert'] = !file_exists(APP_ROOT.'/data/cache/templates') ? 'Template cache has been deleted' : 'Failed to clear template cache';
                        break;
                    case 'refresh_css':
                        $css_fixed = true;
                        $files = glob(APP_ROOT.'/data/users/*');
                        foreach ($files as $file) {
                            unlink($file);
                        }
                        $user_count = Database::conn()->count('kload_users')->execute();
                        $batches = ceil($user_count / 5);
                        for ($i = 0; $i < $batches; $i++) {
                            $users = Database::conn()->select('SELECT `steamid`,`custom_css` FROM `kload_users`')->where("`custom_css` != NULL OR `custom_css` != ''")->limit(5, $i * 5)->execute();
                            $users = (array) $users;
                            foreach ($users as $user) {
                                if (!empty($user['custom_css'])) {
                                    file_put_contents(APP_ROOT.'/data/users/'.$user['steamid'].'.css', $user['custom_css']);
                                    if (!file_exists(APP_ROOT.'/data/users/'.$user['steamid'].'.css')) {
                                        Util::log('action', 'Failed to recreate CSS file for User: '.$user['steamid']);
                                        $css_fixed = false;
                                    } else {
                                        Util::log('action', 'Recreated CSS file for User: '.$user['steamid']);
                                    }
                                }
                            }
                        }
                        $data['alert'] = $css_fixed ? 'Player\'s CSS have been recompiled' : 'Failed to recompile all users, check the logs';
                        break;
                    case 'unban_all':
                        $success = Database::conn()->add('UPDATE `kload_users` SET `banned` = 0')->execute();
                        $data['alert'] = $success ? 'All users have been unbanned' : 'Failed to unban all users';
                        break;
                    case 'reset_perms':
                        $success = Database::conn()->add("UPDATE `kload_users` SET `admin` = 0, `perms` = '[]' WHERE `steamid` != '?'", [$_SESSION['steamid']])->execute();
                        $data['alert'] = $success ? 'Perms have been reset for all users' : 'Failed to reset perms';
                        break;
                    default:
                        $data['alert'] = 'Not a valid action';
                        break;
                }

                Util::flash('alert', $data['alert']);
                Util::redirect('/dashboard/admin/core');
            }
        }

        return self::view('core', $data);
    }

    public function general()
    {
        if (!User::canOr('community_name', 'description')) {
            Util::flash('alerts', 'No permissions: community_name and description');
            Util::redirect('/dashboard/admin');
        }

        if (isset($_POST['save']) && isset($_SESSION['steamid'])) {
            $_POST['youtube']['enable'] = (isset($_POST['youtube']['enable']) ? (int) $_POST['youtube']['enable'] : 0);
            $_POST['youtube']['random'] = (isset($_POST['youtube']['random']) ? (int) $_POST['youtube']['random'] : 0);
            $_POST['youtube']['volume'] = (isset($_POST['youtube']['volume']) ? (int) $_POST['youtube']['volume'] : 0);
            $_POST['youtube']['list'] = (isset($_POST['youtube']['list']) ? $_POST['youtube']['list'] : []);
            if (count($_POST['youtube']['list']) > 0) {
                $yt_ids = [];
                foreach ($_POST['youtube']['list'] as $url) {
                    $url = trim($url);
                    $youtube_id = Util::YouTubeID($url);
                    if ($youtube_id) {
                        $yt_ids[] = $youtube_id;
                    }
                }
                $_POST['youtube']['list'] = $yt_ids;
            }

            $success = Util::updateSetting(['community_name', 'description', 'youtube'], [(isset($_POST['community_name']) ? $_POST['community_name'] : 'K-Load'), (isset($_POST['description']) ? substr($_POST['description'], 0, 250) : ''), $_POST['youtube']], $_POST['csrf']);
            $alert = ($success ? 'Save successful' : 'Failed to save, please try again');

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/admin/general');
        }

        $data = [
            'settings' => Util::getSetting('community_name', 'description', 'youtube', 'logo'),
            'logos'    => [],
        ];
        $data['settings']['youtube'] = json_decode($data['settings']['youtube'], true);

        $logos = glob(APP_ROOT.'/assets/img/logos/*.{jpeg,jpg,png}', GLOB_BRACE);
        foreach ($logos as $logo) {
            $data['logos'][] = basename($logo);
        }

        return self::view('general', $data);
    }

    public function logo()
    {
        if (!User::isSuper($_SESSION['steamid'])) {
            $message = 'You are not a super admin!';
        } else {
            User::validateCSRF($_SESSION['steamid'], $this->http->request->get('csrf'));

            if (file_exists(APP_ROOT.'/assets/img/logos/'.$this->http->request->get('logo'))) {
                $message = 'Logo saved, it is now active across templates that display a logo';
                Util::saveSetting('logo', $this->http->request->get('logo'));
            } else {
                $message = 'Logo does not exist, try again';
            }
        }

        Util::flash('alerts', $message);
        Util::redirect('/dashboard/admin/general');
    }

    public function logoUpload()
    {
        if (!User::isSuper($_SESSION['steamid'])) {
            die();
        }

        if (!$this->http->files->has('logo')) {
            Util::flash('alerts', 'No logo file was given');
            Util::redirect('/dashboard/admin/general');
        }

        User::validateCSRF($_SESSION['steamid'], $this->http->request->get('csrf'));

        $logoFolder = APP_ROOT.'/assets/img/logos';
        Util::mkDir($logoFolder);

        /**
         * @var \Symfony\Component\HttpFoundation\File\UploadedFile
         */
        $file = $this->http->files->get('logo');

        $error = true;
        $message = 'Unknown error';

        switch ($file->getError()) {
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the MAX_FILE_SIZE';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file. Check permissions.';
                break;
            case UPLOAD_ERR_OK:
                if (!in_array($file->getClientMimeType(), ['image/png', 'image/jpg', 'image/jpeg'])) {
                    $message = 'Invalid filetype uploaded for '.$file->getClientOriginalName().'. '.$file->getClientMimeType().' given.';
                    Util::log('error', 'Invalid filetype uploaded for '.$file->getClientOriginalName().'. '.$file->getClientMimeType().' given.');
                    break;
                }

                $error = false;

                $filename = strtolower(preg_replace('/[[:^print:]]/', '', $file->getClientOriginalName()));

                try {
                    $success = $file->move($logoFolder, $filename) instanceof File;
                } catch (FileException $e) {
                    $success = false;
                }

                if ($success === false && $error === false) {
                    $error = true;
                    $message = 'Failed to move uploaded logo into the folder. Check file permissions.';
                }

                if ($success) {
                    Util::log('action', $filename.' was uploaded by '.$_SESSION['steamid']);
                } else {
                    Util::log('error', $_SESSION['steamid'].' tried to upload '.$filename.'. Make sure the proper write permissions are give ');
                }

                break;
        }

        if (!$error) {
            $message = 'Logo uploaded successfully, be sure to set it below';
        }

        Util::flash('alerts', ['message' => $message, 'css' => $error ? 'red' : 'green']);
        Util::redirect('/dashboard/admin/general');
    }

    public function logoDelete()
    {
        if (!User::isSuper($_SESSION['steamid'])) {
            die();
        }

        User::validateCSRF($_SESSION['steamid'], $this->http->request->get('csrf'));

        $deleted = false;
        if (file_exists(APP_ROOT.'/assets/img/logos/'.$this->http->request->get('logo'))) {
            $deleted = unlink(APP_ROOT.'/assets/img/logos/'.$this->http->request->get('logo'));

            if ($deleted) {
                $settings = Util::getSetting('logo');
                if (isset($settings['logo']) && $settings['logo'] === $this->http->request->get('logo')) {
                    Util::saveSetting('logo', null);
                }
            }

            $message = $deleted ? 'Logo successfully deleted' : 'Failed to delete logo, try again';
        } else {
            $message = 'Logo does not exist, refresh the page';
        }

        return new JsonResponse(['data' => ['message' => $message, 'css' => $deleted ? 'green' : 'orange', 'csrf' => User::getCSRF()], 'success' => $deleted]);
    }
}