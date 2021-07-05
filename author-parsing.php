<?php

error_reporting(E_ALL);

function parse_author_string($str)
{
	$debug = false;
	
	$obj = new stdclass;
	$obj->string = $str;
	$obj->score = 0;
	$obj->author = array();


	// patterns
	
	$FAMILY = '(?<family>((da|de)\s+)?[\p{Lu}]\p{L}+(-[\p{Lu}]\p{L}+)?)';
	$GIVEN = '(?<given>(((da|de)\s+)?[\p{Lu}]\.[\s*|-]?)+)';

	$GIVEN_FULL = '(?<given>([\p{Lu}]\p{L}+(-[\p{Lu}]?\p{L}+)?)((\s[\p{Lu}]\.[\s*|-]?)+)?)';

	$patterns = array(
		'FIRST_FAMILY_COMMA_GIVEN' =>'^(?<name>' . $FAMILY . ',\s+' . $GIVEN . ')',
	
	
		'SEPARATOR_GIVEN_FAMILY' => '((?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $GIVEN . $FAMILY . '))',
	
		'SEPARATOR_FAMILY_GIVEN' => '(?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $FAMILY . ',\s+' . $GIVEN . ')',
	
		'FIRST_FAMILY_COMMA_GIVEN_FULL' =>'^(?<name>' . $FAMILY . ',\s+' . $GIVEN_FULL . ')',
		'SEPARATOR_GIVEN_FULL_FAMILY' => '((?<sep>(,|,?\s*and|\s*&))\s*(?<name>' . $GIVEN_FULL . '\s+' . $FAMILY . '))',
	
	);

	if ($debug)
	{
		echo $str . "\n";
	}
	
	// clean up common problems
	$str = preg_replace('/,([^\s])/', ', $1', $str);
	
	// parsed authors
	$best_authors = array();
	$best_score = 0;
	
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
		$a->given = trim($m['given']);
		
		$authors[] = $a;
		
		$score = $first_matched_length / $input_length * 100;
		
		if ($score > $best_score)
		{
			$best_authors = $authors;
			$best_score = $score;
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
					$a->given = trim($v['given'][0]);
		
					$authors[] = $a;
				}
				
				$score = $matched_length / $input_length * 100;
				
				if ($score > $best_score)
				{
					$best_authors = $authors;
					$best_score = $score;
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
		$a->given = trim($m['given']);
		
		$authors[] = $a;
		
		$score = $first_matched_length / $input_length * 100;
		
		if ($score > $best_score)
		{
			$best_authors = $authors;
			$best_score = $score;
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
					$a->given = trim($v['given'][0]);
		
					$authors[] = $a;
				}
				
				$score = $matched_length / $input_length * 100;
				
				if ($score > $best_score)
				{
					$best_authors = $authors;
					$best_score = $score;
				}
			}
		
		}
	}	
	
	$obj->score = $best_score;
	$obj->author = $best_authors;



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

	foreach ($strings as $str)
	{
		$result = parse_author_string($str);
		
		print_r($result);
	}

}
?>

