<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
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
//        'required' => false,
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

if (PHP_VERSION_ID < 80415) {
    checkFailed();
}

$data['app'] = [
    'url'  => (is_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].parse_url(str_replace(basename(__FILE__), '', $_SERVER['REQUEST_URI']), PHP_URL_PATH),
    'path' => parse_url(str_replace(basename(__FILE__), '', $_SERVER['REQUEST_URI']), PHP_URL_PATH),
];

foreach ($extensions as $extension) {
    $ext = $extension['ext'] ?? strtolower($extension['name']);
    $loaded = extension_loaded($ext);
    if (!$loaded && $extension['required']) {
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
        background-color: #1E2529;
    }

    body:before {
        z-index: -1;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        content: '';
        background-attachment: fixed;
        background-repeat: repeat;
        background-size: 32rem;
        pointer-events: none;
    }

    body:before {
        background-image: url("data:image/png;base64,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");
        opacity: .5;
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
        margin: 0;
        font-weight: 300;
        text-transform: uppercase;
    }

    code, pre {
        font-family: monospace;
        padding: 2px 5px;
        color: #ff88a9;
        background-color: rgba(0, 0, 0, .15);
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
        background: #303b41;
    }

    .card-title {
        margin: 0;
        padding: .67em 0;
        text-align: center;
        background: #277fd2;
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
        /*border-bottom: 1px solid transparent;*/
        transition: .2s border-bottom-color ease-out;
    }

    .collection .header .extension-name > small {
        color: #ababab;
    }

    .collection .header div[class*="extension--"] {
        padding: 0.4rem 0.5em;
        max-width: 26px;
        font-size: 2rem;
    }

    .extension--enabled, .pass {
        background-color: #4bb14f !important;
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

    .collection li {
        transition: .25s background-color ease-out, .25s border-bottom-color ease-out;
    }

    .collection li:hover {
        background-color: rgba(0, 0, 0, 0.35);
        border-bottom-color: transparent;
    }

    .collection li:not(:last-child) {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .extension-desc {
        font-size: .8rem;
        opacity: .8;
        padding-left: 1rem;
        margin-top: .25rem;
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
                     style="margin-bottom: 1rem;padding: .75rem 0;text-align: center;">
                    <h1><?= $data['passes'] ? 'You can run K-Load' : 'You cannot run K-Load, please review the issues below' ?></h1>
                </div>
            </div>
        </div>
        <div class="pure-u-1 pure-u-lg-1-3">
            <div>
                <div class="card">
                    <h1 class="card-title"><span style="position:relative;top:-6px;">🧰</span> Extensions</h1>
                    <div>
                        <ul class="collection">

                            <?php foreach ($data['extensions'] as $extension) { ?>
                                <li>
                                    <div class="header">
                                        <div class="extension--<?= $extension['icon'] ?>"></div>
                                        <div>
                                            <h3 class="extension-name">
                                                <?= $extension['name'] ?>
                                                <?php if (!$loaded) { ?>
                                                    <small>* <?= $extension['required'] ? 'Required' : 'Optional' ?></small>
                                                <?php } ?>
                                                <small><code><?= $extension['multiple'] ? $extension['multiple'] : $extension['ext'] ?></code></small>
                                            </h3>
                                            <?php if ($extension['desc']) { ?>
                                                <div class="extension-desc">
                                                    <?= $extension['desc'] ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
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
                    <h1 class="card-title"><span>🖥️</span> Server Info</h1>
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
                                    (8.0+)
                                </td>
                                <td class="<?= PHP_VERSION_ID < 80000 ? 'fail' : '' ?>"><?= phpversion() ?> <a
                                            href="./<?= basename(__FILE__) ?>?phpinfo"
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
                    <h1 class="card-title"><span>📁</span> Upload Limits</h1>
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

