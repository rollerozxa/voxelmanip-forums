<?php
if (!file_exists('conf/config.php'))
	die('Great job getting the files onto a web server. Now install it.');

$start = microtime(true);

$rankset_names = ['None'];

require('conf/config.php');
foreach (glob("lib/*.php") as $filename)
	require_once($filename);

$userip = $_SERVER['REMOTE_ADDR'] ?? '';
$useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$url = $_SERVER['REQUEST_URI'] ?? '';

$log = false;

if (isset($_COOKIE['token'])) {
	if ($sql->result("SELECT id FROM users WHERE token = ?", [$_COOKIE['token']])) {
		$log = true;
		$loguser = $sql->fetch("SELECT * FROM users WHERE token = ?", [$_COOKIE['token']]);
	} else
		setcookie('token', 0);
}

if (!$log) {
	$loguser = [];
	$loguser['id'] = $loguser['powerlevel'] = 0;
	$loguser['theme'] = $defaulttheme;
	$loguser['ppp'] = $loguser['tpp'] = 20;
}

if ($lockdown && $loguser['powerlevel'] < 1) {
	echo <<<HTML
<body style="background-color:#B02020;max-width:500px;color:#ffffff;margin:40px auto;">
	<p>The board is currently in maintenance mode.</p>
	<p>Please forgive any inconvenience caused and stand by until the underlying issues have been resolved.</p>
</body>
HTML;
	die();
}

if (!$log || !$loguser['timezone'])
	$loguser['timezone'] = $defaulttimezone;

$dateformat = 'Y-m-d H:i';

date_default_timezone_set($loguser['timezone']);
dobirthdays(); //Called here to account for timezone bugs.

if ($loguser['ppp'] < 1) $loguser['ppp'] = 20;
if ($loguser['tpp'] < 1) $loguser['tpp'] = 20;

$theme = $override_theme ?? ($_GET['theme'] ?? $loguser['theme']);

if (!is_file("theme/$theme/$theme.css"))
	$theme = $defaulttheme;

//Unban users whose tempbans have expired.
$sql->query("UPDATE users SET powerlevel = 1, title = '', tempbanned = 0 WHERE tempbanned < ? AND tempbanned > 0", [time()]);

$bot = 0;
if (str_replace($botlist, "x", strtolower($useragent)) != strtolower($useragent))
	$bot = 1;

if (!isset($rss)) {
	$sql->query("DELETE FROM guests WHERE date < ?", [(time() - 15*60)]);
	if ($log)
		$sql->query("UPDATE users SET lastview = ?, ip = ?, url = ? WHERE id = ?",
			[time(), $userip, $url, $loguser['id']]);
	else
		$sql->query("REPLACE INTO guests (date, ip, bot) VALUES (?,?,?)", [time(),$userip,$bot]);
}

$sql->query("DELETE FROM ipbans WHERE expires < ? AND expires > 0", [time()]);

$r = $sql->fetch("SELECT * FROM ipbans WHERE ? LIKE ipmask", [$userip]);
if ($r) {
	pageheader('IP banned');
	echo '<table class="c1"><tr class="n2"><td class="b n1 center">Sorry, but your IP address has been banned.</td></tr></table>';
	pagefooter();
	die();
}

