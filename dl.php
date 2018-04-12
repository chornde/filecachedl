<?php

if(empty($_GET['file'])){
	header('HTTP/1.0 404 Not Found');
	exit();
}

// db lookup

$db = new PDO('sqlite:filecache.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$stmt = $db->prepare('select path from movies where basename = ? limit 1');
$stmt->execute([$_GET['file']]);
$file = $stmt->fetch();

if(empty($file)){
	header('HTTP/1.0 404 Not Found');
	exit();
}

// stream output

header('Content-Disposition: attachment; filename="'.$_GET['file'].'"');

$file = fopen($file['path'], 'rb');
$out = fopen('php://output', 'wb');

stream_copy_to_stream($file, $out);

fclose($out);
fclose($file);
