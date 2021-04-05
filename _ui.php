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

function day_weekday($date = null) {
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

$day_shortcut_assignments = [1 => "Q", 2 => "W", 3 => "E", 4 => "R", 5 => "Z", 6 => "U", 7 => "I", 8 => "O"];