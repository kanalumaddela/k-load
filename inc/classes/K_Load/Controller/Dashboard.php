<?php

namespace K_Load\Controller;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Template;
use K_Load\User;
use K_Load\Util;

class Dashboard
{
    public static function index()
    {
        Template::render('@dashboard/index.twig');
    }

    public static function settings()
    {
        if (isset($_POST['save']) && isset($_SESSION['steamid'])) {
            $updated = User::update($_SESSION['steamid'], $_POST);
            $alert = (User::update($_SESSION['steamid'], $_POST) ? 'Background settings have been saved' : 'Failed to save, please try again and check the data/logs if necessary');

            if ($updated) {
                Cache::remove('loading-screen-'.$_SESSION['steamid']);
            }

            Util::flash('alert', $alert);
            User::session($_SESSION['steamid']);
            Util::redirect('/dashboard/settings');
        }

        Template::render('@dashboard/settings.twig', (isset($alert) ? ['alert' => $alert] : []));
    }
}
