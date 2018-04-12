<?php

# core functions

function filenameshort(string $name) : string {
	return (strlen($name) > 50) ? substr($name, 0, 30).'...' : $name ;
}

function filesize_humanreadable(int $size) : string {
	switch(true) {
		case $size > 1000000000:
			$size = $size / 1000000000;
			$unit = 'GB';
			break;
		case $size > 1000000:
			$size = $size / 1000000;
			$unit = 'MB';
			break;
		case $size > 1000:
			$size = $size / 1000;
			$unit = 'KB';
			break;
	}
	$size = round($size, 1);
	return "$size $unit";
}

function videodimensions(array $ffprobe) : string {
	$width = $ffprobe['streams'][0]['width'];
	$height = $ffprobe['streams'][0]['height'];
	return "{$width}x{$height}";
}

function videoformat(array $ffprobe) : string {
	$width = $ffprobe['streams'][0]['width'];
	switch(true){
		case $width >= 3840:
			$format = 'UltraHD';
			break;
		case $width >= 1920:
			$format = 'FullHD';
			break;
		case $width >= 1280:
			$format = '720p';
			break;
		case $width >= 0:
			$format = 'SD';
			break;
	}
	return $format;
}

function videoduration(array $ffprobe) : string {
	$duration = $ffprobe['format']['duration'];
	$min = $duration / 60;
	return round($min).' Min.';
}

$db = new PDO('sqlite:filecache.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$stmt = $db->prepare('select * from movies');
$stmt->execute();
$files = $stmt->fetchAll();
array_walk($files, function(&$file){
	$file['meta'] = json_decode($file['ffprobe'], true);
});

require 'directorylisting.tpl';
