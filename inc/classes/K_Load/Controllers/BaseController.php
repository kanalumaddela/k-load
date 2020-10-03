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

namespace K_Load\Controllers;

use K_Load\App;
use K_Load\Exceptions\InvalidToken;
use Symfony\Component\HttpFoundation\Request;
use function array_merge;
use function count;
use function hash_equals;
use function K_Load\view;
use function time;
use const K_Load\APP_CURRENT_ROUTE;

class BaseController
{
    protected $user;

    protected static $templateFolder = '';

    protected static $dataHooks = [];

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \K_Load\Session
     */
    protected $session;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->boot();

        if (App::has('session')) {
            $this->session = App::get('session');
            $this->user = $this->session->user();
        }
    }

    public function boot()
    {
    }

    public function view($template, array $data = [])
    {
        if (count(static::$dataHooks) > 0) {
            $data = static::addHookData($data);
        }

        return view((static::$templateFolder !== '' ? static::$templateFolder.'/' : '').$template, $data);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function can($perm)
    {
        return true;
    }

    protected function validateCsrf()
    {
        if (empty($userCsrf = $this->request->get('_csrf'))) {
            throw new InvalidToken;
        }

        $csrf = $this->session['csrf'];
        $csrf = $csrf[APP_CURRENT_ROUTE];

        if (time() >= $csrf['expires'] || !hash_equals($csrf['token'], $userCsrf)) {
            throw new InvalidToken;
        }
    }

    protected static function addHookData(array $data)
    {
        $hookData = [];

        foreach (static::$dataHooks as $key => $dataHook) {
            $instance = new $dataHook($data);

            $hookData[$key] = $instance->getData();
        }

        return array_merge($data, $hookData);
    }
}
