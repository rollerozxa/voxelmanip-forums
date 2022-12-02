<?php
require('lib/common.php');

if ($loguser['powerlevel'] < 3) error('You have no permissions to do this!');

$error = '';

if (isset($_POST['savecat'])) {
	// save new/existing category
	$cid = $_GET['cid'];
	$title = trim($_POST['title']);
	$ord = (int)$_POST['ord'];

	if (!$title) $error = 'Please enter a title for the category.';

	if (!$error) {
		if ($cid == 'new') {
			$cid = $sql->result("SELECT MAX(id) FROM categories");
			if (!$cid) $cid = 0;
			$cid++;
			$sql->query("INSERT INTO categories (id,title,ord) VALUES (?,?,?)", [$cid, $title, $ord]);
		} else {
			$cid = (int)$cid;
			if (!$sql->result("SELECT COUNT(*) FROM categories WHERE id=?",[$cid])) redirect('manageforums.php');
			$sql->query("UPDATE categories SET title = ?, ord = ? WHERE id = ?", [$title, $ord, $cid]);
		}
		redirect('manageforums.php?cid='.$cid);
	}
} elseif (isset($_POST['delcat'])) {
	// delete category
	$cid = (int)$_GET['cid'];
	$sql->query("DELETE FROM categories WHERE id = ?",[$cid]);

	redirect('manageforums.php');
} elseif (isset($_POST['saveforum'])) {
	// save new/existing forum
	$fid = $_GET['fid'];
	$cat = (int)$_POST['cat'];
	$title = trim($_POST['title']);
	$descr = $_POST['descr'];
	$ord = (int)$_POST['ord'];

	$minread = (int)$_POST['minread'];
	$minthread = (int)$_POST['minthread'];
	$minreply = (int)$_POST['minreply'];

	if (!$title) $error = 'Please enter a title for the forum.';

	if (!$error) {
		if ($fid == 'new') {
			$fid = $sql->result("SELECT MAX(id) FROM forums");
			if (!$fid) $fid = 0;
			$fid++;
			$sql->query("INSERT INTO forums (id,cat,title,descr,ord,minread,minthread,minreply) VALUES (?,?,?,?,?,?,?,?)",
				[$fid, $cat, $title, $descr, $ord, $minread, $minthread, $minreply]);
		} else {
			$fid = (int)$fid;
			if (!$sql->result("SELECT COUNT(*) FROM forums WHERE id=?",[$fid]))
				redirect('manageforums.php');
			$sql->query("UPDATE forums SET cat=?, title=?, descr=?, ord=?, minread=?, minthread=?, minreply=? WHERE id=?",
				[$cat, $title, $descr, $ord, $minread, $minthread, $minreply, $fid]);
		}
		redirect('manageforums.php?fid='.$fid);
	}
} elseif (isset($_POST['delforum'])) {
	// delete forum
	$fid = (int)$_GET['fid'];
	$sql->query("DELETE FROM forums WHERE id=?",[$fid]);
	redirect('manageforums.php');
}

pageheader('Forum management');

if ($error) noticemsg($error);

