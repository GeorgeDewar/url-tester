<?php

session_start();

// The set of URLs and the results of testing them
$results = array();

// If the user has clicked Go, test the URLs
if(isset($_POST['display'])){
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['urls']) as $url){
		// Ignore URL if empty
		if(trim($url) == '') continue;

		array_push($results, testUrl($url));
	} 
	
	$_SESSION['last_results'] = $results;
	
	include 'main.view.php';
}
elseif(isset($_GET['download'])){
	$results = $_SESSION['last_results'];
	
	header('Content-Type: application/csv');
	header('Content-Disposition: attachment; filename=urltester.csv');
	header('Pragma: no-cache');

	echo "URL,Final Status,Warnings,Details\n";
	foreach($results as $result){
		echo csvEscape($result->url);
		echo ',' . csvEscape($result->results[count($result->results) - 1]);
		echo ',' . csvEscape(implode($result->warnings, ', '));
		echo ',' . csvEscape(implode($result->results, ', '));
	}
}

class TestResult{
	public $url;				// The URL that was tested
	public $results = array();	// The results of testing this URL
	public $warnings = array();	// Warnings which may not indicate that the URL is not working
}

function csvEscape($text){
	return '"' . str_replace('"','""',$text) . '"';
}

function testUrl($url){
	$result = new TestResult();
	$result->url = $url;
	
	if(trim($url) != $url)
		array_push($result->warnings, 'URL has leading or trailing whitespace');
	
	// Trim any whitespace from the URL
	$url = trim($url);
	
	// Fetch the page, and get the headers
	$headers = getHeaders($url);
	if($headers == -1){
		array_push($result->results, 'Error (could not connect)');
		return $result;
	}
	
	$i = 0;
	foreach($headers as $header){
		// If this header is actually the HTTP response header
		if(strpos(strtolower($header), 'http/') === 0){
			
			$code = substr($header,9);
			if(startsWith($code, '3')){
				// This is a redirect, so find the Location header
				$location = findLocation($headers, $i);
				array_push($result->results, 'Redirect (' . $code . ') to \'' . $location . '\'');
			}
			elseif(startsWith($code, '2')){
				// All good
				array_push($result->results, 'OK (' . $code . ')');
			}
			else{
				// Uh oh
				array_push($result->results, 'Error (' . $code . ')');
			}
		}
		$i++;
	}
	
	return $result;
}

function getHeaders($url){
	stream_context_set_default(
	  array('http'=>array(
		'method'=>"GET",
		'header'=>"User-Agent: URL Tester\r\n"
	  ))
	);

	$headers = @get_headers($url);
	
	if($headers != null) return $headers;
	else return -1;
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

