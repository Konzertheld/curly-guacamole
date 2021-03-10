<?php
function day_label($date): string
{
	// Note: Timezones don't matter because both dates are in the same timezone (UTC)
	// Note that we cannot use a variable for today because it would get modified in the function calls below
	// Whatever the hell PHP thought they do
	$oneday = new DateInterval('P1D');
	$day = date_create($date);

	return
		match ($date) {
			date_create()->format("Y-m-d") => "Heute - ",
			date_add(date_create(), $oneday)->format("Y-m-d") => "Morgen - ",
			date_sub(date_create(), $oneday)->format("Y-m-d") => "Gestern - ",
			default => ""
		}
		. ' '
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