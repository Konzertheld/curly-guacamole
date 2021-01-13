<?php
$google_token = null;

function post($url, $params) {
	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($params)
		)
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) {
		print "could not get " . $url;
	}
	return $result;
}

function get_json_google($url, $params) {
	$authdata = json_decode(file_get_contents('data/google_token.json'));
	$options = array(
		'http' => array(
			'header'  => 'Authorization: Bearer ' . $authdata->access_token,
			'method'  => 'GET',
		)
	);
	$context  = stream_context_create($options);
	$result = @file_get_contents($url . '?' . http_build_query($params),false, $context);
	if(strpos($http_response_header[0], "200")) {
		return json_decode($result);
	}
	elseif(strpos($http_response_header[0], "401")) {
		// TODO handle failed login, likely there was a problem with the token before
	}
	else {
		// TODO handle other errors
	}
}

//function rtm_post($url, $params) {
//    ksort($params);
//    $params["api_sig"] = md5(http_build_query($params,"",""));
//    return post($url, $params);
//}

function calculate_duration(DateTime $d1, DateTime $d2) {
	$diff = $d1->diff($d2);
	return $diff->d * 3600 * 24 + $diff->h * 3600 + $diff->i * 60 + $diff->s;
}

function write_items_to_database($conn, $items) {
	// Write events to database
	$conn->beginTransaction();
	foreach($items as $item) {
		$start = google_create_date($item->start);
		$end = google_create_date($item->end);
		// TODO handle all-day events, setting their duration to the default duration
		$duration = calculate_duration($start, $end);
		$ioi->bindValue(':description', $item->summary);
		$upd->bindValue(':description', $item->summary);
		$ioi->bindValue(':google_id', $item->id);
		$upd->bindValue(':google_id', $item->id);
		$ioi->bindValue(':duration', $duration, PDO::PARAM_INT);
		$upd->bindValue(':duration', $duration, PDO::PARAM_INT);
		$ioi->bindValue(':date', $start->format('Y-m-d'));
		$upd->bindValue(':date', $start->format('Y-m-d'));
		$ioi->execute();
		$upd->execute();
	}
	return $conn->commit();
}