<?php
require('lib/common.php');

$act = $_POST['action'] ?? null;
if ($act == 'Register') {
	$name = trim($_POST['name']);

	$timezone = $_POST['timezone'] != $defaulttimezone ? $_POST['timezone'] : null;

	$err = '';
	if ($name == '')
		$err = 'The username must not be empty, please choose one.';
	elseif (strlen($_POST['pass']) < 4)
		$err = 'Your password must be at least 4 characters long.';
	elseif ($_POST['pass'] != $_POST['pass2'])
		$err = "The two passwords you entered don't match.";
	elseif (isset($puzzle) && strtolower($_POST['puzzle']) != strtolower($puzzle[1]))
		$err = "Wrong security question.";
	elseif ($sql->result("SELECT COUNT(*) FROM users WHERE LOWER(name) = ?", [strtolower($name)]))
		$err = 'This username is already taken, please choose another.';
	elseif (!preg_match('/^[a-zA-Z0-9\-_]+$/', $name))
		$err = 'Username contains invalid characters (Only alphanumeric and underscore allowed).';

	if (empty($err)) {
		$token = bin2hex(random_bytes(32));
		$res = $sql->query("INSERT INTO users (`name`,password,token,joined,lastview,ip,timezone) VALUES (?,?,?,?,?,?,?);",
			[$name, password_hash($_POST['pass'], PASSWORD_DEFAULT), $token, time(), time(), $userip, $timezone]);

		$id = $sql->insertid();

		if ($id == 1) $sql->query("UPDATE users SET powerlevel = 4 WHERE id = ?",[$id]);

		// mark existing threads and forums as read
		$sql->query("INSERT INTO threadsread (uid,tid,time) SELECT ?,id,? FROM threads", [$id, time()]);
		$sql->query("INSERT INTO forumsread (uid,fid,time) SELECT ?,id,? FROM forums", [$id, time()]);

		if (function_exists('sendWelcomePM')) {
			$sql->query("INSERT INTO pmsgs (date,ip,userto,userfrom,title,text) VALUES (?,'127.0.0.1',?,1,'Welcome!',?)",
				[time(),$id,sendWelcomePM($name)]);
		}

		setcookie('token', $token, 2147483647);

		redirect('./');
	}
}

pageheader('Register');

$timezones = [];
foreach (timezone_identifiers_list() as $tz) {
	$timezones[$tz] = $tz;
}

if (!empty($err)) noticemsg($err);
?>
<form action="register.php" method="post">
	<table class="c1">
		<tr class="h">
			<td class="b h" colspan="2">Register</td>
		</tr><tr>
			<td class="b n1 center" width="180">Username:</td>
			<td class="b n2"><input type="text" name="name" size="25" maxlength="25"></td>
		</tr><tr>
			<td class="b n1 center">Password:</td>
			<td class="b n2"><input type="password" name="pass" size="25" maxlength="32"></td>
		</tr><tr>
			<td class="b n1 center">Password (again):</td>
			<td class="b n2"><input type="password" name="pass2" size="25" maxlength="32"></td>
		</tr>
		<?php
		echo fieldrow('Timezone',fieldselect('timezone',$defaulttimezone,$timezones));
		if (isset($puzzle)) { ?>
			<tr>
				<td class="b n1 center"><?=$puzzle[0] ?></td>
				<td class="b n2"><input type="text" name="puzzle" size="25" maxlength="20"></td>
			</tr>
		<?php } ?>
		<tr class="n1">
			<td class="b"></td>
			<td class="b">
				<input type="submit" name="action" value="Register">
				<span class="sfont">Please read the <a href="faq.php">FAQ</a> before registering.</span>
			</td>
		</tr>
	</table>
</form>
<?php pagefooter();
