<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2020 Maddela
 * @license   MIT
 */

namespace K_Load\Http;

use K_Load\App;
use K_Load\Facades\Session;

class RedirectResponse extends \Symfony\Component\HttpFoundation\RedirectResponse
{
    public function withInputs()
    {
        Session::flash('old', App::get('request')->request->all());

        return $this;
    }
}
