<?php

error_reporting(E_ALL);

global $config;

// Date timezone
date_default_timezone_set('UTC');

$local = false;
//$local = true;

if ($local)
{
	// Globaly installed CRF++
	$config['crf_path'] = '/usr/local/bin';

	// Mac M1
	$config['crf_path'] = '/opt/homebrew/bin';
}
else
{
	// Local to this site (e.g., when running on Heroku)
	$config['crf_path'] = dirname(__FILE__) . '/src';
}

?>
