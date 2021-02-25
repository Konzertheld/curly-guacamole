<?php
require_once("_base.php");

// this is so unsafe lol, don't use this on a non-local host
// TODO: redundant, opening database also happens in index.php
$conn = new PDO('sqlite:' . __DIR__ . '/data/data.db');
$task_ids = explode(",", $_GET["task_ids"]);

switch($_GET["command"]) {
	case "done":
		$done = $conn->prepare('UPDATE tasks SET done=((done | 1) - (done & 1)) WHERE id = :id');
		$conn->beginTransaction();
		foreach($task_ids as $task_id) {
			$done->bindValue(':id', $task_id);
			$done->execute();
		}
		echo $conn->commit();
		break;
}

echo "end";