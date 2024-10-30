<?php
// prior checks
if(
	!isset($_REQUEST['src']) || empty($_REQUEST['src']) || 
	!isset($_REQUEST['baseurl']) || empty($_REQUEST['baseurl']) ||
	!isset($_REQUEST['subj']) || !in_array($_REQUEST['subj'], array('js', 'css')) 
) {
	header ($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	die('missing parameter');	
}




// recall engine
require_once('classes/engine.php');

$scripts = explode('|', $_REQUEST['src']);
$lcso = new lcso_engine($_REQUEST['subj'], $scripts, $_REQUEST['baseurl']);



// setup custom options
$opts = array();

if(isset($_REQUEST['no_import'])) {
	$opts['no_import'] = true;	
}

if(isset($_REQUEST['no_css_min'])) {
	$opts['no_css_min'] = true;	
}

if(isset($_REQUEST['min_js'])) {
	$opts['min_js'] = explode('|', $_REQUEST['min_js']);	
}




// check for browser cache
$last_modified = 'Sun, 01 Jan 2014 00:00:00 GMT';
$max_age = 60 * 60 * 24 * 30; // 30 days
$etag = md5( 'lcso'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"] );

if ( 
	(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime($last_modified)) || 
	(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag)
) {
	header('HTTP/1.1 304 Not Modified');
	die();
} 




// get optimized code
$code = $lcso->create_code($opts);
if(empty($code)) {die();}




// setup headers and print code
$header_type = ($_REQUEST['subj'] == 'js') ? 'text/javascript' : 'text/css'; 
header('Content-type: '.$header_type);

$size = (function_exists('mb_strlen')) ? mb_strlen($code, '8bit') : strlen($code);
header('Content-Length: '. $size);

header('HTTP/1.1 200 Ok');
header("Expires: Tue, 01 Jan 2019 00:00:00 GMT");
header("Cache-Control: max-age={$max_age}, public, must-revalidate");
header("Last-Modified: {$last_modified}");
header("ETag: {$etag}");
  

// end printing
die($code);


