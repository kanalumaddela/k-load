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

namespace KLoad\Models;

use JsonException;
use function json_decode;

class Setting extends BaseModel
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'settings';

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'value',
    ];

    public function getValueAttribute($value)
    {
        $original = $value;

        try {
            $val = json_decode($original, true, 512, JSON_THROW_ON_ERROR);

            if (empty($val)) {
                $val = $original;
            }

        } catch (JsonException) {
            $val = $original;
        }

        return $val;
    }
}
