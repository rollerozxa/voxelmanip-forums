<?php
$rss = true;
require('lib/common.php');

header('Content-Type: text/xml');

$threads = $sql->query("SELECT u.id uid, u.name uname, p.*, t.title, t.forum, f.id fid, f.title ftitle
		FROM posts p
		LEFT JOIN threads t ON t.id = p.thread
		LEFT JOIN users u ON u.id = p.user
		LEFT JOIN forums f ON f.id = t.forum
		WHERE ? >= f.minread
		ORDER BY p.date DESC LIMIT 30",
	[$loguser['rank']]);

$fullurl = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];

?><?xml version="1.0"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
	<title><?=$boardtitle?></title>
	<description>The latest posts of <?=$boardtitle?></description>
	<link><?=$fullurl?></link>
	<atom:link href="<?=$fullurl?>/rss.php" rel="self" type="application/rss+xml"/>
<?php while ($t = $threads->fetch()) { ?>
	<item>
		<title><?=$t['title']?></title>
		<description>
			&lt;a href="<?=$fullurl?>/thread.php?pid=<?=$t['id']?>#<?=$t['id']?>"&gt;New post&lt;/a&gt; in thread "<?=$t['title']?>"
			by &lt;a href="<?=$fullurl?>/profile.php?id=<?=$t['uid']?>"&gt;<?=$t['uname']?>&lt;/a&gt;
			in forum &lt;a href="<?=$fullurl?>/forum.php?id=<?=$t['forum']?>"&gt;<?=$t['ftitle']?>&lt;/a&gt;
		</description>
		<pubDate><?=date("r",$t['date'])?></pubDate>
		<category><?=$t['ftitle']?></category>
		<guid><?=$fullurl?>/thread.php?pid=<?=$t['id']?>#<?=$t['id']?></guid>
	</item>
<?php } ?>
</channel></rss>
