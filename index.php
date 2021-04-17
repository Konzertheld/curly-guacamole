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

// Update database from Google
$force_reload = isset($_GET['nocache']) && $_GET['nocache'] == 1;
$google_items = google_get_next_events($config, $force_reload);
google_write_items_to_database($conn, $google_items);

// Handle past tasks #8
// Get'em
$pastsql = $conn->prepare('SELECT * FROM tasks LEFT JOIN tasks_tags ON tasks.id = tasks_tags.task_id AND tasks_tags.tag_name = "expired" WHERE date < :date AND done = 0 AND tasks_tags.tag_name IS NULL');
$pastsql->bindValue(':date', date('Y-m-d'));
$pastsql->execute();
$today = date('Y-m-d');
while($row = $pastsql->fetch(PDO::FETCH_OBJ)) {
	// as outlined in #8
	if($day = get_next_day_with_free_space($conn, $row->duration)) {
		if($row->deadline_day == NULL) {
			// no deadline, move to next free day and tag
			move_task($conn, $row->id, $day);
			tag_task($conn, $row->id, 'overdue');
		}
		elseif($row->deadline_day < $today) {
			// expired deadline, tag and do nothing
			tag_task($conn, $row->id, 'expired');
		}
		elseif($row->deadline_day >= $day) {
			// unexpired deadline & next free day is within the deadline, move
			move_task($conn, $row->id, $day);
		}
		else {
			// unexpired deadline but next free day is not within the deadline, try to make room
			$day = get_next_day_with_free_space($conn, $row->duration, true);
			if($row->deadline_day >= $day) {
				// TODO handle this case (we could move the task to a day within the deadline when other tasks are moved)
				if(make_space($conn, $day, $row->duration)) {
					move_task($conn, $row->id, $day);
				}
				else {
					// this is not good
					// TODO handle this case
					die("serious error, moving a deadline task to somewhere within the deadline where supposedly was enough free space failed");
				}
			}
			else {
				// your schedule is way too full!
				move_task($conn, $row->id, date('Y-m-d'));
				if($row->deadline_day <= date('Y-m-d')) {
					tag_task($conn, $row->id, 'overdue');
				}
			}
		}
	}
	else {
		// handle if there is no next free day, however that is supposed to occur TODO
	}
}

// Get items for the next 8 days
// TODO make this safe
$from = !isset($_GET['from']) ? $from = date_sub(date_create(), new DateInterval('P1D'))->format('Y-m-d') : $_GET['from'];
$days = read_items_from_database($conn, $from);

// Generate days for jumping
// Again, waste some computing time because we cannot use variables because PHP is weird
$jump_view_back = date_sub(date_create($from), new DateInterval('P8D'))->format('Y-m-d');
$jump_day_back = date_sub(date_create($from), new DateInterval('P1D'))->format('Y-m-d');
$jump_day_forward = date_add(date_create($from), new DateInterval('P1D'))->format('Y-m-d');
$jump_view_forward = date_add(date_create($from), new DateInterval('P8D'))->format('Y-m-d');
$today = date_create()->format("Y-m-d");

// Display main page
include('start.php');
