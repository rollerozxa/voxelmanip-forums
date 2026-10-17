<?php
needsLogin();

if ($userdata['joined'] > time() - 1*30*24*3600)
	error('403', 'You cannot use the site\'s file uploader yet.');

$notdeleted = !IS_MOD ? 'WHERE f.user = '.$userdata['id'] : '';
$ufields = userfields();
$files = query("SELECT $ufields f.*
		FROM uploader_files f JOIN users u ON u.id = f.user
		$notdeleted ORDER BY date DESC");

twigloader()->display('uploader.twig', [
	'files' => $files
]);
