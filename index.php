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
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mini.css/3.0.1/mini-default.min.css">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
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
			
			var html = '<iframe id="pdf" width="100%" height="700" src="' 
				+ pdfjs_url
				+ encodeURIComponent(proxy_url + encodeURIComponent(pdf_url))
				
				 + '#page=' + page_to_display + '"/>';
				 
			$('#' + element_id).html(html);
	
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
				
		
	</head>
	<body>
	
	<div class="container">
		<header class="row">
			<span class="logo col-sm-3 col-md">Species Cite</span>
		</header>
		<div class="row">
			<div class="col-sm-12 col-md-4" style="background:rgb(192,192,192);">
				
				<form action=".">
				<div class="row">
				<input type="search" id="q" name="q" placeholder="species name" value="<?php echo $q; ?>"/>
				<input class="primary" type="submit" value="Search" />
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
			echo '
<div class="row">
	<div class="col-sm-12">
		<div class="card fluid">';
		
			echo '<h3 class="doc section">';
			if (isset($result->nameComplete))
			{
				if (isset($result->parentTaxon))
				{
					$prefix = substr($result->parentTaxon, 0, 1);
					
					$img_url = 'http://localhost/~rpage/phylopic-taxa/images/' . $prefix . '/' . $result->parentTaxon . '.png';
				
					//echo '[' . $result->parentTaxon . ']';
					echo '<img style="float:left;padding:2px;margin:4px;object-fit:contain;display:block;width:3em;height:3em;border:1px solid rgb(192,192,192);border-radius:8px;"  src="' . $img_url . '">';
				}
			
				// echo '<img style="float:left;width:48px;" src="images/Cecidomyiidae.png">';
			
			
				echo $result->nameComplete;
				if (isset($result->authorship))
				{
					echo ' ' . $result->authorship;
				}
				
			}
			else
			{
				echo '<span>Missing name (missing LSID?)</span>';
			}
			
			echo '</h3>';
			
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
			
			echo '<p class="doc section"><a class="icon-link" href="' . $lsid_resolver . '/' . $result->id. '/jsonld">' . $result->id . '<span class="icon-link"></span></a></p>';

			if (isset($result->publishedIn))
			{
				echo '<p class="doc section">';
				
				if (is_array($result->publishedIn)) // can be an array
				{
					$publishedIn = join(" / ", $result->publishedIn);
				}
				else
				{
					$publishedIn = $result->publishedIn;
				}
				
				echo '<small>' . $publishedIn . '</small>';
				
				
				echo '</p>';
			}
			
			if (isset($result->bhl))
			{
				echo '<p class="doc section">';
				echo '<button onclick="display_bhl(\'viewer\', ' . $result->bhl . ')">BHL</button>';
				echo '</p>';
			}
			

			
			if (isset($result->publication))
			{
				echo '<div class="doc section">';
				
				echo 
	"<select onchange=\"show(this.value, csl_$counter, 'output_$counter')\">
	  <option value=\"apa\">APA</option>
	  <option value=\"harvard1\">Harvard</option>
	  <option value=\"bibtex\">BibTex</option>
	  <!-- <option value=\"ris\>RIS</option> -->
	</select>";			
				
				echo '<script>var csl_' . $counter . '=' . $result->publication->citeproc . ';</script>';				
				echo '<div id="output_' . $counter . '"></div>';
				$counter++;
				
				echo '</div>';
				
				if (isset($result->publication->doi))
				{
					echo '<p class="doc section">';
					
					echo '<a href="https://doi.org/' .$result->publication->doi . '" target="_new">https://doi.org/' . $result->publication->doi . '</a><span class="icon-link"></span>';
										
					echo '</p>';
				}
				
				// View
				echo '<p class="doc section">';
				
				// get fragment information if we have it
				$page = 1;
				
				if (isset($result->fragment_selector))
				{
					$page = $result->fragment_selector;
				}				
				
				// Do we already know that we have a PDF? If so, make a button
				if (isset($result->publication->pdf))
				{
					// echo '<a href="' . $result->publication->pdf[0] . '">PDF</a>';	
					
					echo '<button tertiary onclick="onclick=display_pdf(\'viewer\', \'' . $result->publication->pdf[0] . '\',\'' . $page . '\')">View</button>';
				}
				else
				{
					if (isset($result->publication->doi))
					{
						echo '<button disabled class="unpayall" id="unpaywall_' . $counter . '" data-doi="' . $result->publication->doi . '" data-page="' . $page . '">View</button>';
					}
				}
				echo '</p>';
				
			}
		
			if (isset($result->people))
			{
				echo '<p class="doc section">';
					foreach ($result->people as $person)
					{
						$ids = array();
						
						if (isset($person->id) && preg_match('/wd:Q/', $person->id))
						{
							$ids['wikidata'] = str_replace('wd:', '', $person->id);
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
						
						if (isset($ids['researchgate']))
						{
							$prefix = substr($ids['researchgate'], 0, 1);
							$img_url = 'http://localhost/~rpage/researchgate-harvester/images/' . $prefix . '/' . str_replace('_', '-', $ids['researchgate']) . '.jpg';
						}
						
						echo '<p>';
						
						if (count($ids) > 0)
						{
							
							if ($img_url != '')
							{
								echo '<img style="float:left;padding:2px;margin:4px;object-fit:contain;display:block;width:3em;height:3em;border:1px solid rgb(192,192,192);border-radius:8px;" src="' . $img_url  . '">';
							}
						
						
							echo '<mark class="tag">';
						}
					
						echo $person->name;
						
						
						if (count($ids) > 0)
						{
							echo '</mark>';
						}
						
						
						echo '</p>';
					}
			
				echo '</p>';
			}
		
		echo '</div>
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
			
			</div>
			<div class="col-sm-12  col-md-8">
				<div id="viewer"></div>
			</div>
		</div>
	</div>
	
	</body>
</html>



