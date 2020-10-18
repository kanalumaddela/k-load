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

namespace K_Load;

use function is_array;
use function json_encode;

class Toast
{
    protected $type = 'info';

    protected $content = '';

    protected $time = 3000;

    public function __construct($content)
    {
        if (is_array($content)) {
            $this->content = $content['content'];
            $this->type = $content['type'] ?? 'info';
        }
    }

    public function __toString()
    {
        return json_encode([
            'type'      => $this->type,
            'content'   => $this->content,
            'time'      => $this->time,
            'allowHtml' => false,
        ]);
    }

    public function type(string $type)
    {
        $this->type = $type;

        return $this;
    }
}
