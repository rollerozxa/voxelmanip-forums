<?php
require("lib/common.php");

needs_login();

$targetuserid = $_GET['id'] ?? $loguser['id'];
$act = $_POST['action'] ?? '';

if ($act == 'Edit profile') {
	if ($_POST['pass'] != '' && $_POST['pass'] == $_POST['pass2'] && $targetuserid == $loguser['id']) {
		$newtoken = bin2hex(random_bytes(32));
		setcookie('token', $newtoken, 2147483647);
	}
}

$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$targetuserid]);

if ($loguser['id'] != $targetuserid && ($loguser['powerlevel'] < 3 || $loguser['powerlevel'] <= $user['powerlevel']))
	error("You have no permissions to do this!");

if (!$user) error("This user doesn't exist!");

$user['timezone'] = $user['timezone'] ?: $defaulttimezone;

$canedituser = $loguser['powerlevel'] > 2 && ($loguser['powerlevel'] > $user['powerlevel'] || $targetuserid == $loguser['id']);

if ($act == 'Edit profile') {
	$error = '';

	if ($_POST['pass'] && $_POST['pass2'] && $_POST['pass'] != $_POST['pass2'])
		$error = "- The passwords you entered don't match.<br>";

	$fname = $_FILES['picture'];
	if ($fname['size'] > 0) {
		$ftypes = ['png','jpeg','jpg','gif'];
		$res = getimagesize($fname['tmp_name']);

		if (!in_array(str_replace('image/','',$res['mime']),$ftypes))
			$error .= "- Invalid file type.<br>";
		elseif ($res[0] > 180 || $res[1] > 180)
			$error .= "- The image is too big.<br>";
		elseif ($fname['size'] > 81920)
			$error .= "- The image filesize too big.<br>";
		else {
			if (!move_uploaded_file($fname['tmp_name'], "userpic/$user[id]")) {
				$error .= "- Error creating avatar file.<br>";
			}
		}

		if (!$error) $usepic = 1;
	}
	if (isset($_POST['picturedel'])) $usepic = 0;

	$pass = (strlen($_POST['pass2']) ? $_POST['pass'] : '');

	//Validate birthday values.
	$bday = (int)($_POST['birthD'] ?? null);
	$bmonth = (int)($_POST['birthM'] ?? null);
	$byear = (int)($_POST['birthY'] ?? null);

	if ($bday > 0 && $bmonth > 0 && $byear > 0 && $bmonth <= 12 && $bday <= 31 && $byear <= 3000)
		$birthday = $byear.'-'.str_pad($bmonth, 2, "0", STR_PAD_LEFT).'-'.str_pad($bday, 2, "0", STR_PAD_LEFT);

	if ($canedituser) {
		$targetgroup = $_POST['powerlevel'];

		if ($targetgroup >= $loguser['powerlevel'] && $targetgroup != $user['powerlevel']) {
			$error .= "- You do not have the permissions to assign this group.<br>";
		}

		$targetname = $_POST['name'];

		if ($sql->result("SELECT COUNT(name) FROM users WHERE name = ? AND id != ?", [$targetname, $user['id']])) {
			$error .= "- Name already in use.<br>";
		}
	}

	if (checkcusercolor()) {
		//Validate Custom username color is a 6 digit hex RGB color
		$_POST['nick_color'] = ltrim($_POST['nick_color'], '#');

		if ($_POST['nick_color'] != '') {
			if (!preg_match('/^([A-Fa-f0-9]{6})$/', $_POST['nick_color'])) {
				$error .= "- Custom usercolor is not a valid RGB hex color.<br>";
			}
		}
	}

	if (!$error) {
		// Temp variables for dynamic query construction.
		$fieldquery = '';
		$placeholders = [];

		$fields = [
			'location' => $_POST['location'] ?: null,
			'birth' => $birthday ?? null,
			'bio' => $_POST['bio'] ?: null,
			'email' => $_POST['email'] ?: null,
			'showemail' => isset($_POST['showemail']) ? 1 : 0,
			'head' => $_POST['head'] ?: null,
			'sign' => $_POST['sign'] ?: null,
			'signsep' => isset($_POST['signsep']) ? 1 : 0,
			'theme' => $_POST['theme'] != $defaulttheme ? $_POST['theme'] : null,
			'timezone' => $_POST['timezone'] != $defaulttimezone ? $_POST['timezone'] : null,
			'ppp' => $_POST['ppp'],
			'tpp' => $_POST['tpp'],
			'blocklayouts' => isset($_POST['blocklayouts']) ? 1 : 0,
		];

		if (isset($usepic))
			$fields['usepic'] = $usepic;

		if (isset($_POST['rankset']))
			$fields['rankset'] = $_POST['rankset'];

		if ($pass) {
			$fields['password'] = password_hash($pass, PASSWORD_DEFAULT);
			$fields['token'] = $newtoken;
		}

		if (checkcusercolor())
			$fields['nick_color'] = $_POST['nick_color'];

		if (checkctitle())
			$fields['title'] = $_POST['title'];

		if (isset($targetname))
			$fields['name'] = $targetname;

		if (isset($targetgroup) && $targetgroup != 0)
			$fields['powerlevel'] = $targetgroup;

		// Construct a query containing all fields.
		foreach ($fields as $fieldk => $fieldv) {
			if ($fieldquery) $fieldquery .= ',';
			$fieldquery .= $fieldk.'=?';
			$placeholders[] = $fieldv;
		}

		// 100% safe from SQL injection because no arbitrary user input is ever put directly
		// into the query, rather it is passed as a prepared statement placeholder.
		$placeholders[] = $user['id'];
		$sql->query("UPDATE users SET $fieldquery WHERE id = ?", $placeholders);

		redirect("profile.php?id=$user[id]");
	} else {
		noticemsg("Couldn't save the profile changes. The following errors occured:<br><br>" . $error);

		foreach ($_POST as $k => $v)
			$user[$k] = $v;
		$user['birth'] = $birthday;
	}
}

