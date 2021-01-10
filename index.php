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

// Open database
$conn = new PDO('sqlite:' . __DIR__ . '/data/data.db');
$ioi = $conn->prepare('INSERT OR IGNORE INTO tasks (description, duration, date, google_id) VALUES (:description, :duration, :date, :google_id)');
$upd = $conn->prepare('UPDATE tasks SET description=:description, duration=:duration, date=:date WHERE google_id=:google_id');

// Get next Google events from now
// @TODO limit to 4 weeks
// @TODO handle multiple pages - Google returns plenty, but we must not rely on the first page to contain everything we need
$url = 'https://www.googleapis.com/calendar/v3/calendars/' . $config->google_calendars[0] . '/events';
$params['singleEvents'] = 'true';
$params['orderBy'] = 'startTime';
$params['timeMin'] = date(DATE_RFC3339);
$json = get_json_google($url, $params);

// Write events to database
$conn->beginTransaction();
foreach($json->items as $item) {
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
$conn->commit();

print "200";