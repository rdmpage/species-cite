<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/go.php');

$q = '';

if (isset($_GET['q']))
{
	$q = $_GET['q'];
}

?>

<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>

	body {
	  padding:0px;
	  margin:0px;
	  font-family:sans-serif;
	  overflow:hidden; /* stop any scrolling of whole browser contents */
	}
	
	h2 {
		font-size:1em;
	}
	
		.header
		{
			height: 2em;
			line-height: 2em;
			background-color:rgb(32,32,32);
			color:white; 
		}
	
	
a {
	text-decoration:none;
	color:rgb(28,27,168);
}

a:hover {
	text-decoration:underline;
}
	
/* -------------------------------------------------------------------------------------*/
/* links and identifiers */

/* truncate very long identifiers such as LSIDs */
.external {
  width:250px;
  white-space: nowrap;
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  text-decoration: none;
  color:blue;
  font-size:0.8em;
}

.bhl:before {
  content: url('images/bhl_16x16.png');
  vertical-align: middle;
  padding-right: 3px;
}

.doi:before {
  content: url('images/doi_16x16.png');
  vertical-align: middle;
  padding-right: 3px;
}

.ion:before {
  content: url('images/ion_16x16.png');
  vertical-align: middle;
  padding-right: 3px;
}

.ipni:before {
  content: url('images/ipni_favicon_16.png');
  vertical-align: middle;
  padding-right: 3px;
}

.if:before {
  content: url('images/if_16x16.png');
  vertical-align: middle;
  padding-right: 3px;
}

.ubio:before {
  content: url('images/ubio_16x16.png');
  vertical-align: middle;
  padding-right: 3px;
}

.wikidata:before {
  content: url('images/wikidata_16x16.png');
  vertical-align: middle;
  padding-right: 3px;
}
	
	.dark {
		color: rgb(200,200,200);
	
	}
	
	.dark a {
	color: rgb(54,142,208);
	}
	
	
	
	.content {
		display:flex;
	}
	
	/* left column */
	.left_column {
		min-width:300px;
		height: calc(100vh - 2em);
		background-color:rgb(64,64,64);
		overflow-y: auto;
		flex: 1 0 0;
		/* padding:2px; */
	}

	/* middle column */
	.middle_column {
		/* width: calc((100% - 300px)/2); */	/* 50% of remaining width */
		flex: 2 0 0;
		min-width:300px;
		height: calc(100vh - 2em);
		/* border-left:1px solid red; */
		background-color:rgb(64,64,64);	
	}
	
	/* right column */
	.right_column {
		/* width: calc((100% - 300px)/2); */ /* 50% of remaining width */
		flex: 1 0 0;
		width:300px;
		height: calc(100vh - 2em);
		/* border-left:1px solid red; */
		background-color:rgb(64,64,64);	
		
		overflow-y: auto;	
	}	
	
	
		/* taxon colours */
		/* https://github.com/jhpoelen/taxaprisma */
		
		.life {
			background-color: white;
		}
				
		.fungi {
			background-color: #F52887;
		}

		.plantae {
			background-color: #73AC13;
		}

		.animalia {
			background-color: #1E90FF;
		}
	
	
	
		</style>
		
		<script src="js/jquery.js" type="text/javascript"></script>		
		<script src="js/citation-0.4.0-9.js" type="text/javascript"></script>		
		<script>
			const Cite = require('citation-js')
		</script>	

		<script>
			function show(format, data, element_id) {
				var element = document.getElementById(element_id);				
				element.innerHTML = '';		
				console.log(JSON.stringify(data));
			
				var d = new Cite(data);
				
				var output = '';
				
				switch (format) {
				
					case 'bibtex':
					case 'ris':
						element.style['font-family'] = 'monospace';
						element.style['white-space'] = 'pre';
						output = d.format(format);
						break;
						
					case 'apa':
					default:
						element.style['font-family'] = '';
						element.style['white-space'] = '';
					
						output = d.format('bibliography', {
						  format: 'html',
						  template: format,
						  lang: 'en-US'
						});
					break;
				
				}
				console.log(output);
					
				element.innerHTML = output;			
			}
		</script>	
		
		<script>
		// Display PDF
		function display_pdf(element_id, pdf_url, page) {
			$('#' + element_id).html("");
		
			var page_to_display = page || 1;
			
			var pdfjs_url 	= 'pdfjs/web/viewer.html?file=';
			//pdfjs_url 		= 'pdf.js-hypothes.is/viewer/web/viewer.html?file=';
			
			var proxy_url 	= '../../pdfproxy.php?url=';
			
			var html = '<iframe id="pdf" width="100%" height="auto" frameBorder="0" src="' 
				+ pdfjs_url
				+ encodeURIComponent(proxy_url + encodeURIComponent(pdf_url))
				
				 + '#page=' + page_to_display + '"/>';
				 
			$('#' + element_id).html(html);
			$(window).resize();
	
		}
		</script>	
		
		<script>
		// Display BHL
		function display_bhl(element_id, PageID) {
			$('#' + element_id).html("");
			
			var height = 500;

			// Add BHL page image
			var img = document.createElement("img");
			img.setAttribute("src", "https://aipbvczbup.cloudimg.io/s/height/1000/https://www.biodiversitylibrary.org/pagethumb/" + PageID + ",1000,1000" );
			img.style.width = height + "px";
			img.style.border = "1px solid rgb(192,192,192)";
	
			var div = document.getElementById(element_id);
			div.appendChild(img);				
		}
		</script>		
		
		<script>

		// https://www.w3schools.com/howto/tryit.asp?filename=tryhow_js_copy_clipboard
