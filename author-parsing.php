<?php

error_reporting(E_ALL);

//----------------------------------------------------------------------------------------
function clean_family($str)
{
	$str = mb_convert_case($str, MB_CASE_TITLE);
	
	$str = str_replace(' Von ', ' von ', $str);
	return $str;
}

//----------------------------------------------------------------------------------------
function clean_given($str)
{
	$str = preg_replace('/(\p{Lu})\./u', '$1. ', $str);
	$str = preg_replace('/\s\s+/u', ' ', $str);
	
	return $str;
}

//----------------------------------------------------------------------------------------
function parse_author_string($str)
{
	$debug = false;
	//$debug = true;
	
	$obj = new stdclass;
	$obj->string = $str;
	$obj->score = 0;
	$obj->author = array();
	$obj->patterns = array();


	// patterns
	
	$FAMILY = '(?<family>((da|de|von)\s+)?[\p{Lu}]\p{L}+((-|\s+von\s+)[\p{Lu}]\p{L}+)?)';
	$GIVEN = '(?<given>(((da|de)\s+)?[\p{Lu}]\.[\s*|-]?)+)';
	
	$FAMILY_ALLCAPS = '(?<family>\p{Lu}{2,})';

	$GIVEN_FULL = '(?<given>([\p{Lu}]\p{L}+(-[\p{Lu}]?\p{L}+)?)((\s[\p{Lu}]\.[\s*|-]?)+)?)';

	$patterns = array(
		'FIRST_FAMILY_COMMA_GIVEN' =>'^(?<name>' . $FAMILY . ',\s+' . $GIVEN . ')',
	
		'SEPARATOR_GIVEN_FAMILY' => '((?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $GIVEN . $FAMILY . '))',
	
		'SEPARATOR_FAMILY_GIVEN' => '(?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $FAMILY . ',\s+' . $GIVEN . ')',
	
		'FIRST_FAMILY_COMMA_GIVEN_FULL' =>'^(?<name>' . $FAMILY . ',\s+' . $GIVEN_FULL . ')',
		'SEPARATOR_GIVEN_FULL_FAMILY' => '((?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $GIVEN_FULL . '\s+' . $FAMILY . '))',
		
		'FIRST_FAMILY_ALLCAPS' => '^(?<name>' . $FAMILY_ALLCAPS . ',?\s+' . $GIVEN . ')',
		'SEPARATOR_GIVEN_FAMILY_ALLCAPS' => '((?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $GIVEN . $FAMILY_ALLCAPS . '))',
	

	);
	
	if ($debug)
	{
		foreach ($patterns as $k => $v)
		{
			echo $k . ' = ' . $v . "\n";
		}
	}
	
	

	if ($debug)
	{
		echo $str . "\n";
	}
	
	// clean up common problems
	$str = preg_replace('/,([^\s])/', ', $1', $str);
	
	// parsed authors
	$best_authors = array();
	$best_score = 0;
	$best_patterns = array();
	
	$input_length = mb_strlen($str);
	$matched_length = 0;
	
		
	// First author full name
	if (preg_match('/' . $patterns['FIRST_FAMILY_COMMA_GIVEN_FULL'] . '/u', $str, $m))
	{
		$authors = array();
		
		$first_matched_length = mb_strlen($m['name']);
		
		$offset = $first_matched_length;
		
		$a = new stdclass;
		$a->family = $m['family'];
		$a->family = clean_family($a->family);
		
		$a->given = trim($m['given']);
		$a->given = clean_given($a->given);
		
		$authors[] = $a;
		
		$score = $first_matched_length / $input_length * 100;
		
		if ($score > $best_score)
		{
			$best_authors = $authors;
			$best_score = $score;
			
			$best_patterns = array();
			$best_patterns[] ='FIRST_FAMILY_COMMA_GIVEN_FULL';
			
		}
		
		
		$pat = array('SEPARATOR_GIVEN_FULL_FAMILY');
				
		foreach ($pat as $try_pat)
		{
			$matched_length = $first_matched_length;		
		
			if (preg_match_all('/' . $patterns[$try_pat] . '/u', $str, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $offset))
			{
				//print_r($m);
				
				foreach ($m as $k => $v)
				{
					$matched_length += mb_strlen($v['name'][0]);
				
					$a = new stdclass;
					$a->family = $v['family'][0];
					$a->family = clean_family($a->family);
					
					$a->given = trim($v['given'][0]);
					$a->given = clean_given($a->given);
		
					$authors[] = $a;
				}
				
				$score = $matched_length / $input_length * 100;
				
				if ($score > $best_score)
				{
					$best_authors = $authors;
					$best_score = $score;

					$best_patterns = array();
					$best_patterns[] ='FIRST_FAMILY_COMMA_GIVEN_FULL';
					$best_patterns[] =$try_pat;

				}
			}
		
		}
	}
	
	if (preg_match('/' . $patterns['FIRST_FAMILY_COMMA_GIVEN'] . '/u', $str, $m))
	{
		$authors = array();
		
		$first_matched_length = mb_strlen($m['name']);
		$offset = $first_matched_length;
		
		$a = new stdclass;
		$a->family = $m['family'];
		$a->family = clean_family($a->family);
		
		$a->given = trim($m['given']);
		$a->given = clean_given($a->given);
		
		$authors[] = $a;
		
		$score = $first_matched_length / $input_length * 100;
		
		if ($score > $best_score)
		{
			$best_authors = $authors;
			$best_score = $score;
			$best_patterns = array();
			$best_patterns[] ='FIRST_FAMILY_COMMA_GIVEN';

		}
		
		$pat = array('SEPARATOR_GIVEN_FAMILY', 'SEPARATOR_FAMILY_GIVEN');
				
		foreach ($pat as $try_pat)
		{
			$matched_length = $first_matched_length;		
		
			if (preg_match_all('/' . $patterns[$try_pat] . '/u', $str, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $offset))
			{
				//print_r($m);
				
				foreach ($m as $k => $v)
				{
					$matched_length += mb_strlen($v['name'][0]);
				
					$a = new stdclass;
					$a->family = $v['family'][0];
					$a->family = clean_family($a->family);
					
					$a->given = trim($v['given'][0]);
					$a->given = clean_given($a->given);
		
					$authors[] = $a;
				}
				
				$score = $matched_length / $input_length * 100;
				
				if ($score > $best_score)
				{
					$best_authors = $authors;
					$best_score = $score;
					
					$best_patterns = array();
					$best_patterns[] ='FIRST_FAMILY_COMMA_GIVEN';
					$best_patterns[] = $try_pat;
					
				}
			}
		
		}
	}	
	
	if (preg_match('/' . $patterns['FIRST_FAMILY_ALLCAPS'] . '/u', $str, $m))
	{
		$authors = array();
		
		$first_matched_length = mb_strlen($m['name']);
		$offset = $first_matched_length;
		
		$a = new stdclass;
		$a->family = $m['family'];
		$a->family = clean_family($a->family);
		
		$a->given = trim($m['given']);
		$a->given = clean_given($a->given);
		
		$authors[] = $a;
		
		$score = $first_matched_length / $input_length * 100;
		
		if ($score > $best_score)
		{
			$best_authors = $authors;
			$best_score = $score;
			
			$best_patterns = array();
			$best_patterns[] ='FIRST_FAMILY_ALLCAPS';
		}

		
		$pat = array('SEPARATOR_GIVEN_FAMILY_ALLCAPS');
				
		foreach ($pat as $try_pat)
		{
			$matched_length = $first_matched_length;		
		
			if (preg_match_all('/' . $patterns[$try_pat] . '/u', $str, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $offset))
			{
				//print_r($m);
				
				foreach ($m as $k => $v)
				{
					$matched_length += mb_strlen($v['name'][0]);
				
					$a = new stdclass;
					$a->family = $v['family'][0];
					$a->family = clean_family($a->family);
					
					$a->given = trim($v['given'][0]);
					$a->given = clean_given($a->given);
		
					$authors[] = $a;
				}
				
				$score = $matched_length / $input_length * 100;
				
				if ($score > $best_score)
				{
					$best_authors = $authors;
					$best_score = $score;
					
					$best_patterns = array();
					$best_patterns[] ='FIRST_FAMILY_ALLCAPS';
					$best_patterns[] = $try_pat;
				}
			}
		
		}
	}		
	
	$obj->score = $best_score;
	$obj->author = $best_authors;
	$obj->patterns = $best_patterns;



	return $obj;
}

