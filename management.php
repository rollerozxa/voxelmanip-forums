<?php
require('lib/common.php');
pageheader('Management');

$mlinks = [];
if ($loguser['powerlevel'] > 2) {
	$mlinks = [
		"manageforums.php" => 'Manage forums',
		"ipbans.php" => 'Manage IP bans',
		"editattn.php" => 'Edit news box'];
}

$mlinkstext = '';
if (!empty($mlinks)) {
	foreach ($mlinks as $url => $title)
		$mlinkstext .= sprintf(' <a href="%s"><input type="submit" name="action" value="%s"></a> ', $url, $title);
} else
	$mlinkstext = "You don't have permission to access any management tools.";
?>
<table class="c1">
	<tr class="h"><td class="b">Board management tools</td></tr>
	<tr><td class="b n1 center">
		<br><?=$mlinkstext ?><br><br>
	</td></tr>
</table>
<?php pagefooter();