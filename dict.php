<?php

error_reporting(E_ALL);

function load_dictionary($filename = "data/parsCitDict.txt")
{
	$mode = 0;

	$dict = array();

	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$line = trim(fgets($file_handle));
	
		if ($line != '')
		{
	
			if (preg_match('/^\#\# Male/', $line)) { $mode = 1; }			# male names
			else if (preg_match('/^\#\# Female/', $line)) { $mode = 2; }		# female names
			else if (preg_match('/^\#\# Last/', $line)) { $mode = 4; }		# last names
			else if (preg_match('/^\#\# Chinese/', $line)) { $mode = 4; }		# last names
			else if (preg_match('/^\#\# Months/', $line)) { $mode = 8; }		# month names
			else if (preg_match('/^\#\# Place/', $line)) { $mode = 16; }		# place names
			else if (preg_match('/^\#\# Publisher/', $line)) { $mode = 32; }	# publisher names
			else if (preg_match('/^\#/', $line)) {}
			else
			{
				$key = $line;
				
				$parts = explode("\t", $line);
				if (count($parts) > 1)
				{
					$key = $parts[0];
				}
		
				if (!isset($dict[$key]))
				{
					$dict[$key] = 0;
				}
				if ($dict[$key] < $mode)
				{
					$dict[$key] += $mode;
				}
	
			}
		}	
	}
	
	return $dict;	
}

//print_r($dict);


/*
sub readDict {
  my $dictFileLoc = shift @_;
  my $mode = 0;
  open (DATA, $dictFileLoc) || die "$progname fatal\t\tCannot open \"$dictFileLoc\"!";
  while (<DATA>) {
    if (/^\#\# Male/) { $mode = 1; }			  # male names
    elsif (/^\#\# Female/) { $mode = 2; }		# female names
    elsif (/^\#\# Last/) { $mode = 4; }			  # last names
    elsif (/^\#\# Chinese/) { $mode = 4; }		  # last names
    elsif (/^\#\# Months/) { $mode = 8; }		 # month names
    elsif (/^\#\# Place/) { $mode = 16; }		 # place names
    elsif (/^\#\# Publisher/) { $mode = 32; }	     # publisher names
    elsif (/^\#/) { next; }
    else {
      chop;
      my $key = $_;
      my $val = 0;
      if (/\t/) {				     # has probability
	($key,$val) = split (/\t/,$_);
      }

      # already tagged (some entries may appear in same part of lexicon more than once
      if ($dict{$key} >= $mode) { next; }
      else { $dict{$key} += $mode; }		      # not yet tagged
    }
  }
  close (DATA);
}
*/

