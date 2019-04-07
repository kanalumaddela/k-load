<?php

Database::run("UPDATE `kload_settings` SET `value` = '2.5.0' WHERE `name` = 'version'");

@unlink(APP_ROOT.'/assets/js/site.js');
@unlink(APP_ROOT.'/themes/metra/assets/js/metra.js');
