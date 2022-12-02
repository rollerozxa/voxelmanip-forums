<?php
require('lib/common.php');
needs_login();

$action = $_POST['action'] ?? '';

$topbot = [
	'breadcrumb' => ["private.php" => 'Private messages'],
	'title' => 'Send'
];

if ($loguser['powerlevel'] < 1) error("You have no permissions to do this!");

$userto = $_POST['userto'] ?? '';
$title = $_POST['title'] ?? '';
$message = $_POST['message'] ?? '';

$error = '';

if ($action == 'Submit') {
	$userto = $sql->result("SELECT id FROM users WHERE name LIKE ?", [$userto]);

	if (!$userto) $error = "That user doesn't exist.";
	if (!$message) $error = "You can't send a blank message.";

	$recentpms = $sql->fetch("SELECT date FROM pmsgs WHERE date >= (UNIX_TIMESTAMP()-15) AND userfrom = ?", [$loguser['id']]);
	if ($recentpms)
		$error = "You can't send more than one PM within 15 seconds!";

	if (!$error) {
		$sql->query("INSERT INTO pmsgs (date,ip,userto,userfrom,title,text) VALUES (?,?,?,?,?,?)",
			[time(),$userip,$userto,$loguser['id'],$title,$message]);

		redirect("private.php");
	}
}

if (isset($_GET['pid']) && $pid = $_GET['pid']) {
	$post = $sql->fetch("SELECT u.name name, p.title, p.text FROM pmsgs p LEFT JOIN users u ON p.userfrom = u.id WHERE p.id = ?"
		.($loguser['powerlevel'] < 4 ? " AND (p.userfrom = ".$loguser['id']." OR p.userto=".$loguser['id'].")" : ''), [$pid]);
	if ($post) {
		$userto = $post['name'];
		$title = 'Re: '.$post['title'];
		$message = '[reply="'.$post['name'].'" id="'.$pid.'"]'.$post['text'].'[/reply]';
	}
}

if (isset($_GET['uid']) && $uid = $_GET['uid'])
	$userto = $sql->result("SELECT name FROM users WHERE id = ?", [$uid]);

pageheader('Send private message');

if ($action == 'Preview') {
	$post['date'] = $post['ulastpost'] = time();
	$post['text'] = $message;
	foreach ($loguser as $field => $val)
		$post['u'.$field] = $val;
	$post['headerbar'] = 'Message preview';

	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);

	echo '<br>'.threadpost($post);
} else
	RenderPageBar($topbot);

?><br><?=($error ? noticemsg($error).'<br>' : '')?>
<form action="sendprivate.php" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan="2">Send message</td></tr>
		<tr>
			<td class="b n1 center" width="120">Send to:</td>
			<td class="b n2"><input type="text" name="userto" size="25" maxlength=25 value="<?=esc($userto) ?>"></td>
		</tr><tr>
			<td class="b n1 center">Subject:</td>
			<td class="b n2"><input type="text" name="title" size="80" maxlength="255" value="<?=esc($title) ?>"></td>
		</tr><tr>
			<td class="b n1 center" width="120">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center"></td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc($message) ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table>
</form><br>
<?php
RenderPageBar($topbot);

pagefooter();
