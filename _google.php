<?php
function google_create_date($dateobj)
{
	// TODO: Use nullable operator
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