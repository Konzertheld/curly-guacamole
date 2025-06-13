<?php
function day_label($date, $suppress_prefix = false, $suppress_weekday = false): string
{
	// Note: Timezones don't matter because both dates are in the same timezone (UTC)
	// Note that we cannot use a variable for today because it would get modified in the function calls below
	// Whatever the hell PHP thought they do
	$oneday = new DateInterval('P1D');
	$day = date_create($date);

	return (
		$suppress_prefix ? "" :
		match ($date) {
			date_create()->format("Y-m-d") => "Heute - ",
			date_add(date_create(), $oneday)->format("Y-m-d") => "Morgen - ",
			date_sub(date_create(), $oneday)->format("Y-m-d") => "Gestern - ",
			default => ""
		}
		. ' '
		)
		. ($suppress_weekday ? "" : day_weekday($date) . ', ')
		. $day->format("d.")
		. " "
		. match (substr($date, 5, 2)) {
			"01" => "Januar",
			"02" => "Februar",
			"03" => "März",
			"05" => "Mai",
			"06" => "Juni",
			"07" => "Juli",
			"10" => "Oktober",
			"12" => "Dezember",
			"04", "08", "09", "11" => $day->format("F"),
			default => ""
		}
		. " '"
		. $day->format("y");
}

function day_weekday($date = null): string {
	return match(date_create($date)->format("w")) {
		"1" => "Montag",
		"2" => "Dienstag",
		"3" => "Mittwoch",
		"4" => "Donnerstag",
		"5" => "Freitag",
		"6" => "Samstag",
		"0" => "Sonntag",
		default => ""
	};
}

function day_label_deadline($deadline_day, $day): string {
	// TODO wenn deadline_day == day == heute return "Heute"
	// wenn day == heute und deadline_day > heute return "in X Tagen"
	if($deadline_day == $day) {
		return "An diesem Tag";
	}
	$deadline_date = date_create($deadline_day);
	return $deadline_date->format("d.m.Y");
}

// subtract 2 to get position relative to selected day
$day_shortcut_assignments = [1 => "Q", 2 => "W", 3 => "E", 4 => "R", 5 => "Z", 6 => "U", 7 => "I", 8 => "O"];

function day_sum($days, $day): int {
	$sum = 0;
	foreach($days[$day] as $task) {
		$sum += $task->duration + 1200;
	}
	return $sum;
}

function duration_background_string($duration, $untilnow, $show_full_scale = false): string {
	// TODO: Load total from config, that is the max time we expect to get done on a day (see also _base.php)
	$total = 9 * 3600;
	// include 20 min break into calculation when in per-task mode
	if(!$show_full_scale)
		$duration += 1200;
	if($untilnow > $total) {
		return "background: linear-gradient(transparent 2px, #393939 2px), linear-gradient(90deg, #dd7733 0%, #dd7733 100%);";
	}
	elseif($untilnow + $duration > $total) {
		return sprintf("background: linear-gradient(transparent 2px, #393939 2px), linear-gradient(90deg, #555 %1$.4f%% , #dd7733 %1$.4f%%, #dd7733 %2$.4f%%, #393939 %2$.4f%%", $untilnow / $total * 100, ($untilnow + $duration) / $total * 100);
	}
	// TODO: for some reason PHPStorm shows two warnings here that do not seem to be correct
	return sprintf("background: linear-gradient(transparent 2px, #393939 2px), linear-gradient(90deg, #555 %1$.4f%% , #A6E22E %1$.4f%%, #A6E22E %2$.4f%%, #393939 %2$.4f%%" . ( $show_full_scale ? ", #555 %2$.4f%%, #555 100%%" : ""), $untilnow / $total * 100, ($untilnow + $duration) / $total * 100);
}