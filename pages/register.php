<?php
$error = [];

if (isset($_POST['action'])) {
	$name = trim($_POST['name'] ?? null);
	$pass = $_POST['password'] ?? null;
	$pass2 = $_POST['password2'] ?? null;

	$invite = strtoupper(str_replace(' ', '', $_REQUEST['invite'] ?? null));

	// Check to see user should be able to register...

	if (!$name)
		$error[] = __('Blank username.');

	if (!$pass || strlen($pass) < 12)
		$error[] = __('Password is too short (needs to be at least %d characters).', 12);

	if (!$pass2 || $pass != $pass2)
		$error[] = __("The passwords don't match.");

	if (result("SELECT COUNT(*) FROM users WHERE LOWER(name) = ?", [strtolower($name)]))
		$error[] = __("Username has already been taken.");

	if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $name))
		$error[] = __("Username contains invalid characters (Only alphanumeric and underscore allowed).");

	if (result("SELECT COUNT(*) FROM users WHERE ip = ?", [$ipaddr]) && !DEBUG)
		$error[] = __("Creating multiple accounts (alts) aren't allowed.");

	if (result("SELECT COUNT(*) FROM invites WHERE code = ? AND invitee IS NULL", [$invite]) != 1)
		$error[] = "Invalid or claimed invite code.";

	// If no error found, it will register and redirect to index page.
	// Otherwise register page will be shown again, with $error displayed to the user.

	if ($error == []) {
		// Generate a random 64-length hexadecimal string for token.
		$token = bin2hex(random_bytes(32));

		insertInto('users', [
			'name' => $name,
			'password' => password_hash($pass, PASSWORD_DEFAULT),
			'token' => $token,
			'joined' => time()
		]);

		$id = insertId();
		// If user is ID 1, make them root.
		if ($id == 1) query("UPDATE users SET rank = 4 WHERE id = ?", [$id]);

		if ($invite) {
			query("UPDATE invites SET invitee = ?, claimed = ? WHERE code = ?",
				[$id, time(), $invite]);
		}

		// Log in user right away.
		setcookie('token', $token, 2147483647);

		redirect('/?rd');
	}
}

if (isset($_GET['invite'])) {
	$invitedBy = result("SELECT u.name FROM invites i
		JOIN users u ON i.inviter = u.id
		WHERE i.code = ? AND i.invitee IS NULL", [$_GET['invite']]);
}

twigloader()->display('register.twig', [
	'error' => $error,
	'name' => $name ?? null,
	'invited_by' => $invitedBy ?? null
]);