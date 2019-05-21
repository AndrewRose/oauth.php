<?php
define('CONF', '../oauth.ini'); // keep outh.ini out of the web root!

$settings = parse_ini_file(CONF, TRUE);
if(isset($_GET['code']) && isset($_GET['state']) && in_array($_GET['state'], ['microsoft', 'google']))
{
	$idp = $_GET['state'];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $settings[$idp]['endpointToken']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
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
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
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

	session_start();
	$_SESSION['ssoEmail'] = $data[$settings[$idp]['emailIdentifier']];
	header('Location: '.$settings['default']['uri']);
	session_write_close();
}
else
{

echo 'Login with:
<a id="login-button" href="https://accounts.google.com/o/oauth2/auth?state=google&redirect_uri='.$settings['default']['redirectUri'].'&client_id='.$settings['google']['clientId'].'&scope='.$settings['google']['scope'].'&response_type=code&access_type=online">Google</a> or
<a id="login-button" href="https://login.microsoftonline.com/common/oauth2/v2.0/authorize?state=microsoft&client_id='.$settings['microsoft']['clientId'].'&response_type=code&scope='.$settings['microsoft']['scope'].'&redirect_uri='.$settings['default']['redirectUri'].'">Microsoft</a><br/>';

}
