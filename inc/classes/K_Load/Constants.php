<?php
/**
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

use Exception;
use K_Load\Helpers\Util;
use function basename;
use function constant;
use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function gettype;
use function implode;
use function preg_match_all;
use function str_replace;
use function strtok;
use function strtolower;
use const PREG_SET_ORDER;

class Constants
{
    protected static $defaults = [
        'debug'                        => false,
        'app_language'                 => 'en',
        'enable_log'                   => true,
        'enable_cache'                 => true,
        'clear_cache'                  => 'refresh',
        'enable_registration'          => false,
        'ignore_player_customizations' => true,
        'allow_theme_override'         => true,
        'users_per_page'               => 16,
        'date_format'                  => 'm/d/Y r',
        'demo_mode'                    => false,
    ];

    public static function init()
    {
        defineConstant('IS_FORWARDED', isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']));
        defineConstant('IS_HTTPS', is_https());
        defineConstant('APP_HOST', (IS_HTTPS ? 'https://' : 'http://').$_SERVER['HTTP_HOST']);
        defineConstant('APP_DOMAIN', strtok($_SERVER['HTTP_HOST'], ':'));
        defineConstant('APP_PORT', !IS_FORWARDED ? (int) $_SERVER['SERVER_PORT'] : 80);
        defineConstant('APP_PATH', str_replace('/'.basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']));
        defineConstant('APP_URL', APP_HOST.APP_PATH);
        defineConstant('CACHE_BUSTER', Util::hash(3));

        static::defineUserConstants();

        foreach (self::$defaults as $constant => $value) {
            $constant = defineConstant($constant, $value);

            if (($givenType = gettype(constant($constant))) !== ($type = gettype($value))) {
                throw new Exception('Expected type `'.$type.'` for '.$constant.'. `'.$givenType.'` was given instead');
            }
        }
    }

    public static function defineUserConstants()
    {
        if (file_exists(APP_ROOT.'/data/constants.php') && !file_exists(APP_ROOT.'/data/constants.old.php')) {
            $contents = file_get_contents(APP_ROOT.'/data/constants.php');
            preg_match_all("/define\('(\w+)', *('?\w+'?)\);/", $contents, $matches, PREG_SET_ORDER);

            if (!empty($matches)) {
                copy(APP_ROOT.'/data/constants.php', APP_ROOT.'/data/constants.old.php');

                $inserts = [];

                foreach ($matches as $constantData) {
                    $inserts[] = '$'.strtolower($constantData[1]).' = '.$constantData[2].';';
                }

                file_put_contents(APP_ROOT.'/data/constants.php', '<?php'."\n\n".implode("\n", $inserts));
                unset($inserts);
            }

            unset($contents);
        }

        $constants = get_vars(APP_ROOT.'/data/constants.php');

        foreach ($constants as $constant => $value) {
            defineConstant($constant, $value);
        }

        unset($constants);
    }
}
