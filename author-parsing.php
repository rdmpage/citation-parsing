<?php

error_reporting(E_ALL);

//----------------------------------------------------------------------------------------
function clean_family($str)
{
	$str = mb_convert_case($str, MB_CASE_TITLE);
	
	if (preg_match('/^O\'(.){1}(.*)/', $str, $m))
	{
		$str = "O'" . strtoupper($m[1]) . $m[2];
	}
	
	$str = str_replace(' Von ', ' von ', $str);
	return $str;
}

//----------------------------------------------------------------------------------------
function clean_given($str)
{
	$str = preg_replace('/(\p{Lu})\./u', '$1. ', $str);
	
	// Initials with no separation
	if (preg_match('/^[(\p{Lu})]+$/u', $str))
	{
		$a = str_split($str, 1);
		$str = join(". ", $a);
		$str .= ".";	
	}
	
	$str = preg_replace('/\s\s+/u', ' ', $str);
	$str = preg_replace('/\s-/u', '-', $str);
	$str = preg_replace('/\s$/u', '', $str);
		
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
	
	$FAMILY = '(?<family>((da|de|von)\s+)?[\p{Lu}][\']?\p{L}+((-|\s+von\s+)[\p{Lu}]\p{L}+)?)';

	$GIVEN = '(?<given>(((da|de)\s+)?[\p{Lu}]\.[\s*|-]?)+)';
	
	$GIVEN_NO_DOTS = '(?<given>[\p{Lu}]+)';
	
	$FAMILY_ALLCAPS = '(?<family>\p{Lu}{2,})';

	$GIVEN_FULL = '(?<given>([\p{Lu}]\p{L}+(-[\p{Lu}]?\p{L}+)?)((\s[\p{Lu}]\.[\s*|-]?)+)?)';
	
	$SEPARATOR = '(?<sep>(,|,?\s*and|\s*&|;|\|))';

	$patterns = array(
		'FIRST_FAMILY_COMMA_GIVEN' =>'^(?<name>' . $FAMILY . ',\s+' . $GIVEN . ')',
	
		'SEPARATOR_GIVEN_FAMILY' => '(' . $SEPARATOR . '\s*(?<name>' . $GIVEN . $FAMILY . '))',
	
		'SEPARATOR_FAMILY_GIVEN' => $SEPARATOR . '\s*(?<name>' . $FAMILY . ',\s+' . $GIVEN . ')',

		'SEPARATOR_FAMILY_GIVEN_FULL' => $SEPARATOR . '\s*(?<name>' . $FAMILY . ',\s+' . $GIVEN_FULL . ')',
	
		'FIRST_FAMILY_COMMA_GIVEN_FULL' =>'^(?<name>' . $FAMILY . ',\s+' . $GIVEN_FULL . ')',
		'SEPARATOR_GIVEN_FULL_FAMILY' => '(' . $SEPARATOR . '\s*(?<name>' . $GIVEN_FULL . '\s+' . $FAMILY . '))',
		
		'FIRST_FAMILY_ALLCAPS' => '^(?<name>' . $FAMILY_ALLCAPS . ',?\s+' . $GIVEN . ')',
		'SEPARATOR_GIVEN_FAMILY_ALLCAPS' => '(' . $SEPARATOR . '\s*(?<name>' . $GIVEN . $FAMILY_ALLCAPS . '))',
	
		'FIRST_FAMILY_GIVEN_NO_DOTS' => '^(?<name>' . $FAMILY . ',?\s+' . $GIVEN_NO_DOTS . ')',
		'SEPARATOR_FAMILY_GIVEN_NO_DOTS' => $SEPARATOR . '\s*(?<name>' . $FAMILY . '\s+' . $GIVEN_NO_DOTS . ')',

		'FIRST_FAMILY_PARENTHESES_GIVEN' =>'^(?<name>' . $FAMILY . '\s+\(' . $GIVEN . '\))',
		'SEPARATOR_FAMILY_PARENTHESES_GIVEN' => $SEPARATOR . '\s*(?<name>' . $FAMILY . '\s+\(' . $GIVEN . '\))',

		'FIRST_GIVEN_FULL_FAMILY' =>'^(?<name>' . $GIVEN_FULL . '\s+' . $FAMILY . ')',
		
		'FIRST_GIVEN_FAMILY' =>'^(?<name>' . $GIVEN . '\s+' . $FAMILY . ')',


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
	
	$pattern_tree = array(
		'FIRST_FAMILY_COMMA_GIVEN_FULL' => array(
			'SEPARATOR_GIVEN_FULL_FAMILY',
			'SEPARATOR_FAMILY_GIVEN_FULL'
		),
		
		'FIRST_FAMILY_COMMA_GIVEN' => array(
			'SEPARATOR_GIVEN_FAMILY',
			'SEPARATOR_FAMILY_GIVEN'
		),
		
		'FIRST_FAMILY_ALLCAPS' => array(
			'SEPARATOR_GIVEN_FAMILY_ALLCAPS'
		),
		
		'FIRST_FAMILY_GIVEN_NO_DOTS' => array(
			'SEPARATOR_FAMILY_GIVEN_NO_DOTS'
		),
		
		'FIRST_FAMILY_PARENTHESES_GIVEN' => array(
			'SEPARATOR_FAMILY_PARENTHESES_GIVEN'
		),
		
		'FIRST_GIVEN_FULL_FAMILY' => array(
			'SEPARATOR_GIVEN_FULL_FAMILY'
		),
		
		'FIRST_GIVEN_FAMILY' => array(
			'SEPARATOR_GIVEN_FAMILY'
		),
	
	);
	
	
	// clean up common problems
	$str = preg_replace('/,([^\s])/', ', $1', $str);
	
	// parsed authors
	$best_authors = array();
	$best_score = 0;
	$best_patterns = array();
	
	$input_length = mb_strlen($str);
	$matched_length = 0;
		
	foreach ($pattern_tree as $first_pattern => $rest_pattern)
	{
		if (preg_match('/' . $patterns[$first_pattern] . '/u', $str, $m))
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
				$best_patterns[] = $first_pattern;
			
			}
			
			foreach ($rest_pattern as $try_pat)
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
						$best_patterns[] = $first_pattern;
						$best_patterns[] = $try_pat;

					}
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

	/*
	// ALL CAPS
	$strings = array(
		'ALLEN G.R. & R.H. KUITER',
	
	);
	*/
	
	/*
	// initials no space, no separator family-given
	$strings = array(
	'DeWaard JR, Ivanova NV, Hajibabaei M, Hebert PDN',
	'Boyer de Fonscolombe LJH',
	'Kirby W',
	);
	*/

	/*
	// https://www.persee.fr/doc/bsef_0037-928x_1991_num_96_5_17755
	$strings =  array(
	'Bock (I. R.) & Wheeler (M. R.)',
	);
	*/
	
	/*
	// bad
	$strings =  array(
	'Boyer de Fonscolombe LJH',
	'Saussure, H. de',
	'Robert Francis Scharff', // full names
	'O\'Kane, S. L., K. D. Heil, and G. L. Nesom',
	'Santamaria-A, D., N. Zamora V., y R. Aguilar F.',
	);
	*/
	
	$strings =  array(
	//'James, S.A.',
	//'Moonlight, Peter Watson; Daza, Aniceto',
	//'Dora E. Mora-Retana|Carlos Quirós',
	//'E. L. Taylor, M. F. F. da Silva, J. Oliviero, C. S. Rosário, J. B. Silva & M. R. Santos',
	//'Anstis, M., F. Parker, T. Hawkes, I. Morris, and S. J. Richards.',
	//'G. Pereira-Silva',
	//'R. M. Harley, G. Bromley, A. M. Carvalho, J. L. Hage & H. S. Brito' // http://localhost/~rpage/plazi-tester/?uri=03943308-FFF9-FFE5-F6EE-6D37FD68FE60
	'Poulsen, Axel Dalberg; Bau, Billy; Akoitai, Thomas; Akai, Saxon'
	);
	
	$strings=array(
	'Ralf Britz, Ariane Standing, Biju Kumar, Manoj Kripakaran, Unmesh Katwate, Remya L. Sundar and Rajeev Raghavan',
	'Silva-Albuquerque, Lídia C. and Oscar A. Shibatta',
	);

	foreach ($strings as $str)
	{
		$result = parse_author_string($str);
		
		print_r($result);
	}

}
?>
