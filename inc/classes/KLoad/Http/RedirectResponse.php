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

namespace KLoad\Http;

use KLoad\App;
use KLoad\Facades\Session;

class RedirectResponse extends \Symfony\Component\HttpFoundation\RedirectResponse
{
    public function withInputs()
    {
        Session::flash('old', App::get('request')->request->all());

        return $this;
    }

    public function withError(string $message)
    {
        Session::error($message);

        return $this;
    }
}
