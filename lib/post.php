<?php

function get_username_link($matches) {
	global $sql;
	$name = str_replace('"', '', $matches[1]);

	static $cache;
	if (!isset($cache[$name])) {
		$u = $sql->fetch("SELECT ".userfields()." FROM users WHERE UPPER(name)=UPPER(?)", [$name]);
		$cache[$name] = $u;
	} else $u = $cache[$name];

	if ($u)
		$ulink = userlink($u, null);

	return $ulink ?? $matches[0];
}

// Function that does lots of voodoo magic to make sure the post data is (reasonably) safe
function securityfilter($msg) {
	$tags = ':a(?:pplet|udio)|b(?:ase|gsound|ody|button)|canvas|embed|frame(?:set)?|form|h(?:ead|tml)|i(?:frame|layer|nput)|l(?:ayer|ink)|m(?:ath|eta|eth)|noscript|object|plaintext|s(?:cript|vg|ource)|title|textarea|video|x(?:ml|mp)';
	$msg = preg_replace("'<(/?)({$tags})'si", "&lt;$1$2", $msg);

	$msg = preg_replace('@(on)(\w+\s*)=@si', '$1_$2&#x3D;', $msg);

	$msg = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2jujscript!', $msg);

	$msg = preg_replace("'-moz-binding'si", ' -mo<b></b>z-binding', $msg);
	$msg = str_ireplace("expression", "ex<b></b>pression", $msg);
	$msg = preg_replace("'filter:'si", 'filter&#58;>', $msg);
	$msg = preg_replace("'transform:'si", 'transform&#58;>', $msg);

	$msg = str_replace("<!--", "&lt;!--", $msg);

	return $msg;
}

function makecode($match) {
	$code = esc($match[1]);
	$list = ["\r\n", "[", ":", ")", "_", "@", "-"];
	$list2 = ["<br>", "&#91;", "&#58;", "&#41;", "&#95;", "&#64;", "&#45;"];
	return '<div class="codeblock">'.str_replace($list, $list2, $code).'</div>';
}

function filterstyle($match) {
	$style = $match[2];

	// remove newlines.
	// this will prevent them being replaced with <br> tags and breaking the CSS
	$style = str_replace("\n", '', $style);

	$style = preg_replace("'@(?:keyframes|-webkit-keyframe)'si",'(no animations pls)',$style);

	return $match[1].$style.$match[3];
}

function postfilter($msg) {
	global $smilies;

	if (empty($msg)) return;

	$msg = trim($msg);

	//[code] tag
	$msg = preg_replace_callback("'\[code\](.*?)\[/code\]'si", 'makecode', $msg);

	$msg = preg_replace_callback("@(<style.*?>)(.*?)(</style.*?>)@si", 'filterstyle', $msg);

	$msg = securityfilter($msg);

	$msg = str_replace("\n", '<br>', $msg);

	foreach ($smilies as $smiley)
		$msg = str_replace($smiley['text'], sprintf('<img class="smiley" src="%s" align=absmiddle alt="%s" title="%s">', $smiley['url'], $smiley['text'], $smiley['text']), $msg);

	//Relocated here due to conflicts with specific smilies.
	$msg = preg_replace("@(</?(?:table|caption|col|colgroup|thead|tbody|tfoot|tr|th|td|ul|ol|li|div|p|style|link).*?>)\r?\n@si", '$1', $msg);

	$msg = preg_replace("'\[b\](.*?)\[/b\]'si", '<b>\\1</b>', $msg);
	$msg = preg_replace("'\[i\](.*?)\[/i\]'si", '<i>\\1</i>', $msg);
	$msg = preg_replace("'\[u\](.*?)\[/u\]'si", '<u>\\1</u>', $msg);
	$msg = preg_replace("'\[s\](.*?)\[/s\]'si", '<s>\\1</s>', $msg);

	$msg = preg_replace("'\[spoiler\](.*?)\[/spoiler\]'si", '<span class="spoiler1" onclick=""><span class="spoiler2">\\1</span></span>', $msg);
	$msg = preg_replace("'\[url\](.*?)\[/url\]'si", '<a href=\\1>\\1</a>', $msg);
	$msg = preg_replace("'\[url=(.*?)\](.*?)\[/url\]'si", '<a href=\\1>\\2</a>', $msg);
	$msg = preg_replace("'\[img\](.*?)\[/img\]'si", '<img src=\\1>', $msg);
	$msg = preg_replace("'\[quote\](.*?)\[/quote\]'si", '<blockquote><hr>\\1<hr></blockquote>', $msg);
	$msg = preg_replace("'\[color=([a-f0-9]{6})\](.*?)\[/color\]'si", '<span style="color: #\\1">\\2</span>', $msg);

	$msg = preg_replace("'\[pre\](.*?)\[/pre\]'si", '<code>\\1</code>', $msg);

	$msg = preg_replace_callback('\'@\"((([^"]+))|([A-Za-z0-9_\-%]+))\"\'si', "get_username_link", $msg);

	// Quotes
	$msg = preg_replace("'\[reply=\"(.*?)\" id=\"(.*?)\"\]'si", '<blockquote><span class="quotedby"><small><i><a href=showprivate.php?id=\\2>Sent by \\1</a></i></small></span><hr>', $msg);
	$msg = preg_replace("'\[quote=\"(.*?)\" id=\"(.*?)\"\]'si", '<blockquote><span class="quotedby"><small><i><a href=thread.php?pid=\\2#\\2>Posted by \\1</a></i></small></span><hr>', $msg);
	$msg = preg_replace("'\[quote=(.*?)\]'si", '<blockquote><span class="quotedby"><i>Posted by \\1</i></span><hr>', $msg);
	$msg = str_replace('[/reply]', '<hr></blockquote>', $msg);
	$msg = str_replace('[/quote]', '<hr></blockquote>', $msg);

	$msg = preg_replace("'>>([0-9]+)'si", '>><a href=thread.php?pid=\\1#\\1>\\1</a>', $msg);

	$msg = preg_replace("'\[youtube\]((https?\:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/(watch\?v=)?)?([\-0-9_a-zA-Z]*?)\[\/youtube\]'si", '<iframe width="427" height="240" src="https://www.youtube.com/embed/\\6" frameborder="0" allowfullscreen></iframe>', $msg);

	return $msg;
}

