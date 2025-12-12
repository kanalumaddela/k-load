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
use KLoad\Exceptions\InvalidToken;
use KLoad\Http\RedirectResponse;
use KLoad\Models\Setting;
use KLoad\Traits\UpdateSettings;
use Symfony\Component\HttpFoundation\Response;
use function array_filter;
use function get_defined_vars;
use function KLoad\redirect;
use function strtolower;

class Messages extends AdminController
{
    use UpdateSettings;

    protected static array $defaultData = [
        'enable' => false,
        'random' => false,
        'duration' => 5000,
        'fade' => 750,
        'list' => [],
    ];

    public function index(): Response
    {
        $this->authorize('messages');

        $settings = Setting::where('name', 'messages')->pluck('value', 'name');

        return $this->view('index', get_defined_vars());
    }

    /**
     * @throws InvalidToken
     */
    public function indexPost(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('messages');

        $messages = static::$defaultData;
        $post = $this->getPost()->get('messages');

        $messages['enable'] = (bool)($post['enable'] ?? true);
        $messages['random'] = (bool)($post['random'] ?? false);
        $messages['duration'] = (int)($post['duration'] ?? 5000);
        $messages['fade'] = (int)($post['fade'] ?? 750);

        if (isset($post['list'])) {
            foreach ($post['list'] as $gamemode => $messageList) {
                $messages['list'][strtolower($gamemode)] = array_filter($messageList);
            }
        }

        $redirect = redirect(static::getRoute());

        try {
            $this->updateSetting('messages', $messages);

            return $redirect;
        } catch (Exception) {
            return $redirect->withInputs();
        }
    }
}
