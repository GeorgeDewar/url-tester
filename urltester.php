<?php

$results = null;

if($_SERVER['REQUEST_METHOD'] == 'POST'){

	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['urls']) as $url){
		testUrl($url);
	} 
	
}

function testUrl($url){
	if(trim($url) == '') return;

	$headers = getHeaders($url);
	if($headers == null) return;
	
	$i = 0;
	echo $url;
	
	foreach($headers as $header){
		// If this header is actually the HTTP response header
		if(strpos(strtolower($header), 'http/') === 0){
			echo ', ';
			
			$code = substr($header,9);
			if(startsWith($code, '3')){
				// This is a redirect, so find the Location header
				$location = findLocation($headers, $i);
				echo 'Redirect (' . $code . ') to \'' . $location . '\'';
			}
			elseif(startsWith($code, '2')){
				// All good
				echo 'OK (' . $code . ')';
			}
			else{
				// Uh oh
				echo 'Error (' . $code . ')';
			}
		}
		$i++;
	}
}

function getHeaders($url){
	$opts = array(
	  'http'=>array(
		'method'=>"GET",
		'header'=>"User-Agent: URL Tester\r\n"
	  )
	);

	$context = stream_context_create($opts);
	@file_get_contents($url, false, $context);
	
	if(isset($http_response_header)) return $http_response_header;
	else echo $url . ', Error (could not connect)';
}

function startsWith($string, $test){
	return strpos($string, $test) === 0;
}

function findLocation($headers, $start){
	for($j = $start; $j<sizeof($headers); $j++){
		if(startsWith($headers[$j], 'Location')){
			return substr($headers[$j], 10);
		}
	}
	throw new Exception('Cannot find Location header');
}

?>
<!DOCTYPE html>
<html>
<head>
<title>URL Tester</title>
</head>
<body>

<h1>URL Tester</h1>

<form action="" method="post">
	<p>Enter a list of URLs into the box below, then click Go.</p>
	<textarea name="urls" rows="10" cols="100" placeholder="Your URL list here"><?php if (isset($_POST['urls'])) echo $_POST['urls']; ?></textarea>
	<br />
	<input type="submit" value="Go" />
</form>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	echo '<h1>Results</h1> <p>';

	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['urls']) as $url){
		testUrl($url);
		echo '<br />';
	} 
	
	echo '</p>';
}
?>

</body>
</html>
