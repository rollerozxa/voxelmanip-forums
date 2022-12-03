<?php
require("lib/common.php");

$uid = (int)$_GET['id'] ?? -1;
if ($uid < 0) error("You must specify a user ID!");

$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$uid]);
if (!$user) error("This user does not exist!");

pageheader("Profile for ".$user['name']);

$days = (time() - $user['joined']) / 86400;

$lastpostlink = '';
if ($user['posts'] != 0) {
	$thread = $sql->fetch("SELECT p.id, t.title ttitle, f.title ftitle, t.forum FROM forums f
		LEFT JOIN threads t ON t.forum = f.id LEFT JOIN posts p ON p.thread = t.id
		WHERE p.date = ? AND p.user = ? AND ? >= f.minread", [$user['lastpost'], $uid, $loguser['powerlevel']]);

	if ($thread)
		$lastpostlink = sprintf(
			'<br>in <a href="thread.php?pid=%s#%s">%s</a> (<a href="forum.php?id=%s">%s</a>)',
		$thread['id'], $thread['id'], esc($thread['ttitle']), $thread['forum'], esc($thread['ftitle']));
	else
		$lastpostlink = "<br>in <i>a private forum</i>";
}

$birthday = '';
if ($user['birthday']) {
	$bd1 = new DateTime($user['birthday']);
	$bd2 = new DateTime(date("Y-m-d"));
	$birthday = date("F j, Y", strtotime($user['birthday']))
		.' ('.intval($bd1->diff($bd2)->format("%Y")).' years old)';
}

$email = ($user['email'] && $user['showemail'] ? str_replace(".", "<b> (dot) </b>", str_replace("@", "<b> (at) </b>", esc($user['email']))) : '');

$post = ['date' => time(), 'text' => $samplepost, 'headerbar' => 'Sample post'];

foreach ($user as $field => $val)
	$post['u'.$field] = $val;

$links = [
	"forum.php?user=$uid" => 'View threads',
	"thread.php?user=$uid" => 'Show posts'];

$isblocked = $sql->result("SELECT COUNT(*) FROM blockedlayouts WHERE user = ? AND blockee = ?", [$uid, $loguser['id']]);
if ($log) {
	if (isset($_GET['toggleblock'])) {
		if (!$isblocked)
			$sql->query("INSERT INTO blockedlayouts (user, blockee) VALUES (?,?)", [$uid, $loguser['id']]);
		else
			$sql->query("DELETE FROM blockedlayouts WHERE user = ? AND blockee = ?", [$uid, $loguser['id']]);

		$isblocked = !$isBlocked;
	}

	$links["profile.php?id=$uid&toggleblock"] = ($isblocked ? 'Unblock' : 'Block').' layout';

	if ($loguser['powerlevel'] > 0)
		$links["sendprivate.php?uid=$uid"] = 'Send PM';
}

if ($loguser['powerlevel'] > 3)
	$links["private.php?id=$uid"] = 'Show PMs';
if ($loguser['powerlevel'] > 2 && $loguser['powerlevel'] > $user['powerlevel'])
	$links["editprofile.php?id=$uid"] = 'Edit user';

if ($loguser['powerlevel'] > 1) {
	if ($user['powerlevel'] != -1)
		$links["banmanager.php?id=$uid"] = 'Ban user';
	else
		$links["banmanager.php?unban&id=$uid"] = 'Unban user';
}

//timezone calculations
$usertz = new DateTimeZone($user['timezone'] ?: $defaulttimezone);
$userct = date_format(new DateTime("now", $usertz), $dateformat);
$logtz = new DateTimeZone($loguser['timezone']);
$usertzoff = $usertz->getOffset(new DateTime("now"));
$logtzoff = $logtz->getOffset(new DateTime("now"));

$profilefields = [
	"General information" => [
		'Group'		=> powIdToName($user['powerlevel']),
		'Total posts'	=> sprintf('%s (%1.02f per day)', $user['posts'], $user['posts'] / $days),
		'Total threads'=> sprintf('%s (%1.02f per day)' ,$user['threads'], $user['threads'] / $days),
		'Registered on'=> dateformat($user['joined']).' ('.timeunits($days * 86400).' ago)',
		'Last post'	=> ($user['lastpost'] ? dateformat($user['lastpost'])." (".timeunits(time()-$user['lastpost'])." ago)" : "None").$lastpostlink,
		'Last view'	=> sprintf(
				'%s (%s ago) %s %s',
			dateformat($user['lastview']), timeunits(time() - $user['lastview']),
			($user['url'] ? sprintf('<br>at <a href="%s">%s</a>', esc($user['url']), esc($user['url'])) : ''),
			($loguser['powerlevel'] > 2 ? '<br>from IP: <span class="sensitive">'.$user['ip'].'</span>' : ''))
	],
	"User information" => [
		'Bio'		=> ($user['bio'] ? postfilter($user['bio']) : ''),
		'Location'	=> ($user['location'] ? esc($user['location']) : ''),
		'Email'	=> $email,
		'Birthday'	=> $birthday,
	],
	"User settings" => [
		'Theme' => themename((string)$user['theme'] ?: $defaulttheme),
		'Time offset' => sprintf("%d:%02d from you (Current time: %s)", ($usertzoff - $logtzoff) / 3600, abs(($usertzoff - $logtzoff) / 60) % 60, $userct),
		'Items per page' => sprintf('%d posts, %d threads', $user['ppp'], $user['tpp'])
	]
];

$topbot = [ 'title' => $user['name'], 'actions' => $links ];

RenderPageBar($topbot);
echo '<br>';

foreach ($profilefields as $k => $v) {
	echo '<table class="c1"><tr class="h"><td class="b h" colspan="2">'.$k.'</td></tr>';
	foreach ($v as $title => $value)
		echo '<tr><td class="b n1" width="130"><b>'.$title.'</b></td><td class="b n2">'.$value.'</td>';

	echo '</table><br>';
}

echo threadpost($post).'<br>';
RenderPageBar($topbot);
pagefooter();
