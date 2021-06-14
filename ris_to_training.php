<?php

// RIS to training data

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/ris.php');


//----------------------------------------------------------------------------------------

$xml = '';

function training($reference)
{
	global $xml;
	
	$xml .=  '<sequence>' . "\n";

	//print_r($reference);
	
	// order to process
	$keys = array(
		'authors',
		'year',
		'title',
		'secondary_title',
		'volume',
		//'issue',
		'spage',
		//'epage'
		'doi'
	);
	
	foreach ($keys as $k)
	{
		if (isset($reference->{$k}))
		{
			$v = $reference->{$k};
			switch ($k)
			{
				case 'authors':
					$authors = join(", ", $v);
					$xml .= '<author>';
					$xml .= htmlspecialchars($authors, ENT_XML1 | ENT_COMPAT, 'UTF-8');
					$xml .= '</author>';			
					break;
			
			
				case 'title':
					$title = $v;
					$title = strip_tags($title);
					
					if (!preg_match('/[\.|\?]$/', $title))
					{
						$title .= '.';
					}
					$xml .= '<' . $k . '>';
					$xml .= htmlspecialchars($title, ENT_XML1 | ENT_COMPAT, 'UTF-8');
					$xml .= '</' . $k . '>';			
					break;
				
				case 'secondary_title':
					$xml .= '<journal>';
					$xml .= htmlspecialchars($v, ENT_XML1 | ENT_COMPAT, 'UTF-8');
					$xml .= '</journal>';			
					break;
			
				case 'volume':
					$volume = $v;
					if (isset($reference->issue))
					{
						$volume .= '(' . $reference->issue . ')';
					}
					$volume .= ':';
					$xml .= '<volume>';
					$xml .= htmlspecialchars($volume, ENT_XML1 | ENT_COMPAT, 'UTF-8');
					$xml .= '</volume>';	
					break;
						
				case 'spage':
					$pages = $v;
					if (isset($reference->epage))
					{
						$pages .= '-' . $reference->epage;
					}
					$xml .= '<pages>';
					$xml .= htmlspecialchars($pages, ENT_XML1 | ENT_COMPAT, 'UTF-8');
					$xml .= '</pages>';	
					break;
			
				case 'year':
					$year = '(' . $v . ')';
			
					$xml .= '<date>';				
					$xml .= htmlspecialchars($year, ENT_XML1 | ENT_COMPAT, 'UTF-8');
					$xml .= '</date>';			
					break;	

				default:
					break;
			}	
		}
	
	}

	$xml .=  '</sequence>'. "\n";
	
}


$filename = '';
if ($argc < 2)
{
	echo "Usage: ris_to_training.php <RIS file>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}


$file = @fopen($filename, "r") or die("couldn't open $filename");
fclose($file);


$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= "<dataset>\n";

import_ris_file($filename, 'training');

$xml .=  '</dataset>'. "\n";

echo $xml;


?>