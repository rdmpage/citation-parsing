<?php

// Generate features from an array of token+tags 

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/dict.php');

// Load dictionary
$dict = load_dictionary();

//----------------------------------------------------------------------------------------
// polyfill from https://www.php.net/manual/en/function.mb-str-split.php#125429
if (!function_exists('mb_str_split'))
{
	function mb_str_split($string, $split_length = 1, $encoding = null)
	{
		if (null !== $string && !\is_scalar($string) && !(\is_object($string) && \method_exists($string, '__toString'))) {
			trigger_error('mb_str_split(): expects parameter 1 to be string, '.\gettype($string).' given', E_USER_WARNING);
			return null;
		}
		if (null !== $split_length && !\is_bool($split_length) && !\is_numeric($split_length)) {
			trigger_error('mb_str_split(): expects parameter 2 to be int, '.\gettype($split_length).' given', E_USER_WARNING);
			return null;
		}
		$split_length = (int) $split_length;
		if (1 > $split_length) {
			trigger_error('mb_str_split(): The length of each segment must be greater than zero', E_USER_WARNING);
			return false;
		}
		if (null === $encoding) {
			$encoding = mb_internal_encoding();
		} else {
			$encoding = (string) $encoding;
		}
	
		if (! in_array($encoding, mb_list_encodings(), true)) {
			static $aliases;
			if ($aliases === null) {
				$aliases = [];
				foreach (mb_list_encodings() as $encoding) {
					$encoding_aliases = mb_encoding_aliases($encoding);
					if ($encoding_aliases) {
						foreach ($encoding_aliases as $alias) {
							$aliases[] = $alias;
						}
					}
				}
			}
			if (! in_array($encoding, $aliases, true)) {
				trigger_error('mb_str_split(): Unknown encoding "'.$encoding.'"', E_USER_WARNING);
				return null;
			}
		}
	
		$result = [];
		$length = mb_strlen($string, $encoding);
		for ($i = 0; $i < $length; $i += $split_length) {
			$result[] = mb_substr($string, $i, $split_length, $encoding);
		}
		return $result;
	}
}

