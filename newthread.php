<?php
require('lib/common.php');
needs_login();

$fid = $_GET['id'] ?? null;
$action = $_POST['action'] ?? null;

$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND ? >= minread", [$fid, $loguser['rank']]);

if (!$forum)
	error("Forum does not exist.");
if ($forum['minthread'] > $loguser['rank'])
	error("You have no permissions to create threads in this forum!");

$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

$error = '';

if ($action == 'Submit') {
	if (strlen($title) < 7)
		$error = "You need to enter a longer title.";
	if (strlen($message) == 0)
		$error = "You need to enter a message to your thread.";
	if ($loguser['lastpost'] > time() - 30)
		$error = "Please wait 30 seconds before opening a new thread.";

	if (!$error) {
		$sql->query("UPDATE users SET posts = posts + 1,threads = threads + 1,lastpost = ? WHERE id = ?", [time(), $loguser['id']]);
		$sql->query("INSERT INTO threads (title,forum,user,lastdate,lastuser) VALUES (?,?,?,?,?)",
			[$title,$fid,$loguser['id'],time(),$loguser['id']]);

		$tid = $sql->insertid();
		$sql->query("INSERT INTO posts (user,thread,date,ip) VALUES (?,?,?,?)",
			[$loguser['id'],$tid,time(),$userip]);

		$pid = $sql->insertid();
		$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
			[$pid,$message]);

		$sql->query("UPDATE forums SET threads = threads + 1, posts = posts + 1, lastdate = ?,lastuser = ?,lastid = ? WHERE id = ?",
			[time(), $loguser['id'], $pid, $fid]);

		$sql->query("UPDATE threads SET lastid = ? WHERE id = ?", [$pid, $tid]);

		redirect("thread.php?id=$tid");
	}
}

$topbot = [
	'breadcrumb' => ["forum.php?id=$fid" => $forum['title']],
	'title' => "New thread"
];

pageheader("New thread", $forum['id']);

if ($action == 'Preview') {
	$post['date'] = $post['ulastpost'] = time();
	$loguser['posts']++;
	$post['text'] = $message;
	foreach ($loguser as $field => $val)
		$post['u'.$field] = $val;
	$post['headerbar'] = 'Post preview';

	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);

	echo '<br>'.threadpost($post);
} else
	RenderPageBar($topbot);

?><br><?=($error ? noticemsg($error).'<br>' : '')?>
<form action="newthread.php?id=<?=$fid?>" method="post"><table class="c1">
	<tr class="h"><td class="b h" colspan="2">Thread</td></tr>
	<tr>
		<td class="b n1 center" width="120">Thread title:</td>
		<td class="b n2"><input type="text" name="title" size="100" maxlength="100" value="<?=esc($title) ?>"></td>
	</tr><tr>
		<td class="b n1 center">Format:</td>
		<td class="b n2"><?=posttoolbar() ?></td>
	</tr><tr>
		<td class="b n1 center">Post:</td>
		<td class="b n2"><textarea name="message" id="message" rows="15" cols="80"><?=esc($message) ?></textarea></td>
	</tr><tr>
		<td class="b n1"></td>
		<td class="b n1">
			<input type="submit" name="action" value="Submit">
			<input type="submit" name="action" value="Preview">
		</td>
	</tr>
</table></form><br>
<?php

RenderPageBar($topbot);

pagefooter();
