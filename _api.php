<?php
require_once("_base.php");

// this is so unsafe lol, don't use this on a non-local host
if(isset($_GET["task_ids"])) {
	$task_ids = explode(",", $_GET["task_ids"]);
}

foreach (explode(",", $_GET["commands"]) as $command) {
	switch ($command) {
		case "done":
			echo done_task($conn, $task_ids);
			break;
		case "delete":
			echo "del result " . delete_task($conn, $task_ids);
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
		case "add":
			$task_string = $_GET['task_string'];
			$data = [];
			// this is currently waaay over-flexible as there is only one symbol we really need
			// we really don't need this complicated of a regex but in case I need it here is it:
			// (^| )([=!])([^=! ]|(?<! )[=!]| (?![=!]))+
			// replace all =! with $symbols
			// will then match 1) a space or the beginning of the expression 2) followed by one of the symbols 3) followed by
			// a) a character that is neither a symbol nor a space or b) a symbol that is not preceded by a space or
			// c) a space that is not followed by a symbol
			// that would for example allow for #tags containing symbols and spaces
			// but all we really need is find duration and date and for that, we don't even need a symbol array, just hardcode it

			// find duration
			$regex = "/(?:^| )(=[0-9]+)(?:$| )/";
			if(preg_match_all($regex, $task_string, $matches) > 0) {
				if(count($matches[1]) > 1) {
					// TODO error handling for more than one match
					$data["duration"] = 90 * 60; // TODO load default duration from config
				}
				else {
					$data["duration"] = substr($matches[1][0], 1) * 60;
					// duration successfully found, remove it from string
					// note: we could use the full match to remove eventual spaces but those spaces might be needed, so rather remove them afterwards
					$task_string = str_replace($matches[1][0], "", $task_string);
				}
			}
			else {
				$data["duration"] = 90 * 60; // TODO load default duration from config
			}

			// find recurrance. currently assuming type 2 always
			$regex = "/(?:^| )(\*[0-9]+)(?:$| )/";
			if(preg_match_all($regex, $task_string, $matches) > 0) {
				if(count($matches[1]) > 1) {
					// TODO error handling for more than one match
				}
				else {
					$data["recurrence_days"] = substr($matches[1][0], 1);
					$data["recurrence_type"] = 2;
					// recurrence successfully found, remove it from string
					$task_string = str_replace($matches[1][0], "", $task_string);
				}
			}

			// find date
			// find YYYY-MM-DD and MM-DD, with or wihout +-NNN suffix
			$regex = "/(?:^| )([0-9]{4}-)?([0-9]{2}-[0-9]{2})([-+][0-9]+)?(?:$| )/";
			if (preg_match_all($regex, $task_string, $matches) > 0) {
				// add year if necessary
				$datestr = (empty($matches[1][0]) ? date("Y") . "-" : $matches[1][0]) . $matches[2][0];
				// process deadline info
				if (!empty($matches[3][0])) {
					$span = substr($matches[3][0], 1);
					if (substr($matches[3][0], 0, 1) == "+") {
						$data["date"] = $datestr;
						$data["deadline_day"] = date_add(date_create($datestr), new DateInterval(('P' . $span . 'D')))->format('Y-m-d');
					} else {
						$data["deadline_day"] = $datestr;
						$data["date"] = date_sub(date_create($datestr), new DateInterval(('P' . $span . 'D')))->format('Y-m-d');
					}
					$data["advance_span"] = $span;
				} else {
					$data["date"] = $datestr;
				}
				// date successfully processed, remove it from string
				// note: this is another method, use full match with trim (see above)
				$task_string = str_replace(trim($matches[0][0]), "", $task_string);
			}

			// process other parameters
			if(isset($_GET["done"]) && $_GET["done"]) {
				$data["done"] = true;
			}

			// use the rest as description
			$data["description"] = $task_string;
			echo create_task($conn, $data);
			break;
	}
}
echo "end";