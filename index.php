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
		body {
			padding:20px;
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
		
	</head>
	<body>
	<div class="container">
	<h1>Species Cite<small>Find taxonomic names, where they were published, and by whom.</small></h1>

	<p>A <a href="https://github.com/rdmpage/species-cite">project</a> by Rod Page.</p>
	
	<form action=".">
	<div class="row">
  <input type="search" style="width:80%;" id="q" name="q" placeholder="species name" value="<?php echo $q; ?>"/>
  <input class="primary" type="submit" value="Search" />
  </div>
	</form>

<!--
<div class="row">
	<div class="col-sm-12">
		<div class="card fluid" style="margin: 0.5rem 0.25rem">
			<h3 class="doc section">Title section</h3>
			<p class="doc section">This is a section with some textual content. <span class="icon-link"></span> <a href="#" class="button">PDF</a></p>
		</div>
	</div>
</div>
-->

<?php

	if ($q != '')
	{
		// do stuff here...
		
		$results = do_search($q);
		
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
					
					echo '<span id="unpaywall_' . $counter . '" class="unpaywall" data="' . $result->publication->doi . '"></span>';
					
					echo '</p>';
				}
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
				$( "span.unpaywall" ).each(function() {
  					
  					
  					var id = $(this).attr("id");
  					var doi = $(this).attr("data");
  					
  					
  					
  					var url = "https://api.oadoi.org/v2/" + encodeURIComponent(doi) 
  						+ "?email=unpaywall@impactstory.org" ;
  					
  					$.getJSON(url,
						function(data){
							//console.log(data);
							if (data.is_oa) {
								$("#" + id).html("<a class=\"button tertiary\" href=\"" + data.oa_locations[0].url_for_pdf + "\" target=\"_new\">&nbsp;Read for free&nbsp;</a>");
							}
						}
					);

  					
				});
		';
						
		
		echo '</script>';

		
	}
	
?>
	</div>
	</body>
</html>