function esc($text) {
	return $text ? htmlspecialchars($text) : '';
}

function posttoolbutton($name, $title, $leadin, $leadout) {
	return sprintf(
		'<td><a href="javascript:toolBtn(\'%s\',\'%s\')"><input style="font-size:11pt" type="button" title="%s" value="%s"></a></td>',
	$leadin, $leadout, $title, $name);
}

function posttoolbar() {
	return '<table class="postformatting"><tr>'
		. posttoolbutton("B", "Bold", "[b]", "[/b]")
		. posttoolbutton("I", "Italic", "[i]", "[/i]")
		. posttoolbutton("U", "Underline", "[u]", "[/u]")
		. posttoolbutton("S", "Strikethrough", "[s]", "[/s]")
		. "<td>&nbsp;</td>"
		. posttoolbutton("/", "URL", "[url]", "[/url]")
		. posttoolbutton("!", "Spoiler", "[spoiler]", "[/spoiler]")
		. posttoolbutton("&#133;", "Quote", "[quote]", "[/quote]")
		. posttoolbutton(";", "Code", "[code]", "[/code]")
		. "<td>&nbsp;</td>"
		. posttoolbutton("[]", "IMG", "[img]", "[/img]")
		. posttoolbutton("YT", "YouTube", "[youtube]", "[/youtube]")
		. '</tr></table>';
}

function LoadBlocklayouts() {
	global $blocklayouts, $loguser, $log, $sql;
	if (isset($blocklayouts) || !$log) return;

	$blocklayouts = [];
	$rBlocks = $sql->query("SELECT * FROM blockedlayouts WHERE blockee = ?",[$loguser['id']]);
	while ($block = $rBlocks->fetch())
		$blocklayouts[$block['user']] = 1;
}

function minipost($post) {
	if (isset($post['deleted']) && $post['deleted']) return;

	$ulink = userlink($post, 'u');
	$pdate = dateformat($post['date']);

	$posttext = postfilter($post['text']);

	return <<<HTML
	<tr>
		<td class="b n1 topbar_1 nom">$ulink</td>
		<td class="b n1 topbar_1 blkm nod clearfix">$ulink</td>
		<td class="b n1 topbar_2 sfont blkm">Posted on $pdate
			<span class="f-right"><a href="thread.php?pid={$post['id']}#{$post['id']}">Link</a> | ID: {$post['id']}</span></td>
	</tr><tr valign="top">
		<td class="b n1 sfont sidebar nom">
			Posts: {$post['uposts']}
		</td>
		<td class="b n2 post mainbar">$posttext</td>
	</tr>
HTML;
}

