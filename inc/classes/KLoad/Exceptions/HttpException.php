<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Exceptions;

use Exception;
use Throwable;

class HttpException extends Exception
{
    protected static $statusCodeTemplates = [
        404 => [
            'Not Found',
            '%s not found',
        ],
        403 => [
            'You are not authorized to access this page.',
        ],
    ];

    protected $statusCode;

    public function __construct(int $statusCode = 500, string $message = '', Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        $this->statusCode = $statusCode;

        if (isset(static::$statusCodeTemplates[$statusCode]) && empty($message)) {
            $message = static::$statusCodeTemplates[$statusCode][0];
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
