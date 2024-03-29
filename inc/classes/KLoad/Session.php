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
use JetBrains\PhpStorm\NoReturn;
use KLoad\Helpers\Util;
use RuntimeException;
use function dd;
use function dump;
use function ini_set;
use function is_array;
use function is_string;
use function session_destroy;
use function session_name;
use function session_regenerate_id;
use function session_set_cookie_params;
use function session_start;
use function session_status;
use function time;
use const PHP_SESSION_ACTIVE;

class Session extends DotArray
{
    protected static string $prefix = 'kload';

    public function __construct()
    {
        if ($this->isActive()) {
            throw new RuntimeException('Session already started');
        }

        session_name('K-Load');
        ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 14);
        session_set_cookie_params(60 * 60 * 24 * 14, APP_PATH, APP_DOMAIN, IS_HTTPS, true);
        session_start();

        if (!isset($_SESSION['kload'])) {
            $_SESSION['kload'] = [];
        }

        $items = &$_SESSION['kload'];

        parent::__construct($items);

        $this->items = &$items;
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function dump(): void
    {
        dump($_SESSION);
    }

    #[NoReturn]
 public function dd(): void
 {
     dd($_SESSION);
 }

    public function user()
    {
        return $this->get('user', []);
    }

    public function destroy(): void
    {
        session_destroy();
    }

    public function regenerate(): void
    {
        session_regenerate_id();
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws Exception
     */
    public function flash(string $key, $value = null): void
    {
        $this->checkForSession();

        $toasts = [
            'success' => '',
            'error'   => '',
            'warning' => '',
            'info'    => '',
            'danger'  => '',
        ];

        if (isset($toasts[$key])) {
            $messages = $this->get('flash.messages.'.$key, []);
            $messages[] = $value;
            $this->set('flash.messages.'.$key, $messages);
        } else {
            $this->set('flash.'.$key, $value);
        }
    }

    /**
     * @throws Exception
     */
    public function checkForSession(): void
    {
        if (!$this->isActive()) {
            throw new RuntimeException('No active session found');
        }
    }

    public function error($message): void
    {
        if (is_array($message)) {
            foreach ($message as $msg) {
                $this->error($msg);
            }
        }

        if (is_string($message)) {
            $errors = $this->get('flash.errors', []);
            $errors[] = $message;

            $this->set('flash.errors', $errors);
        }
    }

    public function flushFlash()
    {
        $flash = $this->get('flash', []);

        $this->set('flash', []);

        return $flash;
    }

    public function generateCsrf($route = null)
    {
        if (empty($route)) {
            $route = APP_CURRENT_ROUTE;
        }

        $csrf = $this->get('csrf', []);

        if (!isset($csrf[$route]) || $csrf[$route]['expires'] <= time()) {
            $token = Util::hash(32);

            $csrf[$route] = [
                'token'     => $token,
                'expires'   => time() + 3600,
                'last_used' => null,
                'uses'      => 0,
            ];

            $this->set('csrf', $csrf);
        } else {
            $token = $csrf[$route]['token'];
        }

        return $token;
    }
}
