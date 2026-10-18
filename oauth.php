<?php
define('CONF', '../oauth.ini'); // keep outh.ini out of the web root!

$settings = parse_ini_file(CONF, TRUE);

session_set_cookie_params([
	'secure' => true,
	'httponly' => true,
	'samesite' => 'Lax',
]);

session_start();

if(isset($_GET['login']) && in_array($_GET['login'], ['microsoft', 'google'], TRUE))
{
	$_SESSION['ssoProvider'] = $_GET['login'];
	$_SESSION['ssoState'] = bin2hex(random_bytes(32));

	switch($_GET['login'])
	{
		case 'google':
		{
			$params = [
				'state' => $_SESSION['ssoState'],
				'redirect_uri' => $settings['default']['redirectUri'],
				'client_id' => $settings['google']['clientId'],
				'scope' => $settings['google']['scope'],
				'response_type' => 'code',
				'access_type' => 'online'
			];

			header('Location: https://accounts.google.com/o/oauth2/auth?'.http_build_query($params));
			exit();
		}
		break;

		case 'microsoft':
		{
			$params = [
			    'state' => $_SESSION['ssoState'],
			    'redirect_uri' => $settings['default']['redirectUri'],
			    'client_id' => $settings['microsoft']['clientId'],
			    'scope' => $settings['microsoft']['scope'],
			    'response_type' => 'code',
				'prompt' => 'select_account'
			];

			header('Location: https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query($params));
			exit();
		}
		break;
	}
}
else if(isset($_GET['code'], $_GET['state']) && isset($_SESSION['ssoState'], $_SESSION['ssoProvider']) && hash_equals($_SESSION['ssoState'], $_GET['state']))
{
	$idp = $_SESSION['ssoProvider'];
	unset($_SESSION['ssoProvider']);
	unset($_SESSION['ssoState']);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $settings[$idp]['endpointToken']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, ['client_id' => $settings[$idp]['clientId'], 'client_secret' => $settings[$idp]['clientSecret'], 'redirect_uri' => $settings['default']['redirectUri'], 'code' => $_GET['code'], 'grant_type' => 'authorization_code', 'scope' => $settings[$idp]['scope']]);
	$data = json_decode(curl_exec($ch), true);
	if(!$data || !isset($data['access_token']))
	{
		exit();
	}

	$url = $settings[$idp]['endpointScope'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $settings[$idp]['endpointScope']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$data['access_token']));
	$data = json_decode(curl_exec($ch), true);

	if(!$data || !isset($data[$settings[$idp]['emailIdentifier']]))
	{
		exit();
	}

	// google specific
	if(isset($data['verified_email']) && !$data['verified_email'])
	{
		exit();
	}

	$_SESSION['ssoEmail'] = $data[$settings[$idp]['emailIdentifier']];
	header('Location: '.$settings['default']['uri']);
	exit();
}
else
{
	echo 'Login with: <a id="login-button" href="oauth.php?login=google">Google</a> or <a id="login-button" href="oauth.php?login=microsoft">Microsoft</a><br/>';
}
