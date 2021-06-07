<?php

// Parse results and convert to marked-up content, e.g. XML


$filename = '';
if ($argc < 2)
{
	echo "Usage: results.php <output file>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$state = 0;
$current_tag = '';
$tag_count = -1;

$data = array();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo "<dataset>\n";

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	if ($line == '')
	{
		if ($state == 1)
		{
			// emit
			//print_r($data);
			
			echo "<sequence>\n";

			
			foreach ($data as $d)
			{
				echo '<' . $d->tag . '>';
				echo htmlspecialchars(join(' ', $d->tokens), ENT_XML1 | ENT_COMPAT, 'UTF-8');
				echo '</' . $d->tag . '>';
				echo ' ';
			}
			echo "\n";
			echo "</sequence>\n";
						
		}
		$state = 0;
		$data = array();
		$current_tag = '';
		$tag_count = -1;
		
	}
	else
	{
		$state = 1;
	}
	
	if ($state == 1)
	{
		$row = preg_split("/[\s|\t]+/u", $line);	
		
		// print_r($row);
	
	
		$n = count($row);
		$tag = $row[$n - 1];
		
		if ($tag != $current_tag)
		{
			$tag_count++;			
			$data[$tag_count] = new stdclass;
			$data[$tag_count]->tag = $tag;
			$data[$tag_count]->tokens =array();
			
			$current_tag = $tag;
		}
		
		$data[$tag_count]->tokens[] = $row[0];
	}
}

echo "</dataset>\n";


?>

