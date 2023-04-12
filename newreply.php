<?php
require('lib/common.php');
needs_login();

$tid = $_GET['id'] ?? null;
$action = $_POST['action'] ?? null;

$thread = $sql->fetch("SELECT t.*, f.title ftitle, f.minreply fminreply
	FROM threads t LEFT JOIN forums f ON f.id=t.forum
	WHERE t.id = ? AND ? >= f.minread", [$tid, $loguser['rank']]);

if (!$thread)
	error("Thread does not exist.");
if ($thread['fminreply'] > $loguser['rank'])
	error("You have no permissions to create posts in this forum!");
if ($thread['closed'] && $loguser['rank'] < 2)
	error("You can't post in closed threads!");

$message = trim($_POST['message'] ?? '');

$error = '';
if ($action == 'Submit') {
	$lastpost = $sql->fetch("SELECT id,user,date FROM posts WHERE thread = ? ORDER BY id DESC LIMIT 1", [$thread['id']]);
	if ($lastpost['user'] == $loguser['id'] && $lastpost['date'] >= (time() - 43200) && $loguser['rank'] < 2)
		$error = "You can't double post until it's been at least 12 hours!";
	if ($lastpost['user'] == $loguser['id'] && $lastpost['date'] >= (time() - 30))
		$error = "You must wait 30 seconds before posting consecutively.";
	if (strlen($message) < 15)
		$error = "Your post is too short to be meaningful. Please try to write something longer or refrain from posting.";
	if (strlen($message) == 0)
		$error = "Your post is empty! Enter a message and try again.";

	if (!$error) {
		$sql->query("UPDATE users SET posts = posts + 1, lastpost = ? WHERE id = ?", [time(), $loguser['id']]);
		$sql->query("INSERT INTO posts (user,thread,date,ip) VALUES (?,?,?,?)",
			[$loguser['id'],$tid,time(),$userip]);

		$pid = $sql->insertid();
		$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
			[$pid,$message]);

		$sql->query("UPDATE threads SET posts = posts + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
			[time(), $loguser['id'], $pid, $tid]);

		$sql->query("UPDATE forums SET posts = posts + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
			[time(), $loguser['id'], $pid, $thread['forum']]);

		// nuke entries of this thread in the "threadsread" table
		$sql->query("DELETE FROM threadsread WHERE tid = ? AND NOT (uid = ?)", [$thread['id'], $loguser['id']]);

		redirect("thread.php?pid=$pid#$pid");
	}
}

$topbot = [
	'breadcrumb' => [
		"forum.php?id={$thread['forum']}" => $thread['ftitle'],
		"thread.php?id={$thread['id']}" => esc($thread['title'])],
	'title' => "New reply"
];

pageheader('New reply', $thread['forum']);

$pid = (int)($_GET['pid'] ?? 0);
if ($pid) {
	$post = $sql->fetch("SELECT u.name name, p.user, pt.text, f.id fid, p.thread, f.minread, t.lastid
			FROM posts p
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			LEFT JOIN threads t ON t.id = p.thread
			LEFT JOIN forums f ON f.id = t.forum
			WHERE p.id = ?", [$pid]);

	//does the user have reading access to the quoted post?
	if ($loguser['rank'] < $post['minread']) {
		$post['name'] = 'your overlord';
		$post['text'] = '';
	}

	if ($pid != $post['lastid'])
		$message = sprintf('[quote="%s" id="%s"]%s[/quote]', $post['name'], $pid, str_replace("&", "&amp;", trim($post['text'])));
}

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
<form action="newreply.php?id=<?=$tid?>" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan="2">Reply</td></tr>
		<tr>
			<td class="b n1 center" width="120">Format:</td>
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
	</table>
</form><br>
<?php

$fieldlist = userfields('u', 'u') . ', u.posts uposts, ';
$newestposts = $sql->query("SELECT $fieldlist p.*, pt.text
			FROM posts p
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			WHERE p.thread = ? AND p.deleted = 0
			ORDER BY p.id DESC LIMIT 5", [$tid]);

echo '<table class="c1"><tr class="h"><td class="b h" colspan="2">Thread preview</td></tr>';
while ($post = $newestposts->fetch())
	echo minipost($post);
echo '</table><br>';

RenderPageBar($topbot);

pagefooter();
