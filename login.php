<?php
require('lib/common.php');

$act = $_POST['action'] ?? null;
if ($act == 'Login') {
	$logindata = $sql->fetch("SELECT id,password,token FROM users WHERE name = ?", [$_POST['name']]);

	if ($logindata && password_verify($_POST['pass'], $logindata['password'])) {
		setcookie('token', $logindata['token'], 2147483647);
		redirect('./');
	} else
		$err = "Invalid username or password, cannot log in.";

} elseif ($act == 'logout') {
	setcookie('token', 0);
	redirect('./');
}

pageheader('Login');
if (isset($err)) noticemsg($err);
?>
<form action="login.php" method="post"><table class="c1">
	<tr class="h"><td class="b h" colspan="2">Login</td></tr>
	<tr>
		<td class="b n1 center" width="180">Username:</td>
		<td class="b n2"><input type="text" name="name" size="25" maxlength="25"></td>
	</tr><tr>
		<td class="b n1 center">Password:</td>
		<td class="b n2"><input type="password" name="pass" size="25" maxlength="32"></td>
	</tr><tr>
		<td class="b n1"></td>
		<td class="b n1"><input type="submit" name="action" value="Login"></td>
	</tr>
</table></form>
<?php pagefooter();
