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

class Cookie
{
    protected array $data = [];

    protected int $expires = 0;

    protected string $path = '';

    protected string $domain;

    protected bool $secure = false;

    protected string $prefix = '';

    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        $this->data = &$_COOKIE;
    }

    public function setOptions(array $options): void
    {
        $this->prefix = $options['prefix'] ?? '';
        $this->domain = $options['domain'] ?? $_SERVER['HTTP_HOST'];
        $this->path = $options['path'] ?? $_SERVER['REQUEST_URI'];
        $this->secure = $options['secure'] ?? !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->expires = $options['defaultExpire'] ?? 0;
    }

    public function set($key, $value = null, ?int $expires = 0, bool $httpOnly = false): void
    {
        if (\is_null($expires)) {
            $expires = $this->expires;
        }

        $key = $this->parseKey($key);

        $this->data[$key] = $value;

        if ($expires > 0) {
            $expires = \time() + $expires;
        }

        \setcookie($key, $value, $expires, $this->path, $this->domain, $this->secure, $httpOnly);
    }

    public function parseKey(string $key): string
    {
        if (\strpos($key, '.') !== false) {
            $key = \str_replace('.', '_', $key);
        }

        return $this->prefix.$key;
    }

    public function get($key, $default = null)
    {
        return $this->data[$this->parseKey($key)] ?? $default;
    }

    public function clear(): void
    {
        foreach ($this->data as $key => $value) {
            if (\substr($key, 0, \strlen($this->prefix)) === $this->prefix) {
                $this->delete($key);
            }
        }
    }

    public function delete($key): void
    {
        $key = $this->parseKey($key);

        unset($this->data[$key]);

        \setcookie($key, null, \time() - 3600, $this->path, $this->domain, $this->secure);
    }

    public function has($key): bool
    {
        return isset($this->data[$this->parseKey($key)]);
    }
}
