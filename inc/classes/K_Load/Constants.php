<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace K_Load;

use Exception;
use function constant;
use function define;
use function defined;
use function gettype;
use function strtoupper;

class Constants
{
    protected static $defaults = [
        'debug'                        => false,
        'app_language'                 => 'en',
        'enable_log'                   => true,
        'enable_cache'                 => true,
        'clear_cache'                  => 'refresh',
        'enable_registration'          => true,
        'ignore_player_customizations' => false,
        'allow_theme_override'         => false,
        'users_per_page'               => 16,
        'date_format'                  => 'm/d/Y r',
        'demo_mode'                    => false,
    ];

    public static function init()
    {
        foreach (self::$defaults as $constant => $value) {
            $constant = strtoupper($constant);

            if (defined($constant) && gettype(constant($constant)) !== gettype($value)) {
                $type = gettype($value);
                $givenType = gettype(constant($constant));

                throw new Exception('Expected type `'.$type.'` for '.$constant.'. `'.$givenType.'` was given instead');
            }

            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }
}
