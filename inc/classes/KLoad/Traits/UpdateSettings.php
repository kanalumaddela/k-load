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
use KLoad\Facades\Lang;
use KLoad\Facades\Session;
use KLoad\Models\Setting;

use function KLoad\flash;

trait UpdateSettings
{
    /**
     * @param string $name
     * @param mixed  $value
     * @param bool   $flash
     *
     * @return void
     */
    public function updateSetting(string $name, mixed $value, bool $flash = true): void
    {
        try {
            Setting::where('name', $name)->update(['value' => $value]);

            if ($flash) {
                flash('success', Lang::get($name.'_updated'));
            }
        } catch (QueryException $e) {
            if ($flash) {
                Session::error($e->getMessage());
            }

            throw $e;
        }
    }
}
