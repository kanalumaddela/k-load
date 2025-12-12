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

namespace KLoad\Controllers\Admin;

use Exception;
use KLoad\Controllers\AdminController;
use KLoad\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Response;
use function KLoad\redirect;
use const KLoad\APP_ROUTE_URL;

class Media extends AdminController
{
    public function index(): Response
    {
        return $this->view('index');
    }

    public function upload(): RedirectResponse
    {
        $this->validateCsrf();

        $post = $this->getPost();

        $route = $post->get('route', 'media');

        $redirect = redirect(APP_ROUTE_URL . '/dashboard/admin/' . $route);

        try {
        } catch (Exception $e) {
            $redirect->withError();
        }

        return $redirect;
    }

    protected function validateFiles(FileBag $files, ?string $type = null): void
    {
    }
}
