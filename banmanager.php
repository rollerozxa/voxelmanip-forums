<?php
require('lib/common.php');

$id = (int)$_GET['id'];

$tuser = $sql->result("SELECT rank FROM users WHERE id = ?",[$id]);
if ($loguser['rank'] < 2 || $loguser['rank'] <= $tuser)
	error("You have no permissions to do this!");

if ($uid = $_GET['id']) {
	$numid = $sql->fetch("SELECT id FROM users WHERE id = ?",[$uid]);
	if (!$numid) error("Invalid user ID.");
}

$user = $sql->fetch("SELECT * FROM users WHERE id = ?",[$uid]);

if (isset($_POST['banuser'])) {
	$banreason = ($_POST['tempbanned'] ? "Banned until ".date("Y-m-d H:i",time() + ($_POST['tempbanned'])) : 'Banned');

	if ($_POST['title'])
		$banreason .= ': '.esc($_POST['title']);

	$sql->query("UPDATE users SET rank = -1, title = ?, tempbanned = ? WHERE id = ?",
		[$banreason, ($_POST['tempbanned'] > 0 ? ($_POST['tempbanned'] + time()) : 0), $user['id']]);

	redirect("profile.php?id=$user[id]");
} elseif (isset($_POST['unbanuser'])) {
	if ($user['rank'] != -1) error("This user is not a banned user.");

	$sql->query("UPDATE users SET rank = 1, title = '', tempbanned = 0 WHERE id = ?", [$user['id']]);

	redirect("profile.php?id=$user[id]");
}

$pagebar = [
	'breadcrumb' => ["profile.php?id=$uid" => $user['name']]
];

$pagebar['title'] = (isset($_GET['unban']) ? 'Unban User' : 'Ban User');

pageheader($pagebar['title']);

RenderPageBar($pagebar);

if (isset($_GET['unban'])) {
	?><br><form action="banmanager.php?id=<?=$uid ?>" method="post" enctype="multipart/form-data"><table class="c1">
		<tr class="h"><td class="b">Unban User</td></tr>
		<tr class="n1"><td class="b n1 center"><input type="submit" name="unbanuser" value="Unban User"></td></tr>
	</table></form><br><?php
} else {
	?><br><form action="banmanager.php?id=<?=$uid ?>" method="post" enctype="multipart/form-data">
	<table class="c1">
		<?=catheader('Ban User') ?>
		<tr>
			<td class="b n1 center">Reason:</td>
			<td class="b n2"><input type="text" name="title"></td>
		</tr><tr>
			<td class="b n1 center">Expires?</td>
			<td class="b n2"><?=bantimeselect("tempbanned") ?></td>
		</tr><tr class="n1">
			<td class="b"></td>
			<td class="b"><input type="submit" name="banuser" value="Ban User"></td>
		</tr>
	</table></form><br><?php
}

RenderPageBar($pagebar);

pagefooter();