<?php

// Parse XML-marked results and output in relevant format.
// This where we'd need to di checking and post-processing
// to clean up the output.

error_reporting(E_ALL);


$filename = '';
$output_filename = '';

if ($argc < 2)
{
	echo "Usage: parse_results.php <XML file>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
	$output_filename = basename($filename, '.xml') . '.out';	
}

//touch($output_filename);

// Parse XML file and extract individual tokens and their tags
$xml = file_get_contents($filename);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);

foreach($xpath->query('//sequence') as $node)
{
	$obj = new stdclass;

	foreach ($node->childNodes as $n) { 
		switch ($n->nodeName)
		{
			case '#text':
				break;
				
			default:
				$tag = $n->nodeName;
				$text = $n->firstChild->nodeValue;
				
				if (!isset($obj->{$tag}))
				{
					$obj->{$tag} = array();
				}
				
				$obj->{$tag}[] = $text;
				break;
		}
	} 
	
	print_r($obj);
	
	// post process if needed


	//file_put_contents($output_filename, $features . "\n\n", FILE_APPEND);
	

}



?>

