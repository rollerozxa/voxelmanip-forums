<?php
require('lib/common.php');

pageheader('Online users');

$time = $_GET['time'] ?? null;

if (!$time || !is_numeric($time)) $time = 900;

$showips = $loguser['rank'] > 2;

$users = $sql->query("SELECT * FROM users WHERE lastview > ?", [(time()-$time)]);
?>
<table class="c1 autowidth">
	<tr class="h"><td class="b">Online users during the last <?=str_replace('.', '', timeunits2($time)) ?>:</td></tr>
	<tr class="n1"><td class="b n1 center"><?=timelink(300,'online').' | '.timelink(900,'online').' | '.timelink(3600,'online').' | '.timelink(86400,'online') ?></td></tr>
</table><br>
<table class="c1">
	<tr class="h">
		<td class="b h" width="30">#</td>
		<td class="b h" width="230">Name</td>
		<td class="b h" width="130">Last view</td>
		<td class="b h">URL</td>
		<?=($showips ? '<td class="b h" width="120">IP</td>' : '') ?>
	</tr>
<?php

for ($i = 1; $user = $users->fetch(); $i++) {
	$tr = ($i % 2 ? 1 : 2);
	?><tr class="n<?=$tr ?> center">
		<td class="b"><?=$i ?>.</td>
		<td class="b left"><?=userlink($user) ?></td>
		<td class="b"><span title="<?=date('Y-m-d', $user['lastview'])?>"><?=date('H:i', $user['lastview']) ?></span></td>
		<td class="b left"><?=($user['url'] ? "<a href=$user[url]>".$user['url'].'</a>' : '-') ?></td>
		<?=($showips ? '<td class="b"><span class="sensitive">'.$user['ip'].'</span></td>':'') ?>
	</tr><?php
}
if_empty_query($i, "There are no users online in the given timespan.", 5);

echo '</table>';

pagefooter();
