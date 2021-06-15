<?php

// Parse XML-marked up training data and look at patterns,
// for example, how many times does a date appear in a title of an article?

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/features.php');

$filename = '';
$output_filename = '';

if ($argc < 2)
{
	echo "Usage: understand_train.php <XML file>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
	//$output_filename = basename($filename, '.xml') . '.train';
	//$output_filename = str_replace('.xml', '', $filename) . '.train';	
	
}

//file_put_contents($output_filename, "");

// Parse XML file and extract individual tokens and their tags
$xml = file_get_contents($filename);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);

$count = 0;

$pattern_count = 0;
$pattern = '/\)\.?$/';
$pattern = '/[0-9]{4}/';
$pattern = '/n\.\s*sp\./';

foreach($xpath->query('//sequence') as $node)
{
	$tokens = array();
	
	$count++;

	foreach ($node->childNodes as $n) { 
		switch ($n->nodeName)
		{
			case '#text':
				break;
				
			default:
				$tag = $n->nodeName;
				$text = $n->firstChild->nodeValue;
				$text = trim($text);
				
				
				
				if ($tag == 'title')
				{
					if (preg_match($pattern, $text))
					{
						echo $text . "\n";
						$pattern_count++;
					}
				}
				
				
				
				/*
				$words = preg_split('/\s+/u', $text);
				
				foreach ($words as $word)
				{
					$token = new stdclass;
					$token->tag = $n->nodeName;
					$token->word = $word;
					$tokens[] = $token;
				}
				*/
				break;
		}
	} 
}


echo "$count training examples\n";
echo "$pattern_count matching \"$pattern\"\n";


?>

