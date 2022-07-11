<?php
require('lib/common.php');

//Smilies List
$smilietext = '';
$x = 0;
foreach ($smilies as $smily) {
	if ($x == 0) $smilietext .= "<tr>";
	$smilietext .= sprintf('<td class="b n1"><img class="smiley" src="%s"> %s</td>', $smily['url'], esc($smily['text']));
	$x++;
	$x %= ceil(sqrt(sizeof($smilies)));
	if ($x == 0) $smilietext .= "</tr>";
}

// Rank colours
$nctable = '';
foreach ($powerlevels as $id => $title)
	$nctable .= sprintf('<td class="b n1" width="140"><b><span style="color:#%s">%s</span></b></td>', powIdToColour($id), $title);

$faq = [[
	'id' => 'disclaimer',
	'title' => 'General Disclaimer',
	'content' => <<<HTML
<p>The site does not own and cannot be held responsible for statements made by members on the forum. This site is offered as-is to the user. Any statements made on the board may be altered or removed at the discretion of the staff. Furthermore, all users are expected to have read, understood, and agreed to The Rules before posting.
</p>

<p><strong>We do <em>not</em> sell, distribute or otherwise disclose member information like IP addresses or e-mail addresses to any third party.</strong> If you have questions about any information contained in this FAQ, please send a private message with your question to an administrator <em>before</em> posting.</p>

<p>We only use a token cookie to keep you logged in, no cookies is placed on your device when not logged in. We do not use tracking cookies, you can verify this by looking at the cookie storage for this site in your respective web browser.</p>
HTML
], [
	'id' => 'gpg',
	'title' => 'General Posting Guidelines',
	'content' => <<<HTML
<p>Posting on a message forum is generally relaxed. There are, however, a few things to keep in mind when posting.</p>
<ul>
	<li><b>No trolling, flaming, harrassment or drama.</b><br>
		This behavior is unacceptable and will be dealt with accordingly to the severity, to make the board a pleasant experience for everyone.</li>

	<li><b>No spamming.</b><br>
		Spam is a pretty broad area. Spam can be generalised as multiple posts with no real meaning to the topic or what anyone else is talking about. Also applies to registering with the sole intent of advertising something completely irrelevant.</li>

	<li><b>Do not mention sensitive subjects such as politics or religion.</b><br>
		It is irrelevant to this site and risks creating unnecessary conflict and tension.</li>

	<li><b>The forum's main language is English.</b><br>
		English is a language we all understand relatively well, including the staff. Keep non-English text to an absolute minimum.</li>

	<li><b>Do not back-seat moderate or "minimod".</b><br>
		While this may depend on the circumstances, you may do more harm than good and stir up drama. If you see an issue please contact a staff member privately and they can properly handle it.</li>

	<li><b>No explicit material.</b><br>
		If it is something people normally would look at to pleasure themselves, you should not post it here.</li>

	<li><b>Please proofread your posts and use proper grammar and punctuation.</b><br>
		To a certain extent of course, you are not required to write like you are writing a formal academic paper and have full perfect grammars or speeling, but please read through whatever you are writing so that it looks sane and reasonably readable.</li>

	<li><b>In general, use common sense...</b><br>
		Really goes a long way.</li>
</ul>

<p>Staff have the final say in interpretation of the rules, and may act in any way they see fit to keep the forum a pleasant experience for everyone.</p>
HTML
], [
	'id' => 'move',
	'title' => 'I just made a thread, where did it go?',
	'content' => <<<HTML
<p>It was probably moved or deleted by a staff member. If it was deleted, please make sure your thread meets the criteria we have established. If it was moved, look into the other forums and consider why it was moved there. If you have any questions, PM a staff member.</p>
HTML
], [
	'id' => 'rude',
	'title' => 'An user is being rude to me. What do I do?',
	'content' => <<<HTML
<p>Stay cool. Don't further disrupt the thread by responding <b>at all</b> to the rudeness. Let a member of staff know with a link to the offending post(s). Please note that responding to the rudeness is promoting flaming, which is a punishable offense.</p>
HTML
], [
	'id' => 'banned',
	'title' => "I've been banned. What do I do now?",
	'content' => <<<HTML
<p>Check your title as it will usually show the reason as to why you were banned and an expiration date. If there is no expiration date you will need to prove to the staff why you should be unbanned, or if you would want more information please PM a staff member calmly.</p>
HTML
], [
	'id' => 'smile',
	'title' => 'Are smilies and BBCode supported?',
	'content' => <<<HTML
<p>Here's a table with all available smileys.</p>
<table class="smileytbl">$smilietext</table>

<p>Likewise, some BBCode is supported. See the table below.</p>
<table class="c1 autowidth">
	<tr class="h">
		<td class="b h">Tag</td>
		<td class="b h">Effect</td>
	</tr><tr>
		<td class="b n1">[b]<i>text</i>[/b]</td>
		<td class="b n2"><b>Bold Text</b></td>
	</tr><tr>
		<td class="b n1">[i]<i>text</i>[/i]</td>
		<td class="b n2"><i>Italic Text</i></td>
	</tr><tr>
		<td class="b n1">[u]<i>text</i>[/u]</td>
		<td class="b n2"><u>Underlined Text</u></td>
	</tr><tr>
		<td class="b n1">[s]<i>text</i>[/s]</td>
		<td class="b n2"><s>Striked-out Text</s></td>
	</tr><tr>
		<td class="b n1">[color=<b>hexcolor</b>]<i>text</i>[/color]</td>
		<td class="b n2"><span style="color:#BCDE9A">Custom color Text</span></td>
	</tr><tr>
		<td class="b n1">
			[url]<i>URL</i>[/url]<br>
			[url=<i>URL</i>]<i>Link title</i>[/url]
		</td>
		<td class="b n2">Creates a link with or without a title.</td>
	</tr><tr>
		<td class="b n1">[spoiler]<i>text</i>[/spoiler]</td>
		<td class="b n2">Used for hiding spoilers.</td>
	</tr><tr>
		<td class="b n1">[quote]<i>text</i>[/quote]</td>
		<td class="b n2">Displays a blockquote with the text</td>
	</tr><tr>
		<td class="b n1">[code]<i>code text</i>[/code]</td>
		<td class="b n2">Displays code in a formatted box.</td>
	</tr><tr>
		<td class="b n1">[img]<i>URL of image to display</i>[/img]</td>
		<td class="b n2">Displays an image.</td>
	</tr><tr>
		<td class="b n1">[youtube]<i>video id</i>[/youtube]</td>
		<td class="b n2">Creates an embeded YouTube video.</td>
	</tr><tr>
		<td class="b n1">@"<i>User Name</i>"</td>
		<td class="b n2">Creates a link to a user's profile complete with name colour.</td>
	</tr><tr>
		<td class="b n1">&gt;&gt;<i>Post ID</i></td>
		<td class="b n2">Simple link reference to a particular post for replying to it.</td>
	</tr>
</table>
<p>Also, most HTML tags are able to be used in your posts, but with great power comes great responsibility.</p>
HTML
], [
	'id' => 'reg',
	'title' => 'Can I register more than one account?',
	'content' => <<<HTML
<p><strong>No.</strong> Most uses for a secondary account tend to be to bypass bans, sockpuppet or in other ways cause havoc. All is expressly forbidden and you will be punished when found out (you most likely will).</p>

<p>Another use is to change your username. If you feel that you want to change your username then please message a staff member instead. Keep in mind that too frequent name changes are not allowed.</p>
HTML
], [
	'id' => 'postlayouts',
	'title' => 'What are these post layout thingies?',
	'content' => <<<HTML
<p>Basically, you are able to style and customise your post layout using CSS. You can set up whatever kind of HTML inside the header and signature fields and style them with CSS, and also style the side and top of your post table using special classes suffixed with your user ID.</p>

<p>While we allow very open and customizable layouts and sidebars, we have a few rules that will be strictly enforced. Please read them over and follow them. Loss of post layout privileges will be enacted for those who are repeat offenders. If in doubt ask a staff member.</p>
<ul>
	<li>The styling the post layout applies should only apply to your particular post table. Please use your user-specific table classes and prefix any custom classes to prevent collisions.</li>
	<li>Post layouts should generally have a dark theme to match with the general darkness of the board.</li>
	<li>Keep a good colour contrast and make the post text readable (no bad colour combinations or obnoxious fonts)</li>
	<li>Images and other assets should be as small in filesize as possible.</li>
	<li>Things like CSS filters and animations are not allowed due to reduce the potential lag caused by them.</li>
</ul>
<p>Post layouts which are blatantly inappropriate or plain bad in any other regard will be removed by the discretion of the staff.</p>
HTML
], [
	'id' => 'usercols',
	'title' => 'What do the username colours mean?',
	'content' => <<<HTML
<p>They reflect the rank of the user, which are:</p>
<table class="center"><tr>$nctable</tr></table>
<p>Keep in mind that some users might have a custom colour assigned to them, usually if they are staff or friends with one.</p>
HTML
]];

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
