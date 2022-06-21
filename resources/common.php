<?php

/**
 * Convert minutes to hours and minutes.
 * https://stackoverflow.com/a/8563576/12619942
 * 
 * @param int $time
 * @param ?string $format
 */
function convertToHoursMins(int $time, string $format = '%02d:%02d'): string
{
	if ($time < 0) {
		return null;
	}
	$hours = floor($time / 60);
	$minutes = ($time % 60);
	return sprintf($format, $hours, $minutes);
}
