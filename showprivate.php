<?php
require('lib/common.php');
needs_login();

$fieldlist = userfields('u', 'u').','.userfields_post();

$pid = $_GET['id'] ?? null;

if (!$pid) noticemsg("Error", "Private message does not exist.", true);

$pmsg = $sql->fetch("SELECT $fieldlist p.* FROM pmsgs p LEFT JOIN users u ON u.id = p.userfrom WHERE p.id = ?", [$pid]);
if ($pmsg == null) noticemsg("Error", "Private message does not exist.", true);
$tologuser = ($pmsg['userto'] == $loguser['id']);

if ((!$tologuser && $pmsg['userfrom'] != $loguser['id']) && !($loguser['powerlevel'] > 3))
	noticemsg("Error", "Private message does not exist.", true);
elseif ($tologuser && $pmsg['unread'])
	$sql->query("UPDATE pmsgs SET unread = 0 WHERE id = ?", [$pid]);

pageheader($pmsg['title']);

$pagebar = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'],
		['href' => "private.php".(!$tologuser ? '?id='.$pmsg['userto'] : ''), 'title' => 'Private messages']
	],
	'title' => ($pmsgs['title'] ? esc($pmsgs['title']) : '(untitled)'),
	'actions' => [['href' => "sendprivate.php?pid=$pid", 'title' => 'Reply']]
];

$pmsg['id'] = 0;

RenderPageBar($pagebar);
echo '<br>' . threadpost($pmsg) . '<br>';
RenderPageBar($pagebar);

pagefooter();