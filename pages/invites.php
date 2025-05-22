<?php
needsLogin();

function getRandomString($length = 32) {
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';

	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[random_int(0, $charactersLength - 1)];
	}

	return $randomString;
}

if (isset($_POST['generatecode'])) {
	$existingCodes = result("SELECT COUNT(*) FROM invites WHERE inviter = ? AND invitee IS NULL", [$userdata['id']]);

	if ($existingCodes >= 3 && !IS_MOD) {
		$tooManyCodes = true;
	} else {
		insertInto('invites', [
			'code' => getRandomString(),
			'inviter' => $userdata['id'],
			'generated' => time(),
		]);
	}
}

$userfields = userfields('u1').userfields('u2');
$invites = query("SELECT $userfields i.*
	FROM invites i
	LEFT JOIN users u1 ON i.inviter = u1.id
	LEFT JOIN users u2 ON i.invitee = u2.id
	WHERE i.inviter = ?",
		[$userdata['id']]);

twigloader()->display('invites.twig', [
	'invites' => $invites,
	'toomanycodes' => $tooManyCodes ?? false
]);
