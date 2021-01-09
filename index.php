<?php
require_once('_base.php');
require_once('_google.php');
require_once('_auth.php');

// Are we on the way back from an OAuth process?
if(isset($_GET['code'])) {
    google_exchange();
    header('Location: /planer');
}

// @TODO Check if we need to get Google data

// Check if Google token needs to be refreshed
google_check_and_refresh();

// Load config
$config = json_decode(file_get_contents('data/config.json'));

// For testing, get the first calendar events of the first calendar
$url = 'https://www.googleapis.com/calendar/v3/calendars/' . $config->google_calendars[0] . '/events';
$params['singleEvents'] = 'true';
$params['orderBy'] = 'startTime';
$params['timeMin'] = date(DATE_RFC3339);
$json = get_json($url, $params);
foreach($json->items as $item) {
    print $item->summary . '<br>';
}