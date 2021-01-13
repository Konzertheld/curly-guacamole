<?php
function google_create_date($dateobj) {
	// TODO: Use nullable operator
	if(property_exists($dateobj, "dateTime")) {
		return date_create($dateobj->dateTime);
	}
	else return date_create($dateobj->date);
}

function google_get_next_events($config) {
	// Get next Google events from now
	// @TODO limit to 4 weeks
	// @TODO handle multiple pages - Google returns plenty, but we must not rely on the first page to contain everything we need
	$url = 'https://www.googleapis.com/calendar/v3/calendars/' . $config->google_calendars[0] . '/events';
	$params['singleEvents'] = 'true';
	$params['orderBy'] = 'startTime';
	$params['timeMin'] = date(DATE_RFC3339);
	$json = get_json_google($url, $params);
	return $json->items;
}