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

namespace K_Load;

use Exception;
use InvalidArgumentException;
use function dirname;
use function explode;
use function file_exists;
use function file_put_contents;
use function is_array;
use function mkdir;

class OldConfig
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

    protected $config = [];

    public function __construct($configFile = null, $ignoreMissing = false)
    {
        if ($configFile) {
            $this->location = $configFile;
            $this->exists = file_exists($configFile);

            if ($this->exists() || $ignoreMissing) {
                $this->config = require_once $configFile;
            }
        }
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function setLocation(string $location)
    {
        $this->location = $location;

        return $this;
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->config[$key];
        }

        $val = $default;
        $array = $this->config;

        foreach (explode('.', $key) as $segment) {
            if (!isset($array[$segment])) {
                $val = $default;
                break;
            }

            $array = $array[$segment];
            $val = $array;

        }

        return $val;
    }

    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        }

        if (!is_string($key) || empty($value)) {
            throw new InvalidArgumentException('$key or $value given is an invalid type');
        }

        $this->config[$key] = $value;

        return $this;
    }

    public function save()
    {
        if (empty($this->location)) {
            throw new Exception('location not set, cannot save config');
        }

        $loc = dirname($this->location);

        mkdir($loc, 644, true);

        if (!file_exists($loc)) {
            throw new Exception('Config directory: `'.$loc.'` could not be created');
        }

        file_put_contents($this->location, "<?php\n\nreturn ".var_export_fixed($this->config));
        if (!file_exists($this->location)) {
            throw new Exception('Config could not be saved in: '.$this->location);
        }
    }

    public function create(array $config)
    {
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