<?php

$notdeleted = ($userdata['rank'] < 2 ? 'WHERE f.deleted = 0' : '');
$ufields = userfields();
$files = query("SELECT $ufields f.*
		FROM uploader_files f JOIN users u ON u.id = f.user
		$notdeleted ORDER BY date DESC");

twigloader()->display('uploader.twig', [
	'files' => $files
]);
