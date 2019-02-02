<?php

use K_Load\Setup;
use K_Load\Test;
use K_Load\Template;
use K_Load\Util;

// prevent direct access
if (!defined('APP_ROOT')) {
	header('Location: ../');
}

if (isset($_SERVER['QUERY_STRING']) && ['QUERY_STRING'] == 'phpinfo') {
	phpinfo();
	die();
}

$errors = [];

if (file_exists(APP_ROOT.'/data/config.php')) {
	$config = include APP_ROOT.'/data/config.php';
}
if (isset($_POST['install']) && isset($_SESSION['steamid'])) {

	$valid = [
		'apikeys' => [
			'steam' => '',
			'youtube' => ''
		],
		'mysql' => [
			'host' => '',
			'port' => '',
			'user' => '',
			'pass' => '',
			'db' => ''
		]
	];

	$mysql_check = Test::mysql($_POST['mysql']);
	$steam_check = Test::steam($_POST['apikeys']['steam']);

	if ($mysql_check && $steam_check) {
		$config = array_intersect_key($_POST,$valid);
		$config['loading_themes'] = [];
		$config['admins'][0] = '{{ user_id }}';
		$config['loading_themes'] =  array_column(Template::loadingThemes(true), 'name');
		$config['dashboard_theme'] = 'default';
		array_multisort($config);
		Setup::install($config);
		Util::redirect('/dashboard/admin');
		die();
	} else {
		$errors[] = (!$mysql_check ? 'Something went wrong with your mysql setup' : '' );
		$errors[] = (!$steam_check ? 'Fix your steam api key' : '' );
		$config = $_POST;
	}

	$errors = array_filter($errors);
}


