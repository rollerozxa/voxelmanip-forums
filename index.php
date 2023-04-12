<?php
require('lib/common.php');

$action = $_GET['action'] ?? null;

//mark forum read
if ($log && $action == 'markread') {
	$fid = $_GET['fid'];
	if ($fid == 'all') {
		//mark all read
		$sql->query("DELETE FROM threadsread WHERE uid = ?", [$loguser['id']]);
		$sql->query("REPLACE INTO forumsread (uid,fid,time) SELECT ?, f.id, ? FROM forums f", [$loguser['id'], time()]);
		redirect('index.php');
	} else {
		//delete obsolete threadsread entries
		$sql->query("DELETE r FROM threadsread r LEFT JOIN threads t ON t.id = r.tid WHERE t.forum = ? AND r.uid = ?", [$fid, $loguser['id']]);
		//add new forumsread entry
		$sql->query("REPLACE INTO forumsread VALUES (?,?,?)", [$loguser['id'], $fid, time()]);
		redirect("forum.php?id=$fid");
	}
}

pageheader();

$categs = $sql->query("SELECT id,title FROM categories ORDER BY ord,id");
while ($c = $categs->fetch())
	$categ[$c['id']] = $c['title'];

$forums = $sql->query("SELECT f.*, ".($log ? "r.time rtime, " : '').userfields('u', 'u')." "
		. "FROM forums f "
		. "LEFT JOIN users u ON u.id=f.lastuser "
		. "LEFT JOIN categories c ON c.id=f.cat "
		. ($log ? "LEFT JOIN forumsread r ON r.fid = f.id AND r.uid = ".$loguser['id'] : '')
		. " WHERE ? >= f.minread AND f.cat != 0 "
		. " ORDER BY c.ord,c.id,f.ord,f.id ",
		[$loguser['rank']]);
$cat = -1;

if ($log)
	echo '<a class="f-right" href="./?action=markread&fid=all">Mark all forums read</a>';

announcement_row();
echo '<br>';

while ($forum = $forums->fetch()) {
	if ($forum['cat'] != $cat) {
		if ($cat != -1) echo '</table><br>';

		$cat = $forum['cat'];
		?><table class="c1">
		<tr class="h">
			<td class="b h" width="32">&nbsp;</td>
			<td class="b h"><?=$categ[$cat] ?></td>
			<td class="b h nom" width="60">Threads</td>
			<td class="b h nom" width="60">Posts</td>
			<td class="b h" width="190">Last post</td>
		</tr><?php
	}

	$lastpost = 'None';
	if ($forum['posts'] > 0 && $forum['lastdate'] > 0)
		$lastpost = sprintf(
			'<nobr>%s</nobr><br><span class=sfont>by %s <a href="thread.php?pid=%s#%s">&raquo;</a></span>',
		dateformat($forum['lastdate']), userlink($forum, 'u'), $forum['lastid'], $forum['lastid']);

	$status = ($forum['lastdate'] > ($log ? $forum['rtime'] : time() - 3600) ? rendernewstatus("n") : '');

	?><tr class="center">
		<td class="b n1"><?=$status ?></td>
		<td class="b n2 left">
			<?=($forum['minread'] > 0 ? '(' : '') ?><a href="forum.php?id=<?=$forum['id'] ?>"><?=$forum['title'] ?></a><?=($forum['minread'] > 0 ? ')' : '') ?>
			<br><span class="sfont"><?=str_replace("%%%RANDOM%%%", $randdesc[array_rand($randdesc)], $forum['descr']) ?></span>
		</td>
		<td class="b n1 nom"><?=$forum['threads'] ?></td>
		<td class="b n1 nom"><?=$forum['posts'] ?></td>
		<td class="b n2"><?=$lastpost ?></td>
	</tr><?php
}
echo '</table>';
pagefooter();
