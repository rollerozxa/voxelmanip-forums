<?php
require('lib/common.php');

//Smilies List
$smilietext = '';
$x = 0;
foreach ($smilies as $smily) {
	if ($x % 6 == 0) $smilietext .= "<tr>";
	$smilietext .= sprintf('<td class="b n1"><img class="smiley" src="%s"> %s</td>', $smily['url'], esc($smily['text']));
	$x++;
	if ($x % 6 == 0) $smilietext .= "</tr>";
}

// Rank colours
$nctable = '';
foreach ($ranks as $id => $title)
	$nctable .= sprintf('<td class="b n1" width="140"><b><span style="color:#%s">%s</span></b></td>', powIdToColour($id), $title);

if (file_exists('conf/faq.php'))
	require('conf/faq.php');
else
	require('conf/faq.sample.php');

pageheader("FAQ");

?><table class="c1 faq">
	<tr class="h"><td class="b h">FAQ</td></tr>
	<tr><td class="b n1"><ol class="toc">
		<?php foreach ($faq as $faqitem) printf('<li><a href="#%s">%s</a></li>', $faqitem['id'], $faqitem['title']); ?>
	</ol></td></tr>
<?php
foreach ($faq as $faqitem) {
	printf('<tr class="h"><td class="b h" id="%s">%s</td></tr><tr><td class="b n1">%s</td></tr>',
		$faqitem['id'], $faqitem['title'], $faqitem['content']);
}
echo '</table>';

pagefooter();
