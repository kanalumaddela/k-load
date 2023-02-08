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

use Exception;
use KLoad\Controllers\AdminController;
use KLoad\Exceptions\InvalidToken;
use KLoad\Http\RedirectResponse;
use KLoad\Models\Setting;
use KLoad\Traits\UpdateSettings;
use Symfony\Component\HttpFoundation\Response;
use function array_filter;
use function array_keys;
use function get_defined_vars;
use function KLoad\redirect;
use function strtolower;

class Rules extends AdminController
{
    use UpdateSettings;

    protected static array $defaultData = [
        'enable'         => false,
        'numbering_type' => 1, // 1|a|A|i|I
        'list'           => [],
    ];

    private static array $numbering_types = [
        '0' => '',
        '1' => '',
        'a' => '',
        'A' => '',
        'i' => '',
        'I' => '',
    ];

    public function index(): Response
    {
        $this->authorize('rules');

        $settings = Setting::where('name', 'rules')->pluck('value', 'name');
        $numbering_types = array_keys(static::$numbering_types);

        return $this->view('index', get_defined_vars());
    }

    /**
     * @throws InvalidToken
     */
    public function indexPost(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('rules');

        $rules = static::$defaultData;
        $post = $this->getPost()->get('rules');

        $rules['enable'] = (bool) ($post['enable'] ?? true);
        $rules['numbering_type'] = $post['numbering_type'] ?? 1;

        if (!isset(static::$numbering_types[$rules['numbering_type']])) {
            $rules['numbering_type'] = 1;
        }

        if (isset($post['list'])) {
            foreach ($post['list'] as $gamemode => $ruleList) {
                $rules['list'][strtolower($gamemode)] = array_filter($ruleList);
            }
        }

        $redirect = redirect(static::getRoute());

        try {
            $this->updateSetting('rules', $rules);

            return $redirect;
        } catch (Exception) {
            return $redirect->withInputs();
        }
    }
}
