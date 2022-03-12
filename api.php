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
	
	
	$doi_hack = false;
	
	if ($doi_hack)
	{
		// Try to protect DOIs from being broken up by inserting spaces between punctuation
		// Regular expression from https://www.wikidata.org/wiki/Property:P356
	
		preg_match_all('/((DOI:\s*|doi:\s*|https?:\/\/(dx\.)?doi.org\/)10\.[0-9]{4,}(?:\.[0-9]+)*(?:\/|%2F)(?:(?![\"&\'])\S)+)/', $citations, $m);
		foreach ($m[0] as $doi)
		{
			$doi_string = $doi;
		
			// replace periods in DOIs by •
			$doi_string = str_replace('.', '•', $doi_string);
		
			$citations = str_replace($doi, $doi_string, $citations);
		}
	
		// space between punctuation and following alphanumeric token token
		$citations = preg_replace('/([:|,|\.])([A-Za-z0-9])/', "$1 $2", $citations);	
	
		// restore periods in DOis
		$citations = preg_replace('/•/u', ".", $citations);
	
		// restore DOI prefix
		$citations = preg_replace('/(DOI:)\s+/i', "$1", $citations);	
	}
	else
	{
		// space between punctuation and following alphanumeric token token
		// note that we ignore ".", if we don't then we need hack to handle "."
		// within DOIs and URLs.
		$citations = preg_replace('/([:|,])([A-Za-z0-9])/', "$1 $2", $citations);	
	}
	
	$citations = preg_replace('/\s+\/\/\s+/', ". ", $citations);	
					
	// trim letters from dates
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
			
		case 'tsv':
			$csl = json_decode($json);
			
			$rows = array();
			
			foreach ($csl as $bib)
			{
				$rows[] = csl_to_tsv($bib);
			}
			
			$response = join("\n", $rows);
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
