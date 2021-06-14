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
	//$output_filename = basename($filename, '.xml') . '.out';	
	$output_filename = str_replace('.xml', '', $filename) . '.out';	
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
	
	// clean
	
	if (isset($obj->title))
	{
		$obj->title[0] = preg_replace('/\.$/', '', $obj->title[0]);
	}

	if (isset($obj->date))
	{
		$obj->date[0] = preg_replace('/\(/', '', $obj->date[0]);
		$obj->date[0] = preg_replace('/[a-z]?\)/', '', $obj->date[0]);
		$obj->date[0] = preg_replace('/\./', '', $obj->date[0]);
	}
	
	if (isset($obj->journal))
	{
		$obj->journal[0] = preg_replace('/\,$/', '', $obj->journal[0]);
	}

	if (isset($obj->volume))
	{
		$matched = false;
		
		if (preg_match('/^(?<volume>\d+)[:|,]$/', $obj->volume[0], $m))
		{
			$matched = true;
			$obj->volume[0] = $m['volume'];
		}

		if (preg_match('/^(?<volume>\d+)\s*\((?<issue>[^\)]+)\)/', $obj->volume[0], $m))
		{
			$matched = true;
			$obj->volume[0] = $m['volume'];
			$obj->issue[0] = $m['issue'];
		}

	}
	

	if (isset($obj->pages))
	{
		$obj->pages[0] = preg_replace('/\./', '', $obj->pages[0]);
	}
	
	
	
	
	echo '<pre>';
	print_r($obj);
	echo '</pre>';
	
	// post process if needed


	//file_put_contents($output_filename, $features . "\n\n", FILE_APPEND);
	

}



?>

