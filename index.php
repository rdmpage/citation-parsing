<?php
?>

<html>
<head>
	<meta charset="utf-8" />
	<title>Citations</title>

	<link href="entireframework.min.css" rel="stylesheet" type="text/css">
	<script src="js/jquery.js" type="text/javascript"></script>
	
	<style>
			.hero {
				background: #eee;
				padding: 20px;
				border-radius: 10px;
				margin-top: 1em;
			}

			.hero h1 {
				margin-top: 0;
				margin-bottom: 0.3em;
			}
	</style>	
	
</head>
<body>
<div class="container">
<div class="hero">
<h1>
	Parse citations
</h1>
<p>
Convert citation strings into structured JSON.
</p>
</div>

<p>
Enter citation strings, one per line, then click <strong>Parse</strong>.
To create training data use the <a href="editor.html" target="_new">editor</a>.
</p>

<div>
	<textarea class="smooth" style="font-size:1em;box-sizing: border-box;width:100%;" id="text"  name="text" rows="10" >
Möllendorff O (1894) On a collection of land-shells from the Samui Islands, Gulf of Siam. Proceedings of the Zoological Society of London, 1894: 146–156.
de Morgan J (1885) Mollusques terrestres & fluviatiles du royaume de Pérak et des pays voisins (Presqúile Malaise). Bulletin de la Société Zoologique de France, 10: 353–249.
Morlet L (1889) Catalogue des coquilles recueillies, par M. Pavie dans le Cambodge et le Royaume de Siam, et description ďespèces nouvelles (1). Journal de Conchyliologie, 37: 121–199. 
Naggs F (1997) William Benson and the early study of land snails in British India and Ceylon. Archives of Natural History, 24:37–88.
	</textarea>
    <br />
   <button class="btn btn-a btn-sm smooth" onclick="parse()">Parse</button>
   
</div>

<!--
<div class="msg">
Parsed citations will appear below.
</div>
-->


<div id="output" style="display:none;">
<p><a id="apixml" href="." target="_new">API call for result in XML</a></p>
<p><a id="apiris" href="." target="_new">API call for result in RIS</a></p>
<p><a id="api" href="." target="_new">API call for result below</a></p>
<p><a id="edit" href="." target="_new">Open results in editor</a></p>
<div id="result" style="padding:1em;font-family:monospace;font-size:0.8em;white-space:pre-wrap;background-color:rgb(50,50,50);color:#8EFA00;"></div>
</div>


<!--
<h2>OpenURL</h2>
<div id="openurl"></div>
-->

</div>

<script>
function parse() {
	var output =  document.getElementById("output");
	output.style.display = "none";

	document.getElementById("result").innerHTML = "";

	var text = document.getElementById("text").value;
	
	var url = 'api.php?text=' + encodeURIComponent(text);
		
	var xmlurl = 'api.php?text=' + encodeURIComponent(text) + '&format=xml';

	var risurl = 'api.php?text=' + encodeURIComponent(text) + '&format=ris';

	var editurl = 'editor.html?text=' + encodeURIComponent(text);

	 $.getJSON(url + '&callback=?', function(data) {
		if (data) {
			document.getElementById("api").setAttribute('href', url);
			document.getElementById("apixml").setAttribute('href', xmlurl);
			document.getElementById("apiris").setAttribute('href', risurl);
			document.getElementById("edit").setAttribute('href', editurl);
			document.getElementById("result").innerHTML = JSON.stringify(data, null, 2);
		} else {
		
		}
		output.style.display = "block";
	 });
}


</script>

</body>
</html>

<?php

?>
