<!DOCTYPE html>
<html>
<head>
<title>URL Tester</title>
</head>
<body>

<h1>URL Tester</h1>

<form action="" method="post">
	<p>Enter a list of URLs into the box below, then click Go.</p>
	<textarea name="urls" rows="10" cols="100" placeholder="Your URL list here"><?php if (isset($_POST['urls'])) echo htmlspecialchars($_POST['urls']); ?></textarea>
	<br />
	<input type="submit" name="display" value="Go" /> <a href="urltester.php">Reset</a>
</form>

<?php if($_SERVER['REQUEST_METHOD'] == 'POST'){ ?>
	<h1>Results</h1>
	<a href="?download">Download as CSV</a>
	<table style="border: 1px;">
	<tr>
		<td>URL</td><td>Results</td><td>Warnings</td>
	</tr>
	<?php foreach($results as $result){ ?>
		<tr>
			<td><?php echo htmlspecialchars($result->url); ?></td>
			<td><?php echo htmlspecialchars(implode($result->results, ', ')); ?></td>
			<td><?php echo htmlspecialchars(implode($result->warnings, ', ')); ?></td>
		</tr>
	<?php } ?>
	</table>
<?php } ?>

</body>
</html>