/* extension check */
$extensions = [
	'BCMath' => [
		'bcmath',
		true
	],
	'Bzip2' => [
		'bz2',
		false
	],
	'cURL' => [
		'curl',
		true
	],
	'JSON' => [
		'json',
		true
	],
	'Mcrypt' => [
		'mcrypt',
		false
	],
	'Multibyte String' => [
		'mbstring',
		true
	],
	'MySQLi' => [
		'mysqli',
		true
	],
	'XML' => [
		'xml',
		true
	],
	'Zip' => [
		'zip',
		true
	],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Setup &#9679; K-Load v2</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/2.0.46/css/materialdesignicons.min.css" />
	<link rel="stylesheet" href="<?= APP_PATH ?>/assets/css/materialize-stepper.min.css" />
	<link rel="stylesheet" href="<?= APP_PATH ?>/assets/css/install.css" />
</head>
<body>
<style type="text/css"></style>
	<div class="container">
		<h1>K-Load Setup</h1>
		<div class="card z-depth-2">
			<a href="<?= APP_PATH ?>/?phpinfo" target="_blank" style="position:absolute;right:14px;top:8px;">phpinfo</a>
			<?php if ( !isset($_SESSION['steamid']) ) { ?>
			<div style="padding: 25px 10px;text-align: center;"">
				<a href="<?= steam::loginUrl() ?>">
					<img src="https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_02.png">
				</a>
			</div>
			<?php } else { ?>
			<div class="card-content">
				<form action="<?= APP_PATH.'/install' ?>" method="post" autocomplete="off">
					<ul class="stepper linear">
						<li id="requirements" class="step active">
							<div class="step-title waves-effect waves-dark">Requirements</div>
							<div class="step-content">
								<div class="row">
									<div class="desc">
										<p>
											This is a list of extensions that are REQUIRED for K-Load to run. The majority of webhosts should have these extensions
											installed, but if not, try contacting them and getting it installed. If you are self hosted, you should know how to install
											the required extensions.
										</p>
									</div>
									<ul class="collection">
									<?php
										if (PHP_VERSION_ID < 70000) {
											echo '<li class="collection-item red-text"><div><i class="mdi mdi-close left"></i>PHP Version: '.PHP_VERSION.'<br>PHP 7 and above is required</div></li>'."\n";
										} else {
											echo '<li class="collection-item green-text"><div><i class="mdi mdi-check left"></i>PHP Version: '.PHP_VERSION.'</div></li>'."\n";
										}

										$install_error = false;
										foreach ($extensions as $extension => $info) {
											$ext_color = 'green';
											$ext_icon = 'check';
											$ext_msg = 'is loaded';

											if (!extension_loaded($info[0])) {
												$ext_color = 'orange';
												$ext_icon = 'alert-circle';
												$ext_msg = 'is not loaded!';
												if ($info[1]) {
													$install_error = true;
													$ext_color = 'red';
													$ext_icon = 'close';
												}
											}
											echo '<li class="collection-item '.$ext_color.'-text"><div><i class="mdi mdi-'.$ext_icon.' left"></i>'.$extension.' extension '.$ext_msg.'</div></li>'."\n";
										}

									?>
									</ul>
								</div>
								<div class="step-actions">
									<button type="button" class="waves-effect waves-dark btn blue next-step">Next</button>
								</div>
							</div>
						</li>
						<?php if (!$install_error) { ?>
						<li id="settings" class="step">
							<div class="step-title waves-effect waves-dark">Settings</div>
							<div class="step-content">
								<div class="row">
									<div class="col s12 desc">
										<p>
											During the initial setup, the 1st person to login and run the install becomes the root admin. This
											is saved to the <code>data/config.php</code> file to ensure you'll always have access and can't be locked out (unless, of course, your config.php)
											is altered.
										</p>
										<p>
											Here are some general settings that are required before running K-Index. You can visit steam's site to get
											your api key by clicking the "Get Key" button. This key is primarily used to retrieve a player's steam info such
											as their steam name, avatar, and profile. This is used for member, staff, and other areas in the site.
										</p>
									</div>
									<div class="input-field col s12 m6">
										<i class="mdi mdi-steam prefix"></i>
										<input required type="number" id="admin_id" name="admins[]" value="<?= $_SESSION['steamid'] ?>" readonly="readonly">
										<label for="admin_id">Admin SteamID</label>
									</div>
									<div class="input-field col s12 m8">
										<i class="mdi mdi-key prefix"></i>
										<input required type="text" id="steam_apikey" name="apikeys[steam]" value="<?= $config['apikeys']['steam'] ?? '' ?>">
										<label for="steam_apikey">Steam API Key</label>
									</div>
									<div class="input-field col s12 m4">
										<a href="https://steamcommunity.com/dev/apikey" target="_blank" class="btn">Get Key</a>
									</div>
								</div>
								<div class="step-actions">
									<button class="waves-effect waves-dark btn next-step" data-feedback="steamTest">Next</button>
									<button class="waves-effect waves-dark btn-flat previous-step">Previous</button>
								</div>
							</div>
						</li>

						<li id="mysql" class="step">
							<div class="step-title waves-effect waves-dark">MySQL</div>
							<div class="step-content">
								<div class="row">
									<div class="col s12 desc">
										<p>
											Create the database beforehand and give it a separate user account with access ONLY to that database. DO NOT use the mysql root account.
											I have confidence in my code, but it's always better to take security precautions no matter what.
										</p>
									</div>
									<div class="input-field col s12 m9">
										<i class="mdi mdi-server-network prefix"></i>
										<input required type="text" id="mysql_host" name="mysql[host]" class="validate" placeholder="localhost" value="<?= $config['mysql']['host'] ?? 'localhost' ?>">
										<label for="mysql_host">MySQL Host</label>
									</div>
									<div class="input-field col s12 m3">
										<i class="mdi mdi-ethernet prefix"></i>
										<input required type="number" id="mysql_port" name="mysql[port]" class="validate" placeholder="3306" value="<?= $config['mysql']['port'] ?? 3306 ?>">
										<label for="mysql_port">MySQL Port</label>
									</div>
									<div class="input-field col s12 m6">
										<i class="mdi mdi-account-network prefix"></i>
										<input required type="text" id="mysql_user" name="mysql[user]" class="validate" placeholder="root" value="<?= $config['mysql']['user'] ?? 'root' ?>">
										<label for="mysql_user">MySQL User</label>
									</div>
									<div class="input-field col s12 m6">
										<i class="mdi mdi-textbox-password prefix"></i>
										<input required type="password" id="mysql_pass" name="mysql[pass]" class="validate" placeholder="*********" value="<?= $config['mysql']['pass'] ?? '' ?>">
										<label for="mysql_pass">MySQL Pass</label>
									</div>
									<div class="input-field col s12 m6">
										<i class="mdi mdi-database prefix"></i>
											<input required type="text" id="mysql_db" name="mysql[db]" class="validate" placeholder="k-load" value="<?= $config['mysql']['db'] ?? 'k-load' ?>">
										<label for="mysql_db">MySQL Database</label>
									</div>
								</div>
								<div class="step-actions">
									<button type="button" class="waves-effect waves-dark btn blue next-step" data-feedback="mysqlTest">Next</button>
									<button type="button" class="waves-effect waves-dark btn-flat previous-step">Previous</button>
								</div>
							</div>
						</li>
						<li class="step">
							<div class="step-title waves-effect waves-dark">Finish</div>
							<div class="step-content">
								<div class="row">
									<div class="col s12 center-align">
										<input type="submit" name="install" class="btn" value="Install">
									</div>
								</div>
							</div>
						</li>
						<?php } ?>
					</ul>
				</form>
			</div>
			<?php } ?>
		</div>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>
	<script src="<?= APP_PATH ?>/assets/js/materialize-stepper.min.js"></script>
	<script>
		var site = {
			url: '<?= APP_PATH ?>'
		};
	</script>
	<script>
		$('.stepper').activateStepper({
			autoFocusInput: false,
			autoFormCreation: false
		});
		function steamTest() {
			var key = $('#steam_apikey').val();
			if (!key) {
				alert('Please enter an API key');
			} else {
				$.post( site.url+'/test/steam', {key} ,function( data ) {
					data = data;
					console.log(data);
					var css = data.success ? 'green' : 'red';
					toast(data.message, 5000, css);
					if (data.success) {
						$('.stepper').nextStep();
					}
				});
			}
			$('.stepper').destroyFeedback();
		}
		function mysqlTest() {
			var mysql = {
				host: $("input[name='mysql[host]']").val(),
				port: parseInt($("input[name='mysql[port]']").val()),
				user: $("input[name='mysql[user]']").val(),
				pass: $("input[name='mysql[pass]']").val(),
				db: $("input[name='mysql[db]']").val()
			};

			if (!mysql.host || !mysql.port || !mysql.user || !mysql.pass || !mysql.db) {
				alert('Please fill in all the fields');
			} else {
				$.post( site.url+'/test/mysql', {mysql} ,function( data ) {
					console.log(data);
					var css = data.success ? 'green' : 'red';
					toast(data.message, 5000, css);
					if (data.success) {
						$('.stepper').nextStep();
					}
				});
			}
			$('.stepper').destroyFeedback();
		}
		function toast(message = '', time = 5000, css = '') {
			if (typeof current_message == 'undefined') { current_message = '' }
			if (current_message == message) { return; } else { current_message = message; }
			if (Materialize) {
				if (time == -1 || time == 0) {
					Materialize.toast(message, Infinity, css, function(){current_message = '';});
				} else {
					Materialize.toast(message, time, css, function(){current_message = '';});
				}
			}
		}
		var alerts = ["<?= implode('","', $errors) ?>"];
		if (alerts.length > 0) {
			alerts.forEach(function(item) {
				console.log(item);
				toast(item, 5000, 'red');
			});
		}
	</script>
</body>
