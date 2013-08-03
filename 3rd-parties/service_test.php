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
$headers = getallheaders();
print_r($headers);


if( function_exists('apache_response_headers') ) {
	echo '--- APACHE HEADERS ---';
	$response_headers = apache_response_headers();
	print_r($response_headers);
}
?>