function copy_citation(id) {

  var copyText = document.getElementById(id);
  copyText.select();
  copyText.setSelectionRange(0, 99999)
  document.execCommand("copy");
  alert("Copied the text: " + copyText.value);
}
</script>	
				
		
	</head>
	<body onload="$(window).resize();">
	
	<div class="header">
		Species Cite
	</div>

	<!-- three columns -->
	<div class="content" "="">

		<!-- left column -->
		<div class="left_column">
						
			<form action=".">
			<div>
			<input style="font-size:1em;"  type="text" id="q" name="q" placeholder="species name" value="<?php echo $q; ?>"/>
			<input style="border:1px solid black;-webkit-appearance: none;appearance: none;font-size:1em;"  type="submit" value="Search" />
			</div>
			</form>	
		
				
<?php

	if ($q != '')
	{
		// do stuff here...
		
		$results = do_search(trim($q));
		
		if (0)
		{
			echo '<pre>';
			print_r($results);
			echo '</pre>';
		}
		
		$counter = 0;
		
		foreach ($results as $result)
		{
			
			// print_r($result);
		
			echo '
		<div style="margin:8px;background-color:rgb(192,192,192);">';
		
		
			// what colour will be use?
			$class = 'life';
			
			if (isset($result->code))
			{
				if (is_object($result->code))
				{
					$code = $result->code->{'@id'};
				}
				else
				{
					$code = $result->code;
				}
			
			
				switch ($code)
				{
					case 'tn:ICZN':
						$class = "animalia";
						break;
						
					case 'tn:botanical':
						$class = "plantae";
						break;

					case 'tn:ICBN':
						$class = "fungi";
						break;
						
					default:
						$class = 'life';
						break;
				}
			
			}

		
			echo '<div class="' . $class . '" style="display:block;overflow:auto;">';
			
			
			if (isset($result->nameComplete))
			{
				if (isset($result->parentTaxon))
				{
					$prefix = substr($result->parentTaxon, 0, 1);
					
					$img_url = './imageproxy.php?url=' . urlencode('https://raw.githubusercontent.com/rdmpage/phylopic-taxa/master/images/' . $prefix . '/' . $result->parentTaxon . '.png');
				
					//echo '[' . $result->parentTaxon . ']';
					echo '<img style="float:left;padding:2px;margin:4px;object-fit:contain;display:block;width:3em;height:3em;"  src="' . $img_url . '">';
				}
			
				// echo '<img style="float:left;width:48px;" src="images/Cecidomyiidae.png">';
			
				echo '<span style="font-size:1.5em;">';
				echo $result->nameComplete;
				if (isset($result->authorship))
				{
					echo ' ' . $result->authorship;
				}
				echo '</span>';
				
			}
			else
			{
				echo '<span>Missing name (missing LSID?)</span>';
			}
			
			echo '</div>';
			
			
			$lsid_resolver = 'https://lsid.herokuapp.com';
			$parts = explode(':', $result->id);
			
			switch ($parts[2])
			{
				case 'marinespecies.org':
					$lsid_resolver = 'https://lsid-two.herokuapp.com';
					break;
					
				case 'ubio.org':
					$lsid_resolver = 'https://lsid-two.herokuapp.com';
					//$lsid_resolver = 'http://localhost/~rpage/lsid-cache-two';
					break;
					
			
				default:
					$lsid_resolver = 'https://lsid.herokuapp.com';
					break;
			
			}
			echo '<div style="padding:4px;">';
			//echo '<h4>Details</h4>';
			
			$link_class = "";
			
			if (preg_match('/organismnames.com/', $result->id))
			{
				$link_class = "ion";
			}
			if (preg_match('/ubio.org/', $result->id))
			{
				$link_class = "ubio";
			}
			
			
			
			echo '<div><a class="external ' . $link_class . '" href="' . $lsid_resolver . '/' . $result->id. '/jsonld">' . $result->id . '<span class="icon-link"></span></a></div>';

			if (isset($result->publishedIn))
			{
				echo '<div>';
				
				if (is_array($result->publishedIn)) // can be an array
				{
					$publishedIn = join(" / ", $result->publishedIn);
				}
				else
				{
					$publishedIn = $result->publishedIn;
				}
				
				echo '<span style="color:rgb(64,64,64);font-size:0.7em;">' . $publishedIn . '</span>';
				
				
				echo '</div>';
			}
			
			if (isset($result->bhl))
			{
				echo '<div>';
				
				echo '<a class="external bhl" href="http://www.biodiversity.org/page/' . $result->bhl . '" target="_new">' . $result->bhl . '</a>';
				echo '<button onclick="display_bhl(\'viewer\', ' . $result->bhl . ')">View BHL page</button>';
				
				echo '</div>';
			}
			

			
			if (isset($result->publication))
			{
				echo '<h2>Publication</h2>';
				
				echo  ' <a class="external wikidata" href="https://www.wikidata.org/wiki/' . str_replace('wd:', '', $result->publication->id) . '" target="_new">' . str_replace('wd:', '', $result->publication->id) . '</a>';

				
				echo '<div>';
				
				echo 
	"<select onchange=\"show(this.value, csl_$counter, 'output_$counter')\">
	  <option value=\"apa\">APA</option>
	  <option value=\"harvard1\">Harvard</option>
	  <option value=\"bibtex\">BibTex</option>
	  <!-- <option value=\"ris\>RIS</option> -->
	</select>";			
				
				echo '<script>var csl_' . $counter . '=' . $result->publication->citeproc . ';</script>';				
				echo '<div id="output_' . $counter . '" style="padding:4px;font-size:0.8em;color:rgb(64,64,64);overflow-x:auto;"></div>';
				
				if (0)
				{
					// this needs more thought
					echo '<button onclick="copy_citation(\'output_' . $counter . '\')">Copy to clipboard</button>';
				}
				
				
				$counter++;
				
				echo '</div>';
				
				if (isset($result->publication->doi))
				{
					echo '<div>';
					
					echo '<a class="external doi" href="https://doi.org/' .$result->publication->doi . '" target="_new">' . $result->publication->doi . '</a><span class="icon-link"></span>';
										
					echo '</div>';
				}
				
				// View
				echo '<div>';
				
				// get fragment information if we have it
				$page = 1;
				
				if (isset($result->fragment_selector))
				{
					$page = $result->fragment_selector;
				}				
				
				// Do we already know that we have a PDF? If so, make a button
				if (isset($result->publication->pdf))
				{
					echo '<button onclick="display_pdf(\'viewer\', \'' . $result->publication->pdf[0] . '\',\'' . $page . '\')">View PDF</button>';
				}
				else
				{
					if (isset($result->publication->doi))
					{
						echo '<button disabled class="unpayall" id="unpaywall_' . $counter . '" data-doi="' . $result->publication->doi . '" data-page="' . $page . '">Unpaywall</button>';
					}
				}
				echo '</div>';
				
			}
		
			if (isset($result->people))
			{
				echo '<h2>People</h2>';
				echo '<div>';
					foreach ($result->people as $person)
					{
						$ids = array();
						
						if (isset($person->wikidata))
						{
							$ids['wikidata'] = $person->wikidata;
						}

						if (isset($person->researchgate))
						{
							$ids['researchgate'] = $person->researchgate;
						}

						if (isset($person->orcid))
						{
							$ids['orcid'] = $person->orcid;
						}
												
						$img_url = '';
						
						if (isset($person->thumbnailUrl))
						{
							//$person->thumbnailUrl = str_replace('&amp;', '&', $person->thumbnailUrl);
							$img_url = './imageproxy.php?url=' . rawurlencode($person->thumbnailUrl);		
						}
						
						if (isset($ids['researchgate']))
						{
							$prefix = substr($ids['researchgate'], 0, 1);
							$img_url = './imageproxy.php?url=' . urlencode('https://raw.githubusercontent.com/rdmpage/researchgate-harvester/master/images/' . $prefix . '/' . str_replace('_', '-', $ids['researchgate']) . '.jpg');
						}

						if ($img_url == '')
						{
							$img_url = 'images/no-icon.svg';
						}
						
						echo '<div style="display:block;overflow:auto;margin-bottom:4px;line-height:2em;">';
					
						echo '<img style="float:left;margin-right:4px;object-fit:cover;display:block;width:2em;height:2em;border:1px solid rgb(192,192,192);" src="' . $img_url  . '">';
					
						echo '<span style="font-size:0.8em;">' . $person->name . '</span>';
												
						if (count($ids) > 0)
						{
							if (isset($ids['orcid']))
							{
								echo ' <a href="https://orcid.org/' . $ids['orcid'] . '" target="_new"><img src="images/orcid_16x16.png"></a>';
							}
							if (isset($ids['wikidata']))
							{
								echo  ' <a href="https://www.wikidata.org/wiki/' . $ids['wikidata'] . '" target="_new"><img src="images/wikidata_16x16.png"></a>';
							}
						}						
						
						echo '</div>';
					}
			
				
			
				echo '</div>';
			}
		
		echo '
		</div>
</div>';
		}
		
		echo '<script>';
		for ($i = 0; $i < $counter; $i++)
		{
			echo "show('apa', csl_$i, 'output_$i');";	
		}
		
		echo '
				// code to call unpaywall
				$( "button" ).each(function() {
   					
  					var id = $(this).attr("id");
  					var doi = $(this).attr("data-doi");
  					var page = $(this).attr("data-page");
  					
  					var url = "https://api.oadoi.org/v2/" + encodeURIComponent(doi) 
  						+ "?email=unpaywall@impactstory.org" ;
  					
  					$.getJSON(url,
						function(data){
							console.log(data);
							if (data.is_oa) {
								$("#" + id).removeAttr("disabled");
								$("#" + id).html("View (via Unpaywall)");
								$("#" + id).attr("onclick", "display_pdf(\'viewer\', \'" + data.oa_locations[0].url_for_pdf + "\', \'" + page + "\')");
							}
						}
					);

  					
				});
		';
						
		
		echo '</script>';

		
	}
	
