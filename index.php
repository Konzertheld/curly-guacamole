<?php
require_once('_base.php');
require_once('_google.php');
require_once('_auth.php');

// Are we on the way back from an OAuth process?
if(isset($_GET['code'])) {
	google_exchange();
	header('Location: /planer');
}

// Check if Google token needs to be refreshed
google_check_and_refresh();

// Load config
$config = json_decode(file_get_contents('data/config.json'));

// Open database
$conn = new PDO('sqlite:' . __DIR__ . '/data/data.db');
$ioi = $conn->prepare('INSERT OR IGNORE INTO tasks (description, duration, date, google_id) VALUES (:description, :duration, :date, :google_id)');
$upd = $conn->prepare('UPDATE tasks SET description=:description, duration=:duration, date=:date WHERE google_id=:google_id');

// Update database from Google
// @TODO Check if we need to get Google data
$google_items = google_get_next_events($config);
write_items_to_database($conn, $google_items);

// Get items for the next 8 days
// TODO beginning from X

print "200";