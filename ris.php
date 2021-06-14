<?php

/**
 * @file ris.php
 *
 */

require_once (dirname(__FILE__) . '/nameparse.php');

$debug = false;


$key_map = array(
	'ID' => 'publisher_id',
	'T1' => 'title',
	'TI' => 'title',
	'TT' => 'alternativetitle',
	'SN' => 'issn',
	'JO' => 'secondary_title',
	'JF' => 'secondary_title',
	'BT' => 'secondary_title', // To handle TROPICS fuckup
	'T2' => 'secondary_title', 
	'VL' => 'volume',
	'IS' => 'issue',
	'SP' => 'spage',
	'EP' => 'epage',
	'N2' => 'abstract',
	'UR' => 'url',
	'AV' => 'availability',
	'Y1' => 'year',
	'KW' => 'keyword',
	'L1' => 'pdf', 
	'N1' => 'notes',
	'L2' => 'fulltext', // check this, we want to have a link to the PDF...
	'L4' => 'thumbnail', 
	'DO' => 'doi', // Mendeley 0.9.9.2
	
	'DP' => 'contributor', // database provencance for BHL
	
	'C1' => 'collation', // WoRMS
	);
	
//--------------------------------------------------------------------------------------------------
function process_ris_key($key, $value, &$obj)
{
	global $key_map;
	global $debug;
	
	
	//echo "key=$key value=$value\n";
	
	switch ($key)
	{
		case 'AU':
		case 'A1':					
			// Interpret author names
			
			// Trim trailing periods and other junk
			//$value = preg_replace("/\.$/", "", $value);
			$value = preg_replace("/&nbsp;$/", "", $value);
			$value = preg_replace("/,([^\s])/", ", $1", $value);
			
			// Handle case where initials aren't spaced
			$value = preg_replace("/, ([A-Z])([A-Z])$/", ", $1 $2", $value);

			// Clean Ingenta crap						
			$value = preg_replace("/\[[0-9]\]/", "", $value);
			
			// Space initials nicely
			$value = preg_replace("/\.([A-Z])/", ". $1", $value);
			
			// Make nice
			$value = mb_convert_case($value, 
				MB_CASE_TITLE, mb_detect_encoding($value));
				
			if (0)
			{
							
				// Get parts of name
				$parts = parse_name($value);
				
				$author = new stdClass();
				
				if (isset($parts['last']))
				{
					$author->surname = $parts['last'];
				}
				if (isset($parts['suffix']))
				{
					$author->suffix = $parts['suffix'];
				}
				if (isset($parts['first']))
				{
					$author->forename = $parts['first'];
					
					if (array_key_exists('middle', $parts))
					{
						$author->forename .= ' ' . $parts['middle'];
					}
				}
			}
			else
			{
				$author = $value;
			}
			$obj->authors[] = $author;
			break;	
			
			// alternative athors, e.g. Japanese
		case 'AT':
			$obj->alternativeauthors[] = $value;	
			break;				
	
		case 'JF':
			$value = mb_convert_case($value, 
				MB_CASE_TITLE, mb_detect_encoding($value));
				
			$value = preg_replace('/ Of /', ' of ', $value);	
			$value = preg_replace('/ the /', ' the ', $value);	
			$value = preg_replace('/ and /', ' and ', $value);	
			$obj->{$key_map[$key]} = $value;
			break;
			
		case 'T1':
			$value = preg_replace('/([^\s])\(/', '$1 (', $value);	
			$value = str_replace("\ü", "ü", $value);
			$value = str_replace("\ö", "ö", $value);

			$value = str_replace("“", "\"", $value);
			$value = str_replace("”", "\"", $value);
						
			$obj->{$key_map[$key]} = $value;
			break;
				
		// Handle cases where both pages SP and EP are in this field
		case 'SP':
			if (preg_match('/^(?<spage>[0-9]+)\s*[-|–|—]\s*(?<epage>[0-9]+)$/u', trim($value), $matches))
			{
				$obj->spage = $matches['spage'];
				$obj->epage = $matches['epage'];
			}
			else
			{
				$obj->{$key_map[$key]} = $value;
			}				
			break;

		case 'EP':
			if (preg_match('/^(?<spage>[0-9]+)\s*[-|–|—]\s*(?<epage>[0-9]+)$/u', trim($value), $matches))
			{
				$obj->spage = $matches['spage'];
				$obj->epage = $matches['epage'];
			}
			else
			{
				$obj->{$key_map[$key]} = $value;
			}				
			break;
			
		case 'DA': // Used by Harvard for correspondence
		case 'PY': // used by Ingenta, and others
		case 'Y1':
		   $date = $value; 
		   
		   
		   //2001 [2002]
		   if (preg_match("/^(?<year>[0-9]{4})\s+\[/", $date, $matches))
		   {                       
				   $obj->year = $matches['year'];
		   }
		   
		   
		   if (preg_match("/(?<year>[0-9]{4})\/(?<month>[0-9]{1,2})\/(?<day>[0-9]{1,2})/", $date, $matches))
		   {                       
			   $obj->year = $matches['year'];
			   $obj->date = sprintf("%d-%02d-%02d", $matches['year'], $matches['month'], $matches['day']);			   
		   }
		   

		   if (preg_match("/^(?<year>[0-9]{4})\/(?<month>[0-9]{1,2})\/(\/)?$/", $date, $matches))
		   {                       
				   $obj->year = $matches['year'];
				   $obj->date = sprintf("%d-%02d-%02d", $matches['year'], $matches['month'], '0');					   
		   }

		   if (preg_match("/^(?<year>[0-9]{4})\/(?<month>[0-9]{1,2})$/", $date, $matches))
		   {                       
				   $obj->year = $matches['year'];
		   }

		   if (preg_match("/[0-9]{4}\/\/\//", $date))
		   {                       
			   $year = trim(preg_replace("/\/\/\//", "", $date));
			   if ($year != '')
			   {
					   $obj->year = $year;
			   }
		   }

		   if (preg_match("/^[0-9]{4}$/", $date))
		   {                       
				  $obj->year = $date;
		   }
		   
		   
		   if (preg_match("/^(?<year>[0-9]{4})\-[0-9]{2}\-[0-9]{2}$/", $date, $matches))
		   {
		   		$obj->year = $matches['year'];
				$obj->date = $date;
		   }
		   
		   /*
		   // 1957/1962
		   if (preg_match("/^(?<year>[0-9]{4}\/[0-9]{4})/", $date, $matches))
		   {                       
				   $obj->year = $matches['year'];
		   }
			*/
		   
		   //echo $value . "\n";
		   
		   
		   break;
		   
		case 'KW':
			$obj->keywords[] = $value;
			break;

	
		// Mendeley 0.9.9.2
		case 'DO':
			$obj->doi = $value;
			break;
			
		default:
			if (array_key_exists($key, $key_map))
			{
				// Only set value if it is not empty
				if ($value != '')
				{
					$obj->{$key_map[$key]} = $value;
				}
			}
			break;
	}
}