function pageheader($pagetitle = '', int $fid = null) {
	global $sql, $log, $loguser, $boardtitle, $boardlogo, $theme, $boarddesc, $userip;

	if ($log)
		$sql->query("UPDATE users SET lastforum = ? WHERE id = ?", [($fid == null ? 0 : $fid), $loguser['id']]);
	else
		$sql->query("UPDATE guests SET lastforum = ? WHERE ip = ?", [($fid == null ? 0 : $fid), $userip]);

	if ($pagetitle) $pagetitle .= " - ";

	$boardlogo = '<a href="./"><img class="boardlogo" src="'.$boardlogo.'" style="max-width:100%"></a>';

	$attn = $sql->result("SELECT attention FROM misc");
	if ($attn)
		$boardlogo = <<<HTML
<table width="100%"><tr>
	<td>$boardlogo</td>
	<td class="nom" width="300">
		<table class="c1 center">
			<tr class="h"><td class="b h">News</td></tr>
			<tr class="n1 center"><td class="b sfont">$attn</td></tr>
		</table>
	</td>
</tr></table>
HTML;

	$links = [
		'./' => 'Main',
		'faq.php' => 'FAQ',
		'memberlist.php' => 'Memberlist',
		'activeusers.php' => 'Active users',
		'thread.php?time=86400' => 'Latest posts',
		'online.php' => 'Online users',
		'search.php' => 'Search'];

	if ($log) {
		if ($loguser['powerlevel'] > 0)
			$userlinks['editprofile.php'] = 'Edit profile';
		if ($loguser['powerlevel'] > 2)
			$userlinks['management.php'] = 'Admin';
	}

	if (!$log) {
		$userlinks['register.php'] = 'Register';
		$userlinks['login.php'] = 'Login';
	} else
		$userlinks['javascript:document.logout.submit()'] = 'Logout';

	if ($log) {
		$unreadpms = $sql->result("SELECT COUNT(*) FROM pmsgs WHERE userto = ? AND unread = 1 AND del_to = 0", [$loguser['id']]);

		$userlink = sprintf(
			'<span class="menulink">%s <a href="private.php"><img src="img/pm%s.png" width="20" alt="PMs" align="abscenter"></a>%s</span>',
		userlink($loguser), (!$unreadpms ? '-off' : ''), ($unreadpms ? " ($unreadpms)" : ''));
	}

	?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
		<title><?=$pagetitle.$boardtitle?></title>
		<?php if (isset($boarddesc)) { ?><meta name="description" content="<?=$boarddesc?>"><?php } ?>
		<link rel="stylesheet" href="theme/common.css">
		<link rel="stylesheet" href="theme/<?=$theme?>/<?=$theme?>.css">
		<link href="rss.php" type="application/atom+xml" rel="alternate">
		<script src="js/tools.js"></script>
	</head>
	<body>
		<table class="c1">
			<tr class="nt n2 center"><td class="b n1 center" colspan="2"><?=$boardlogo?></td></tr>
			<tr class="n2">
				<td class="n2 nb headermenu">
					<?php foreach ($links as $url => $title) echo "<a class=\"menulink\" href=\"$url\">$title</a>"; ?>
				</td>
				<td class="n2 nb headermenu_right">
					<?php echo $userlink??''; foreach ($userlinks as $url => $title) echo "<a class=\"menulink\" href=\"$url\">$title</a>"; ?>
				</td>
			</tr>
		</table>
		<form action="login.php" method="post" name="logout">
			<input type="hidden" name="action" value="logout">
		</form><br><?php

	if ($fid || !$pagetitle) {
		$andlastforum = ($fid != 0 ? " AND lastforum =".$fid : '');

		$onusers = $sql->query("SELECT ".userfields().",lastpost,lastview FROM users WHERE lastview > ? $andlastforum ORDER BY name",
			[(time() - 15*60)]);
		$onuserlist = '';
		$onusercount = 0;
		while ($user = $onusers->fetch()) {
			$onuserlist .= ($onusercount ? ', ' : '').userlink($user);
			$onusercount++;
		}

		$result = $sql->query("SELECT COUNT(*) guest_count, SUM(bot) bot_count FROM guests WHERE date > ? $andlastforum",
			[(time() - 15*60)]);

		while ($data = $result->fetch()) {
			$numbots = $data['bot_count'];
			$numguests = $data['guest_count'] - $numbots;

			if ($numguests)	$onuserlist .= " | $numguests guest".plural($numguests);
			if ($numbots)	$onuserlist .= " | $numbots bot".plural($numbots);
		}
	}

	if ($fid) {
		$fname = $sql->result("SELECT title FROM forums WHERE id = ?", [$fid]);
		$onuserlist = "$onusercount user".plural($onusercount)." currently in $fname".($onusercount > 0 ? ": " : '').$onuserlist;

		?><table class="c1"><tr class="n1"><td class="b n1 center"><?=$onuserlist ?></td></tr></table><br><?php
	} elseif (!$pagetitle) {
		$rbirthdays = $sql->query("SELECT birth, ".userfields()." FROM users WHERE birth LIKE ? ORDER BY name", ['%'.date('m-d')]);

		$birthdays = [];
		while ($user = $rbirthdays->fetch()) {
			$b = explode('-', $user['birth']);
			$birthdays[] = userlink($user)." (".(date("Y") - $b['0']).")";
		}

		$birthdaybox = '';
		if (count($birthdays))
			$birthdaybox = '<tr class="n1 center"><td class="b n2 center">Birthdays today: '.implode(", ", $birthdays).'</td></tr>';

		$count = $sql->fetch("SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM threads) t, (SELECT COUNT(*) FROM posts) p,
				(SELECT COUNT(*) FROM posts WHERE date > ?) d, (SELECT COUNT(*) FROM posts WHERE date > ?) h",
			[(time() - 86400), (time() - 3600)]);

		$lastuser = $sql->fetch("SELECT ".userfields()." FROM users ORDER BY id DESC LIMIT 1");

		$onuserlist = "$onusercount user".plural($onusercount).' online'.($onusercount > 0 ? ': ' : '').$onuserlist;

		?><table class="c1">
			<?=$birthdaybox ?>
			<tr><td class="b n1">
				<table style="width:100%"><tr>
					<td class="nb nom" width="200"></td>
					<td class="nb center" style="min-width:100px"><span class="white-space:nowrap">
						<?=$count['t'] ?> threads and <?=$count['p'] ?> posts total.<br><?=$count['d'] ?> new posts
						today, <?=$count['h'] ?> last hour.<br>
					</span></td>
					<td class="nb right" width="200">
						<?=$count['u'] ?> registered users<br> Newest: <?=($lastuser ? userlink($lastuser) : 'none') ?>
					</td>
				</tr></table>
			</td></tr>
			<tr><td class="b n2 center"><?=$onuserlist ?></td></tr>
		</table><br><?php
	}
}

function noticemsg($msg, $title = "Error") {
	?><table class="c1">
		<tr class="h"><td class="b h center"><?=$title ?></td></tr>
		<tr><td class="b n1 center"><?=$msg ?></td></tr>
	</table><?php
}

function error($msg) {
	pageheader('Error');
	noticemsg($msg.'<br><a href="./">Back to main</a>', 'Error');
	pagefooter();
	die();
}

function pagefooter() {
	global $start;
	$time = microtime(true) - $start;

	$commit = file_get_contents('.git/refs/heads/master');
	$commitmsg = ($commit !== false ? '(commit '.substr($commit, 0, 7).')' : '(unknown commit)');
	?><br>
	<table class="c1">
		<tr><td class="b n2 footer">
			<span class="stats nom">
				<?=sprintf("Page rendered in %1.3f ms. (%dKB of memory used)", $time*1000, memory_get_usage(false) / 1024); ?>
			</span>

			<img src="img/poweredbyvoxelmanip.png" class="poweredby"
				title="like a warm hug from someone you love">

			Voxelmanip Forums <?=$commitmsg?><br>
			&copy; 2022 ROllerozxa, <a href="credits.php">et al</a>.
		</td></tr>
	</table></body></html><?php
}
