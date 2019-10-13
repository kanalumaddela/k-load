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

use Exception;
use K_Load\Controllers\BaseController;
use K_Load\User;
use K_Load\Util;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Markup;

class Music extends BaseController
{
    public static $templateFolder = 'admin/music';

    /**
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        User::validatePerm('music');

        if (isset($_POST['save']) && isset($_POST['music'])) {
            // validate basic shit
            $_POST['music']['volume'] = (int) ($_POST['music']['volume'] ?? 15);
            $_POST['music']['enable'] = (int) ($_POST['music']['enable'] ?? 0);
            $_POST['music']['random'] = (int) ($_POST['music']['random'] ?? 0);
            $_POST['music']['source'] = isset($_POST['music']['source']) ? (in_array($_POST['music']['source'], ['youtube', 'files', 'soundcloud']) ? $_POST['music']['source'] : 'youtube') : 'youtube';

            // validate youtube shit
            if (!isset($_POST['youtube']['list'])) {
                $_POST['youtube']['list'] = [];
            } else {
                // we only need the list now reeee
                $_POST['youtube'] = array_intersect_key($_POST['youtube'], ['list' => []]);
            }

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

            // validate music file order
            if (!isset($_POST['music']['order'])) {
                $_POST['music']['order'] = [];
            }

            $_POST['music']['order'] = array_unique($_POST['music']['order']);

            if (($orderLength = count($_POST['music']['order'])) > 0) {
                $musicFileOrder = $_POST['music']['order'];

                for ($i = 0; $i < $orderLength; $i++) {
                    if (!file_exists(APP_ROOT.'/data/music/'.$musicFileOrder[$i])) {
                        unset($musicFileOrder[$i]);
                    }
                }

                $_POST['music']['order'] = array_values(array_unique($musicFileOrder));
            }

            $success = Util::updateSetting(['music', 'youtube'], [$_POST['music'], $_POST['youtube']], $_POST['csrf']);
            $alert = ($success ? 'Music settings have been saved' : 'Failed to save, please try again and check the data/logs if necessary');

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/admin/music');
        }

        $data = Util::getSetting('music', 'youtube');
        if (!isset($data['music'])) {
            $temp_music_data = [
                'source' => 'youtube',
                'random' => $data['youtube']['volume'] ?? 0,
                'enable' => $data['youtube']['volume'] ?? 0,
                'volume' => $data['youtube']['volume'] ?? 15,
            ];

            $success = Util::updateSetting(['music'], [$temp_music_data], null, true);

            if (!$success) {
                throw new Exception('Failed to implement new music data. Please check the mysql error logs in data/logs/mysql');
            }

            $data['music'] = $temp_music_data;
        } else {
            $data['music'] = json_decode($data['music'], true);
        }
        $data['youtube'] = json_decode($data['youtube'], true);

        $dir = APP_ROOT.'/data/music';
        $files = glob($dir.'/*.ogg');
        $length = count($files);

        if ($length > 0) {
            for ($i = 0; $i < $length; $i++) {
                $filename = str_replace($dir.'/', '', $files[$i]);
                $name = str_replace('.ogg', '', $filename);

                $files[$i] = [
                    'filename'    => $filename,
                    'name'        => $name,
                    'name_hashed' => md5($name),
                    'row_id'      => 'file_'.md5($name),
                    'url'         => APP_URL.'/data/music/'.$filename,
                ];
            }
        }

        $forceUpdate = false;
        foreach ($data['music']['order'] as $gamemode => $songs) {
            foreach ($songs as $index => $song) {
                if (!file_exists(APP_ROOT.'/data/music/'.$song)) {
                    $forceUpdate = true;
                    unset($data['music']['order'][$gamemode][$index]);
                }
            }

            if (count($data['music']['order'][$gamemode]) === 0) {
                unset($data['music']['order'][$gamemode]);
            } else {
                $data['music']['order'][$gamemode] = array_values($data['music']['order'][$gamemode]);
            }
        }

        if ($forceUpdate) {
            Util::saveSetting('music', $data['music']);
        }

        $data['music_files'] = $files;
        $data['music_files_json'] = new Markup(json_encode($files), 'utf-8');

        return self::view('index', $data);
    }

    public function musicUpload()
    {
        User::validatePerm('music');

        $messages = [];

        if (!$this->http->files->has('music_files')) {
            $messages[] = 'No file(s) given or upload limits exceeded';
        }

        User::validateCSRF($_SESSION['steamid'], $this->http->request->get('csrf'));

        $musicFolder = APP_ROOT.'/data/music';

        $files = $this->http->files->all();

        $files = $files['music_files'] ?? [];

        /*
         * @var \Symfony\Component\HttpFoundation\File\UploadedFile
         */
        foreach ($files as $file) {
            $message = '';

            switch ($file->getError()) {
                case UPLOAD_ERR_NO_FILE:
                    $message = '`%s` is not a file.';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $message = '`%s` exceeds the upload_max_filesize directive in php.ini';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = '`%s` exceeds the MAX_FILE_SIZE';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    if (!in_array('Missing temporary folder', $messages)) {
                        $message = 'Missing temporary folder';
                    }
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = 'Failed to write `%s`. Check permissions.';
                    break;
                case UPLOAD_ERR_OK:
                    if ($file->getClientMimeType() !== 'audio/ogg') {
                        $message = 'Invalid filetype uploaded for `%s`. '.$file->getClientMimeType().' given.';
                        Util::log('error', 'Invalid filetype uploaded for '.$file->getClientOriginalName().'. '.$file->getClientMimeType().' given.');
                        break;
                    }

                    $error = false;

                    $filename = preg_replace('/[[:^print:]]/', '', $file->getClientOriginalName());

                    try {
                        $moved = $file->move($musicFolder, $filename) instanceof File;
                    } catch (FileException $e) {
                        $moved = false;
                    }

                    if ($moved === false && $error === false) {
                        $message = 'Failed to move `%s` into the music folder. Check file permissions.';
                    }

                    if ($moved) {
                        $message = '`%s` was uploaded';
                        Util::log('action', $filename.' was uploaded by '.$_SESSION['steamid']);
                    } else {
                        Util::log('error', $_SESSION['steamid'].' tried to upload '.$filename.'. Make sure the proper write permissions are give ');
                    }

                    break;
                default:
                    $message = 'Unknown error when uploading `%s`';
            }

            if (!empty($message)) {
                $messages[] = sprintf($message, empty($file->getClientOriginalName()) ? 'no-name' : htmlentities($file->getClientOriginalName()));
            }
        }

