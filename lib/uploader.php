<?php

const UPLOADS_DIR = 'data/uploads';
const UPLOADER_MAX_SIZE = 1*1024*1024;

function sizeunit($bytes, $precision = 2) {
	$units = ['B', 'kB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
	return round($bytes, $precision).' '.$units[$pow];
}

function getSize($id, $filename) {
	return sizeunit(filesize(uploadPath($id, $filename)));
}

function uploadPath($id, $filename) {
	return UPLOADS_DIR.'/'.$id.'.'.strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function uploadUrl($id, $filename) {
	return '/uploads/'.$id.'.'.strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function generateId($length = 10) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $max = strlen($alphabet) - 1;
    $id = '';
    for ($i = 0; $i < $length; $i++)
        $id .= $alphabet[random_int(0, $max)];

    return $id;
}
