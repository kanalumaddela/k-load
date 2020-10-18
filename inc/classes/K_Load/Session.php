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
use RuntimeException;
use function dd;
use function dump;
use function is_array;
use function is_string;
use function json_encode;
use function session_destroy;
use function session_name;
use function session_regenerate_id;
use function session_start;
use function session_status;
use const PHP_SESSION_ACTIVE;

class Session extends DotArray
{
    protected static $prefix = 'kload';

    public function __construct($items = [], array $options = [])
    {
        if ($this->isActive()) {
            throw new RuntimeException('Session already started: '.json_encode($_SESSION));
        } else {
            session_name('K-Load');
            session_set_cookie_params(60 * 60 * 24 * 14, APP_PATH, APP_DOMAIN, IS_HTTPS, true);
            session_start();


            if (!isset($_SESSION['kload'])) {
                $_SESSION['kload'] = [];
            }

            $items = &$_SESSION['kload'];
        }

        parent::__construct($items);

        $this->items = &$items;
    }

    public function isActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function dump()
    {
        dump($_SESSION);
    }

    public function dd()
    {
        dd($_SESSION);
    }

    public function user()
    {
        return $this->get('user', []);
    }

    public function destroy()
    {
        session_destroy();
    }

    public function regenerate()
    {
        session_regenerate_id();
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \Exception
     */
    public function flash(string $key, $value = null)
    {
        $this->checkForSession();

        $toasts = [
            'success' => '',
            'error'   => '',
            'warning' => '',
            'info'    => '',
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
     * @throws \Exception
     */
    public function checkForSession()
    {
        if (!$this->isActive()) {
            throw new Exception('No active session found');
        }
    }

    public function error($message)
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
}