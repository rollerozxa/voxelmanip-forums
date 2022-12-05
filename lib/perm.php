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

$powerlevels = [
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

function powIdToColour($id, $colourset) {
	// This could be far less messy but I'm not a good coder and just copypasted everything ~nctp2109
	return match($id) {
		-1 => '888888',
		0  => 'ffffff',
	};
	if ($colorset = 1) {
		return match ($id) {
			1  => '97ACEF',
			2  => 'AFFABE',
			3  => 'FFEA95',
			4  => '5555FF'
			};
	}
	elseif ($colorset = 2) {
		return match ($id) {
			1  => 'F185C9',
			2  => 'C762F2',
			3  => 'C53A9E',
			4  => 'FF5588'
			};
	}
	else {
		return match ($id) {
			1  => '7C60B0',
			2  => '47B53C',
			3  => 'F0C413',
			4  => 'FF55FF'
			};
	}
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
