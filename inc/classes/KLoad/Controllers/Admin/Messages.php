<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2023 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Controllers\Admin;

use KLoad\Controllers\AdminController;
use KLoad\Http\RedirectResponse;
use KLoad\Models\Setting;
use Symfony\Component\HttpFoundation\Response;
use function KLoad\redirect;
use const KLoad\APP_ROUTE_URL;

class Messages extends AdminController
{
    protected static string $templateFolder = __CLASS__;

    protected static array $defaultData = [
        'enable' => true,
        'random' => false,
        'duration' => 5000,
        'list' => [],
    ];

    public function index(): Response
    {
        $this->authorize('messages');

        $messages = Setting::find('messages');

        dd($messages);

        return $this->view('index', get_defined_vars());
    }

    public function indexPost(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('messages');

        $messages = static::$defaultData;
        $post = $this->getPost();

        $messages['enable'] = $post->get('enable', false);

        Setting::where('name', 'messages')->update(['value' => $messages]);

        return redirect(APP_ROUTE_URL . '/dashboard/admin/messages');
    }


}