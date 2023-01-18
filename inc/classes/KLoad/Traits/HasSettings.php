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

namespace KLoad\Traits;

use Illuminate\Database\QueryException;
use KLoad\Facades\Session;
use KLoad\Models\Setting;
use function KLoad\flash;
use function KLoad\lang;

class HasSettings
{
    public function updateSetting($name, $value, $flash = true)
    {
        try {
            Setting::where('name', $name)->update(['value' => $value]);
            if ($flash) {
                flash('success', lang());
            }
        } catch (QueryException $e) {
            // redirect(APP_CURRENT_URL)->withError($e->getMessage())->withInputs();
            Session::error($e->getMessage());
        }
    }
}