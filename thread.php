<?php
require('lib/common.php');

$page = (int)($_REQUEST['page'] ?? 1);
if ($page < 1) $page = 1;

$fieldlist = userfields('u', 'u') . ',' . userfields_post();

$ppp = $_GET['ppp'] ?? $loguser['ppp'];
if ($ppp < 1) $ppp = $loguser['ppp'];

if (isset($_GET['id'])) {
	$tid = (int)$_GET['id'];
	$viewmode = "thread";
} elseif (isset($_GET['user'])) {
	$uid = (int)$_GET['user'];
	$viewmode = "user";
} elseif (isset($_GET['time'])) {
	$time = (int)$_GET['time'];
	$viewmode = "time";
} elseif (isset($_GET['pid'])) { // "link" support (i.e., thread.php?pid=999whatever)
	$pid = (int)$_GET['pid'];
	$numpid = $sql->result("SELECT p.id FROM posts p WHERE p.id = ?", [$pid]);
	if (!$numpid) error("Thread post does not exist.");

	$tid = $sql->result("SELECT thread FROM posts WHERE id = ?", [$pid]);
	$page = floor($sql->result("SELECT COUNT(*) FROM posts WHERE thread = ? AND id < ?", [$tid, $pid]) / $ppp) + 1;
	$viewmode = "thread";
} else
	error("Thread does not exist.");


if ($viewmode == "thread")
	$threadcreator = $sql->result("SELECT user FROM threads WHERE id = ?", [$tid]);
else
	$threadcreator = 0;

$action = '';
$act = $_POST['action'] ?? '';

if (isset($tid) && $log && $act && ($loguser['powerlevel'] > 1 ||
		($loguser['id'] == $threadcreator && $act == "rename" && $loguser['powerlevel'] > 0))) {

	if ($act == 'stick')	$action = ',sticky=1';
	if ($act == 'unstick')	$action = ',sticky=0';
	if ($act == 'close')	$action = ',closed=1';
	if ($act == 'open')		$action = ',closed=0';
	if ($act == 'trash')	movethread($tid, $trashid, 1);
	if ($act == 'rename')	$action = ",title=?";
	if ($act == 'move')		movethread($tid, $_POST['arg']);
}

// determine string for revision pinning
if (isset($_GET['pin']) && isset($_GET['rev']) && is_numeric($_GET['pin']) && is_numeric($_GET['rev']) && $loguser['powerlevel'] > 1)
	$pinstr = "AND (pt2.id<>$_GET[pin] OR pt2.revision<>($_GET[rev]+1)) ";
else
	$pinstr = '';

$offset = (($page - 1) * $ppp);

