<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link https://www.maddela.org
 * @link https://github.com/kanalumaddela/k-load-v2
 *
 * @author kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license MIT
 */
use SteamID\SteamID;

class Steam
{
    public static $host;
    public static $current;

    private static $apikey;

    public static function Init()
    {
        self::$host = APP_HOST;
        self::$current = APP_URL_CURRENT;
    }

    public static function Key(string $key)
    {
        self::$apikey = $key;
    }

    public static function login()
    {
        self::Redirect(self::LoginUrl());
    }

    public static function Redirect($url = null)
    {
        if (!$url) {
            $url = self::$host;
        }
        header('Location: '.$url, true, 302);
        die();
    }

    public static function LoginUrl()
    {
        global $steamLogin;

        return $steamLogin->getLoginURL();
    }

    public static function Logout()
    {
        session_destroy();
        if (isset($_SERVER['HTTP_REFERER'])) {
            if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) !== false) {
                self::Redirect($_SERVER['HTTP_REFERER']);
            }
        }

        self::Redirect();
    }

    public static function Session($steamid)
    {
        $_SESSION = self::Convert($steamid);
        $_SESSION['logged_in'] = 1;
        self::Redirect($_GET['openid_return_to'] ?? null);
    }

    public static function Convert($steamid)
    {
        try {
            $steamids = new SteamID($steamid);
            $data['steamid'] = $steamids->getSteamID64();
            $data['steamid2'] = $steamids->getSteam2RenderedID();
            $data['steamid3'] = $steamids->getSteam3RenderedID();

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    public static function User($steamid, $format = null)
    {
        $steamid = explode(',', $steamid);
        $steamid = $steamid[0];
        $user = self::Info($steamid, $format);

        return $user['response']['players'][0] ?? null;
    }

    public static function Info($steamids, $format = 'json')
    {
        $url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.self::$apikey.'&steamids='.$steamids.'&format='.$format;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, $url);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, true);

        return $result;
    }

    public static function Users($steamids, $format = null)
    {
        if (is_array($steamids)) {
            $steamids = implode(',', $steamids);
        }
        if (is_string($steamids)) {
            $steamids = preg_replace('/\s+/', '', $steamids);
        }

        $users = self::Info($steamids, $format);

        return $users['response']['players'] ?? null;
    }

    public static function Group($name)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, 'https://steamcommunity.com/groups/'.$name.'/memberslistxml/?xml=1');
        $data = simplexml_load_string(curl_exec($curl), 'SimpleXMLElement', LIBXML_NOCDATA);
        curl_close($curl);

        return $data ?? null;
    }
}
