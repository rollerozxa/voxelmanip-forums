<?php
needsLogin();

$notdeleted = !IS_MOD ? 'WHERE f.user = '.$userdata['id'] : '';
$ufields = userfields();
$files = query("SELECT $ufields f.*
		FROM uploader_files f JOIN users u ON u.id = f.user
		$notdeleted ORDER BY date DESC");

twigloader()->display('uploader.twig', [
	'files' => $files
]);
