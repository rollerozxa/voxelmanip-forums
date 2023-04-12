<?php
require("lib/common.php");

pageheader("Search");

$query = $_GET['q'] ?? '';
$where = $_GET['w'] ?? 0;
?>
<form action="search.php" method="get">
<table class="c1">
	<tr class="h"><td class="b h" colspan="2">Search</td>
	<tr>
		<td class="n1 center" width="150">Search for</td>
		<td class="n1"><input type="text" name="q" size="40" value="<?=esc($query) ?>"></td>
	</tr><tr>
		<td class="n1"></td>
		<td class="n1">
			in <input type="radio" class="radio" name="w" value="0" id="threadtitle" <?=(($where == 0) ? 'checked' : '') ?>><label for="threadtitle">thread title</label>
			<input type="radio" class="radio" name="w" value="1" id="posttext" <?=(($where == 1) ? 'checked' : '') ?>><label for="posttext">post text</label>
		</td>
	</tr><tr>
		<td class="n1"></td>
		<td class="n1"><input type="submit" name="action" value="Search"></td>
	</tr>
</table>
</form>
<?php
if (!isset($_GET['action']) || strlen($query) < 3) {
	if (isset($_GET['action']) && strlen($query) < 3) {
		echo '<br><table class="c1"><tr><td class="b n1 center">Please enter more than 2 characters!</td></tr></table>';
	}
	pagefooter();
	die();
}

echo '<br><table class="c1"><tr class="h"><td class="b h" style="border-bottom:0" colspan=3>Results</td></tr>';

$ufields = userfields('u','u');
if ($where == 1) {
	$fieldlist = userfields_post();
	$posts = $sql->query("SELECT $ufields, $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.id tid, t.title ttitle, t.forum tforum
			FROM posts p
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			LEFT JOIN threads t ON p.thread = t.id
			LEFT JOIN forums f ON f.id = t.forum
			WHERE pt.text LIKE CONCAT('%', ?, '%') AND ? >= f.minread
			ORDER BY p.id",
		[$query, $loguser['rank']]);

	for ($i = 1; $post = $posts->fetch(); $i++) {
		if ($i == 1) echo '</table>';
		$pthread['id'] = $post['tid'];
		$pthread['title'] = $post['ttitle'];
		echo '<br>' . threadpost($post,$pthread);
	}

	if_empty_query($i, 'No posts found.', 1, false);
	if ($i == 1) echo '</table>';
} else {
	$page = $_GET['page'] ?? 1;
	if ($page < 1) $page = 1;

	$threads = $sql->query("SELECT $ufields, t.*
			FROM threads t
			LEFT JOIN users u ON u.id = t.user
			LEFT JOIN forums f ON f.id = t.forum
			WHERE t.title LIKE CONCAT('%', ?, '%') AND ? >= f.minread
			ORDER BY t.lastdate DESC LIMIT ?,?",
		[$query, $loguser['rank'], ($page - 1) * $loguser['tpp'], $loguser['tpp']]);

	$threadcount = $sql->result("SELECT COUNT(*) FROM threads t
			LEFT JOIN forums f ON f.id=t.forum
			WHERE t.title LIKE CONCAT('%', ?, '%') AND ? >= f.minread",
		[$query, $loguser['rank']]);

	?><tr class="c">
		<td class="b h">Title</td>
		<td class="b h" style="min-width:80px">Started by</td>
		<td class="b h" width="200">Date</td>
	</tr><?php

	for ($i = 1; $thread = $threads->fetch(); $i++) {
		$tr = ($i % 2 ? 2 : 3);

		?><tr class="n<?=$tr ?> center">
			<td class="b left wbreak">
				<a href="thread.php?id=<?=$thread['id'] ?>"><?=esc($thread['title']) ?></a> <?=($thread['sticky'] ? ' (Sticky)' : '')?>
			</td>
			<td class="b"><?=userlink($thread,'u') ?></td>
			<td class="b"><?=dateformat($thread['lastdate']) ?></td>
		</tr><?php
	}
	if_empty_query($i, "No threads found.", 6);

	$query = urlencode($query);
	echo '</table>'.pagelist($threadcount, $loguser['tpp'], "search.php?q=$query&action=Search&w=0", $page);
}

pagefooter();
