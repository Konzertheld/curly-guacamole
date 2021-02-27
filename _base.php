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
		return false;
	}
	else {
		// TODO handle other errors
		return false;
	}
}

//function rtm_post($url, $params) {
//    ksort($params);
//    $params["api_sig"] = md5(http_build_query($params,"",""));
//    return post($url, $params);
//}

function calculate_duration(DateTime $d1, DateTime $d2) {
	$diff = $d1->diff($d2);
	if($diff->d == 1 && $diff->h == 0 && $diff->i == 0 && $diff->s == 0) {
		// Google all-day event, return default duration
		// TODO load default duration from config
		return 90 * 60;
	}
	return $diff->d * 3600 * 24 + $diff->h * 3600 + $diff->i * 60 + $diff->s;
}

function write_items_to_database(PDO $conn, $items) {
	// Write events to database
	// TODO this is so google specific it should not be here
	$ioi = $conn->prepare('INSERT OR IGNORE INTO tasks (description, duration, date, google_id, deadline_day) VALUES (:description, :duration, :date, :google_id, :deadline_day)');
	$upd = $conn->prepare('UPDATE tasks SET description=:description, duration=:duration, date=:date, deadline_day=:deadline_day WHERE google_id=:google_id');
	$conn->beginTransaction();
	foreach($items as $item) {
		$start = google_create_date($item->start);
		$end = google_create_date($item->end);
		$duration = calculate_duration($start, $end);
		$ioi->bindValue(':description', $item->summary);
		$upd->bindValue(':description', $item->summary);
		$ioi->bindValue(':google_id', $item->id);
		$upd->bindValue(':google_id', $item->id);
		if(google_is_appointment($item)) {
			$ioi->bindValue(':deadline_day', $end->format('Y-m-d'));
			$upd->bindValue(':deadline_day', $end->format('Y-m-d'));
		}
		else {
			$ioi->bindValue(':deadline_day', null);
			$upd->bindValue(':deadline_day', null);
		}
		$ioi->bindValue(':duration', $duration, PDO::PARAM_INT);
		$upd->bindValue(':duration', $duration, PDO::PARAM_INT);
		$ioi->bindValue(':date', $start->format('Y-m-d'));
		$upd->bindValue(':date', $start->format('Y-m-d'));
		$ioi->execute();
		$upd->execute();
	}
	return $conn->commit();
}

function read_items_from_database(PDO $conn, $from = null) {
	if(empty($from)) {
		$from = date('Y-m-d');
	}
	$read = $conn->prepare('SELECT id, description, duration, date, advance_span, deadline_day, deadline_time, done FROM tasks WHERE date >= :today AND date < :date ORDER BY date ASC, done ASC');
	$read->bindValue(':today', date_create($from)->format('Y-m-d'));
	$read->bindValue(':date', date_add(date_create($from), new DateInterval('P8D'))->format('Y-m-d'));
	$read->execute();
	for($i = 0; $i < 8; $i++) {
		$days[date_add(date_create($from), new DateInterval('P' . $i . 'D'))->format('Y-m-d')] = [];
	}
	while($row = $read->fetch(PDO::FETCH_OBJ)) {
		$days[$row->date][] = $row;
	}
	return $days;
}

function get_next_day_with_free_space(PDO $conn, $space_needed, $deadline_only = false) {
	// TODO: Get additional time per task (20 min = 1200 seconds) from config
	// Get future days including today with their used time from the database
	// Why include today? There might still be time left
	if($deadline_only) {
		$stmt = $conn->prepare('SELECT date, SUM(duration) + COUNT(id) * 1200 usedtime FROM tasks WHERE date >= :date AND deadline_day IS NOT NULL GROUP BY date');
	}
	else {
		$stmt = $conn->prepare('SELECT date, SUM(duration) + COUNT(id) * 1200 usedtime FROM tasks WHERE date >= :date GROUP BY date');
	}
	$stmt->bindValue(':date', date('Y-m-d'));
	$stmt->execute();
	$days = $stmt->fetchAll(PDO::FETCH_ASSOC);
	// Unpack result into arrays for the days and their usedtime
	$days_merged = array_merge_recursive(...$days);
	// Go through next days and figure out which is the first one that is either
	// - not present in the result (empty)
	// - or not full (has less than X time used)
	// TODO: Load maximum lookahead (30 days) from config- it determines how far a task is moved max
	// Start with +0 from today as today might have enough space. It was important to include today in the query above, otherwise today would not appear in the list,
	// being interpreted as completely free every time
	for($i = 0; $i < 30; $i++) {
		$date = date_add(date_create(), new DateInterval(('P' . $i .'D')))->format('Y-m-d');
		$position = array_search($date, $days_merged['date']);
		if($position === false || $days_merged['usedtime'][$position] + $space_needed < 9 * 3600) {
			// TODO: Load maximum day load (9h) from config
			return $date;
		}
	}

	return false;
}

function move_task(PDO $conn, $id, $date) {
	if(!is_array($id)) $id = [$id];
	$upd = $conn->prepare('UPDATE tasks SET date=:date WHERE id=:id');
	$conn->beginTransaction();
	foreach($id as $task_id) {
		$upd->bindValue(':date', $date);
		$upd->bindValue(':id', $task_id);
		$upd->execute();
	}
	return $conn->commit();
}

function done_task(PDO $conn, $task_ids)
{
	if (!is_array($task_ids)) $id = [$task_ids];
	$done = $conn->prepare('UPDATE tasks SET done=((done | 1) - (done & 1)) WHERE id = :id');
	$conn->beginTransaction();
	foreach ($task_ids as $task_id) {
		$done->bindValue(':id', $task_id);
		$done->execute();
	}
	return $conn->commit();
}

function tag_task(PDO $conn, $id, $tag) {
	// TODO BUG this inserts rows again, ignore does not work
	$ioi = $conn->prepare('INSERT OR IGNORE INTO tasks_tags (task_id, tag_name) VALUES (:id, :tag)');
	$ioi->bindValue(':id', $id);
	$ioi->bindValue(':tag', $tag);
	return $ioi->execute();
}