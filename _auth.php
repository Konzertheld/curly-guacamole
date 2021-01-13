<?php
function google_auth_url() {
	$json = json_decode(file_get_contents('data/client_secret.json'));
	$url = 'https://accounts.google.com/o/oauth2/v2/auth?';
	$params['redirect_uri'] = 'http://' . $_SERVER['HTTP_HOST'] . '/planer/'; // has to match with the below function
	$params['client_id'] = $json->web->client_id;
	$params['response_type'] = 'code';
	$params['access_type'] = 'offline'; // we might need refresh tokens later
	$params['scope'] = implode(' ', [
		'https://www.googleapis.com/auth/calendar.events.readonly',
		'https://www.googleapis.com/auth/calendar.readonly'
	]);
	$params['include_granted_scopes'] = 'true';
	$url .= http_build_query($params);
	return $url;
}

function google_exchange() {
	$auth_json = json_decode(file_get_contents('data/client_secret.json'));
	$url = 'https://oauth2.googleapis.com/token';
	$params['client_id'] = $auth_json->web->client_id;
	$params['client_secret'] = $auth_json->web->client_secret;
	$params['code'] = $_GET['code'];
	$params['grant_type'] = 'authorization_code';
	$params['redirect_uri'] = 'http://' . $_SERVER['HTTP_HOST'] . '/planer/'; // has to match with the above function
	print_r($params);
	$result_original = post($url, $params);
	$result = json_decode($result_original);
	$result->expires_at = time() + $result->expires_in;
	$google_token = $result->access_token;
	if(!$google_token) die("no token, fuck");
	file_put_contents('data/google_token.json', json_encode($result));
}

function google_check_and_refresh() {
	if(file_exists('data/google_token.json')) {
		$credentials = json_decode(file_get_contents('data/client_secret.json'));
		$token = json_decode(file_get_contents('data/google_token.json'));
		if (time() >= $token->expires_at) {
			if(isset($token->refresh_token)) {
				$url = 'https://oauth2.googleapis.com/token';
				$params['client_id'] = $credentials->web->client_id;
				$params['client_secret'] = $credentials->web->client_secret;
				$params['refresh_token'] = $token->refresh_token;
				$params['grant_type'] = 'refresh_token';
				$result_original = post($url, $params);
				$result = json_decode($result_original);
				$token->access_token = $result->access_token;
				$token->expires_at = time() + $result->expires_in;
				file_put_contents('data/google_token.json', json_encode($token));
				return;
			}
			// else jump to header() below
		}
		else {
			// token found and not expired
			return;
		}
	}
	header('Location: ' . google_auth_url());
}