?>						
			
		</div> <!-- left -->
			
		<!-- middle column -->
		<div id="middle_column" class="middle_column">
			<div id="viewer"></div>
		</div>

		<!-- right column -->
		<div class="right_column">
			
			<!-- think what to do here -->
			<div class="dark" style="font-size:0.8em;">
				Credits:
				
				<ul>
				<li>Taxonomic names from <a href="http://www.organismnames.com">ION</a>, <a href="https://www.ipni.org">IPNI</a>, <a href="http://www.indexfungorum.org">Index Fungorum</a>, and <a href="http://www.ubio.org/NZ/">Nomenclator Zoologicus</a>.</li>
				<li>LSIDs from caches <a href="https://lsid.herokuapp.com">one</a> and <a href="https://lsid-two.herokuapp.com">two</a>.</li>
				<li>Mapping between names and publications from <a href="http://bionames.org">BioNames</a> and unpublished projects.</li>
				<li>Bibliographic data from <a href="https://www.wikidata.org/">Wikidata</a>.</li>
				<li>PDFs from <a href="https://archive.org/details/taxonomyarchive">Internet Archive</a> and the <a href="https://web.archive.org">Wayback Machine</a>.</li>
				<li>BHL pages from the <a href="https://www.biodiversitylibrary.org">Biodiversity Heritage Library</a>.</li>
				<li>Taxon images from <a href="http://phylopic.org">Phylopic</a>.</li>
				<li>People images from <a href="https://www.wikidata.org/">Wikidata</a> and <a href="https://www.researchgate.net">ResearchGate</a>.</li>
				<li>Citation formatting from <a href="https://citation.js.org">citation.js</a>.</li>
				</ul>
			
			
			</div>
			
			
			
		</div>
			
	</div>
	
<script>
	/* http://stackoverflow.com/questions/6762564/setting-div-width-according-to-the-screen-size-of-user */
	$(window).resize(function() { 
		/* Only resize document window if we have a document cloud viewer */
		var h = $('#middle_column').height();		
		$("#pdf").css({"height":h });		
	});	
</script>	
	
	</body>
</html>



