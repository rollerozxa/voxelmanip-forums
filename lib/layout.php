<?php

function redirect($url) {
	header("Location: ".$url);
	die();
}

function rendernewstatus($type) {
	if (!$type) return '';

	$text = match ($type) {
		'n'  => 'NEW',
		'o'  => 'OFF',
		'on' => 'OFF'
	};
	$statusimg = match ($type) {
		'n'  => 'new.png',
		'o'  => 'off.png',
		'on' => 'offnew.png'
	};

	return "<img src=\"img/status/$statusimg\" alt=\"$text\">";
}

function RenderActions($actions) {
	$out = '';
	foreach ($actions as $url => $title) {
		$out .= '<li>';
		if ($url != 'none')
			$out .= sprintf('<a href="%s">%s</a>', esc($url), $title);
		else
			$out .= $title;
		$out .= '</li>';
	}
	echo '<ul class="menulisting">'.$out.'</ul>';
}

function RenderPageBar($pagebar) {
	if (empty($pagebar)) return;

	echo '<div class="breadcrumb"><a href="./">Main</a> &raquo; ';
	if (!empty($pagebar['breadcrumb'])) {
		foreach ($pagebar['breadcrumb'] as $url => $title)
			printf('<a href="%s">%s</a> &raquo; ', esc($url), $title);
	}
	echo $pagebar['title'].'<div class="actions">';
	if (!empty($pagebar['actions']))
		renderActions($pagebar['actions']);
	echo "</div></div>";
}

function catheader($title) {
	return sprintf('<tr class="h"><td class="b h" colspan="2">%s</td>', $title);
}

function fieldrow($title, $input) {
	return sprintf('<tr><td class="b n1 center">%s:</td><td class="b n2">%s</td>', $title, $input);
}

function fieldinput($size, $max, $field, $value = null) {
	global $user;
	$val = str_replace('"', '&quot;', ($value ?? $user[$field]) ?: '');
	return sprintf('<input type="text" name="%s" size="%s" maxlength="%s" value="%s">', $field, $size, $max, $val);
}

function fieldtext($rows, $cols, $field) {
	global $user;
	return sprintf('<textarea wrap="virtual" name="%s" rows=%s cols=%s>%s</textarea>', $field, $rows, $cols, esc($user[$field]));
}

function fieldcheckbox($field, $checked, $label) {
	return sprintf('<label><input type="checkbox" name="%s" value="1" %s>%s </label>', $field, ($checked ? ' checked' : ''), $label);
}

function fieldselect($field, $checked, $choices, $onchange = '') {
	if ($onchange != '')
		$onchange = ' onchange="'.$onchange.'"';
	$text = sprintf('<select name="%s"%s>', $field, $onchange);
	foreach ($choices as $k => $v)
		$text .= sprintf('<option value="%s"%s>%s</option>', $k, ($k == $checked ? ' selected' : ''), $v);
	$text .= '</select>';
	return $text;
}

function bantimeselect($name) {
	return fieldselect($name, 0, [
		"0"			=> "Never",
		"3600"		=> "1 hour",
		"10800"		=> "3 hours",
		"86400"		=> "1 day",
		"172800"	=> "2 days",
		"259200"	=> "3 days",
		"604800"	=> "1 week",
		"1209600"	=> "2 weeks",
		"2419200"	=> "1 month",
		"4838400"	=> "2 months",
		"14515200"	=> "6 months",
	]);
}

function pagelist($total, $limit, $url, $sel = 0, $showall = false, $tree = false) {
	$pagelist = '';
	$pages = ceil($total / $limit);
	if ($pages < 2) return '';
	for ($i = 1; $i <= $pages; $i++) {
		if (	$showall	// If we don't show all the pages, show:
			|| ($i < 7 || $i > $pages - 7)		// First / last 7 pages
			|| ($i > $sel - 5 && $i < $sel + 5)	// 10 choices around the selected page
			|| !($i % 10)						// Show 10, 20, etc...
		) {
			if ($i == $sel)
				$pagelist .= " $i";
			else
				$pagelist .= " <a href=\"$url&page=$i\">$i</a>";
		} elseif (substr($pagelist, -1) != '.')
			$pagelist .= ' ...';
	}

	if ($tree)
		$listhtml = '<span class="sfont">(pages: %s)</span>';
	else
		$listhtml = '<div class="pagelist">Pages: %s</div>';

	return sprintf($listhtml, $pagelist);
}

function themelist() {
	$themes = glob('theme/*', GLOB_ONLYDIR);
	sort($themes);
	foreach ($themes as $f) {
		$id = explode("/",$f)[1];
		$themelist[$id] = themename($id);
	}

	return $themelist;
}

function themename($id) {
	if (file_exists("theme/$id/$id.css") && preg_match("~/* META\n(.*?)\n~s", file_get_contents("theme/$id/$id.css"), $matches)) {
		return $matches[1];
	}
}

function ranklist() {
	global $rankset_names;
	foreach ($rankset_names as $rankset) {
		$rlist[] = $rankset;
	}
	return $rlist;
}

function announcement_row() {
	global $sql, $newsid;

	if (!isset($newsid) || !$newsid) return;

	$ufields = userfields('u');
	$anc = $sql->fetch("SELECT t.id tid,t.title,t.user,t.lastdate date,$ufields FROM threads t JOIN users u ON t.user = u.id WHERE t.forum = $newsid ORDER BY lastdate DESC LIMIT 1");

	if (isset($anc['title'])) {
		$anlink = sprintf(
			'<a href="thread.php?id=%s">%s</a> - by %s on %s',
		$anc['tid'], $anc['title'], userlink($anc), dateformat($anc['date']));

		?><table class="c1">
			<tr class="h"><td class="b" colspan="2">Latest Announcement</td></tr>
			<tr class="n1"><td class="b n1 nom" width="32"></td>
			<td class="b left"><?=$anlink ?>
			<span class="f-right"><a href="forum.php?id=<?=$newsid?>">All announcements</a></span>
		</td></tr></table><?php
	}
}

function forumlist($currentforum = -1) {
	global $sql, $loguser;

	$r = $sql->query("SELECT c.title ctitle,f.id,f.title,f.cat FROM forums f LEFT JOIN categories c ON c.id=f.cat WHERE ? >= f.minread ORDER BY c.ord,c.id,f.ord,f.id",
		[$loguser['powerlevel']]);
	$out = '<select id="forumselect">';
	$c = -1;
	while ($d = $r->fetch()) {
		if ($d['cat'] != $c) {
			if ($c != -1)
				$out .= '</optgroup>';
			$c = $d['cat'];
			$out .= '<optgroup label="'.$d['ctitle'].'">';
		}
		$out .= sprintf(
			'<option value="%s"%s>%s</option>',
		$d['id'], ($d['id'] == $currentforum ? ' selected="selected"' : ''), $d['title']);
	}
	$out .= "</optgroup></select>";

	return $out;
}

/**
 * Display $message if $result (the result of a SQL query) is empty (has no lines).
 */
function if_empty_query($result, $message, $colspan = 0, $table = false) {
	if ($result == 1) {
		if ($table) echo '<table class="c1">';
		echo '<tr><td class="b n1 center" '.($colspan != 0 ? "colspan=$colspan" : '')."><p>$message</p></td></tr>";
		if ($table) echo '</table>';
	}
}

function plural($value) {
	return ($value != 1 ? 's' : '');
}
