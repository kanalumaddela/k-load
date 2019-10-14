<!doctype html>
<html lang="en">
<head>
    <title><?= $errorData['code'] ?? 500 ?> Error | K-Load</title>
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
        font-family: 'Segoe UI', sans-serif;
        color: #fff;
        background-color: #393e52;
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

    #container {
        position: absolute;
        top: 50%;
        left: 50%;
        -webkit-transform: translate(-50%, -50%);
        width: 95%;
        max-width: 1056px;
    }

    #logo-block {
        text-align: center;
    }

    #logo-block > * {
        display: inline-block;
        vertical-align: middle;
    }

    #logo-block > h1 {
        margin: -10px 0 0 0;
        font-size: 76px;
        font-weight: 400;
    }

    .vertical-separator {
        width: 3px;
        background-color: #fff;
        height: 95px;
        margin: 0 15px;
    }

    .card {
        padding: 25px 60px;
        background-color: #464f69;
        border-radius: 2px;
        box-shadow: 0 2px 6px -3px rgba(0, 0, 0, .65);
    }

    .btn {
        display: inline-block;
        padding: 20px 12px;
        background-color: #1c85da;
        border-radius: 2px;
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
        border-bottom: #303647 1px solid;
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
        <img style="width: 90%;max-width: 300px" src="<?= APP_URL.'/assets/img/logo-text.png' ?>">
        <span class="vertical-separator"></span>
        <h1>ERROR</h1>
    </div>

    <div style="margin-top: 40px" class="card">
        <div style="text-align: center;">
            <a class="btn" target="_blank" href="https://www.gmodstore.com/help/tickets/create/addon/5000">Create a
                Ticket | <img style="max-height: 20px;"
                              src="https://media.gmodstore.com/_/assets/img/gmodstore.svg"></a>
        </div>
        <br>
        <table>
            <tbody>
            <tr>
                <td><h4>Code:</h4></td>
                <td>
                    <code><?= $errorData['sentry_id'] ?? 'no code given' ?></code>
                    &#60;--- Include this when creating a ticket
                </td>
            </tr>
            <tr>
                <td><h4>Message:</h4></td>
                <td><?= $errorData['exception']->getMessage() ?></td>
            </tr>
            <?php if (DEBUG) {
                ?>
                <tr>
                    <td><h4>Trace:</h4></td>
                    <td style=" max-width: 0; overflow: auto; ">
                        <pre style="max-height: 300px;overflow-y:auto"><?php var_dump($errorData['exception']->getTrace()) ?></pre>
                    </td>
                </tr>
                <?php
            } else {
                ?>
                <tr>
                    <td><h4>Notice:</h4></td>
                    <td>If you'd like to attempt to resolve the issue yourself, add/change <code>DEBUG</code> to
                        <code>true</code> in
                        your <code>data/constants.php</code> to view the code trace
                    </td>
                </tr>
                <?php
            } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
