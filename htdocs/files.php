<?php

if(array_key_exists("QUERY_DEBUG", $_GET)) {
	define("QUERY_DEBUG", TRUE);
} else {
	define("QUERY_DEBUG", FALSE);
}
define("APP_PATH", "/home/vcap/app");
$db = "";
$GLOBALS['msg'] = "";

function db_connect() {
	global $db;
	
	$mysqlUser = 'SPBC_DASHBOARD_so';
	$mysqlPass = 'Spbc2021';
	$mysqlDb = 'SPBC_DASHBOARD';
	$hostName = 'maria4011-lb-fm-in.dbaas.intel.com';
	$hostPort = '3307';

	$db = mysqli_init();
	mysqli_ssl_set($db, NULL, NULL, '../intel_certs.pem', NULL, NULL);
	mysqli_real_connect($db, $hostName, $mysqlUser, $mysqlPass, $mysqlDb, $hostPort, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
}

function untar($targz) {
	$tar = preg_replace("/.gz/","",$targz);
	
	// clean up old files since the server only has 4G of disk space
	exec("cd /home/vcap/app; find files* -mmin +240 | grep -v blank.txt | xargs rm -rf &");
	
	try {
		if($targz == "") { return 0; }
		
		$GLOBALS['msg'] .= "Expanding $targz<br>";
		if(file_exists($tar)) {
			unlink($tar); }
			
		$p = new PharData($targz);
		$p->decompress();
		$p->extractTo(APP_PATH."/files", null, true);
		
		unlink($tar);
		unlink($targz);
		$GLOBALS['msg'] .= "Finished expanding $targz<br>";
		return 1;
	} catch(Exception $e) {
		$GLOBALS['msg'] .= $e;
		if(file_exists($tar)) unlink($tar);
		if(file_exists($targz)) unlink($targz);
		
		print "<h1>Failed to untar archive</h1><p>Couldn't untar archive $targz.</p><p>Debug info: ".$GLOBALS['msg']."</p>";
		http_response_code(503);
		exit;
		
		return 0;
	}
}

function find_tar($filename) {
	if($filename == "" || $filename == "/") {
		return "";
	}
	
	$GLOBALS['msg'] .= "Finding tar for $filename<br>";
	
	$tar = APP_PATH."/files_tar/$filename.tar.gz";
	if(file_exists($tar)) {
		return $tar;
	} else {
		return find_tar(preg_replace("/\/[^\/]*$/","",$filename));
	}
}

function is_valid_file($filename) {
	return file_exists(APP_PATH."/files_tar/$filename.tar.gz");
}

function serve_file($filename, $ignore_mtime=false) {
	$absfile = APP_PATH."/files/$filename";
	
	if(file_exists($absfile)) {
		if($ignore_mtime || filemtime($absfile) >= time() - 10*60) {
			header("X-Files-Debug: Hello ".urlencode($GLOBALS['msg'])."");
			header("Content-Type: ".mime_content_type_custom($absfile)."; charset=UTF-8");
			echo file_get_contents($absfile);
			return true;
		}
	}
	
	return false;
}

function queryRow($query, $default="") {
    global $db;
    $startTime = microtime(TRUE);

	$res = $db->query($query);
	if($res !== FALSE && $res->num_rows > 0) {
		$data = $res->fetch_all(MYSQLI_ASSOC);
		$val = $data[0];
		$res->free();
	} else {
		$val = FALSE;
	}

    if(QUERY_DEBUG) { printf("$query took %0.3f sec<br>", ((microtime(TRUE)-$startTime))); }

    if($val !== FALSE && $val != "") {
        return $val;
    } else {
        return $default;
    }
}

function queryRows($query, $default="") {
    global $db;
    $startTime = microtime(TRUE);

	$res = $db->query($query);
	if($res !== FALSE && $res->num_rows > 0) {
		$data = $res->fetch_all(MYSQLI_ASSOC);
		$val = $data;
		$res->free();
	} else {
		$val = FALSE;
	}

    if(QUERY_DEBUG) { printf("$query took %0.3f sec<br>", ((microtime(TRUE)-$startTime))); }

    if($val !== FALSE && $val != "") {
        return $val;
    } else {
        return $default;
    }
}

function retrieve_file($filename) {
	$absfile = APP_PATH."/files/$filename";
	$local_mtime = 0;
	
	db_connect();
	
	if(file_exists($absfile)) {
		$local_mtime = filemtime($absfile);
		$GLOBALS['msg'] .= "local_mtime=$local_mtime ";
	}
	
	$row = find_row_from_db($filename);
	
	if(count($row) > 0) {
		$filename = $row['filename'];
		$targz = APP_PATH."/files_tar/$filename.tar.gz";
		$GLOBALS['msg'] .= "db_mtime=".$row['mtime']." ";
		if($row['mtime'] > $local_mtime) {
			$row = queryRow("SELECT data FROM files WHERE filename='$filename'", array());
			$fh = fopen($targz, "wb");
			fwrite($fh, $row['data']);
			fclose($fh);
			
			return untar($targz);
		}
		
		return true; // local mtime newer than DB, didn't need to redo download/untar
	} else {
		return false;
	}
}

function find_row_from_db($filename) {
	if($filename == "" || $filename == "/") {
		return array();
	}
	
	$GLOBALS['msg'] .= "Searching DB for $filename<br>";
	
	$row = queryRow("SELECT filename, mtime FROM files WHERE filename='$filename'", array());
	if(count($row) > 0) {
		return $row;
	} else {
		$new_filename = preg_replace("/\/[^\/]*$/","",$filename);
		if($new_filename == $filename) {
			return array();
		}
		return find_row_from_db($new_filename);
	}
}

function print_files() {
	db_connect();
	
	// too slow to get data sizes $rows = queryRows("SELECT filename, mtime, OCTET_LENGTH(data) as datasize FROM files", array());
	$rows = queryRows("SELECT filename, mtime FROM files", array());
	
	print "<table><tr><th>Filename</th><th>Uploaded Time</th></tr>\n";
	
	foreach ($rows as $row) {
		print "<tr><td><a href=\"/files/".$row['filename']."/\">".$row['filename']."</a></td><td>".date("F j, Y, g:i a", $row['mtime'])."</td></tr>\n";
	}
	
	print "</table>";
}


$file = $_GET["file"];

if($file == "" || $file == "/") {
	print_files();
	exit();
}

if(preg_match("/coverage-/", $file) && substr($file, -1) == "/") {
	$file .= "dashboard.html";
} else if(substr($file, -1) == "/") {
	$file .= "index.html";
}

if(!serve_file($file)) {
	if(retrieve_file($file)) {
		if(serve_file($file, true)) {
			exit();
		} else {
			$GLOBALS['msg'] = "<h1>Page not found</h1><p>Couldn't find file $file.</p><p>Debug info: ".$GLOBALS['msg']."</p>";
			http_response_code(404);
		}
	} else {
		$GLOBALS['msg'] = "<h1>Directory not found</h1><p>Couldn't find archive for $file.</p><p>Debug info: ".$GLOBALS['msg']."</p>";
		http_response_code(400);
	}
}

print $GLOBALS['msg'];









// Build-in mime_content_type doesn't work well on CloudFoundry, so used this one from the PHP comments
function mime_content_type_custom($filename) {

	$mime_types = array(

		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',
		'flv' => 'video/x-flv',

		// images
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// archives
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'exe' => 'application/x-msdownload',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',

		// audio/video
		'mp3' => 'audio/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',

		// adobe
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',

		// ms office
		'doc' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',

		// open office
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	);

	$ext = strtolower(array_pop(explode('.',$filename)));
	if (array_key_exists($ext, $mime_types)) {
		return $mime_types[$ext];
	}
	elseif (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $filename);
		finfo_close($finfo);
		return $mimetype;
	}
	else {
		return 'application/octet-stream';
	}
}


?>
