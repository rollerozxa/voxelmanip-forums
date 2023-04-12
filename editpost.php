<?php
require('lib/common.php');
needs_login();

$act = $_GET['act'] ?? '';
$action = $_POST['action'] ?? '';

$pid = $_GET['pid'] ?? null;

if ($act == 'delete' || $act == 'undelete') {
	if ($loguser['rank'] <= 1)
		error("You do not have the permission to do this.");

	$sql->query("UPDATE posts SET deleted = ? WHERE id = ?", [($act == 'delete' ? 1 : 0), $pid]);
	redirect("thread.php?pid=$pid#$pid");
}

$thread = $sql->fetch("SELECT p.user puser, t.*, f.title ftitle FROM posts p LEFT JOIN threads t ON t.id = p.thread "
	."LEFT JOIN forums f ON f.id=t.forum WHERE p.id = ? AND ? >= f.minread", [$pid, $loguser['rank']]);

if (!$thread) $pid = 0;

if ($thread['closed'] && $loguser['rank'] <= 1)
	error("You can't edit a post in closed threads!");
if ($loguser['rank'] < 3 && $loguser['id'] != $thread['puser'])
	error("You do not have permission to edit this post.");
if ($pid == -1)
	error("Invalid post ID.");

$post = $sql->fetch("SELECT u.id, p.user, pt.text FROM posts p
		LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
		LEFT JOIN users u ON p.user = u.id WHERE p.id = ?",
	[$pid]);

if (!$post) error("Post doesn't exist.");

$error = '';
$message = $_POST['message'] ?? trim($post['text']);

if ($action == 'Submit') {
	if ($message == $post['text'])
		$error = "No changes detected.";
	if (strlen($message) < 15)
		$error = "You can't blank out your post!";

	if (!$error) {
		$newrev = $sql->result("SELECT revision FROM posts WHERE id = ?", [$pid]) + 1;

		$sql->query("UPDATE posts SET revision = ? WHERE id = ?", [$newrev, $pid]);

		$sql->query("INSERT INTO poststext (id,text,revision,date) VALUES (?,?,?,?)",
			[$pid, $message, $newrev, time()]);

		redirect("thread.php?pid=$pid#edit");
	}
}

$topbot = [
	'breadcrumb' => [
		"forum.php?id={$thread['forum']}" => $thread['ftitle'],
		"thread.php?id={$thread['id']}" => esc($thread['title'])],
	'title' => 'Edit post'
];

pageheader('Edit post',$thread['forum']);

if ($action == 'Preview') {
	$euser = $sql->fetch("SELECT * FROM users WHERE id = ?", [$post['id']]);
	$post['date'] = $post['ulastpost'] = time();
	$post['text'] = $message;
	foreach ($euser as $field => $val)
		$post['u'.$field] = $val;
	$post['headerbar'] = 'Post preview';

	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);
	echo '<br>'.threadpost($post);
} else
	RenderPageBar($topbot);

?><br><?=($error ? noticemsg($error).'<br>' : '')?>
<form action="editpost.php?pid=<?=$pid?>" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan=2>Edit Post</td></tr>
		<tr>
			<td class="b n1 center" width=120>Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center" width=120>Post:</td>
			<td class="b n2"><textarea wrap="virtual" name="message" id="message" rows="15" cols="80"><?=esc($message) ?></textarea></td>
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
