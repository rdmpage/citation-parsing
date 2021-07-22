<?php
?>

<html>
<head>
	<meta charset="utf-8" />
	<title>Citations</title>
	<style>
		body {
			font-family:sans-serif;
			padding:20px;
		}
		
	button {
		font-size:1em;
	}
			
	</style>
	<script src="js/jquery.js" type="text/javascript"></script>			
	
</head>
<body>
<h1>
	Parse citations
</h1>



<!--
<div>
	<form id="form" action="./" method="get">
<textarea style="font-size:1em;" id="citations"  name="text" rows="20" cols="60" >
Bokermann, W. C. A. 1950. Rescriçao e novo nome genérico para Coelonotus fissilis Mir. Rib.,1920. Papéis Avulsos de Zoologia. São Paulo 9: 215–222. 
</textarea>

    <br />
   <input style="border:1px solid black;-webkit-appearance: none;appearance: none;font-size:1em;" type="submit" value="Parse" />
   </form>
</div>

-->


<h2>Text to parse</h2>
<p>Enter citation strings, one per line.</p>

<div>
	<textarea style="font-size:1em;box-sizing: border-box;width:100%;" id="text"  name="text" rows="10" >
Möllendorff O (1894) On a collection of land-shells from the Samui Islands, Gulf of Siam. Proceedings of the Zoological Society of London, 1894: 146–156.
de Morgan J (1885) Mollusques terrestres & fluviatiles du royaume de Pérak et des pays voisins (Presqúile Malaise). Bulletin de la Société Zoologique de France, 10: 353–249.
Morlet L (1889) Catalogue des coquilles recueillies, par M. Pavie dans le Cambodge et le Royaume de Siam, et description ďespèces nouvelles (1). Journal de Conchyliologie, 37: 121–199. 
Naggs F (1997) William Benson and the early study of land snails in British India and Ceylon. Archives of Natural History, 24:37–88.
	</textarea>
    <br />
   <button onclick="parse()">Parse</button>
</div>


<h2>Output</h2>
<p><a id="api" href=".">API call for result below</a></p>
<div id="output" style="font-family:monospace;white-space:pre-wrap;background-color:rgb(50,50,50);color:#8EFA00;"></div>

<!--
<h2>OpenURL</h2>
<div id="openurl"></div>
-->



<script>
function parse() {

	document.getElementById("output").innerHTML = "";

	var text = document.getElementById("text").value;
	
	var url = 'api.php?text=' + encodeURIComponent(text);
	
	document.getElementById("api").setAttribute('href', url);
	
	 $.getJSON(url + '&callback=?', function(data) {
		if (data) {
			document.getElementById("output").innerHTML = JSON.stringify(data, null, 2);
		}
	 });
}


</script>

</body>
</html>

<?php

?>
