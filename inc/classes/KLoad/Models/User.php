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

use KLoad\Facades\Config;
use function in_array;

class User extends BaseModel
{
    public $timestamps = false;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'steamid',
        'steamid2',
        'steamid3',
        'admin',
        'perms',
        'settings',
        'banned',
    ];

    protected $dates = [
        'registered',
    ];

    protected $casts = [
        'admin'    => 'boolean',
        'banned'   => 'boolean',
        'settings' => 'array',
        'perms'    => 'array',
    ];

    public static function isSuper($steamid): bool
    {
        return in_array($steamid, Config::get('admins', []));
    }

    /**
     * @param $steamid
     *
     * @return User
     */
    public static function findBySteamid($steamid): User
    {
        return self::where('steamid', $steamid)->first();
    }

    public function can($perm)
    {
        return in_array($perm, $this->perms, true);
    }
}
