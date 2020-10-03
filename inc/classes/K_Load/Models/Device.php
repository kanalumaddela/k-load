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

class Device extends BaseModel
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'sessions';

    protected $primaryKey = 'device_id';

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'steamid',
        'login_key',
        'token',
        'expires',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'steamid', 'steamid');
    }
}