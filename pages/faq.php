<?php

//Smilies List
$smiliestext = '';
$x = 0;
foreach ($smilies as $text => $url) {
	if ($x % 6 == 0) $smiliestext .= "<tr>";
	$smiliestext .= sprintf('<td class="n1"><img class="smiley" src="%s"> %s</td>',
		$url, htmlspecialchars($text));
	$x++;
	if ($x % 6 == 0) $smiliestext .= "</tr>";
}

// Rank colours
$ranktable = '';
foreach ($ranks as $id => $title)
	$ranktable .= sprintf('<td class="n1 w-120px"><b><span style="color:#%s">%s</span></b></td>', rankIdToColour($id), $title);

require('data/faq.php');

twigloader()->display('faq.twig', [
	'faq' => $faq
]);