//--------------------------------------------------------------------------------------------------
function import_ris($ris, $callback_func = '')
{
	global $debug;
	
	//$debug = true;
	
	$volumes = array();
	
	$rows = explode("\n", $ris);
	
	//print_r($rows);
	
	$state = 1;	
		
	foreach ($rows as $r)
	{
		$parts = explode ("  - ", $r);
		
		$key = '';
		if (isset($parts[1]))
		{
			$key = trim($parts[0]);
			$value = trim($parts[1]); // clean up any leading and trailing spaces
		}
				
		if (isset($key) && ($key == 'TY'))
		{
			$state = 1;
			$obj = new stdClass();
			$obj->authors = array();
			
			if ('JOUR' == $value)
			{
				$obj->genre = 'article';
			}
			if ('ABST' == $value)
			{
				$obj->genre = 'article';
			}
		}
		if (isset($key) && ($key == 'ER'))
		{
			$state = 0;
						
			// Cleaning...									
			if ($debug)
			{
				print_r($obj);
			}	
			
			if ($callback_func != '')
			{
				echo "hello\n\n";
				$callback_func($obj);
			}
			
		}
		
		if ($state == 1)
		{
			if (isset($value))
			{
				process_ris_key($key, $value, $obj);
			}
		}
	}
	
	
}


//--------------------------------------------------------------------------------------------------
// Use this function to handle very large RIS files
function import_ris_file($filename, $callback_func = '')
{
	global $debug;
	$debug = false;
	//$debug = true;
	
	$file_handle = fopen($filename, "r");
			
	$state = 1;	
	
	while (!feof($file_handle)) 
	{
		$r = fgets($file_handle);
		$parts = explode ("  - ", $r);
		
		//print_r($parts);
		
		$key = '';
		if (isset($parts[1]))
		{
			$key = trim($parts[0]);
			$value = trim($parts[1]); // clean up any leading and trailing spaces
		}
				
		if (isset($key) && ($key == 'TY'))
		{
			$state = 1;
			$obj = new stdClass();
			$obj->authors = array();
			
			if ('JOUR' == $value)
			{
				$obj->genre = 'article';
			}
		}
		if (isset($key) && ($key == 'ER'))
		{
			$state = 0;
						
			// Cleaning...
			
			if (count($obj->authors) == 0)
			{
				unset($obj->authors);
			}

			if ($debug)
			{
				print_r($obj);
			}	
			
			
			
			if ($callback_func != '')
			{
				$callback_func($obj);
			}
			
		}
		
		if ($state == 1)
		{
			if (isset($value))
			{
				process_ris_key($key, $value, $obj);
			}
		}
	}
	
	
}


?>