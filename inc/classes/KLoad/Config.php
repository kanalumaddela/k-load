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

namespace KLoad;

use Exception;
use function copy;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function is_string;
use function time;

class Config extends DotArray
{
    private static $templateConfig = [
        'dashboard_theme' => 'default',
        'loading_theme'   => 'default',
        'apikeys'         => [
            'steam' => '',
        ],
        'admins'          => [],
        'mysql'           => [
            'host' => 'localhost',
            'port' => '3306',
            'user' => 'root',
            'pass' => '',
            'db'   => 'k-load',
        ],
    ];

    public $exists = false;

    protected $location;

    public function __construct($items = [], $ignoreMissing = false)
    {
        if (is_string($items)) {
            $this->setLocation($items);
            $this->exists = file_exists($items);

            if ($this->fileExists() || $ignoreMissing) {
                $items = require_once $items;
            }
        }

        parent::__construct($items);
    }

    public function setLocation(string $location)
    {
        $this->location = $this->checkLocation($location);

        return $this;
    }

    public function checkLocation(string $location)
    {
        if (is_dir($location)) {
            $location = $location.'/config.php';
        }

        return $location;
    }

    public function fileExists(): bool
    {
        return $this->exists;
    }

    public function save()
    {
        if (empty($this->location)) {
            throw new Exception('location not set, cannot save config');
        }

//        $loc = dirname($this->location);
//
//        mkdir($loc, 644, true);
//
//        if (!file_exists($loc)) {
//            throw new Exception('Config directory: `'.$loc.'` could not be created');
//        }

        static::saveConfig($this->location, $this->all());

//        copy($this->location, $this->location.'.'.time().'.old');
//
//        file_put_contents($this->location, "<?php\n".App::getCopyright()."\n\nreturn ".var_export_fixed($this->all()).';');
        if (!file_exists($this->location)) {
            throw new Exception('Config could not be saved in: ' . $this->location);
        }
    }

    public static function saveConfig(string $location, $data, bool $saveOriginal = true)
    {
        if (file_exists($location) && $saveOriginal) {
            copy($location, $location . '.' . time() . '.old');
        }

        file_put_contents($location, "<?php\n" . App::getCopyright() . "\n\nreturn " . var_export_fixed($data) . ';');
    }

    public function create(array $config)
    {
        exit('todo: ' . __CLASS__ . '@' . __METHOD__);

        $template = static::$templateConfig;

        foreach ($config as $key => $value) {
            if (isset($template[$key])) {
                $template[$key] = $value;
            }
        }

        file_put_contents(APP_ROOT.'/data/config.php', "<?php\n\nreturn ".var_export_fixed($this->config));

        if (!file_exists(APP_ROOT.'/data/config.php')) {
            throw new Exception('Config could not be saved in: '.APP_ROOT.'/data/config.php');
        }
    }
}