// test
if (0)
{

	$strings = array(
	//'Anstis, M., F. Parker, T. Hawkes, I. Morris, and S. J. Richards.',
	//'BIJU, P., JOSEKUTTY,E.J.&AUGUSTINE, J.',
	//'Pedersen, H., Kurzweil, H., Suddee, S., de Vogel, E.F., Cribb, P.J., Chantanaorrapint, S., Wattana, S., Gale, S.W., Seelanan, T. & Suwanphakdee, C. ',
	//'Ng PKL & Davie PJF',
	//'Anstis, M., F. Parker, T. Hawkes, I. Morris, and S. J. Richards.',
	//'Rothfels, C.J., Sundue, M.A., Kuo, L.-Y., Larsson,A.,  Kato,  M.,  Schuettpelz,  E.  &  Pryer,  K.M. ',

	// full names!
	//'Chu  Wei-ming,  Wang  Zhong-ren,  Hsieh  Yin-tang  & He Zhao-rong',

	// misisng period before hyphen
	//'Christenhusz,  M.J.M.,  Zhang,  X-C.  &  Schneider,H.',

	//'Acosta-Galvis, A. R., and A. Pinzón',
	//'Ardila-Robayo, M. C., and R. A. Maldonado-Silva',
	//'Andrade, I. S., L. C. da S. Barros, A. F. de S. Oliveira, F. A. Juncá, and F. de M. Magalhães',

	//'Batista, A., A. Hertz, G. Köhler, K. Mebert, and M. Vesely',
	//'Frost, D. R., T. Grant, J. Faivovich, R. H. Bain, A. Haas, C. F. B. Haddad, R. O. de Sá, A. Channing, M. Wilkinson, S. C. Donnellan, C. J. Raxworthy, J. A. Campbell, B. L. Blotto, P. E. Moler, R. C. Drewes, R. A. Nussbaum, J. D. Lynch, D. M. Green, and W. C. Wheeler.',
	/*
	"Nagy, Z. T., Glaw, F. & Vences, M.",
	"GRISMER, L. LEE; MONTRI SUMONTHA, MICHAEL COTA, JESSE L. GRISMER, PERRY L. WOOD, JR., OLIVIER S. G. PAUWELS & KIRATI KUNYA",

	"Preißer, W.",
	"NORVAL, G. & J.-J. MAO",
	"Grogan, W. L., Jr.",
	"Tóth, T.; L. Krecsák  &  J. Gál",
	"Beamer, D. A., and M. J. Lannoo",
	*/
	"Chen, Chao-Chun, Sergei I. Golovatch & Hsueh-Wen Chang.",
	"Plamoottil, Mathews & Nelson P. Abraham. ",
	"Zaldívar-Riverón, Alejandro, J. J. Martinez, Sergey A. Belokobylskij, Carlos Pedraza-Lara, Scott R. Shaw, Paul Hanson & Fernando Varela",
	"Liu, Yi-Qin & Hong-Wei Chen.",
	"Li, W.-X., H. Xiao, R.-G. Zan, Z.-Y. Luo, C.-H. Ban & J.-B. Fen. ",
	"Raven, R.J., Baehr, B.C. & Harvey, M.S. ",
	"Furusaka, Shino, Chinatsu Kozakai, Yui Nemoto, Yoshihiro Umemura, Tomoko Naganuma, Koji Yamazaki & Shinsuke Koike. ",
	"Yao, Junli, Cornelis V. Achterberg, Michael J. Sharkey & Jia-hua Chen",
	);

	// ALL CAPS
	$strings = array(
		'ALLEN G.R. & R.H. KUITER',
	
	);
	

	foreach ($strings as $str)
	{
		$result = parse_author_string($str);
		
		print_r($result);
	}

}
?>

