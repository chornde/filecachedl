<!DOCTYPE html>
<html>
	<head>
		<title>Movies</title>
		<link href="https://fonts.googleapis.com/css?family=Tajawal" rel="stylesheet">
		<style>
			html { background-color:#eee; }
			.fil { display:grid; grid-template-columns:33.33% 33.33% 33.33%; width:80%; max-width:700px; margin:15px auto; }
			.nam { grid-row:1; grid-column:1/3; }
			.res { grid-row:1; grid-column:3/4; }
			.ext { grid-row:2; grid-column:1/2; }
			.siz { grid-row:2; grid-column:2/3; }
			.dur { grid-row:2; grid-column:3/4; }
			
			a { color: #fb7; }
			.fil { overflow:hidden; border-radius:5px; font:15px Tajawal; color:white; }
			.fil > * { padding:5px 7px; }
			.nam, .res { background: linear-gradient(to bottom, #4c4c4c 0%,#595959 25%,#666666 50%,#474747 80%,#2c2c2c 100%);  }
			.ext, .siz, .dur { background: linear-gradient(to bottom, #000000 0%,#111111 30%,#2b2b2b 50%,#1c1c1c 70%,#131313 80%);  }
		</style>
	</head>
	<body>
		<?php if(!empty($files)): ?>
			<?php foreach($files as $file): ?>
				<div class="fil">
					<div class="nam"><a href="dl.php?file=<?=$file['basename']?>"><?=$file['filename']?></a></div>
					<div class="res"><?=videoformat($file['meta'])?></div>
					<div class="ext"><?=strtoupper($file['extension'])?> <?=videodimensions($file['meta'])?></div>
					<div class="siz"><?=filesize_humanreadable($file['size'])?></div>
					<div class="dur"><?=videoduration($file['meta'])?></div>
				</div>
			<?php endforeach; ?>
		<?php else: ?>
			<p>keine Dateien vorhanden</p>
		<?php endif; ?>
	</body>
</html>
<pre><?#=print_r($files);?></pre>