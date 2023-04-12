<?php

function needs_login() {
	global $log;
	if (!$log) {
		pageheader('Login required');
		noticemsg("You need to be logged in to do that!<br><a href=login.php>Please login here.</a>");
		pagefooter();
		die();
	}
}

$ranks = [
	-1 => 'Banned',
	//0  => 'Guest',
	1  => 'Normal User',
	2  => 'Moderator',
	3  => 'Administrator',
	4  => 'Root',
];

function powIdToName($id) {
	return match ($id) {
		-1 => 'Banned',
		0  => 'Guest',
		1  => 'Normal User',
		2  => 'Moderator',
		3  => 'Administrator',
		4  => 'Root'
	};
}

function powIdToColour($id) {
	return match ($id) {
		-1 => '888888',
		0  => 'ffffff',
		1  => '4f77ff',
		2  => '47b53c',
		3  => 'd8b00d',
		4  => 'aa3c3c'
	};
}

function powNameToId($id) {
	return match ($id) {
		'Banned'		=> -1,
		'Guest'			=> 0,
		'Normal User'	=> 1,
		'Moderator'		=> 2,
		'Administrator'	=> 3,
		'Root'			=> 4
	};
}
