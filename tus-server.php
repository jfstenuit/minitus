<?php

$statedb = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '.state.sqlite';
$uploaddir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$jwtcookie = "CF_Authorization";

if (!isset($_COOKIE[$jwtcookie])) {
	header("HTTP/1.0 401 Unauthorized");
	exit(0);
}
list($jwtheader,$jwt,$jwtsignature) = explode('.',$_COOKIE[$jwtcookie]);
$jwt = json_decode(base64_decode($jwt),true);
if (!isset($jwt['email'])) {
	header("HTTP/1.0 401 Unauthorized");
	exit(0);
}

$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ||
           $_SERVER['SERVER_PORT']==443 ||
		   (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https')
		  ) ? 'https':'http';
$host = $_SERVER['HTTP_HOST'];
$self = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$db = new SQLite3( $statedb );
$sql="CREATE TABLE IF NOT EXISTS uploads (
  id char(32) NOT NULL,
  name varchar(255),
  size bigint(20),
  offset bigint(20) NOT NULL DEFAULT 0,
  created_at datetime,
  expires_at datetime
)";
$db->query($sql);

$method=$_SERVER['REQUEST_METHOD'];
if (array_key_exists('HTTP_X_HTTP_METHOD_OVERRIDE',$_SERVER)) {
	$method=$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
}

if (!array_key_exists('HTTP_TUS_RESUMABLE',$_SERVER) || ($_SERVER['HTTP_TUS_RESUMABLE'] != '1.0.0')) {
	header("HTTP/1.0 412 Precondition Failed");
	header('Tus-Version: 1.0.0');
	exit(0);
}

header('Tus-Resumable: 1.0.0');
if ($method=="HEAD") {
	$fileid = pathinfo($_SERVER['REQUEST_URI'],PATHINFO_FILENAME);
	$sql="SELECT offset,size FROM uploads WHERE id=:id";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id', $fileid, SQLITE3_TEXT);
	$result = $stmt->execute();
	$offset = null; $size = null;
	if ($result) {
		$row = $result->fetchArray();
		$offset = $row[0];
		$size = $row[1];
	}
	if (is_int($offset)) {
		// error_log("HEAD: File ".$fileid." found - offset:".$offset);
		header("HTTP/1.0 200 No Content");
		header('Upload-Offset: '.$offset);
		header('Upload-Length: '.$size);
		header('Cache-Control: no-store');
	} else {
		// error_log("HEAD: File ".$fileid." not found");
		header("HTTP/1.0 401 Not Found");
	}
	exit(0);
} elseif ($method=="PATCH") {
	$fileid = pathinfo($_SERVER['REQUEST_URI'],PATHINFO_FILENAME);
	$body = file_get_contents('php://input');
	if ( strlen($body) != $_SERVER['CONTENT_LENGTH'] ) {
		error_log("PATCH: body length = ".strlen($body)." announced length = ".$_SERVER['CONTENT_LENGTH']);
		header("HTTP/1.0 500 Content-Length and body length mismatch");
		exit(0);
	}
	// "c" : Open the file for writing only. If the file does not exist, it is created.
	$fh = fopen($uploaddir . DIRECTORY_SEPARATOR . $fileid, "c");
	fseek($fh,$_SERVER['HTTP_UPLOAD_OFFSET'],SEEK_SET);
	fwrite($fh,$body);
	fclose($fh);
	$newoffset = $_SERVER['HTTP_UPLOAD_OFFSET'] + $_SERVER['CONTENT_LENGTH'];
	$sql="UPDATE uploads SET offset=:offset WHERE id=:id";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id', $fileid, SQLITE3_TEXT);
	$stmt->bindValue(':offset', $newoffset, SQLITE3_INTEGER);
	$result = $stmt->execute();
	header("HTTP/1.0 204 No Content");
	header("Upload-Offset: ".$newoffset);
	exit(0);
} elseif ($method=="POST") {
	// New file
	$size=$_SERVER['HTTP_UPLOAD_LENGTH'];
	// 24 bytes encoded in Base64 is 32 characters
	$fileid = str_replace(array('+','/','='),array('-','_',''),base64_encode(openssl_random_pseudo_bytes(24,$cstrong)));
	
	foreach ( explode(',', $_SERVER['HTTP_UPLOAD_METADATA']) as $kv ) {
		list($k,$v) = explode(' ',$kv);
		$meta[$k] = base64_decode($v);
	}
	
	$now=time();
	$sql="INSERT INTO uploads(id,name,size,offset,created_at,expires_at)
		VALUES (:id,:name,:size,:offset,:created_at,:expires_at)";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id',$fileid,SQLITE3_TEXT);
	$stmt->bindValue(':name',$meta['name'],SQLITE3_TEXT);
	$stmt->bindValue(':size',$size,SQLITE3_INTEGER);
	$stmt->bindValue(':offset',0,SQLITE3_INTEGER);
	$stmt->bindValue(':created_at',date('Y-m-d\TH:i:s.Z\Z', $now),SQLITE3_TEXT);
	$stmt->bindValue(':expires_at',date('Y-m-d\TH:i:s.Z\Z', $now + 86400),SQLITE3_TEXT);
	$stmt->execute();
	
	header("HTTP/1.1 201 Created");
	header('Location: '.$scheme.'://'.$host.$self.$fileid);
}

?>
