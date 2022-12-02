<?php
require('lib/common.php');

if ($loguser['powerlevel'] < 3) error("You have no permissions to do this!");

if (isset($_POST['action']))
	$sql->query("UPDATE misc SET attention = ?", [$_POST['attn']]);

$attndata = $sql->result("SELECT attention FROM misc");

pageheader("Edit news");
?>
<form action="editattn.php" method="post">
	<table class="c1">
		<tr class="h"><td class="b h">Edit news box</td></tr>
		<tr class="n1">
			<td class="b center">
				<textarea name="attn" rows="8" cols="80"><?=$attndata ?></textarea>
				<br><input type="submit" name="action" value="Submit">
			</td>
		</tr>
	</table>
</form>
<?php pagefooter();