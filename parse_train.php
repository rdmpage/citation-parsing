<?php

// Parse XML-marked up training data and output in CRF++ format to train a model

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/features.php');

$filename = '';
$output_filename = '';

if ($argc < 2)
{
	echo "Usage: parse_train.php <XML file>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
	//$output_filename = basename($filename, '.xml') . '.train';
	$output_filename = str_replace('.xml', '', $filename) . '.train';	
	
}

file_put_contents($output_filename, "");

// Parse XML file and extract individual tokens and their tags
$xml = file_get_contents($filename);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);

foreach($xpath->query('//sequence') as $node)
{
	$tokens = array();

	foreach ($node->childNodes as $n) { 
		switch ($n->nodeName)
		{
			case '#comment':
			case '#text':
				break;
				
			default:
				$tag = $n->nodeName;
				$text = $n->firstChild->nodeValue;
				$text = trim($text);
				$words = preg_split('/\s+/u', $text);
				
				foreach ($words as $word)
				{
					$token = new stdclass;
					$token->tag = $n->nodeName;
					$token->word = $word;
					$tokens[] = $token;
				}
				break;
		}
	} 
	
	// print_r($tokens);
	
	$features = get_features($tokens);
	
	// echo $features . "\n\n";
	
	file_put_contents($output_filename, $features . "\n\n", FILE_APPEND);
	

}



?>
