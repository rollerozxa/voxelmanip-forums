<?php
require('lib/common.php');

needs_login();

$page = $_GET['page'] ?? null;
if (!$page) $page = 1;
$view = $_GET['view'] ?? 'read';

if ($view == 'sent') {
	$fieldn = 'to';
	$fieldn2 = 'from';
	$sent = true;
} else {
	$fieldn = 'from';
	$fieldn2 = 'to';
	$sent = false;
}

$id = ($loguser['rank'] > 3 ? ($_GET['id'] ?? 0) : 0);

$showdel = isset($_GET['showdel']);

if (isset($_GET['action']) && $_GET['action'] == "del") {
	$owner = $sql->result("SELECT user$fieldn2 FROM pmsgs WHERE id = ?", [$id]);
	if ($loguser['rank'] > 3 || $owner == $loguser['id'])
		$sql->query("UPDATE pmsgs SET del_$fieldn2 = ? WHERE id = ?", [!$showdel, $id]);
	else
		error("You are not allowed to (un)delete that message.");

	$id = 0;
}

$ptitle = 'Private messages' . ($sent ? ' (sent)' : '');
if ($id && $loguser['rank'] > 3) {
	$user = $sql->fetch("SELECT id,name,customcolour,rank FROM users WHERE id = ?", [$id]);
	if ($user == null) error("User doesn't exist.");
	pageheader($user['name']."'s ".strtolower($ptitle));
	$title = userlink($user)."'s ".strtolower($ptitle);
} else {
	$id = $loguser['id'];
	pageheader($ptitle);
	$title = $ptitle;
}

$tpp = $loguser['tpp'];
$ufields = userfields('u', 'u');
$pmsgc = $sql->result("SELECT COUNT(*) FROM pmsgs WHERE user$fieldn2 = ? AND del_$fieldn2 = ?", [$id, $showdel]);
$pmsgs = $sql->query("SELECT $ufields, p.* FROM pmsgs p
					LEFT JOIN users u ON u.id = p.user$fieldn
					WHERE p.user$fieldn2 = ? AND del_$fieldn2 = ?
					ORDER BY p.unread DESC, p.date DESC
					LIMIT ?,?",
				[$id, $showdel, (($page - 1) * $tpp), $tpp]);

$topbot = ['title' => $title];

if ($sent)
	$topbot['actions'] = ['private.php'.($id != $loguser['id'] ? "?id=$id&" : '') => "View received"];
else
	$topbot['actions'] = ['private.php?'.($id != $loguser['id'] ? "id=$id&" : '').'view=sent' => "View sent"];

$topbot['actions']['sendprivate.php'] = 'Send new';

$fpagelist = '<br>';
if ($pmsgc > $loguser['tpp']) {
	if ($id != $loguser['id'])
		$furl = "private.php?id=$id&view=$view";
	else
		$furl = "private.php?view=$view";
	$fpagelist = pagelist($pmsgc, $loguser['tpp'], $furl, $page).'<br>';
}

RenderPageBar($topbot);
?><br>
<table class="c1">
	<tr class="h">
		<td class="b" width="17"></td>
		<td class="b" width="24">&nbsp;</td>
		<td class="b">Title</td>
		<td class="b" width="130"><?=ucfirst($fieldn) ?></td>
		<td class="b" width="130">Sent on</td>
	</tr>
	<?php
	for ($i = 1; $pmsg = $pmsgs->fetch(); $i++) {
		$status = ($pmsg['unread'] ? rendernewstatus("n") : '');

		$tr = ($i % 2 ? 2 : 3);
		?>
		<tr class="n<?=$tr ?> center">
			<td class="b n2">
				<a href="private.php?action=del&id=<?=$pmsg['id'] ?>&view=<?=$view ?>"><img src="img/smilies/no.png" align="absmiddle"></a>
			</td>
			<td class="b n1"><?=$status ?></td>
			<td class="b left wbreak"><a href="showprivate.php?id=<?=$pmsg['id'] ?>"><?=esc($pmsg['title'] ?: 'untitled') ?></a></td>
			<td class="b"><?=userlink($pmsg, 'u') ?></td>
			<td class="b"><nobr><?=dateformat($pmsg['date']) ?></nobr></td>
		</tr>
		<?php
	}
	if_empty_query($i, "There are no private messages.", 5);
	?>
</table>
<?php
echo $fpagelist;
RenderPageBar($topbot);
pagefooter();