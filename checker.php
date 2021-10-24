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
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] === 'phpinfo') {
    phpinfo();
    exit();
}

$extensions = [
    [
        'name' => 'BCMath',
        'desc' => 'Used for converting steamids',
    ],
    [
        'name' => 'cURL',
        'desc' => 'Used to send HTTP requests',
    ],
    [
        'name'     => 'Fileinfo',
        'required' => false,
        'desc'     => 'Used to retrieve information about a given file',
    ],
    [
        'name' => 'GMP',
        'desc' => 'Another method for converting steamids',
    ],
    [
        'name' => 'JSON',
        'desc' => 'Used for api requests and decoding steam api responses',
    ],
    [
        'name' => 'Multibyte String',
        'ext'  => 'mbstring',
        'desc' => 'Required for general handling of text',
    ],
    [
        'name' => 'PDO',
        'desc' => 'Required to connect to MySQL database',
        'sub'  => ['pdo_mysql'],
    ],
    [
        'name' => 'XML',
        'desc' => 'Used for decoding steam profile information',
        'sub'  => ['libxml', 'simplexml'],
    ],
    [
        'name'     => 'Zip',
        'required' => false,
        'desc'     => 'Used for extracting/creating zip files',
    ],
];

function is_https()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

function checkFailed()
{
    global $data;

    if ($data['passes']) {
        $data['passes'] = false;
    }
}

$data = [
    'extensions' => [],
    'passes'     => true,
];

$data['app'] = [
    'url'  => (is_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].parse_url(str_replace(basename(__FILE__), '', $_SERVER['REQUEST_URI']), PHP_URL_PATH),
    'path' => parse_url(str_replace(basename(__FILE__), '', $_SERVER['REQUEST_URI']), PHP_URL_PATH),
];

foreach ($extensions as $extension) {
    $ext = $extension['ext'] ?? strtolower($extension['name']);
    $loaded = extension_loaded($ext);
    if (!$loaded) {
        checkFailed();
    }

    $data['extensions'][] = [
        'name'     => $extension['name'],
        'ext'      => $ext,
        'loaded'   => $loaded,
        'required' => !isset($extension['required']) || $extension['required'] === true,
        'multiple' => isset($extension['sub']) ? $ext.', '.implode(', ', $extension['sub']) : false,
        'desc'     => $extension['desc'] ?? false,
        'icon'     => $loaded ? 'enabled' : (isset($extension['required']) && $extension['required'] === false ? 'optional' : 'disabled'),
    ];
}

// write check
$tmp_file = __DIR__.'/kload-tmp-write-check.txt';
if (file_exists($tmp_file)) {
    exit('K-Load | Cannot perform write check. File already exists');
}
touch($tmp_file);
$data['writeable'] = file_exists($tmp_file);
if (!$data['writeable']) {
    checkFailed();
}
if (file_exists($tmp_file)) {
    unlink($tmp_file);
}
unset($tmp_file);

// calculate upload limits
function convertIniStringToBytes($value)
{
    $conversions = [
        'K' => 1024,
        'M' => 1048576,
        'G' => 1073741824,
    ];

    preg_match('/(\d+)([KMG])/', $value, $matches);

    if (!isset($matches[1], $matches[2])) {
        return 0;
    }

    return $matches[1] * $conversions[$matches[2]];
}

$data['upload'] = [
    'post_max_size'       => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_file_uploads'    => ini_get('max_file_uploads'),
];

$data['upload']['true_max_uploads'] = floor(convertIniStringToBytes($data['upload']['post_max_size']) / convertIniStringToBytes($data['upload']['upload_max_filesize']));

$data['web_server'] = [
    'env' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
];
$data['web_server']['determined'] = null;

if (stripos($data['web_server']['env'], 'nginx') !== false) {
    $data['web_server']['determined'] = 'nginx';
}
if (stripos($data['web_server']['env'], 'apache') !== false) {
    $data['web_server']['determined'] = 'apache';
}

$data['web_server']['setup'] = [
    'apache' => [],
    'nginx'  => [],
];