pageheader('Edit profile');

$listtimezones = [];
foreach (timezone_identifiers_list() as $tz)
	$listtimezones[$tz] = $tz;

$birthM = $birthD = $birthY = '';
if ($user['birth']) {
	$birthday = explode('-', $user['birth']);
	$birthY = $birthday[0]; $birthM = $birthday[1]; $birthD = $birthday[2];
}

$passinput = '<input type="password" name="pass" size="13" maxlength="32"> Retype: <input type="password" name="pass2" size="13" maxlength="32">';
$birthinput = sprintf(
	'<input type="text" name="birthD" size="5" maxlength="2" value="%s" placeholder="Day">
	<input type="text" name="birthM" size="5" maxlength="2" value="%s" placeholder="Month">
	<input type="text" name="birthY" size="5" maxlength="4" value="%s" placeholder="Year">',
$birthD, $birthM, $birthY);

$erasepfp = ($user['usepic'] ? ' or <input type="checkbox" name="picturedel" value="1" id="picturedel"><label for="picturedel">Erase existing avatar</label>' : '');

echo '<form action="editprofile.php?id='.$targetuserid.'" method="post" enctype="multipart/form-data"><table class="c1">'
.	catheader('Edit profile')
.	catheader('Login information')
.($canedituser ? fieldrow('Username', fieldinput(40, 255, 'name')) : fieldrow('Username', $user['name']))
.fieldrow('Password', $passinput)
.	catheader('Appearance')
.($canedituser ? fieldrow('Rank', fieldselect('powerlevel', $user['powerlevel'], $powerlevels)) : '')
.(count($rankset_names) > 1 ? fieldrow('Rankset', fieldselect('rankset', $user['rankset'], ranklist())) : '')
.((checkctitle()) ? fieldrow('Title', fieldinput(40, 255, 'title')) : '')
.fieldrow('Avatar', '<input type="file" name="picture" size="40">'.$erasepfp
		.'<br><span class="sfont">Must be PNG, JPG or GIF, within 80KB and 180x180.</span>')
.(checkcusercolor() ? fieldrow('Custom colour', sprintf('<input type="color" name="nick_color" value="#%s">', $user['nick_color'])) : '')
.	catheader('User information')
.fieldrow('Location', fieldinput(40, 60, 'location'))
.fieldrow('Birthday', $birthinput)
.fieldrow('Bio', fieldtext(5, 80, 'bio'))
.fieldrow('Email address', fieldinput(40, 60, 'email')
		.'<br>'.fieldcheckbox('showemail', $user['showemail'], 'Show email on profile page'))
.	catheader('Post layout')
.fieldrow('Header', fieldtext(7, 80, 'head'))
.fieldrow('Signature', fieldtext(7, 80, 'sign'))
.fieldrow('Signature line', fieldcheckbox('signsep', $user['signsep'], 'Show signature separator'))
.	catheader('Options')
.fieldrow('Theme', fieldselect('theme', $user['theme'] ?? $defaulttheme, themelist(), "themePreview(this.value)"))
.fieldrow('Timezone', fieldselect('timezone', $user['timezone'], $listtimezones))
.fieldrow('Posts per page', fieldinput(3, 3, 'ppp'))
.fieldrow('Threads per page', fieldinput(3, 3, 'tpp'))
.fieldrow('Post layouts', fieldcheckbox('blocklayouts', $user['blocklayouts'], 'Block all post layouts'))
.	catheader('&nbsp;'); ?>
<tr class="n1"><td class="b"></td><td class="b"><input type="submit" name="action" value="Edit profile"></td>
</table></form><?php

pagefooter();
