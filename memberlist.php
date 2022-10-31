<?php
require('lib/common.php');
pageheader('Memberlist');

$sort = $_GET['sort'] ?? 'posts';
$pow = $_GET['pow'] ?? '';
$page = $_GET['page'] ?? '';
$orderby = $_GET['orderby'] ?? '';

$ppp = 50;
if ($page < 1) $page = 1;

$sortby = ($orderby == 'a' ? " ASC" : " DESC");

$order = 'posts' . $sortby;
if ($sort == 'name') $order = 'name' . $sortby;
if ($sort == 'reg') $order = 'joined' . $sortby;

$where = (is_numeric($pow) ? "WHERE powerlevel = $pow" : '');

$users = $sql->query("SELECT * FROM users $where ORDER BY $order LIMIT ?,?", [($page - 1) * $ppp, $ppp]);
$num = $sql->result("SELECT COUNT(*) FROM users $where");

$pagelist = '';
if ($num >= $ppp) {
	$pagelist = 'Pages:';
	for ($p = 1; $p <= 1 + floor(($num - 1) / $ppp); $p++)
		$pagelist .= ($p == $page ? " $p" : ' ' . mlink($p, $sort, $pow, $p, $orderby) . "</a>");
}

$groups = [];
foreach ($powerlevels as $id => $title) {
	$grouptitle = '<span style="color:#'.powIdToColour($id).'">'.$title.'</span>';
	$groups[] = mlink($grouptitle, $sort, $id, $page, $orderby);
}

?>
<table class="c1 autowidth">
	<tr class="h"><td class="b h" colspan="2">Memberlist</td></tr>
	<tr>
		<td class="b n1 center">Sort by:</td>
		<td class="b n2 center">
			<?=mlink('Posts', '', $pow, $page, $orderby) ?> |
			<?=mlink('Username', 'name', $pow, $page, $orderby) ?> |
			<?=mlink('Registration date', 'reg', $pow, $page, $orderby) ?>
			<span class="f-right">
				<?=mlink('[ &#x25BC; ]', $sort, $pow, $page, 'd') ?>
				<?=mlink('[ &#x25B2; ]', $sort, $pow, $page, 'a') ?>
			</span>
		</td>
	</tr><tr>
		<td class="b n1 center">Group:</td>
		<td class="b n2 center">
			<?php foreach ($groups as $group) echo $group.' | ' ?>
			<?=mlink('All', $sort, '', $page, $orderby) ?>
		</td>
	</tr>
</table><br>
<table class="c1">
	<tr class="h">
		<td class="b h" width="32">#</td>
		<td class="b h" width="62">Picture</td>
		<td class="b h">Name</td>
		<td class="b h" width="145">Registered on</td>
		<td class="b h" width="75">Posts</td>
	</tr>
<?php

for ($i = 1; $user = $users->fetch(); $i++) {
	$tr = ($i % 2 ? 1 : 2);
	$picture = ($user['usepic'] ? '<img src="userpic/'.$user['id'].'" width="60" height="60">' : '');
	?><tr class="n<?=$tr ?>" style="height:69px">
		<td class="b center"><?=$user['id'] ?>.</td>
		<td class="b center"><?=$picture ?></td>
		<td class="b"><?=userlink($user) ?></td>
		<td class="b center"><?=dateformat($user['joined']) ?></td>
		<td class="b center"><?=$user['posts'] ?></td>
	</tr><?php
}
if_empty_query($i, "No users found.", 5);
echo '</table>';

if ($pagelist)
	echo '<br>'.$pagelist.'<br>';
pagefooter();

function mlink($name, $sort, $pow, $page, $orderby) {
	return '<a href="memberlist.php?'.
		($sort ? "sort=$sort" : '').($pow != '' ? "&pow=$pow" : '').($page != 1 ? "&page=$page" : '').
		($orderby != '' ? "&orderby=$orderby" : '').'">'
		.$name.'</a>';
}
