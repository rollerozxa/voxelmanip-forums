<?php
require('lib/common.php');

$page = (int)($_GET['page'] ?? 1);
$fid = (int)($_GET['id'] ?? 0);
$uid = (int)($_GET['user'] ?? 0);
$time = (int)($_GET['time'] ?? 0);

$tpp = $loguser['tpp'];
$offset = (($page - 1) * $tpp);

$topbot = [];

$fieldlist = userfields('u1', 'u1').",".userfields('u2', 'u2');
$isread = $threadsread = '';

if ($log) {
	$isread = ($log ? ', (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread ' : '');
	$threadsread = ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id]) "
		."LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$loguser[id]) " : '');
}

$ufields = userfields('u1', 'u1').",".userfields('u2', 'u2').",";
if ($fid) {
	if ($log) {
		$forum = $sql->fetch("SELECT f.*, r.time rtime FROM forums f LEFT JOIN forumsread r ON (r.fid = f.id AND r.uid = ?)
			WHERE f.id = ? AND ? >= minread", [$loguser['id'], $fid, $loguser['rank']]);
		if (!$forum['rtime']) $forum['rtime'] = 0;

		$isread = ", (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<'$forum[rtime]') isread";
		$threadsread = "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id])";
	} else
		$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND ? >= minread", [$fid, $loguser['rank']]);

	if (!isset($forum['id'])) error("Forum does not exist.");

	//append the forum's title to the site title
	pageheader($forum['title'], $fid);

	$threads = $sql->query("SELECT $ufields t.* $isread FROM threads t
			LEFT JOIN users u1 ON u1.id = t.user
			LEFT JOIN users u2 ON u2.id = t.lastuser
			$threadsread
			WHERE t.forum = ?
			ORDER BY t.sticky DESC, t.lastdate DESC
			LIMIT ?,?",
		[$fid, $offset, $tpp]);

	$topbot = [
		'title' => $forum['title'],
		'actions' => []
	];
	if ($log)
		$topbot['actions']["index.php?action=markread&fid=$fid"] = "Mark forum read";

	if ($loguser['rank'] >= $forum['minthread'])
		$topbot['actions']["newthread.php?id=$fid"] = 'New thread';

} elseif ($uid) {
	$user = $sql->fetch("SELECT name FROM users WHERE id = ?", [$uid]);

	if (!$user) error("User does not exist.");

	pageheader("Threads by ".$user['name']);

	$threads = $sql->query("SELECT $ufields t.*, f.id fid $isread, f.title ftitle FROM threads t
			LEFT JOIN users u1 ON u1.id = t.user
			LEFT JOIN users u2 ON u2.id = t.lastuser
			LEFT JOIN forums f ON f.id = t.forum
			$threadsread
			WHERE t.user = ? AND ? >= minread
			ORDER BY t.sticky DESC, t.lastdate DESC
			LIMIT ?,?",
		[$uid, $loguser['rank'], $offset, $tpp]);

	$forum['threads'] = $sql->result("SELECT count(*) FROM threads t
			LEFT JOIN forums f ON f.id = t.forum
			WHERE t.user = ? AND ? >= minread",
		[$uid, $loguser['rank']]);

	$topbot = [
		'breadcrumb' => ["profile.php?id=$uid" => $user['name']],
		'title' => 'Threads'
	];
} elseif ($time) {
	$mintime = ($time > 0 && $time <= 2592000 ? time() - $time : 86400);

	pageheader('Latest posts');

	$threads = $sql->query("SELECT $ufields t.*, f.id fid $isread, f.title ftitle
			FROM threads t
			LEFT JOIN users u1 ON u1.id = t.user
			LEFT JOIN users u2 ON u2.id = t.lastuser
			LEFT JOIN forums f ON f.id = t.forum
			$threadsread
			WHERE t.lastdate > ? AND ? >= f.minread
			ORDER BY t.lastdate DESC
			LIMIT ?,?",
		[$mintime, $loguser['rank'], $offset, $tpp]);

	$forum['threads'] = $sql->result("SELECT count(*) FROM threads t
			LEFT JOIN forums f ON f.id = t.forum
			WHERE t.lastdate > ? AND ? >= f.minread",
		[$mintime, $loguser['rank']]);

} else {
	error("Forum does not exist.");
}

$showforum = $time ?? $uid;

$fpagelist = '';
if ($forum['threads'] > $tpp) {
	if ($fid)		$furl = "forum.php?id=$fid";
	elseif ($uid)	$furl = "forum.php?user=$uid";
	elseif ($time)	$furl = "forum.php?time=$time";
	$fpagelist = '<br>'.pagelist($forum['threads'], $tpp, $furl, $page);
}

RenderPageBar($topbot);

if ($time) {
	?><table class="c1 autowidth">
		<tr class="h"><td class="b">Latest Threads</td></tr>
		<tr><td class="b n1 center">
			By Threads | <a href="thread.php?time=<?=$time ?>">By Posts</a> (<a href="rss.php">RSS</a>)<br><br>
			<?=timelink(900,'forum').' | '.timelink(3600,'forum').' | '.timelink(86400,'forum').' | '.timelink(604800,'forum') ?>
		</td></tr>
	</table><?php
}

if ($fid) announcement_row();

?><br>
<table class="c1">
	<tr class="h">
		<td class="b h" width="32">&nbsp;</td>
		<?=($showforum ? '<td class="b h">Forum</td>' : '') ?>
		<td class="b h">Title</td>
		<td class="b h nom" width="130">Started by</td>
		<td class="b h nom" width="60">Replies</td>
		<td class="b h nom" width="60">Views</td>
		<td class="b h" width="190">Last post</td>
	</tr><?php
$lsticky = 0;

for ($i = 1; $thread = $threads->fetch(); $i++) {
	$pagelist = ' '.pagelist($thread['posts'], $loguser['ppp'], 'thread.php?id='.$thread['id'], 0, false, true);

	$status = ($thread['closed'] ? 'o' : '');

	if ($log) {
		if (!$thread['isread']) $status .= 'n';
	} else {
		if ($thread['lastdate'] > (time() - 3600)) $status .= 'n';
	}

	$status = rendernewstatus($status);

	if ($thread['sticky'] && !$showforum)
		$tr = 1;
	else
		$tr = ($i % 2 ? 2 : 3);

	if (!$thread['sticky'] && $lsticky && !$showforum)
		echo '<tr class="c"><td class="b" colspan="7" style="font-size:1px">&nbsp;</td>';
	$lsticky = $thread['sticky'];

	?><tr class="n<?=$tr ?> center">
		<td class="b n1"><?=$status ?></td>
		<?=($showforum ? sprintf('<td class="b"><a href="forum.php?id=%s">%s</a></td>', $thread['fid'], $thread['ftitle']) : '')?>
		<td class="b left wbreak">
			<a href="thread.php?id=<?=$thread['id'] ?>"><?=esc($thread['title']) ?></a><?=$pagelist ?>
		</td>
		<td class="b nom"><?=userlink($thread, 'u1') ?></td>
		<td class="b nom"><?=$thread['posts']-1 ?></td>
		<td class="b nom"><?=$thread['views'] ?></td>
		<td class="b">
			<nobr><?=dateformat($thread['lastdate']) ?></nobr><br>
			<span class="sfont">by <?=userlink($thread, 'u2') ?> <a href="thread.php?pid=<?=$thread['lastid'] ?>#<?=$thread['lastid'] ?>">&raquo;</a></span>
		</td>
	</tr><?php
}
if_empty_query($i, "No threads found.", ($showforum ? 7 : 6));

echo "</table>$fpagelist".(!$time ? '<br>' : '');

RenderPageBar($topbot);
pagefooter();