if (isset($_GET['cid']) && $cid = $_GET['cid']) {
	// category editor
	if ($cid == 'new') {
		$cat = ['id' => 0, 'title' => '', 'ord' => 0];
	} else {
		$cid = (int)$cid;
		$cat = $sql->fetch("SELECT * FROM categories WHERE id=?",[$cid]);
	}
	?><form action="" method="POST">
		<table class="c1">
			<tr class="h"><td class="b h" colspan="2"><?=($cid == 'new' ? 'Create' : 'Edit') ?> category</td></tr>
			<tr>
				<td class="b n1 center">Title:</td>
				<td class="b n2"><input type="text" name="title" value="<?=esc($cat['title']) ?>" size="50" maxlength="500"></td>
			</tr><tr>
				<td class="b n1 center">Display order:</td>
				<td class="b n2"><input type="text" name="ord" value="<?=$cat['ord'] ?>" size="4" maxlength="10"></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<input type="submit" name="savecat" value="Save category">
						<?=($cid == 'new' ? '' : '<input type="submit" name="delcat" value="Delete category" onclick="if (!confirm("Really delete this category?")) return false;"> ') ?>
					<button type="button" id="back" onclick="window.location='manageforums.php';">Back</button>
				</td>
			</tr>
		</table>
	</form><?php
} elseif (isset($_GET['fid']) && $fid = $_GET['fid']) {
	// forum editor
	if ($fid == 'new') {
		$forum = [
			'id' => 0, 'cat' => 1, 'title' => '', 'descr' => '',
			'ord' => 0,
			'minread' => -1, 'minthread' => 1, 'minreply' => 1];
	} else {
		$fid = (int)$fid;
		$forum = $sql->fetch("SELECT * FROM forums WHERE id = ?", [$fid]);
	}
	$qcats = $sql->query("SELECT id,title FROM categories ORDER BY ord, id");
	$cats = [0 => 'None (Hidden)'];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat['title'];

	?><form action="" method="POST">
		<table class="c1">
			<tr class="h"><td class="b h" colspan="2"><?=($fid == 'new' ? 'Create' : 'Edit') ?> forum</td></tr>
			<tr>
				<td class="b n1 center">Title:</td>
				<td class="b n2"><input type="text" name="title" value="<?=esc($forum['title']) ?>" size="50" maxlength="500"></td>
			</tr><tr>
				<td class="b n1 center">Description:<br><small>HTML allowed.</small></td>
				<td class="b n2"><textarea wrap="virtual" name="descr" rows="3" cols="50"><?=esc($forum['descr']) ?></textarea></td>
			</tr><tr>
				<td class="b n1 center">Category:</td>
				<td class="b n2"><?=fieldselect('cat', $forum['cat'], $cats) ?></td>
			</tr><tr>
				<td class="b n1 center">Display order:</td>
				<td class="b n2"><input type="text" name="ord" value="<?=$forum['ord'] ?>" size="4" maxlength="10"></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">Permissions</td></tr>
			<tr>
				<td class="b n1 center">Who can view:</td>
				<td class="b n2"><?=fieldselect('minread', $forum['minread'], $powerlevels) ?></td>
			</tr><tr>
				<td class="b n1 center">Who can make threads:</td>
				<td class="b n2"><?=fieldselect('minthread', $forum['minthread'], $powerlevels) ?></td>
			</tr><tr>
				<td class="b n1 center">Who can reply:</td>
				<td class="b n2"><?=fieldselect('minreply', $forum['minreply'], $powerlevels) ?></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<input type="submit" name="saveforum" value="Save forum">
					<?=($fid == 'new' ? '' : '<input type="submit" name="delforum" value="Delete forum" onclick="if (!confirm("Really delete this forum?")) return false;">') ?>
					<button type="button" id="back" onclick="window.location='manageforums.php'">Back</button>
				</td>
			</tr>
		</table>
	</form><?php
} else {
	// main page -- category/forum listing

	$qcats = $sql->query("SELECT id,title FROM categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat['title'];

	$qforums = $sql->query("SELECT f.id,f.title,f.cat FROM forums f LEFT JOIN categories c ON c.id = f.cat ORDER BY c.ord, c.id, f.ord, f.id");
	$forums = [];
	while ($forum = $qforums->fetch())
		$forums[$forum['id']] = $forum;

	$catlist = ''; $c = 1;
	foreach ($cats as $cid => $cat) {
		$catlist .= sprintf('<tr><td class="b n%s"><a href="manageforums.php?cid=%s">%s</a></td></tr>', $c, $cid, $cat);
		$c = ($c == 1) ? 2 : 1;
	}

	$cats[0] = 'None (Hidden)';
	$forumlist = ''; $c = 1; $lc = -1;
	foreach ($forums as $forum) {
		if ($forum['cat'] != $lc) {
			$lc = $forum['cat'];
			$forumlist .= sprintf('<tr class="c"><td class="b c">%s</td></tr>', $cats[$forum['cat']]);
		}
		$forumlist .= sprintf('<tr><td class="b n%s"><a href="manageforums.php?fid=%s">%s</a></td></tr>', $c, $forum['id'], $forum['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	?><table style="width:100%">
		<tr>
			<td class="nb" style="width:50%;vertical-align:top">
				<table class="c1">
					<tr class="h"><td class="b">Categories</td></tr>
					<?=$catlist ?>
					<tr class="h"><td class="b">&nbsp;</td></tr>
					<tr><td class="b n1"><a href="manageforums.php?cid=new">New category</a></td></tr>
				</table>
			</td>
			<td class="nb" style="width:50%;vertical-align:top">
				<table class="c1">
					<tr class="h"><td class="b">Forums</td></tr>
					<?=$forumlist ?>
					<tr class="h"><td class="b">&nbsp;</td></tr>
					<tr><td class="b n1"><a href="manageforums.php?fid=new">New forum</a></td></tr>
				</table>
			</td>
		</tr>
	</table><?php
}

pagefooter();
