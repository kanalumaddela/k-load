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

namespace K_Load\Models;

use K_Load\Facades\Config;
use K_Load\Facades\Session;
use function in_array;
use function strtolower;

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

    public static function isSuper($steamid)
    {
        return in_array($steamid, Config::get('admins', []));
    }

    /**
     * @param $steamid
     *
     * @return \K_Load\Models\User
     */
    public static function findBySteamid($steamid)
    {
        return self::where('steamid', $steamid)->first();
    }

    public static function getCsrf()
    {
        return '';
    }

    public function scopeCsrf($query)
    {
        $query->leftJoin('sessions', 'users.steamid', '=', 'sessions.steamid');
    }

    public function getSteamid2Attribute($value)
    {
        return strtolower($value);
    }

    public function can($perm)
    {
        return in_array($perm, $this->perms);
    }

    public function getSteamInfoAttribute()
    {
        return Session::get('steamlogin', []);
    }
}
