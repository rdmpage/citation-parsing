<?php


$citations = (isset($_GET['citations']) ? $_GET['citations'] : '');

if ($citations) 
{
	// 
	print_r($citations);
	
	$tmpdir = dirname(__FILE__) . '/tmp';
	$timestamp = time();
	
	$base_name =  $tmpdir . '/' . $timestamp;
	
	$text_filename = $base_name . '.txt';
	
	file_put_contents($text_filename, trim($citations));
	
	
	$command = 'php refs_to_train.php ' . $text_filename;	
	//echo '<pre>' . $command . '</pre><br>';	
	system($command);
	
	$command = 'php parse_train.php ' . $base_name . '.src.xml';
	//echo '<pre>' . $command . '</pre><br>';
	system($command);
	
	$command = '/usr/local/bin/crf_test  -m core.model ' . $base_name . '.src.train > ' . $base_name . '.out.train';
	//echo '<pre>' . $command . '</pre><br>';
	system($command);
	
	$command = 'php parse_results_to_xml.php ' . $base_name . '.out.train > ' . $base_name . '.out.xml';
	//echo '<pre>' . $command . '</pre><br>';
	system($command);
	
	$command = 'php parse_results_to_native.php ' . $base_name . '.out.xml';
	//echo '<pre>' . $command . '</pre><br>';
	system($command);
	
	
	
	
	
}
else
{

?>

<html>
<head>
	<meta charset="utf-8" />
	<title>Citations</title>
</head>
<body>
<h1>
	Parse citations
</h1>

<div>

	<form id="form" action="./" method="get">
<textarea id="citations"  name="citations" rows="5" cols="80">
Bokermann, W. C. A. 1950. Rescriçao e novo nome genérico para Coelonotus fissilis Mir. Rib.,1920. Papéis Avulsos de Zoologia. São Paulo 9: 215–222. 
</textarea>

    <br />
   <input type="submit" value="Parse" />
   </form>
</div>



</body>
</html>

<?php
}
?>
