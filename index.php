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
		
	</head>
	<body>
	
	<div class="container">
		<header class="row">
			<span class="logo col-sm-3 col-md">Species Cite</span>
		</header>
		<div class="row">
			<div class="col-sm-12 col-md-4">
				
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
		
			echo '<h3 class="doc section">' . $result->nameComplete . '</h3>';
			
			$lsid_resolver = 'https://lsid.herokuapp.com';
			$parts = explode(':', $result->id);
			
			switch ($parts[2])
			{
				case 'marinespecies.org':
					$lsid_resolver = 'https://lsid-two.herokuapp.com';
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
				
				// Do we already know that we have a PDF? If so, make a button
				if (isset($result->publication->pdf))
				{
					// echo '<a href="' . $result->publication->pdf[0] . '">PDF</a>';					
					echo '<button tertiary onclick="onclick=display_pdf(\'viewer\', \'' . $result->publication->pdf[0] . '\')">View</button>';
				}
				else
				{
					if (isset($result->publication->doi))
					{
						echo '<button disabled class="unpayall" id="unpaywall_' . $counter . '" data="' . $result->publication->doi . '">View</button>';
					}
				}
				echo '</p>';
				
			}
		
			if (isset($result->people))
			{
			
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
  					var doi = $(this).attr("data");
  					
  					var url = "https://api.oadoi.org/v2/" + encodeURIComponent(doi) 
  						+ "?email=unpaywall@impactstory.org" ;
  					
  					$.getJSON(url,
						function(data){
							console.log(data);
							if (data.is_oa) {
								$("#" + id).removeAttr("disabled");
								$("#" + id).html("View (via Unpaywall)");
								$("#" + id).attr("onclick", "display_pdf(\'viewer\', \'" + data.oa_locations[0].url_for_pdf + "\')");
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



