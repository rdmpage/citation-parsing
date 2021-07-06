<?php

// Parse XML-marked results and output in relevant format.
// This where we'd need to di checking and post-processing
// to clean up the output.

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/author-parsing.php');

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

$csl_citations = array();

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
	
	// post process
	
	if (isset($obj->title))
	{
		$obj->title[0] = preg_replace('/\.$/', '', $obj->title[0]);
		$obj->title[0] = preg_replace('/\. —$/u', '', $obj->title[0]);
		$obj->title[0] = preg_replace('/^[—|-]\s+/u', '', $obj->title[0]);
	}

	if (isset($obj->date))
	{
		$obj->date[0] = preg_replace('/\(/', '', $obj->date[0]);
		$obj->date[0] = preg_replace('/[a-z]?\)/', '', $obj->date[0]);
		$obj->date[0] = preg_replace('/\./', '', $obj->date[0]);
	}
	
	//------------------------------------------------------------------------------------
	if (isset($obj->journal))
	{
		$obj->journal[0] = preg_replace('/\,$/', '', $obj->journal[0]);
		$obj->journal[0] = preg_replace('/^[—|-]\s+/u', '', $obj->journal[0]);
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
				
		// (10) 14(82):
		if (preg_match('/^\((?<series>[^\)]+)\)\s*(?<volume>\d+)\s*\((?<issue>[^\)]+)\)/', $obj->volume[0], $m))
		{
			$matched = true;
			$obj->{'collection-title'}[0] = $m['series'];
			$obj->volume[0] = $m['volume'];
			$obj->issue[0] = $m['issue'];
		}
		
		// (6) 10():
		if (preg_match('/^\((?<series>[^\)]+)\)\s*(?<volume>\d+)\s*\(\)/', $obj->volume[0], $m))
		{
			$matched = true;
			$obj->{'collection-title'}[0] = $m['series'];
			$obj->volume[0] = $m['volume'];
		}
		
		// 51():
		if (preg_match('/^(?<volume>\d+)\s*\(\):/', $obj->volume[0], $m))
		{
			$matched = true;
			$obj->volume[0] = $m['volume'];
		}
		
		// No. 	
		$obj->volume[0] = preg_replace('/No\.\s+/', '', $obj->volume[0]);
		// :
		$obj->volume[0] = preg_replace('/:$/', '', $obj->volume[0]);
		
		

	}
	
	if (isset($obj->pages))
	{
		$obj->pages[0] = preg_replace('/\./', '', $obj->pages[0]);
		$obj->pages[0] = preg_replace('/pp\s*/i', '', $obj->pages[0]);
		$obj->pages[0] = preg_replace('/–/u', '-', $obj->pages[0]);
		
		// should train this out
		// , 8 pls
		$obj->pages[0] = preg_replace('/,\s+\d+\s+pls$/i', '', $obj->pages[0]);
		// , pls 1-3
		$obj->pages[0] = preg_replace('/,?\s+pls(.*)$/i', '', $obj->pages[0]);
		
		
	}

	//------------------------------------------------------------------------------------
	if (isset($obj->publisher))
	{
		$obj->publisher[0] = preg_replace('/\,$/', '', $obj->publisher[0]);
	}
	
	if (isset($obj->location))
	{
		$obj->location[0] = preg_replace('/\,$/', '', $obj->location[0]);
	}
	
	//------------------------------------------------------------------------------------
	if (isset($obj->url))
	{
		if (preg_match('/https?:\/\/doi.org\/(?<doi>.*)/', $obj->url[0], $m))
		{
			$obj->DOI[0] = $m['doi'];
		}
	}
	
	//------------------------------------------------------------------------------------
	// authors
	if (isset($obj->author))
	{
		$authors = parse_author_string($obj->author[0]);
		
		if (count($authors->author) > 0)
		{
			$obj->author_parsed = $authors->author;
		}
	
	}
	
	//------------------------------------------------------------------------------------
	//editors
	if (isset($obj->editor))
	{
		$editor_string = $obj->editor[0];
		
		
		$editor_string = preg_replace('/^In:\s+/i', '', $editor_string);
		$editor_string = preg_replace('/\s+\(Ed[s]?[\.]?\),?/i', '', $editor_string);
		
		// echo $editor_string . "\n";
		
		$authors = parse_author_string($editor_string);
		
		if (count($authors->author) > 0)
		{
			$obj->editor_parsed = $authors->author;
		}
	
	}
	
	
	//------------------------------------------------------------------------------------
	// Generate CSL	
	$csl = new stdclass;
	
	// guess type
	$csl->type = 'article-journal';
	
	if (isset($obj->publisher))
	{
		$csl->type = 'book';
	}
	
	if (isset($obj->editor))
	{
		$csl->type = 'chapter';
	}
	
	if (isset($obj->author_parsed))
	{
		$csl->author = $obj->author_parsed;
	}
	
	if (isset($obj->editor_parsed))
	{
		$csl->editor = $obj->editor_parsed;
	}
	
	if (isset($obj->title))
	{
		$csl->title = $obj->title[0];
	}
	
	// journal or container
	if (isset($obj->journal))
	{
		$csl->{'container-title'} = $obj->journal[0];
	}
	if (isset($obj->{'container-title'}))
	{
		$csl->{'container-title'} = $obj->{'container-title'}[0];
	}

	// series
	if (isset($obj->{'collection-title'}))
	{
		$csl->{'collection-title'} = $obj->{'collection-title'}[0];
	}
	
	// collation	
	if (isset($obj->volume))
	{
		$csl->volume = $obj->volume[0];
	}
	if (isset($obj->issue))
	{
		$csl->issue = $obj->issue[0];
	}
	if (isset($obj->pages))
	{
		$csl->page = $obj->pages[0];
	}
	
	if (isset($obj->date))
	{
		$csl->issued = new stdclass;
		$csl->issued->{'date-parts'} = array();
		$csl->issued->{'date-parts'}[0] = array();

		$csl->issued->{'date-parts'}[0][] = (Integer)$obj->date[0];
	}
	
	if (isset($obj->DOI))
	{
		$csl->DOI = $obj->DOI[0];
	}
	
	$csl_citations[] = $csl;
	
	
	if (0)
	{
		echo '<pre>';
		print_r($obj);
		echo '</pre>';
	
		echo '<pre>';
		print_r($csl);
		echo '</pre>';
	}
	
	// post process if needed


	//file_put_contents($output_filename, $features . "\n\n", FILE_APPEND);
	

}

echo '<pre>';
echo json_encode($csl_citations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo '</pre>';



?>

