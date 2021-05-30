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

namespace KLoad\Cache;

use J0sh0nat0r\SimpleCache\Drivers\ArrayDriver;
use J0sh0nat0r\SimpleCache\Drivers\File;
use J0sh0nat0r\SimpleCache\Exceptions\InvalidKeyException;
use J0sh0nat0r\SimpleCache\IDriver;
use const KLoad\APP_ROOT;
use const KLoad\ENABLE_CACHE;

class KDriver implements IDriver
{
    protected array $drivers = [];

    public function __construct()
    {
        $this->drivers['array'] = new \J0sh0nat0r\SimpleCache\Cache(ArrayDriver::class);
        $this->drivers['file'] = new \J0sh0nat0r\SimpleCache\Cache(File::class, [
            'dir' => APP_ROOT . '/data/cache',
        ]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $time
     *
     * @return bool
     * @throws InvalidKeyException
     *
     */
    public function put($key, $value, $time)
    {
        if (ENABLE_CACHE) {
            return $this->getDriver('file')->store($key, $value, $time);
        }

        return $this->getDriver('array')->store($key, $value, $time);
    }

    /**
     * @param string $driver
     *
     * @return \J0sh0nat0r\SimpleCache\Cache
     */
    public function getDriver(string $driver)
    {
        return $this->drivers[$driver];
    }

    /**
     * @param string $key
     *
     * @throws InvalidKeyException
     *
     * @return bool|bool[]
     */
    public function has($key)
    {
        return ENABLE_CACHE ? $this->getDriver('file')->has($key) : $this->getDriver('array')->has($key);
    }

    /**
     * @param string $key
     *
     * @throws InvalidKeyException
     *
     * @return array|callable|mixed|string|null
     */
    public function get($key)
    {
        return ENABLE_CACHE ? $this->getDriver('array')->get($key, $this->getDriver('file')->get($key)) : $this->getDriver('array')->get($key);
    }

    /**
     * @param string $key
     *
     * @throws InvalidKeyException
     *
     * @return bool|void
     */
    public function remove($key)
    {
        $this->getDriver('array')->remove($key);
        $this->getDriver('file')->remove($key);
    }

    /**
     * @return mixed|void
     */
    public function clear()
    {
        $this->getDriver('array')->clear();
        $this->getDriver('file')->clear();
    }
}