$data['web_server']['setup']['nginx']['guide'] = 'This goes inside your nginx <code>server { ... }</code> block';
$data['web_server']['setup']['nginx']['config'] = <<<nginx
    location {$data['app']['path']} {
        try_files \$uri \$uri/ {$data['app']['path']}index.php\$is_args\$args;
    }
nginx;

$data['web_server']['setup']['apache']['guide'] = 'Create an <code>.htaccess</code> file with the following contents in the folder where K-Load will go';
$data['web_server']['setup']['apache']['config'] = <<<apache
# hello hi, no index 4 u
Options -Indexes

# how 2 make routes work
&#x3C;IfModule mod_rewrite.c>
    Options -MultiViews
    RewriteEngine On
    RewriteBase {$data['app']['path']}

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . {$data['app']['path']}index.php [L]
&#x3C;/IfModule>
apache;

//$data['extensions'] = [];

define('LOGO', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABkCAMAAAAL3/3yAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAADNQTFRFAAAA////////////////////////////////////////////////////////////////t5XiggAAABB0Uk5TABAgMEBQYHCAj5+vv8/f7yMagooAAAggSURBVHja7Vxb16QoDPxQRES8/P9fuw+zrahUUqHnzM45a167uRiSVCVEf35eeeWVV1555ZVXXnnllVde+bPSDTGmnKc4evd37jDkf2VC/5jyKZ0y26TOBveRtr2QZeyMmv6srP5zyjnnFC/ie2qR/NneDP6QikcI2mzr5582ZXXTRVO/JJnUdWxTfezKUvu+7zNxPMfQWP99LObTNXD8dTQ8p4v17W+GSbpjlNdW26Ekzw6t/zGUc6lb9vSWy0Er3P3szIaFDr2yR/OC59CqEfbFRJkIPMefeV2N0uYXZzUs1fyjtN6+DdRWq7rabPs+NrLaYw3QlnmW/N2CUlyepDXcUqjcWdAi/yZdkUjhNl69WVsx6kOToisKW1c2cEBdrfkOVkz0u7gWi0F229oE+CqfhNKV4zlGJV5tafgVN/uxDPmrzbBA8K1EtxTTh5vdtNXbEddEsJrA0F94QunnYdt33kqjwRTPNcv1+ok4n/rQB2kwG8qPNdLcMbtbeNO6GpZC8Q7FbvfNq+cDhxoJ1g0tOAybpQcsNBBMhqVY4gwxqDifOpolNLQkDayu9NQJOWEQf55NhqUg8YJhtgC0KD7e7dduM1MdInUC0An+nkmnjiZqJh3Q+dSbYWhJGhY65TjBcGDKDJopeHK6j9IXRre9iAOjpEo01EpG70/XWwwLrsCxtkPpYUVYVcggm+squD4Ymqxk9O4PJsMam7KLx/Oth9/69oRMSm7rQycrGdXQQoxIq24FG2VYB9CF9oRMKsLMNatrIFh66oQDQCDINmFYmzuOPn6RkGVs70tlqG/V1Y+Bc5/WuzITet2w4uknMzPloLhpYoY2ESxjsZJlGas+YWFYxzHnLzAoQDftn0Nds64KtOh+03+zyh1CSTG97rVqddJDi38OvRAs4/2MBQy5xCir5rdcdL6r3CFq63r4EM+huYWMqlmX4GDjV8ryVyfQvVZPyKCyHvCVmsionnXh8NZ/pax8deZFxSU9VEJl3XE0tpHR+zL6DVbgyi+asvzNlrOmCT0hc1BZN/gKjWSUyboAfZ2/qlLn24qThkxexRUYs25D+2aCdQM4x3vs2FwhqBjW4Rm5vToJlRUu6URJsBp0JdQRm9gm8bd8327QdqBnm1BZl6HtZJTLujh8tjj2kxKpREtPyGA0zQV8ufVLXVmuwcj4rkSYh2Gd2vXNYBjRkRfXYN+QUS7AUDuy0O2u4vYK1hEJGYKeohzRVhkFZuB5j504nS7iY8Xn8WtUA2MQYivF0OKeZW9surP0hCzGi5hZNCz3fNTUjEEbMM4DR9dkvS5vrbnYwFDUaapZZ5bdW69OwuIf6MmY25RlAMOeLE+IFzHVOaJsObr7Q5xG7SRdk7IM12AkcxDDcapCd5Qn1hOygBwVNgu26MpyDRY53JV0Wjesc0jfmJAhnO5g81tLiLdcg5EXG1I4BlN4URuDbtEzcFTcWznKaFcFA0tPSOY8VshNUBx24sxEXyLClEdv5SbOVW3F7I2lzwcZbmRjkNWKEZyoTrJgGJIYdqpwYClB6vBM50/nRUEAY3IbvYNFyeXeG9iLzQe1VvtcORILGLpGNob9KUvOoSdkEFOefZSLEKSdjJuWazCS6+M0GhvW6S4iGPZWnO6fPadBYA9e7uv1BpZGptGDfs+y4bn7pkNCOD1U+nM37CBRBkPLNZiVOWRoWBGXQn0TvcsEGIY7pD23Uc2NnFUBlprDjMw8CoTQC0BLYBCCuLniTZ2ln9pYgjTTLHi1uO2ExJbqJAwmay1Aza1VeEtPCKcsSDACo6uahesJGUTZajAfGsullmswooNBjO8rpaylJSFbgH+AoWtbWctyDfa47bMlO5xhVYBGT8jgCyIAR2Nb7cFyDUYqC10trqSyOvs12IiOHACzaytrWXpCOGWhxIM1rOfkOgYtCC7R0GQJ1c9lJouyJBAZQaxlDevJHdTqpN+1jBPfYljKWqZXoyk0BOqnDesxuZ6+Z/TkeOjSwB5sb4MlQlkojzvOOPu6jCggqAmZh+EaDw0NxNT2ajSTG6b6Jgb1JB2afNQwKEOVYBwtmkpp9mB7NZpob++AXxPN8+jBtIRsxNdbAo4m+6WY7dVo4iYs1cOHJ0LEAuBfyUiLvpjegKOdnT0YX41Wy8oehOlM0DlETGQMcqtAMDcmO6eJqRETZsWZzs4LZFiRYP4BmIAXV6yQABFHg5U9WF+NHpX/J9WwHBE/I1/LLt5hrViPXAZfjV+UMb4arVWCxv0LwzqPeqYxyG9i84Kcy0Uje5ispYpZ+sRF8fmCgcT2qkozCYaurGuuzlrZ7XbTK6nGV6N/xLejy63PAEQTZ7bUNdj1Y1TVhnYFR+fdxAU2czpZXEKmy1mGFZ5ysvbe6Bjk40J8/UIJSt7EHlzxbRQsAd0VbfGzxvVLIbdT7lj6t9XQ4zTL5djRIyevf+FIveVbLezBN5RMptsHaCrfOAkIIn1DIXYgdgheKlELm6OlazI2FOPcov0/oPPNLHpE2x6XrjGXc5YvwSRGVxtOLxhd8YZVfxd1Vjc4uebCZjKwh8woK/9YtLV5GzWrekU21AwXryebUU929RSG8sIn7Pb4AbLDhxfoELrRe1zDd7ncQseIjlJWZSU301u33P7WOmFEDJK/Icnc8gW617sFDD9DM3nK0ZKAVdaE1ehlGhQE644Pv0px8vw6rKKsyAg6t8tHvvZlqsP3eMzDfKXxI52yx6Gx5f8/lO7zJH/tF5VfeeWVV1555ZVXXnnllVde+T/LPxWcyYqAhiycAAAAAElFTkSuQmCC');

?>

<!doctype html>
<html lang="">

<head>
    <meta charset="utf-8">
    <title>K-Load Requirements Checker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.1/build/pure-min.css"
          integrity="sha384-oAOxQR6DkCoMliIh8yFnu25d7Eq/PHS21PClpwjOTeU2jRSq11vu66rf90/cZr47" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.1/build/grids-responsive-min.css"/>
</head>

<style>
    body {
        padding-top: 25px;
        font-family: 'Segoe UI', sans-serif;
        color: #fff;
        background-color: #0e1938;
    }

    a, a:visited {
        color: #72b8ff;
    }

    a:hover {
        color: #ff86da;
    }

    p {
        margin: 0;
    }

    h1, h2, h3, h4, h5, h6 {
        /*margin: 0;*/
        font-weight: 300;
        text-transform: uppercase;
    }

    code, pre {
        font-family: monospace;
        padding: 2px 5px;
        color: #ff88a9;
        background-color: #1b2b58;
    }

    table {
        table-layout: fixed;
        width: 100%;
    }

    table td:nth-child(1) {
        width: 30%;
        font-weight: bold;
    }

    .card.upload-limits table td:nth-child(1) {
        width: 45%;
    }

    .card.upload-limits table td:nth-child(2) {
        text-align: right;
    }

    table td:nth-child(2) {
        word-break: break-all;
    }

    #container {
        margin: auto;
        width: 95%;
        max-width: 1400px;
    }

    #logo-block {
        position: relative;
        text-align: center;
    }

    #logo-block > * {
        display: inline-block;
        vertical-align: middle;
    }

    .logo-img[alt] {
        font-size: 100px;
        font-family: 'Roboto Condensed', sans-serif;
        font-style: italic;
    }

    .vertical-separator {
        display: inline-block;
        margin: 0 15px;
        height: 100px;
        width: 2.5px;
        background-color: #fff;
    }

    .pure-g [class*=pure-u] > div {
        padding: 0 10px;
    }

    .pure-table, .pure-table td {
        border-color: rgba(255, 255, 255, 0.1);
    }

    .card {
        margin-bottom: 25px;
        padding: 1px;
        background: #243975;
    }

    .card-title {
        margin: 0;
        padding: .67em 0;
        text-align: center;
        background: #3757b5;
    }

    .collection {
        margin: 0;
        padding-inline-start: 0;
        list-style: none;
    }

    .collection .header {
        display: table;
    }

    .collection .header > * {
        display: table-cell;
        vertical-align: middle;
    }

    .collection .header .extension-name {
        padding-left: 1rem;
        width: 100%;
        text-transform: unset;
        border-bottom: 1px solid transparent;
        transition: .2s border-bottom-color ease-out;
    }

    .collection .header .extension-name > small {
        color: #ababab;
    }

    .collection .header div[class*="extension--"] {
        padding: 0.5em 1rem;
        max-width: 26px;
        font-size: 2rem;
    }

    .extension--enabled, .pass {
        background-color: #5cbf43 !important;
    }

    .extension--disabled, .fail {
        background-color: #d64f45 !important;
    }

    .extension--optional {
        background-color: orange;
    }

    .extension--enabled:before {
        content: "\2714";
    }

    .extension--disabled:before {
        content: "\2716";
    }

    .extension--optional:before {
        content: "\26A0";
    }

    .collection .body {
        max-height: 0;
        transition: .3s max-height ease-out;
        overflow: hidden;
    }

    .collection .body div {
        padding: 15px 25px;
    }

    .collection li {
        transition: .25s background-color ease-out, .25s border-bottom-color ease-out;
    }

    .collection li:hover {
        background-color: rgba(0, 0, 0, 0.35);
        border-bottom-color: transparent;
    }

    /*.header .extension-name*/
    .collection li:not(:last-child) {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .collection li:hover .header .extension-name {
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }

    .collection li:hover .body {
        max-height: 100px;
    }
</style>


<body>
<div id="container">
    <div id="logo-block">
        <img class="logo-img" src="<?= LOGO ?>" alt="K-LOAD">
        <div class="vertical-separator"></div>
        <div>
            <h2>Requirements<br>Checker</h2>
        </div>
    </div>

    <br>
    <br>

    <div class="pure-g">
        <div class="pure-u-1">
            <div>
                <div class="<?= $data['passes'] ? 'pass' : 'fail' ?>"
                     style="margin-bottom: 20px;padding: 1px;text-align: center;">
                    <h1><?= $data['passes'] ? 'You can run K-Load' : 'You cannot run K-Load, please review the issues below' ?></h1>
                </div>
            </div>
        </div>
        <div class="pure-u-1 pure-u-lg-1-3">
            <div>
                <div class="card">
                    <h1 class="card-title"><span style="position:relative;top:-6px;">&#x1F9F0;</span> Extensions</h1>
                    <div>
                        <ul class="collection">

                            <?php foreach ($data['extensions'] as $extension) { ?>
                                <li>
                                    <div class="header">
                                        <div class="extension--<?= $extension['icon'] ?>"></div>
                                        <h3 class="extension-name">
                                            <?= $extension['name'] ?>
                                            <?php if (!$loaded) { ?>
                                                <small>* <?= $extension['required'] ? 'Required' : 'Optional' ?></small>
                                            <?php } ?>
                                            <br>
                                            <small><code><?= $extension['multiple'] ? $extension['multiple'] : $extension['ext'] ?></code></small>
                                        </h3>
                                    </div>
                                    <?php if ($extension['desc']) { ?>
                                        <div class="body">
                                            <div>
                                                <?= $extension['desc'] ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="pure-u-1 pure-u-lg-1-3">
            <div>
                <div class="card">
                    <h1 class="card-title"><span>&#x1F5A5;</span> Server Info</h1>
                    <div>
                        <table class="pure-table pure-table-bordered">
                            <tbody>
                                <tr>
                                    <td>Web Server</td>
                                    <td><?= $data['web_server']['env'] ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        PHP Version
                                        <br>
                                        (7.2.5+)
                                    </td>
                                    <td><?= phpversion() ?> <a href="./<?= basename(__FILE__) ?>?phpinfo"
                                                               target="_blank">phpinfo()</a></td>
                                </tr>
                                <tr>
                                    <td>URL</td>
                                    <td><a href="<?= $data['app']['url'] ?>"><?= $data['app']['url'] ?></a></td>
                                </tr>
                                <tr>
                                    <td>Path</td>
                                    <td><?= $data['app']['path'] ?></td>
                                </tr>
                                <tr>
                                    <td>Directory</td>
                                    <td><code><?= __DIR__ ?></code></td>
                                </tr>
                                <tr>
                                    <td>Writeable?</td>
                                    <td class="<?= $data['writeable'] ? 'pass' : 'fail' ?>"><?= $data['writeable'] ? 'Yes' : 'No (Please look into fixing this)' ?></td>
                                </tr>
                                <?php if (!empty($data['web_server']['determined'])) { ?>
                                    <tr>
                                        <td colspan="2" style="font-weight: normal">
                                            <div style="text-align: center">
                                                <h3>Web Server Setup</h3>
                                                <p>
                                                    <?= $data['web_server']['setup'][$data['web_server']['determined']]['guide'] ?>
                                                </p>
                                            </div>
                                            <pre><?= $data['web_server']['setup'][$data['web_server']['determined']]['config'] ?></pre>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="pure-u-1 pure-u-lg-1-3">
            <div>
                <div class="card upload-limits">
                    <h1 class="card-title"><span>&#x1F4C1;</span> Upload Limits</h1>
                    <div>
                        <table class="pure-table pure-table-bordered">
                            <tbody>
                                <tr>
                                    <td>Post Upload Size</td>
                                    <td><?= $data['upload']['post_max_size'] ?></td>
                                </tr>
                                <tr>
                                    <td>Max File Size</td>
                                    <td><?= $data['upload']['upload_max_filesize'] ?></td>
                                </tr>
                                <tr>
                                    <td>Max # of Uploads</td>
                                    <td><?= $data['upload']['max_file_uploads'] ?></td>
                                </tr>
                                <tr>
                                    <td>Max # of Uploads @ <?= ini_get('upload_max_filesize') ?> each</td>
                                    <td><?= $data['upload']['true_max_uploads'] ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>

</html>

