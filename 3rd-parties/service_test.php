<?php
header('Content-Type: text/plain');

if( rand() < 0.5 ) echo "success\n";
?>
--- POST ---
<?php print_r($_POST) ?>

--- GET ---
<?php print_r($_GET) ?>

--- META ---
<?php
print_r(array(
		'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD']
		, 'QUERY_STRING' => $_SERVER['QUERY_STRING']
		, 'HTTP_HOST' => $_SERVER['HTTP_HOST']
		, 'HTTP_REFERER' => $_SERVER['HTTP_REFERER']
		, 'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT']
	));
?>

--- HEADERS ---
<?php
if( function_exists('http_get_request_headers')) {
	echo "(http-request-headers)\n";

	$headers = http_get_request_headers();
}
elseif( function_exists('getallheaders')) {
	echo "(getallheaders)\n";
	$headers = getallheaders();
}
elseif( function_exists('apache_response_headers') ) {
	echo "(apache_response_headers)\n";
	$headers = apache_response_headers();
}
else {
	echo "(extracted)\n";
	// http://www.php.net/manual/en/function.getallheaders.php#84262
	$headers = array();
	foreach ($_SERVER as $name => $value) {
		if (substr($name, 0, 5) == 'HTTP_') {
			$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		}
	}
}
print_r($headers);
?>