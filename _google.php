<?php
function google_create_date($dateobj)
{
	if (property_exists($dateobj, "dateTime")) {
		return date_create($dateobj->dateTime);
	} else return date_create($dateobj->date);
}

function google_is_appointment($item)
{
	// TODO: and if in specific calendar
	return property_exists($item->start, "dateTime") && property_exists($item->end, "dateTime");
}

function google_get_next_events($config)
{
	// Get next Google events from now
	$now = date(DATE_RFC3339);
	if (property_exists($config, 'last_google_check')) {
		$from = $config->last_google_check;
	} else {
		$from = $now;
	}
	// @TODO limit to 4 weeks
	// @TODO handle multiple pages - Google returns plenty, but we must not rely on the first page to contain everything we need
	// TODO get events since we last loaded, not from now
	$url = 'https://www.googleapis.com/calendar/v3/calendars/' . $config->google_calendars[0] . '/events';
	$params['singleEvents'] = 'true';
	$params['orderBy'] = 'startTime';
	$params['timeMin'] = $from;
	$json = get_json_google($url, $params);
	$config->last_google_check = $now;
	file_put_contents('data/config.json', json_encode($config));
	return $json->items;
}

function google_write_items_to_database(PDO $conn, $items)
{
	// Write events to database
	// TODO this is so google specific it should not be here
	$ioi = $conn->prepare('INSERT OR IGNORE INTO tasks (description, duration, date, google_id, deadline_day) VALUES (:description, :duration, :date, :google_id, :deadline_day)');
	$conn->beginTransaction();
	foreach ($items as $item) {
		$start = google_create_date($item->start);
		$end = google_create_date($item->end);
		$duration = calculate_duration($start, $end);
		$ioi->bindValue(':description', $item->summary);
		$ioi->bindValue(':google_id', $item->id);
		if (google_is_appointment($item)) {
			$ioi->bindValue(':deadline_day', $end->format('Y-m-d'));
		} else {
			$ioi->bindValue(':deadline_day', null);
		}
		$ioi->bindValue(':duration', $duration, PDO::PARAM_INT);
		$ioi->bindValue(':date', $start->format('Y-m-d'));
		$ioi->execute();
	}
	return $conn->commit();
}