<?php

// Test author parsing code

require_once (dirname(__FILE__) . '/author-parsing.php');

$testdata = '[
{
  "string": "Anstis, M., F. Parker, T. Hawkes, I. Morris, and S. J. Richards.",
  "parsed": [{
    "family": "Anstis",
    "given": "M."
  }, {
    "family": "Parker",
    "given": "F."
  }, {
    "family": "Hawkes",
    "given": "T."
  }, {
    "family": "Morris",
    "given": "I."
  }, {
    "family": "Richards",
    "given": "S. J."
  }]
}, {
  "string": "Chen, Chao-Chun, Sergei I. Golovatch & Hsueh-Wen Chang.",
  "parsed": [{
    "family": "Chen",
    "given": "Chao-Chun"
  }, {
    "family": "Golovatch",
    "given": "Sergei I."
  }, {
    "family": "Chang",
    "given": "Hsueh-Wen"
  }]
}, {
  "string": "O\'Kane, S. L., K. D. Heil, and G. L. Nesom",
  "parsed": [{
    "family": "O\'Kane",
    "given": "S. L."
  }, {
    "family": "Heil",
    "given": "K. D."
  }, {
    "family": "Nesom",
    "given": "G. L."
  }]
}, {
  "string": "DeWaard JR, Ivanova NV, Hajibabaei M, Hebert PDN",
  "parsed": [{
    "family": "Dewaard",
    "given": "J. R."
  }, {
    "family": "Ivanova",
    "given": "N. V."
  }, {
    "family": "Hajibabaei",
    "given": "M."
  }, {
    "family": "Hebert",
    "given": "P. D. N."
  }]
}, {
  "string": "Bock (I. R.) & Wheeler (M. R.)",
  "parsed": [{
    "family": "Bock",
    "given": "I. R."
  }, {
    "family": "Wheeler",
    "given": "M. R."
  }]
}, {
  "string": "Yao, Junli, Cornelis V. Achterberg, Michael J. Sharkey & Jia-hua Chen",
  "parsed": [{
    "family": "Yao",
    "given": "Junli"
  }, {
    "family": "Achterberg",
    "given": "Cornelis V."
  }, {
    "family": "Sharkey",
    "given": "Michael J."
  }, {
    "family": "Chen",
    "given": "Jia-hua"
  }]
}, {
  "string": "Furusaka, Shino, Chinatsu Kozakai, Yui Nemoto, Yoshihiro Umemura, Tomoko Naganuma, Koji Yamazaki & Shinsuke Koike",
  "parsed": [{
    "family": "Furusaka",
    "given": "Shino"
  }, {
    "family": "Kozakai",
    "given": "Chinatsu"
  }, {
    "family": "Nemoto",
    "given": "Yui"
  }, {
    "family": "Umemura",
    "given": "Yoshihiro"
  }, {
    "family": "Naganuma",
    "given": "Tomoko"
  }, {
    "family": "Yamazaki",
    "given": "Koji"
  }, {
    "family": "Koike",
    "given": "Shinsuke"
  }]
},	
{
  "string": "Clark, M.R., Rowden, A.A., Schlacher, T.A., Guinotte, J., Dunstan, P.K., Williams, A., O’Hara, T.D., Watling, L., Niklitschek, E. & Tsuchida, S.",
  "parsed": [{
    "family": "Clark",
    "given": "M. R."
  }, {
    "family": "Rowden",
    "given": "A. A."
  }, {
    "family": "Schlacher",
    "given": "T. A."
  }, {
    "family": "Guinotte",
    "given": "J."
  }, {
    "family": "Dunstan",
    "given": "P. K."
  }, {
    "family": "Williams",
    "given": "A."
  }, {
    "family": "O\'Hara",
    "given": "T. D."
  }, {
    "family": "Watling",
    "given": "L."
  }, {
    "family": "Niklitschek",
    "given": "E."
  }, {
    "family": "Tsuchida",
    "given": "S."
  }]
},

{
"string": "Vidlička, Ľ., Vrsansky, P. & Shcherbakov, D.E.",
"parsed":[{"family":"Vidlička","given":"Ľ."},{"family":"Vrsansky","given":"P."},{"family":"Shcherbakov","given":"D. E."}]
},

{
"string": "Möllendorff, O. von",
"parsed": [{"family":"Möllendorff","non-dropping-particle":"von","given":"O."}]
},

{
"string":"Zafar-ul Islam, M.Z. and Rahmani, A.R.",
"parsed": [{"family":"Zafar-ul Islam","given":"M. Z."},{"family":"Rahmani","given":"A. R."}]
},

{
"string": "Benthem Jutting, W.S.S. van.",
"parsed": [{"family":"Benthem Jutting","non-dropping-particle":"van","given":"W. S. S."}]
},

{
"string": "van Benthem Jutting WSS",
"parsed": [{"family":"Benthem Jutting","non-dropping-particle":"van","given":"W. S. S."}]
},

{
"string":"le CERF F.",
"parsed": [{"family":"Cerf","non-dropping-particle":"le","given":"F."}]
},

{
"string":"von DOLLA TORRE K. W.",
"parsed": [{"family":"Dolla Torre","non-dropping-particle":"von","given":"K. W."}]
}

	
]';


/*
{
"string":"",
"parsed": []
}
*/

$testcases = json_decode($testdata);

//print_r($testcases);

// do the tests
echo "Testing\n\n";

$fail = array();

foreach ($testcases as $test)
{
	echo " Input: " . $test->string . "\n";
	echo "  Test: ";
	
	$expected = json_encode($test->parsed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	
	$result = parse_author_string($test->string);
	
	if (strcmp($expected, json_encode($result->author, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) == 0)
	{
		//echo $expected . "\n";
		echo "ok\n";
	}
	else
	{
		echo "failed\n";
		echo "Expected:\n" . $expected . "\n";
		echo "Got:\n" . json_encode($result->author) . "\n";
	}
	
	echo "\n";

}

print_r($fail);

?>
