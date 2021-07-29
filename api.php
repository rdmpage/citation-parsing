<?php

// api

error_reporting(E_ALL);

require_once('vendor/autoload.php');

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

require_once('csl_utils.php');

//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//----------------------------------------------------------------------------------------
function display_parse($citations, $format= 'json', $callback = '')
{

	$config['crf_path'] = '/usr/local/bin';
	$config['crf_path'] = dirname(__FILE__) . '/src';

	// clean up to improve parsing
	
	// remove bad characters
	
	$citations = preg_replace('/\x18/', "", $citations);
	
	// space between colon and following alphanumeic token token
	$citations = preg_replace('/:([A-Za-z0-9])/', ": $1", $citations);
	
	// trim letters form dates
	$citations = preg_replace('/([0-9]{4})[a-z]/', "$1", $citations);

	// 
	//print_r($citations);
	
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
	
	$command = $config['crf_path'] . '/crf_test  -m core.model ' . $base_name . '.src.train > ' . $base_name . '.out.train';
	//echo '<pre>' . $command . '</pre><br>';
	system($command);
	
	$command = 'php parse_results_to_xml.php ' . $base_name . '.out.train > ' . $base_name . '.out.xml';
	//echo '<pre>' . $command . '</pre><br>';
	system($command);
	
	$command = 'php parse_results_to_native.php ' . $base_name . '.out.xml';
	//echo '<pre>' . $command . '</pre><br>';
	
	
	$output = array();	
	exec($command, $output);	
	$json = join("\n", $output);
	
	$response = '';
	$response_mimetype = "text/plain";
		
	switch ($format)
	{
		case 'html':
			$csl = json_decode($json);
			$style = 'apa';
		
			$style_sheet = StyleSheet::loadStyleSheet($style);
			$citeProc = new CiteProc($style_sheet);
			$response = $citeProc->render($csl, "bibliography");
			break;	
			
		case 'ris':
			$csl = json_decode($json);
			
			$ris = '';
			foreach ($csl as $bib)
			{
				$ris .= csl_to_ris($bib);
				$ris .= "\n";
			}
			
			$response = $ris;
			break;	
	
		case 'openurl':
			$csl = json_decode($json);
			$n = count($csl);
			for ($i = 0; $i < $n; $i++)
			{
				$csl[$i]->openurl = csl_to_openurl($csl[$i]);
			}
			
			$json = json_encode($csl, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			$response = $json;
			$response_mimetype = "application/json";
			break;	
	
		case 'json':
			default:		
			$response = $json;
			$response_mimetype = "application/json";
			break;
	
	}
	
	header("Content-type: " . $response_mimetype);
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	echo $response;
	if ($callback != '')
	{
		echo ')';
	}			
	
	
	


}



//----------------------------------------------------------------------------------------
function main()
{
	$callback = '';
	$handled = false;	
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Submit job
	if (!$handled)
	{
		$citations = (isset($_GET['text']) ? $_GET['text'] : '');	
		if ($citations)
		{
			$format = 'json';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}			
			
			if (!$handled)
			{
				display_parse($citations, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	
	if (!$handled)
	{
		default_display();
	}	

}


main();


?>

