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

namespace KLoad\Controllers;

use function fclose;
use function fopen;
use function fwrite;
use function get_defined_constants;
use function str_starts_with;
use function var_export;
use const KLoad\APP_ROOT;

class Test extends BaseController
{
    public function constants(): void
    {
        $file = fopen(APP_ROOT . '/constants.php', 'wb');
        fwrite($file, "<?php\n");

        $constants = get_defined_constants(true);

        foreach ($constants['user'] as $constant => $val) {
            if (str_starts_with($constant, 'KLoad')) {
                $val = var_export($val, true);
                fwrite($file, "define('{$constant}', {$val});\n");
            }
        }

        fclose($file);
    }
}
