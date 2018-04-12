<?php

header('content-type:text/plain');

interface Trigger {
	
	public function __construct(PDO $db);
	
	public function fresh(array $files);
	
	public function changed(array $files);
	
	public function steady(array $files);
	
	public function obsolete(array $files);
	
}

// use ffprobe to determine meta data
// ffmpeg BIN-directory must be set in PATH variable on windows

class FFProbeTrigger implements Trigger {
	
	public $ffprobe = 'ffprobe -loglevel quiet -show_format -show_streams -print_format json';
	
	public function __construct(PDO $db){
		$this->db = $db;
	}
	
	public function fresh(array $files){
		$update = $this->db->prepare('
			update movies
			set
				ffprobe = ?
			where path = ?
		');
		foreach($files as $path => $file){
			$ffprobe = shell_exec("{$this->ffprobe} \"{$path}\"");
			$update->execute([$ffprobe, $path]);
		}
	}
	
	public function changed(array $files){
		
	}
	
	public function steady(array $files){
		
	}
	
	public function obsolete(array $files){
		
	}
	
}

// basic sync of local files with the database

class FileSyncTrigger implements Trigger {
	
	public function __construct(PDO $db){
		$this->db = $db;
	}
	
	public function fresh(array $files){
		$stmt = $this->db->prepare('
			insert into movies (path, dirname, basename, extension, filename, size, modified)
			values(?, ?, ?, ?, ?, ?, ?)
		');
		foreach($files as $file){
			$stmt->execute([$file['path'], $file['dirname'], $file['basename'], $file['extension'], $file['filename'], $file['size'], $file['modified']]);
		}
	}
	
	public function changed(array $files){
		$stmt = $this->db->prepare('
			update movies
			set
				dirname = ?,
				basename = ?,
				extension = ?,
				filename = ?,
				size = ?,
				modified = ?
			where path = ?
		');
		foreach($files as $file){
			$stmt->execute([$file['dirname'], $file['basename'], $file['extension'], $file['filename'], $file['size'], $file['modified'], $file['path']]);
		}
	}
	
	public function steady(array $files){
	}
	
	public function obsolete(array $files){
		$stmt = $this->db->prepare('
			delete from movies
			where path = ?
		');
		foreach($files as $file){
			$stmt->execute([$file['path']]);
		}
	}
	
}

// prepare local files to be cached within database and determine their status

class FileCache {
	
	public $paths = [];
	public $files = [];
	public $db = null;
	public $dbfiles = [];
	public $trigger = [];
	
	// grab all local files
	
	public function __construct(PDO $db, array $patterns){
		$this->db = $db;
		foreach($patterns as $pattern){
			$this->paths = array_merge($this->paths, glob($pattern, GLOB_BRACE));
		}
		$files = [];
		array_walk($this->paths, function($path, $p) use (&$files){
			$files[$path] = pathinfo($path);
			$files[$path]['path'] = $path;
			$files[$path]['size'] = filesize($path);
			$files[$path]['modified'] = filemtime($path);
		});
		$this->files = $files;
	}
	
	// create database
	
	public function setup(){
		// $this->db->query('
			// drop table movies;
		// ');
		$this->db->query('
			create table if not exists movies(
				path text,
				dirname text,
				basename text,
				extension text,
				filename text,
				size int,
				modified int,
				ffprobe text
			);
		');
		$stmt = $this->db->prepare('PRAGMA table_info(movies);');
		$stmt->execute();
		$info = $stmt->fetchAll();
		$columns = array_column($info, 'name');
		$this->db->columns = $columns;
	}
	
	// detect and group files if they are new, have (not) changed, or can be deleted from DB
	
	public function detect(){
		$stmt = $this->db->prepare('
			select *
			from movies
		');
		$stmt->execute();
		$dbfiles = $stmt->fetchAll();
		foreach($dbfiles as $dbfile){ // index by path
			$this->dbfiles[$dbfile['path']] = $dbfile;
		}
		$this->fresh = array_diff_key($this->files, $this->dbfiles);
		$this->obsolete = array_diff_key($this->dbfiles, $this->files);
		$this->steady = array_intersect_key($this->files, $this->dbfiles);
		$this->changed = [];
		array_walk($this->steady, function($file, $f){
			if($this->steady[$f]['size'] != $this->dbfiles[$f]['size'] || $this->steady[$f]['modified'] != $this->dbfiles[$f]['modified']){
				$this->changed[$f] = $file;
				unset($this->steady[$f]);
			}
		});
	}
	
	// process detected files with different methods
	
	public function trigger(array $triggers){
		foreach($triggers as $trigger){
			$this->trigger[] = $inst = new $trigger($this->db);
			$inst->fresh($this->fresh);
			$inst->changed($this->changed);
			$inst->steady($this->steady);
			$inst->obsolete($this->obsolete);
		}
	}
	
}

$db = new PDO('sqlite:filecache.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$cache = new FileCache($db, ['I:\\JD\*.{avi,mkv}']);
$cache->setup();
$cache->detect();
$cache->trigger([FileSyncTrigger::class, FFProbeTrigger::class]);

print_r($cache); exit();