function threadpost($post, $pthread = '') {
	global $loguser, $blocklayouts, $log;

	if (isset($post['deleted']) && $post['deleted']) {
		$postlinks = '';
		if ($loguser['powerlevel'] > 1) {
			$postlinks = sprintf(
				'<a href="thread.php?pid=%s&pin=%s&rev=%s#%s">Peek</a> | <a href="editpost.php?pid=%s&act=undelete">Undelete</a> | ',
			$post['id'], $post['id'], $post['revision'], $post['id'], $post['id']);
		}
		$postlinks .= 'ID: '.$post['id'];

		$ulink = userlink($post, 'u');
		return <<<HTML
<table class="c1 post"><tr>
	<td class="b n1 topbar_1">$ulink</td>
	<td class="b n1 sfont topbar_2">(post deleted) <span class="f-right">$postlinks</span></td>
</tr></table>
HTML;
	}

	$ranktext = getrank($post['urankset'], $post['uposts']);
	if ($ranktext) $ranktext .= '<br><br>';
	$usertitle = ($post['utitle'] ? $post['utitle'].'<br>' : '');

	// Blocklayouts, supports user/user ($blocklayouts) and user/world (token).
	LoadBlockLayouts(); //load the blocklayout data - this is just once per page.
	if (!$log || $loguser['blocklayouts'])
		$isBlocked = true;
	else
		$isBlocked = isset($blocklayouts[$post['uid']]);

	if ($isBlocked)
		$post['usignature'] = $post['uheader'] = '';

	$headerbar = $threadlink = $postlinks = $revisionstr = '';

	if (isset($post['headerbar']))
		$headerbar = sprintf('<tr class="h"><td class="b h" colspan="2">%s</td></tr>', $post['headerbar']);

	$post['id'] = $post['id'] ?? null;

	if ($pthread)
		$threadlink = ", in <a href=\"thread.php?id=$pthread[id]\">" . esc($pthread['title']) . "</a>";

	if ($post['id'])
		$postlinks = "<a href=\"thread.php?pid=$post[id]#$post[id]\">Link</a>"; // headlinks for posts

	if (isset($post['revision']) && $post['revision'] >= 2)
		$revisionstr = " (rev. {$post['revision']} on ".dateformat($post['ptdate']).")";

	if (isset($post['thread']) && $log) {
		if (isset($post['thread']) && $post['id'])
			$postlinks .= " | <a href=\"newreply.php?id=$post[thread]&pid=$post[id]\">Reply</a>";

		// "Edit" link for admins or post owners, but not banned users
		if ($loguser['powerlevel'] > 2 || $loguser['id'] == $post['uid'])
			$postlinks .= " | <a href=\"editpost.php?pid=$post[id]\">Edit</a>";

		if ($loguser['powerlevel'] > 1)
			$postlinks .= ' | <a href="editpost.php?pid='.urlencode($post['id']).'&act=delete">Delete</a>';

		if ($loguser['powerlevel'] > 2)
			$postlinks .= ' | IP: <span class="sensitive">'.$post['ip'].'</span>';

		if (isset($post['maxrevision']) && $loguser['powerlevel'] > 1 && $post['maxrevision'] > 1) {
			$revisionstr.=" | Revision ";
			for ($i = 1; $i <= $post['maxrevision']; $i++)
				$revisionstr .= "<a href=\"thread.php?pid=$post[id]&pin=$post[id]&rev=$i#$post[id]\">$i</a> ";
		}
	}

	if (isset($post['thread']))
		$postlinks .= " | ID: $post[id]";

	$ulink = userlink($post, 'u');
	$pdate = dateformat($post['date']);
	$uid = $post['uid'];

	$joined = date('Y-m-d', $post['ujoined']);
	$lastpost = ($post['ulastpost'] ? timeunits(time() - $post['ulastpost']) : 'none');
	$lastview = timeunits(time() - $post['ulastview']);

	$picture = ($post['uavatar'] ? "<img src=\"userpic/{$post['uid']}\">" : '');

	if ($post['usignature']) {
		$signsep = $post['usignsep'] ? '<hr>' : '';

		if (!$post['uheader'])
			$post['usignature'] = '<br><br><small>'.$signsep.$post['usignature'].'</small>';
		else
			$post['usignature'] = $signsep.$post['usignature'];
	}

	$posttext = postfilter($post['uheader'].$post['text'].$post['usignature']);

	return <<<HTML
<table class="c1 post table{$uid}" id="{$post['id']}">
	$headerbar
	<tr>
		<td class="b n1 topbar_1 topbar{$uid}_1 nom">$ulink</td>
		<td class="b n1 topbar_1 topbar{$uid}_2 blkm nod clearfix">
			<span style="float:left;margin-right:10px">$picture</span>
			$ulink <div class="sfont" style="margin-top:0.5em">$usertitle</div>
		</td>
		<td class="b n1 topbar_2 topbar{$uid}_2 sfont blkm clearfix">Posted on $pdate$threadlink$revisionstr <span class="f-right">$postlinks</span></td>
	</tr><tr valign="top">
		<td class="b n1 sfont sidebar sidebar{$uid} nom">
			$ranktext$usertitle$picture
			<br>Posts: {$post['uposts']}<br>
			<br>Since: $joined<br>
			<br>Last post: $lastpost
			<br>Last view: $lastview
		</td>
		<td class="b n2 post mainbar mainbar{$uid}" id="post_{$post['id']}">$posttext</td>
	</tr>
</table>
HTML;
}