        foreach ($messages as $message) {
            Util::flash('alerts', $message);
        }

        Util::redirect('/dashboard/admin/music');
    }

    public function deleteMusic()
    {
        if (!User::can('music')) {
            die();
        }

        $filename = isset($_POST['file']) ? $_POST['file'] : null;
        $success = false;
        $message = 'Failed to delete. Try again';

        $filepath = '/data/music/'.$filename;
        if (!empty($filename) && file_exists(APP_ROOT.'/data/music/'.$filename)) {
            $success = unlink(APP_ROOT.$filepath);
            $message = $success ? 'File deleted' : $message;
        } else {
            $message = 'File does not exist, cannot delete';
        }

        Util::json(['success' => $success, 'message' => $message], true);
    }

    public function saveMusicOrder()
    {
        User::validatePerm('music');
        User::validateCSRF($_SESSION['steamid'], $this->http->request->get('csrf'));

        if ($this->http->request->has('music')) {
            $music = $this->http->request->get('music');

            if (isset($music['order']) && count($music['order']) > 0) {
                $order = [];

                foreach ($music['order'] as $gamemode => $songs) {
                    $order[$gamemode] = [];

                    foreach ($songs as $song) {
                        if (file_exists(APP_ROOT.'/data/music/'.$song)) {
                            $order[$gamemode][] = $song;
                        }
                    }

                    if (empty($order[$gamemode])) {
                        unset($order[$gamemode]);
                    }
                }

                if (count($order) > 0) {
                    $settings = Util::getSetting('music');
                    $settings['music'] = json_decode($settings['music'], true);
                    $settings['music']['order'] = $order;

                    Util::saveSetting('music', $settings['music']);
                }
            }
        }

        Util::flash('alerts', 'Attempted to save the music order. Any non-existing songs were not saved.');

        Util::redirect('/dashboard/admin/music');
    }
}