if ($viewmode == "thread") {
	if (!$tid) $tid = 0;

	$params = ($act == 'rename' ? [$_POST['title'], $tid] : [$tid]);
	$sql->query("UPDATE threads SET views = views + 1 $action WHERE id = ?", $params);

	$thread = $sql->fetch("SELECT t.*, f.title ftitle, t.forum fid".($log ? ', r.time frtime' : '').' '
			. "FROM threads t LEFT JOIN forums f ON f.id=t.forum "
			. ($log ? "LEFT JOIN forumsread r ON (r.fid=f.id AND r.uid=$loguser[id]) " : '')
			. "WHERE t.id = ? AND ? >= f.minread",
			[$tid, $loguser['powerlevel']]);

	if (!isset($thread['id'])) error("Thread does not exist.");

	//append thread's title to page title
	pageheader($thread['title'], $thread['fid']);

	//mark thread as read
	if ($log && $thread['lastdate'] > $thread['frtime'])
		$sql->query("REPLACE INTO threadsread VALUES (?,?,?)", [$loguser['id'], $thread['id'], time()]);

	//check for having to mark the forum as read too
	if ($log) {
		$readstate = $sql->fetch("SELECT ((NOT ISNULL(r.time)) OR t.lastdate < ?) n FROM threads t
				LEFT JOIN threadsread r ON (r.tid = t.id AND r.uid = ?) WHERE t.forum = ? ORDER BY n ASC",
			[$thread['frtime'], $loguser['id'], $thread['fid']]);

		//if $readstate[n] is 1, MySQL did not create a group for threads where ((NOT ISNULL(r.time)) OR t.lastdate<'$thread[frtime]') is 0;
		//thus, all threads in the forum are read. Mark it as such.
		if ($readstate['n'] == 1)
			$sql->query("REPLACE INTO forumsread VALUES (?,?,?)", [$loguser['id'], $thread['fid'], time()]);
	}

	$posts = $sql->query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.forum tforum
			FROM posts p
			LEFT JOIN threads t ON t.id = p.thread
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			WHERE p.thread = ?
			ORDER BY p.id LIMIT ?,?",
		[$tid, $offset, $ppp]);

} elseif ($viewmode == "user") {
	$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$uid]);

	if ($user == null) error("User doesn't exist.");

	pageheader("Posts by ".$user['name']);

	$posts = $sql->query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.id tid, f.id fid, t.title ttitle, t.forum tforum
			FROM posts p
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			LEFT JOIN threads t ON p.thread = t.id
			LEFT JOIN forums f ON f.id = t.forum
			WHERE p.user = ? AND ? >= f.minread
			ORDER BY p.id LIMIT ?,?",
		[$uid, $loguser['powerlevel'], $offset, $ppp]);

	$thread['posts'] = $sql->result("SELECT count(*) FROM posts p WHERE user = ?", [$uid]);
} elseif ($viewmode == "time") {
	$mintime = ($time > 0 && $time <= 2592000 ? time() - $time : 86400);

	pageheader('Latest posts');

	$posts = $sql->query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.id tid, f.id fid, t.title ttitle, t.forum tforum
			FROM posts p
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			LEFT JOIN threads t ON p.thread = t.id
			LEFT JOIN forums f ON f.id = t.forum
			WHERE p.date > ? AND ? >= f.minread
			ORDER BY p.date DESC
			LIMIT ?,?",
		[$mintime, $loguser['powerlevel'], $offset, $ppp]);

	$thread['posts'] = $sql->result("SELECT count(*) FROM posts WHERE date > ?", [$mintime]);
} else
	pageheader();

$pagelist = '';
if ($thread['posts'] > $ppp) {
	if ($viewmode == "thread")		$furl = "thread.php?id=$tid";
	elseif ($viewmode == "user")	$furl = "thread.php?user=$uid";
	elseif ($viewmode == "time")	$furl = "thread.php?time=$time";
	$pagelist = '<br>'.pagelist($thread['posts'], $ppp, $furl, $page, true);
}

if ($viewmode == "thread") {
	$topbot = [
		'breadcrumb' => ['forum.php?id='.$thread['forum'] => $thread['ftitle']],
		'title' => esc($thread['title'])
	];

	$faccess = $sql->fetch("SELECT id,minreply FROM forums WHERE id = ?",[$thread['forum']]);
	if ($faccess['minreply'] <= $loguser['powerlevel']) {
		if ($loguser['powerlevel'] > 1 && $thread['closed'])
			$topbot['actions'] = ['none' => 'Thread closed', "newreply.php?id=$tid" => 'New reply'];
		elseif ($thread['closed'])
			$topbot['actions'] = ['none' => 'Thread closed'];
		else
			$topbot['actions'] = ["newreply.php?id=$tid" => 'New reply'];
	}
} elseif ($viewmode == "user") {
	$topbot = [
		'breadcrumb' => ["profile.php?id=$uid" => $user['name']],
		'title' => 'Posts'
	];
} elseif ($viewmode == "time") {
	$topbot = [];
	$time = $_GET['time'];
}

