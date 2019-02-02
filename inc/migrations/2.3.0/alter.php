<?php

if (file_exists(APP_ROOT.'/themes/default/preview.png')) {
    unlink(APP_ROOT.'/themes/default/preview.png');
}
if (file_exists(APP_ROOT.'/themes/magnus/chrome_2018-02-20_20-53-17.jpg')) {
    unlink(APP_ROOT.'/themes/magnus/chrome_2018-02-20_20-53-17.jpg');
}
if (file_exists(APP_ROOT.'/themes/magnus/preview.jpg')) {
    unlink(APP_ROOT.'/themes/magnus/preview.jpg');
}
if (file_exists(APP_ROOT.'/themes/metra/chrome_2018-04-01_17-32-10.png')) {
    unlink(APP_ROOT.'/themes/metra/chrome_2018-04-01_17-32-10.png');
}
if (file_exists(APP_ROOT.'/themes/oxygen/preview.png')) {
    unlink(APP_ROOT.'/themes/oxygen/preview.png');
}
if (file_exists(APP_ROOT.'/themes/neuron/preview.png')) {
    unlink(APP_ROOT.'/themes/neuron/preview.png');
}
if (file_exists(APP_ROOT.'/themes/slick/chrome_2018-04-02_12-52-50.png')) {
    unlink(APP_ROOT.'/themes/slick/chrome_2018-04-02_12-52-50.png');
}

Database::run("UPDATE `kload_settings` SET `value` = '2.3.0' WHERE `name` = 'version'");
