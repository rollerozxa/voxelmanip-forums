<?php
// ** Configuration File **
// Please look through the file and fill in the appropriate information.

$sqlhost = 'localhost';
$sqluser = 'sqlusername';
$sqlpass = 'sqlpassword';
$sqldb   = 'sqldatabase';

$trashid = 2; // Designates the id for your trash forum.
//$newsid = 3; // Designates the id for your announcements forum. (uncomment to enable)

$boardtitle = "Insert title here"; // This is what will be displayed at the top of your browser window.
$boarddesc = "A very cool forum."; // This is used for description meta tag.
$boardlogo = "theme/logo.png"; // The logo that is used for the header.

$defaulttheme = "0"; // Select the default theme to be used.
$defaulttimezone = "Europe/Stockholm"; // Default timezone if people do not select their own.

// Simple security question for the registration page. Uncomment to disable.
$puzzle = ["What is the square root of 144?", "12"];

//$override_theme = ''; // If you want to lock everyone to a specific theme.

$lockdown = false; // Put board in lockdown mode.
//$lockdownText = ""; // Custom text to be put in the lockdown mode page.
//$rainbowusers = false; // Make usernames all rainbowy!

// List of bots
$botlist = ['ia_archiver','baidu','yahoo','bot','spider'];

// Sample post that is shown on profile pages.
$samplepost = <<<HTML
[b]This[/b] is a [i]sample message.[/i] It shows how [u]your posts[/u] will look on the board.
[quote=Anonymous][spoiler]Hello![/spoiler][/quote]
[code]if (true) {\r
	print "The world isn't broken.";\r
} else {\r
	print "Something is very wrong.";\r
}[/code]
[url=]Test Link. Ooh![/url]
HTML;

// List of smilies
$smilies = [
	['text' => '-_-', 'url' => 'img/smilies/annoyed.png'],
	['text' => 'o_O', 'url' => 'img/smilies/bigeyes.png'],
	['text' => ':D', 'url' => 'img/smilies/biggrin.png'],
	['text' => 'o_o', 'url' => 'img/smilies/blank.png'],
	['text' => ':x', 'url' => 'img/smilies/crossmouth.png'],
	['text' => ';_;', 'url' => 'img/smilies/cry.png'],
	['text' => '^_^', 'url' => 'img/smilies/cute.png'],
	['text' => '@_@', 'url' => 'img/smilies/dizzy.png'],
	['text' => ':@', 'url' => 'img/smilies/dropsmile.png'],
	['text' => 'O_O', 'url' => 'img/smilies/eek.png'],
	['text' => '>:]', 'url' => 'img/smilies/evil.png'],
	['text' => ':eyeshift:', 'url' => 'img/smilies/eyeshift.png'],
	['text' => ':(', 'url' => 'img/smilies/frown.png'],
	['text' => '8-)', 'url' => 'img/smilies/glasses.png'],
	['text' => ':LOL:', 'url' => 'img/smilies/lol.png'],
	['text' => '>:[', 'url' => 'img/smilies/mad.png'],
	['text' => '<_<', 'url' => 'img/smilies/shiftleft.png'],
	['text' => '>_>', 'url' => 'img/smilies/shiftright.png'],
	['text' => 'x_x', 'url' => 'img/smilies/sick.png'],
	['text' => ':|', 'url' => 'img/smilies/slidemouth.png'],
	['text' => ':)', 'url' => 'img/smilies/smile.png'],
	['text' => ':P', 'url' => 'img/smilies/tongue.png'],
	['text' => ':B', 'url' => 'img/smilies/vamp.png'],
	['text' => ';)', 'url' => 'img/smilies/wink.png'],
	['text' => ':-3', 'url' => 'img/smilies/wobble.png'],
	['text' => ':S', 'url' => 'img/smilies/wobbly.png'],
	['text' => '>_<', 'url' => 'img/smilies/yuck.png'],
	['text' => ':box:', 'url' => 'img/smilies/box.png'],
	['text' => ':yes:', 'url' => 'img/smilies/yes.png'],
	['text' => ':no:', 'url' => 'img/smilies/no.png']
];

// Define this function to send a welcome PM to all newly registered users.
// function sendWelcomePM($username) { }


// Random forum descriptions.
// It will be replacing the value %%%RANDOM%%% in the forum description.
$randdesc = [
	"Value1",
	"Value2"
];
