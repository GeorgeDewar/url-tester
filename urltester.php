<?php

// The set of URLs and the results of testing them
$results = array();

// If the user has clicked Go, test the URLs
if($_SERVER['REQUEST_METHOD'] == 'POST'){

	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['urls']) as $url){
		// Ignore URL if empty
		if(trim($url) == '') continue;

		array_push($results, testUrl($url));
	} 
	
}

class TestResult{
	public $url;				// The URL that was tested
	public $results = array();	// The results of testing this URL
	public $warnings = array();	// Warnings which may not indicate that the URL is not working
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
	$opts = array(
	  'http'=>array(
		'method'=>"GET",
		'header'=>"User-Agent: URL Tester\r\n"
	  )
	);

	$context = stream_context_create($opts);
	@file_get_contents($url, false, $context);
	
	if(isset($http_response_header)) return $http_response_header;
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

<?php if($_SERVER['REQUEST_METHOD'] == 'POST'){ ?>
	<h1>Results</h1>
	<table style="border: 1px;">
	<tr>
		<td>URL</td><td>Results</td><td>Warnings</td>
	</tr>
	<?php foreach($results as $result){ ?>
		<tr>
			<td><?php echo $result->url; ?></td>
			<td><?php echo implode($result->results, ', '); ?></td>
			<td><?php echo implode($result->warnings, ', '); ?></td>
		</tr>
	<?php } ?>
	</table>
<?php } ?>

</body>
</html>