$modlinks = '';
if (isset($tid) && ($loguser['powerlevel'] > 1 || ($loguser['id'] == $thread['user'] && !$thread['closed'] && $loguser['powerlevel'] > 0))) {
	$link = "<a href=javascript:submitmod";
	if ($loguser['powerlevel'] > 1) {
		$stick = '<li>'.$link.($thread['sticky'] ? "('unstick')>Unstick" : "('stick')>Stick").'</a></li>';
		$close = '<li>'.$link.($thread['closed'] ? "('open')>Open" : "('close')>Close").'</a></li>';
		$trash = ($thread['forum'] != $trashid ? '<li><a href=javascript:submitmod(\'trash\') onclick="trashConfirm(event)">Trash</a></li>' : '');

		$edit = '<li><a href="javascript:showrbox()">Rename</a></li><li><a href="javascript:showmove()">Move</a></li>';

		$fmovelinks = addslashes(forumlist($thread['forum']))
		.	'<input type="submit" id="move" value="Submit" name="movethread" onclick="submitmove(movetid())">';
	} else {
		$fmovelinks = $stick = $close = $trash = '';
		$edit = '<a href=javascript:showrbox()>Rename</a>';
	}

	$renamefield = addcslashes(
		'<input type="text" name="title" id="title" size=60 maxlength=255 value="'.esc($thread['title']).'">'.
		'<input type="submit" name="submit" value="Rename" onclick="submitmod(\'rename\')">',
	"'"); //because of javascript, single quotes will gum up the works

	$threadtitle = addcslashes(htmlentities($thread['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8'), "'");

	$modlinks = <<<HTML
<form action="thread.php?id=$tid" method="post" name="mod" id="mod">
<table class="c1"><tr class="n2">
	<td class="b n2">
		<span id="moptions">Thread options: <ul class="menulisting" style="margin-left:10px">$stick$close$trash$edit</ul></span>
		<script>
moptions = document.getElementById('moptions');
function showrbox() { moptions.innerHTML = 'Rename thread: $renamefield'; }
function showmove() { moptions.innerHTML = 'Move to: $fmovelinks'; }
		</script>
		<input type="hidden" id="arg" name="arg" value="">
		<input type="hidden" id="action" name="action" value="">
	</td>
</table>
</form>
HTML;
}

RenderPageBar($topbot);

if (isset($time)) {
	?><table class="c1 autowidth">
		<tr class="h"><td class="b">Latest Posts</td></tr>
		<tr><td class="b n1 center">
			<a href="forum.php?time=<?=$time ?>">By Threads</a> | By Posts (<a href="rss.php">RSS</a>)<br><br>
			<?=timelink(900,'thread').' | '.timelink(3600,'thread').' | '.timelink(86400,'thread').' | '.timelink(604800,'thread') ?>
		</td></tr>
	</table><?php
}

echo "$modlinks$pagelist";

for ($i = 1; $post = $posts->fetch(); $i++) {
	$pthread = [];
	if (isset($uid) || isset($time)) {
		$pthread['id'] = $post['tid'];
		$pthread['title'] = $post['ttitle'];
	}
	if (!isset($_GET['pin']) || $post['id'] != $_GET['pin']) {
		//$post['maxrevision'] = $post['revision']; // not pinned, hence the max. revision equals the revision we selected
		$post['maxrevision'] = 1;
	} else
		$post['maxrevision'] = $post['cur_revision'];

	if (isset($thread['forum']) && $loguser['powerlevel'] > 1 && isset($_GET['pin']) && $post['id'] == $_GET['pin'])
		$post['deleted'] = false;

	echo "<br>".threadpost($post, $pthread);
}

if_empty_query($i, "No posts were found.", 0, true);

echo "$pagelist".(!isset($time) ? '<br>' : '');

if (isset($thread['id']) && $loguser['powerlevel'] >= $faccess['minreply'] && !$thread['closed']) {
	?><form action="newreply.php?id=<?=$tid?>" method="post">
<table class="c1">
	<tr class="h"><td class="b h" colspan=2>Warp Whistle Reply</a></td>
	<tr>
		<td class="b n1 center" width="120">Format:</td>
		<td class="b n2"><?=posttoolbar() ?></td>
	</tr><tr>
		<td class="b n1 center">Reply:</td>
		<td class="b n2"><textarea wrap="virtual" name="message" id="message" rows="8" cols="80"></textarea></td>
	</tr><tr class="n1">
		<td class="b"></td>
		<td class="b">
			<input type="submit" name="action" value="Submit">
			<input type="submit" name="action" value="Preview">
		</td>
	</tr>
</table></form><br><?php
}

RenderPageBar($topbot);

pagefooter();
