<?php

/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Controllers;

use KLoad\App;
use KLoad\Exceptions\InvalidToken;
use KLoad\Facades\Lang;
use KLoad\Request;
use KLoad\Session;
use KLoad\View\View;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;
use function array_merge;
use function count;
use function hash_equals;
use function KLoad\view;
use function time;
use const KLoad\APP_CURRENT_ROUTE;
use const KLoad\APP_ROUTE_URL;

class BaseController
{
    protected mixed $user;

    protected static string $title = '';

    protected static string $templateFolder = '';

    protected static string $route = '';

    protected static array $dataHooks = [];

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Session
     */
    protected Session $session;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->boot();

        if (App::has('session')) {
            $this->session = App::get('session');
            $this->user = $this->session->user();
        }

        View::addGlobal('title', !empty(static::$title) ? static::$title . ' | K-Load' : 'K-Load');
    }

    public function boot(): void
    {
        $this->setTitle(static::$title);
    }

    public function setTitle(string $title, bool $useLang = true): void
    {
        if ($useLang) {
            $title = Lang::get($title);
        }

        static::$title = $title;

        View::addGlobal('title', !empty(static::$title) ? static::$title . ' | K-Load' : 'K-Load');
    }

    /**
     * @return string
     */
    public static function getRoute(): string
    {
        return APP_ROUTE_URL . '/' . self::$route;
    }

    public function view($template, array $data = []): Response
    {
        if (count(static::$dataHooks) > 0) {
            $data = static::addHookData($data);
        }

        return view($this->getTemplateFolder() . $template, $data);
    }

    public function getTemplateFolder(): string
    {
        return static::$templateFolder !== '' ? static::$templateFolder . '/' : '';
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getPost(): InputBag
    {
        return $this->request->request;
    }

    public function getPostFiles(): FileBag
    {
        return $this->request->files;
    }

    protected function can(string ...$perms): bool
    {
        return true;
    }

    protected function authorize(string ...$perms): void
    {
        if ($this->user['super']) {
            return;
        }
//        throw new NotAuthorized();
    }

    protected function validateCsrf(): void
    {
        if (empty($userCsrf = $this->request->get('_csrf'))) {
            throw new InvalidToken();
        }

        $csrf = $this->session['csrf'];
        $csrf = $csrf[APP_CURRENT_ROUTE];

        if (time() >= $csrf['expires'] || !hash_equals($csrf['token'], $userCsrf)) {
            throw new InvalidToken();
        }
    }

    protected static function getDataHooks(): array
    {
        return empty(static::$dataHooks) ? static::$dataHooks : [];
    }

    protected static function addHookData(array $data): array
    {
        $hookData = [];

        foreach (static::$dataHooks as $key => $dataHook) {
            $instance = new $dataHook($data);

            $hookData[$key] = $instance->getData();
        }

        return array_merge($data, $hookData);
    }
}
