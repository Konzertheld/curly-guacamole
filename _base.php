<?php
$google_token = null;

function post($url, $params)
{
	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header' => "Content-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($params)
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) {
		print "could not get " . $url;
	}
	return $result;
}

function get_json_google($url, $params)
{
	$authdata = json_decode(file_get_contents('data/google_token.json'));
	$options = array(
		'http' => array(
			'header' => 'Authorization: Bearer ' . $authdata->access_token,
			'method' => 'GET',
		)
	);
	$context = stream_context_create($options);
	$result = @file_get_contents($url . '?' . http_build_query($params), false, $context);
	if (strpos($http_response_header[0], "200")) {
		return json_decode($result);
	} elseif (strpos($http_response_header[0], "401")) {
		// TODO handle failed login, likely there was a problem with the token before
		return false;
	} else {
		// TODO handle other errors
		return false;
	}
}

//function rtm_post($url, $params) {
//    ksort($params);
//    $params["api_sig"] = md5(http_build_query($params,"",""));
//    return post($url, $params);
//}

function calculate_duration(DateTime $d1, DateTime $d2)
{
	$diff = $d1->diff($d2);
	if ($diff->d == 1 && $diff->h == 0 && $diff->i == 0 && $diff->s == 0) {
		// Google all-day event, return default duration
		// TODO load default duration from config
		return 90 * 60;
	}
	return $diff->d * 3600 * 24 + $diff->h * 3600 + $diff->i * 60 + $diff->s;
}

function read_items_from_database(PDO $conn, $from = null)
{
	$read = $conn->prepare('SELECT id, description, duration, date, advance_span, deadline_day, deadline_time, done FROM tasks WHERE date >= :today AND date < :date ORDER BY date ASC, done ASC');
	$read->bindValue(':today', date_create($from)->format('Y-m-d'));
	$read->bindValue(':date', date_add(date_create($from), new DateInterval('P8D'))->format('Y-m-d'));
	$read->execute();
	for ($i = 0; $i < 8; $i++) {
		$days[date_add(date_create($from), new DateInterval('P' . $i . 'D'))->format('Y-m-d')] = [];
	}
	while ($row = $read->fetch(PDO::FETCH_OBJ)) {
		$days[$row->date][] = $row;
	}
	return $days;
}

function get_next_day_with_free_space(PDO $conn, $space_needed, $regarding_deadline_only = false)
{
	// TODO: Get additional time per task (20 min = 1200 seconds) from config
	// Get future days including today with their used time from the database
	// Why include today? There might still be time left
	// TODO merge statements using ?:
	if ($regarding_deadline_only) {
		$stmt = $conn->prepare('SELECT date, SUM(duration) + COUNT(id) * 1200 usedtime FROM tasks WHERE date >= :date AND deadline_day IS NOT NULL GROUP BY date');
	} else {
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
	for ($i = 0; $i < 30; $i++) {
		$date = date_add(date_create(), new DateInterval(('P' . $i . 'D')))->format('Y-m-d');
		$position = array_search($date, $days_merged['date']);
		// TODO load maximum usedtime per day from config
		if ($position === false || $days_merged['usedtime'][$position] + $space_needed < 9 * 3600) {
			// TODO: Load maximum day load (9h) from config
			return $date;
		}
	}

	return false;
}

function make_space(PDO $conn, $day, $space_needed) {
	// check if space_needed is larger than maximum time usage per day
	// TODO load that from config
	if($space_needed > (9 * 3600)) {
		return false;
	}

	// check how much space is already free
	$stmt = $conn->prepare('SELECT SUM(duration) + COUNT(id) * 1200 usedtime FROM tasks WHERE date = :date GROUP BY date');
	$stmt->bindValue(':date', $day);
	$stmt->execute();
	$result_all = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$freetime = 9 * 3600 - $result_all[0]["usedtime"];
	// return if it is enough
	if($freetime >= $space_needed) {
		return true;
	}
	// check how much space can be freed - tasks with deadline are not allowed to be moved
	$stmt = $conn->prepare('SELECT SUM(duration) + COUNT(id) * 1200 usedtime FROM tasks WHERE date = :date AND deadline_day IS NOT NULL GROUP BY date');
	$stmt->bindValue(':date', $day);
	$stmt->execute();
	$result_deadline = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$uncriticaltime = 9 * 3600 - $result_deadline[0]["usedtime"];
	// return if it is not enough
	if($uncriticaltime < $space_needed) {
		return false;
	}
	// iterate over non-deadline tasks and move them until enough time is free
	// TODO order by... whatever
	$stmt = $conn->prepare('SELECT id, duration FROM tasks WHERE date = :date AND deadline_day IS NULL');
	$stmt->bindValue(':date', $day);
	$stmt->execute();
	while($freetime < $space_needed && $task = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$move_one = move_task($conn, $task["id"], get_next_day_with_free_space($conn, $task["duration"]));
		if(!$move_one) {
			return false; // damnit
		}
		$freetime += $task["duration"];
	}

	return $freetime >= $space_needed; // at this point, if false is returned, something went really wrong
}

function move_task(PDO $conn, $id, $date)
{
	if($date == false) {
		// propably called from make_space()
		return false;
	}

	if (!is_array($id)) $id = [$id];
	$upd = $conn->prepare('UPDATE tasks SET date=:date WHERE id=:id');
	$conn->beginTransaction();
	foreach ($id as $task_id) {
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

function tag_task(PDO $conn, $id, $tag)
{
	$ioi = $conn->prepare('INSERT OR IGNORE INTO tasks_tags (task_id, tag_name) VALUES (:id, :tag)');
	$ioi->bindValue(':id', $id);
	$ioi->bindValue(':tag', $tag);
	return $ioi->execute();
}