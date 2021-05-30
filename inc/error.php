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

use Sentry\SentrySdk;
use function Sentry\captureException;
use function Sentry\captureLastError;

function kload_error_page($errorData = [])
{
    $debug = defined('KLoad\DEBUG') && constant('\KLoad\DEBUG');

    if (!defined('KLoad\DEV') || defined('KLoad\DEV') && !constant('\KLoad\DEV')) {
        switch ($errorData['type']) {
            case 'exception':
                captureException($errorData['raw']);
                break;
            case 'error':
                captureLastError();
                break;
        }

        $code = SentrySdk::getCurrentHub()->getLastEventId();

        if (!empty($code)) {
            $errorData['sentry_id'] = $code;
        }
    }

    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <title><?= ucfirst($errorData['type']) ?> | K-Load</title>
        <meta charset="utf-8"/>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>
    <style>
        html, *, *:before, *:after {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            color: #fff;
            background-color: #500505;
        }

        body, h1, h2, h3, h4, h5, h6 {
            margin: 0;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        code {
            padding: 2px 3px;
            color: #fa2da1;
            background-color: #363636
        }

        .code {
            margin: 0;
            background-color: rgba(0, 0, 0, 0.15);
        }

        #container {
            position: absolute;
            top: 45%;
            left: 50%;
            -webkit-transform: translate(-50%, -50%);
            width: 95%;
            max-width: 1056px;
        }

        #logo-block {
            text-align: center;
        }

        .card {
            padding: 2rem 3rem;
            background-color: rgba(255, 255, 255, 0.08);
            border-radius: .25rem;
            box-shadow: 0 2px 6px -3px rgba(0, 0, 0, .65);
        }

        .btn {
            display: inline-block;
            padding: 20px 12px;
            background-color: #1c85da;
            border-radius: .25rem;
            box-shadow: 0 2px 3px -2px rgba(0, 0, 0, 0.35);
            transition: box-shadow .2s ease-out;
        }

        .btn:hover {
            box-shadow: 0 2px 5px -2px rgba(0, 0, 0, 0.75);
        }

        .btn > * {
            vertical-align: middle;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        tr:not(:last-child) {
            border-bottom: rgba(0, 0, 0, 0.2) 1px solid;
        }

        td {
            padding: 12px 2px;
        }

        td:nth-child(2) {
            padding-left: 15px;
        }
    </style>
    <body>
    <div id="container">
        <div id="logo-block">
            <img style="width: 30vw;max-width: 300px;min-width: 150px"
                 src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABkCAMAAAAL3/3yAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAADNQTFRFAAAA////////////////////////////////////////////////////////////////t5XiggAAABB0Uk5TABAgMEBQYHCAj5+vv8/f7yMagooAAAggSURBVHja7Vxb16QoDPxQRES8/P9fuw+zrahUUqHnzM45a167uRiSVCVEf35eeeWVV1555ZVXXnnllVde+bPSDTGmnKc4evd37jDkf2VC/5jyKZ0y26TOBveRtr2QZeyMmv6srP5zyjnnFC/ie2qR/NneDP6QikcI2mzr5582ZXXTRVO/JJnUdWxTfezKUvu+7zNxPMfQWP99LObTNXD8dTQ8p4v17W+GSbpjlNdW26Ekzw6t/zGUc6lb9vSWy0Er3P3szIaFDr2yR/OC59CqEfbFRJkIPMefeV2N0uYXZzUs1fyjtN6+DdRWq7rabPs+NrLaYw3QlnmW/N2CUlyepDXcUqjcWdAi/yZdkUjhNl69WVsx6kOToisKW1c2cEBdrfkOVkz0u7gWi0F229oE+CqfhNKV4zlGJV5tafgVN/uxDPmrzbBA8K1EtxTTh5vdtNXbEddEsJrA0F94QunnYdt33kqjwRTPNcv1+ok4n/rQB2kwG8qPNdLcMbtbeNO6GpZC8Q7FbvfNq+cDhxoJ1g0tOAybpQcsNBBMhqVY4gwxqDifOpolNLQkDayu9NQJOWEQf55NhqUg8YJhtgC0KD7e7dduM1MdInUC0An+nkmnjiZqJh3Q+dSbYWhJGhY65TjBcGDKDJopeHK6j9IXRre9iAOjpEo01EpG70/XWwwLrsCxtkPpYUVYVcggm+squD4Ymqxk9O4PJsMam7KLx/Oth9/69oRMSm7rQycrGdXQQoxIq24FG2VYB9CF9oRMKsLMNatrIFh66oQDQCDINmFYmzuOPn6RkGVs70tlqG/V1Y+Bc5/WuzITet2w4uknMzPloLhpYoY2ESxjsZJlGas+YWFYxzHnLzAoQDftn0Nds64KtOh+03+zyh1CSTG97rVqddJDi38OvRAs4/2MBQy5xCir5rdcdL6r3CFq63r4EM+huYWMqlmX4GDjV8ryVyfQvVZPyKCyHvCVmsionnXh8NZ/pax8deZFxSU9VEJl3XE0tpHR+zL6DVbgyi+asvzNlrOmCT0hc1BZN/gKjWSUyboAfZ2/qlLn24qThkxexRUYs25D+2aCdQM4x3vs2FwhqBjW4Rm5vToJlRUu6URJsBp0JdQRm9gm8bd8327QdqBnm1BZl6HtZJTLujh8tjj2kxKpREtPyGA0zQV8ufVLXVmuwcj4rkSYh2Gd2vXNYBjRkRfXYN+QUS7AUDuy0O2u4vYK1hEJGYKeohzRVhkFZuB5j504nS7iY8Xn8WtUA2MQYivF0OKeZW9surP0hCzGi5hZNCz3fNTUjEEbMM4DR9dkvS5vrbnYwFDUaapZZ5bdW69OwuIf6MmY25RlAMOeLE+IFzHVOaJsObr7Q5xG7SRdk7IM12AkcxDDcapCd5Qn1hOygBwVNgu26MpyDRY53JV0Wjesc0jfmJAhnO5g81tLiLdcg5EXG1I4BlN4URuDbtEzcFTcWznKaFcFA0tPSOY8VshNUBx24sxEXyLClEdv5SbOVW3F7I2lzwcZbmRjkNWKEZyoTrJgGJIYdqpwYClB6vBM50/nRUEAY3IbvYNFyeXeG9iLzQe1VvtcORILGLpGNob9KUvOoSdkEFOefZSLEKSdjJuWazCS6+M0GhvW6S4iGPZWnO6fPadBYA9e7uv1BpZGptGDfs+y4bn7pkNCOD1U+nM37CBRBkPLNZiVOWRoWBGXQn0TvcsEGIY7pD23Uc2NnFUBlprDjMw8CoTQC0BLYBCCuLniTZ2ln9pYgjTTLHi1uO2ExJbqJAwmay1Aza1VeEtPCKcsSDACo6uahesJGUTZajAfGsullmswooNBjO8rpaylJSFbgH+AoWtbWctyDfa47bMlO5xhVYBGT8jgCyIAR2Nb7cFyDUYqC10trqSyOvs12IiOHACzaytrWXpCOGWhxIM1rOfkOgYtCC7R0GQJ1c9lJouyJBAZQaxlDevJHdTqpN+1jBPfYljKWqZXoyk0BOqnDesxuZ6+Z/TkeOjSwB5sb4MlQlkojzvOOPu6jCggqAmZh+EaDw0NxNT2ajSTG6b6Jgb1JB2afNQwKEOVYBwtmkpp9mB7NZpob++AXxPN8+jBtIRsxNdbAo4m+6WY7dVo4iYs1cOHJ0LEAuBfyUiLvpjegKOdnT0YX41Wy8oehOlM0DlETGQMcqtAMDcmO6eJqRETZsWZzs4LZFiRYP4BmIAXV6yQABFHg5U9WF+NHpX/J9WwHBE/I1/LLt5hrViPXAZfjV+UMb4arVWCxv0LwzqPeqYxyG9i84Kcy0Uje5ispYpZ+sRF8fmCgcT2qkozCYaurGuuzlrZ7XbTK6nGV6N/xLejy63PAEQTZ7bUNdj1Y1TVhnYFR+fdxAU2czpZXEKmy1mGFZ5ysvbe6Bjk40J8/UIJSt7EHlzxbRQsAd0VbfGzxvVLIbdT7lj6t9XQ4zTL5djRIyevf+FIveVbLezBN5RMptsHaCrfOAkIIn1DIXYgdgheKlELm6OlazI2FOPcov0/oPPNLHpE2x6XrjGXc5YvwSRGVxtOLxhd8YZVfxd1Vjc4uebCZjKwh8woK/9YtLV5GzWrekU21AwXryebUU929RSG8sIn7Pb4AbLDhxfoELrRe1zDd7ncQseIjlJWZSU301u33P7WOmFEDJK/Icnc8gW617sFDD9DM3nK0ZKAVdaE1ehlGhQE644Pv0px8vw6rKKsyAg6t8tHvvZlqsP3eMzDfKXxI52yx6Gx5f8/lO7zJH/tF5VfeeWVV1555ZVXXnnllVde+T/LPxWcyYqAhiycAAAAAElFTkSuQmCC">
        </div>

        <div style="margin-top: 5%" class="card">
            <div style="text-align: center;">
                <a class="btn" target="_blank" href="https://www.gmodstore.com/help/tickets/create/addon/5000">Create a
                    Ticket - <img style="max-height: 20px;"
                                  src="https://media.gmodstore.com/_/assets/img/gmodstore.svg"></a>
            </div>
            <br>
            <div style="width: 100%;overflow: auto">
                <table>
                    <tbody>
                    <?php if (isset($errorData['sentry_id'])) { ?>
                        <tr>
                            <td><h4>Ticket Code:</h4></td>
                            <td style="display: flex;align-items: center">
                                <code style="font-size: 1.65rem"><?= $errorData['sentry_id'] ?></code>
                                &#60;--- Include this when creating a ticket
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><h4>Code:</h4></td>
                        <td>
                            <code><?= $errorData['code'] ?></code>
                        </td>
                    </tr>
                    <tr>
                        <td><h4>Message:</h4></td>
                        <td><?= $errorData['message'] ?? 'No Message' ?></td>
                    </tr>
                    <?php if ($debug) { ?>
                        <tr>
                            <td><h4>File:</h4></td>
                            <td>Line <?= $errorData['line_no'] ?> in <code class="code"><?= $errorData['file'] ?></code>
                            </td>
                        </tr>
                        <?php if ($errorData['type'] === 'exception') { ?>
                            <tr>
                                <td><h4>Trace:</h4></td>
                                <td style=" max-width: 0; overflow: auto; ">
                                    <pre class="code"
                                         style="max-height: 300px;overflow-y:auto"><?php print_r($errorData['raw']->getTrace()); ?></pre>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td><h4>Notice:</h4></td>
                            <td>If you'd like to attempt to resolve the issue yourself, add/change <code>$debug =
                                    true;</code> to
                                your <code>data/constants.php</code> to view the stack trace
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </body>
    </html>

    <?php
}

?>