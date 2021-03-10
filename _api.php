<?php
require_once("_base.php");

// this is so unsafe lol, don't use this on a non-local host
// TODO: redundant, opening database also happens in index.php
$conn = new PDO('sqlite:' . __DIR__ . '/data/data.db');
$task_ids = explode(",", $_GET["task_ids"]);

foreach (explode(",", $_GET["commands"]) as $command) {
	switch ($command) {
		case "done":
			echo done_task($conn, $task_ids);
			break;
		case "today":
			echo move_task($conn, $task_ids, date("Y-m-d"));
			break;
		case "delete":
			$done = $conn->prepare('DELETE FROM tasks WHERE id = :id');
			$conn->beginTransaction();
			foreach ($task_ids as $task_id) {
				$done->bindValue(':id', $task_id);
				$done->execute();
			}
			echo $conn->commit();
			break;
		case "duration":
			$done = $conn->prepare('UPDATE tasks SET duration=:duration WHERE id = :id');
			$conn->beginTransaction();
			foreach ($task_ids as $task_id) {
				$done->bindValue(':id', $task_id);
				$done->bindValue(':duration', $_GET["additional_data"]);
				$done->execute();
			}
			echo $conn->commit();
			break;
		case "move":
			echo move_task($conn, $task_ids, date_create($_GET["additional_data"])->format("Y-m-d"));
			break;
	}
}
echo "end";