//----------------------------------------------------------------------------------------
// Take tokens and generate features
function get_features ($tokens)
{
	global $dict;

	$result = array();
	
	// Feature based on whole line
	$words = array();
	
	foreach ($tokens as $token)
	{
		$words[] = $token->word;
	}
	
	$text = join (" ", $words);
	$hasPossibleEditor = (preg_match('/(ed\.|editor|editors|eds\.)/i', $text)) ? "possibleEditors" : "noEditors";

	// Features for each token
	$current_token_number = 0;

	foreach ($tokens as $token)
	{
		// features
		$current_token_number++;
		
		// prep
		$word = $token->word;
		
		$wordNP = $token->word; // no punctuation
		$wordNP = preg_replace('/[^\\p{L}|\d]/u', '', $wordNP);
		if (preg_match('/^\s*$/u', $wordNP))
		{
			$wordNP = "EMPTY";
		}
		$wordLCNP = mb_strtolower($wordNP);
		if (preg_match('/^\s*$/u', $wordLCNP))
		{
			$wordLCNP = "EMPTY";
		}
		
		// feature
		// 0
		$features[0] = $word;
		
		// split word into characters
		if (function_exists('mb_str_split'))
		{
			$chars = mb_str_split($word);
		}
		else
		{
			$chars = my_mb_str_split($word);
		}
		
		// print_r($chars);
		
		$numchars = count($chars);
		
		$lastChar = $chars[$numchars - 1];
		if (preg_match('/\p{L}/u', $lastChar))
		{
			$lastChar = 'a';
		}
		if (preg_match('/\p{Lu}/u', $lastChar))
		{
			$lastChar = 'A';
		}
		if (preg_match('/\d/u', $lastChar))
		{
			$lastChar = '0';
		}
		$features[1] = $lastChar; // 1 = last char
		
 		// 2-5
		$features[2] = $chars[0]; // 2 = first char
		$features[3] = join("", array_slice($chars, 0, 2)); // 3 = first 2 chars
		$features[4] = join("", array_slice($chars, 0, 3)); // 4 = first 3 chars
		$features[5] = join("", array_slice($chars, 0, 4)); // 5 = first 4 chars
		
		// 6-9
		$features[6] = $chars[$numchars - 1]; // 6 = last char
		$features[7] = join("", array_slice($chars, $numchars - 2)); // 7 = last 2 chars
		$features[8] = join("", array_slice($chars, $numchars - 3)); // 8 = last 3 chars
		$features[9] = join("", array_slice($chars, $numchars - 4)); // 9 = last 4 chars
		
		// 10
		$features[10] = $wordLCNP; // 10 = lowercased word, no punct
		
		// 11 capitalization
		$ortho = 'others';
		if (preg_match('/^\p{Lu}$/u', $wordNP))
		{
			$ortho = "singleCap";
		} 
		else if (preg_match('/^\p{Lu}+$/u', $wordNP))
		{
			$ortho = "AllCap";
		} 
		else if (preg_match('/^\p{Lu}\p{L}+/u', $wordNP))
		{
			$ortho = "InitCap";
		}
		$features[11] = $ortho;
		
		// 12 - numbers
		$num = 'nonNum';
		
		if (preg_match('/^(19|20)[0-9][0-9]$/', $wordNP))
		{
			$num = 'year';
		}
		else if (preg_match('/[0-9][\-|–][0-9]/u', $word))
		{
			$num = 'possiblePage';
		}
		else if (preg_match('/[0-9]\([0-9]+\)/', $word))
		{
			$num = 'possibleVol';
		}
		else if (preg_match('/^[0-9]$/', $wordNP))
		{
			$num = '1dig';
		}
		else if (preg_match('/^[0-9][0-9]$/', $wordNP))
		{
			$num = '2dig';
		}
		else if (preg_match('/^[0-9][0-9][0-9]$/', $wordNP))
		{
			$num = '3dig';
		}
		else if (preg_match('/^[0-9]+$/', $wordNP))
		{
			$num = '4+dig';
		}
		else if (preg_match('/^[0-9]+(th|st|nd|rd)$/', $wordNP))
		{
			$num = 'ordinal';
		}
				
		$features[12] = $num;
		
		// gazetteer (names)
		
		
		// gazetteer
		$dictStatus = 0;
		if (isset($dict[$wordLCNP]))
		{
			$dictStatus = $dict[$wordLCNP];
		}
		$isInDict = $dictStatus;
		
      	if ($dictStatus & 32) { $dictStatus ^ 32; $publisherName = "publisherName"; } else { $publisherName = "no"; }
    	if ($dictStatus & 16) { $dictStatus ^ 16; $placeName = "placeName"; } else { $placeName = "no"; }
      	if ($dictStatus & 8) { $dictStatus ^ 8; $monthName = "monthName"; } else { $monthName = "no"; }
      	if ($dictStatus & 4) { $dictStatus ^ 4; $lastName = "lastName"; } else { $lastName = "no"; }
      	if ($dictStatus & 2) { $dictStatus ^ 2; $femaleName = "femaleName"; } else { $femaleName = "no"; }
      	if ($dictStatus & 1) { $dictStatus ^ 1; $maleName = "maleName"; } else { $maleName = "no"; }
	
		$features[13] = $isInDict;  		# 13 = name status
		$features[14] = $maleName; 			# 14 = male name
		$features[15] = $femaleName; 		# 15 = female name
		$features[16] = $lastName;			# 16 = last name
		$features[17] = $monthName; 		# 17 = month name
		$features[18] = $placeName; 		# 18 = place name
		$features[19] = $publisherName; 	# 19 = publisher name
						
		$features[20] = $hasPossibleEditor;	# 20 = possible editor
		
		// relative location
		$location = floor(($current_token_number - 1) / count($tokens) * 12);
		$features[21] = $location; // 21 = relative location
		
		// 22 punctuation
		$punct = 'others';
		if (preg_match('/^[\"\'\`\‘]/', $word))
		{
			$punct = 'leadQuote';
		}
		else if (preg_match('/[\"\'\`\’][^s]?$/', $word))
		{
			$punct = 'endQuote';
		}
		else if (preg_match('/\-.*\-/', $word))
		{
			$punct = 'multiHyphen';
		}
		else if (preg_match('/[\-\,\:\;–]$/u', $word))
		{
			$punct = 'contPunct';
		}
		else if (preg_match('/[\!\?\.\"\']$/', $word))
		{
			$punct = 'stopPunct';
		}
		else if (preg_match('/^[\(\[\{\<].+[\)\]\}\>].?$/', $word))
		{
			$punct = 'braces';
		}
		else if (preg_match('/^[0-9]{2-5}\([0-9]{2-5}\).?$/', $word))
		{
			$punct = 'possibleVol';
		}		
 		$features[22] = $punct;
		
		$features[23] = $token->tag;
		
  
		//echo "Features\n";
		
		
		// print_r($features);
		
		$result[] = join(" ", $features);

	}

	return join("\n", $result);
}





?>