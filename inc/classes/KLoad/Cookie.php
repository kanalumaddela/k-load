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

use function is_null;
use function setcookie;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function time;

class Cookie
{
    protected $data = [];

    protected $expires = 0;

    protected $path = '';

    protected $domain;

    protected $secure = false;

    protected $prefix = '';

    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        $this->data = &$_COOKIE;
    }

    public function setOptions(array $options)
    {
        $this->prefix = $options['prefix'] ?? '';
        $this->domain = $options['domain'] ?? $_SERVER['HTTP_HOST'];
        $this->path = $options['path'] ?? $_SERVER['REQUEST_URI'];
        $this->secure = $options['secure'] ?? !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->expires = $options['defaultExpire'] ?? 0;
    }

    public function set($key, $value = null, ?int $expires = 0, bool $httpOnly = false)
    {
        if (is_null($expires)) {
            $expires = $this->expires;
        }

        $key = $this->parseKey($key);

        $this->data[$key] = $value;

        if ($expires > 0) {
            $expires = time() + $expires;
        }

        setcookie($key, $value, $expires, $this->path, $this->domain, $this->secure, $httpOnly);
    }

    public function parseKey(string $key)
    {
        if (strpos($key, '.') !== false) {
            $key = str_replace('.', '_', $key);
        }

        return $this->prefix.$key;
    }

    public function get($key, $default = null)
    {
        return $this->data[$this->parseKey($key)] ?? $default;
    }

    public function clear()
    {
        foreach ($this->data as $key => $value) {
            if (substr($key, 0, strlen($this->prefix)) === $this->prefix) {
                $this->delete($key);
            }
        }
    }

    public function delete($key)
    {
        $key = $this->parseKey($key);

        unset($this->data[$key]);

        setcookie($key, null, time() - 3600, $this->path, $this->domain, $this->secure);
    }

    public function has($key)
    {
        return isset($this->data[$this->parseKey($key)]);
    }
}
