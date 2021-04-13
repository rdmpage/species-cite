<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/vendor/autoload.php');

$config['cache']						= dirname(__FILE__) . '/cache';
$config['wikidata_sparql_endpoint'] 	= 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';


//----------------------------------------------------------------------------------------
// get
function get($url, $format)
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   	
	curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
			"Accept: " . $format, 
			"User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405"
			)
		);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
// post
function post($url, $format = 'application/ld+json', $data =  null)
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
			"Accept: " . $format, 
			"User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405"
			)
		);
		

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}


// get an article/person/periodical


//----------------------------------------------------------------------------------------
function wikidata_cache($qid)
{
	global $config;
	
	$number = str_replace('Q', '', $qid);
	
	$cache_dir = $config['cache'] . '/wikidata';
	
	if (!file_exists($cache_dir))
	{
		$oldumask = umask(0); 
		mkdir($cache_dir, 0777);
		umask($oldumask);
	}
	
	$dir = floor($number / 1000);	
	
	$cache_dir .= '/' . $dir;
	
	if (!file_exists($cache_dir))
	{
		$oldumask = umask(0); 
		mkdir($cache_dir, 0777);
		umask($oldumask);
	}
	
	$filename = $cache_dir . '/' . $qid . '.json';
	
	return $filename;
}


//----------------------------------------------------------------------------------------
// Get one item from Wikidata
function get_work($qid, $force = false, $debug = false)
{
	global $config;	

	$triples = '';
	

	$uri = 'http://www.wikidata.org/entity/' . $qid;

	$sparql = 'PREFIX schema: <http://schema.org/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

CONSTRUCT
{
	?item a ?type . 

	?item schema:name ?title .

	?item schema:url ?url .

	# scholarly article
	?item schema:name ?title .
	?item schema:volumeNumber ?volume .
	?item schema:issueNumber ?issue .
	?item schema:pagination ?page .
	?item schema:datePublished ?datePublished .

	# author(s)
	?item schema:author ?author .
	?author a ?author_type  .
	?author schema:name ?author_name .
	?author schema:position ?author_order .
	
	# orcid
	?author schema:identifier ?orcid_author_identifier .
	?orcid_author_identifier a <http://schema.org/PropertyValue> .
	?orcid_author_identifier <http://schema.org/propertyID> "orcid" .
	?orcid_author_identifier <http://schema.org/value> ?orcid .
	
	# author sameAs
	?author schema:sameAs $orcid_uri . 
	?author schema:sameAs $researchgate_uri . 
	
	# researchgate
	?author schema:identifier ?researchgate .

	# container
	?item schema:isPartOf ?container .
	?container schema:name ?container_title .
	?container schema:issn ?issn .

	# doi
	?item schema:identifier ?doi_identifier .
	?doi_identifier a <http://schema.org/PropertyValue> .
	?doi_identifier <http://schema.org/propertyID> "doi" .
	?doi_identifier <http://schema.org/value> ?doi .
	
	 # handle
	 ?item schema:identifier ?handle_identifier .
	 ?handle_identifier a <http://schema.org/PropertyValue> .
	 ?handle_identifier <http://schema.org/propertyID> "handle" .
	 ?handle_identifier <http://schema.org/value> ?handle .

	 # internetarchive
	 ?item schema:identifier ?internetarchive_identifier .
	 ?internetarchive_identifier a <http://schema.org/PropertyValue> .
	 ?internetarchive_identifier <http://schema.org/propertyID> "internetarchive" .
	 ?internetarchive_identifier <http://schema.org/value> ?internetarchive .
 
	 # jstor
	 ?item schema:identifier ?jstor_identifier .
	 ?jstor_identifier a <http://schema.org/PropertyValue> .
	 ?jstor_identifier <http://schema.org/propertyID> "jstor" .
	 ?jstor_identifier <http://schema.org/value> ?jstor .
  
	 # pmc
	 ?item schema:identifier ?pmc_identifier .
	 ?pmc_identifier a <http://schema.org/PropertyValue> .
	 ?pmc_identifier <http://schema.org/propertyID> "pmc" .
	 ?pmc_identifier <http://schema.org/value> ?pmc .

	 # pmid
	 ?item schema:identifier ?pmid_identifier .
	 ?pmid_identifier a <http://schema.org/PropertyValue> .
	 ?pmid_identifier <http://schema.org/propertyID> "pmid" .
	 ?pmid_identifier <http://schema.org/value> ?pmid .	

	# PDF
	?item schema:encoding ?encoding .
	?encoding schema:fileFormat "application/pdf" .
	?encoding schema:contentUrl ?citation_pdf_url .

	# same As
	# Same as the canonical representation of the DOI	
	?item schema:sameAs $doi_uri . 

	# funder
	?item schema:funder ?funder .
	?funder schema:name ?funder_name . 

	# license
	?item schema:license ?license_url .

	# citation
	?item schema:citation ?cites .

}
WHERE
{
   VALUES ?item { wd:' . $qid . ' }
  
  ?item wdt:P31 ?type .
  
  ?item wdt:P1476 ?title .
  
  # authors
  OPTIONAL {
   {
   ?item p:P2093 ?author .
    ?author ps:P2093 ?author_name .
    ?author pq:P1545 ?author_order. 
        
    # Assume it\'s a person
    VALUES ?author_type { schema:Person } .
       
   }
   UNION
   {
     ?item p:P50 ?statement .
     OPTIONAL
     {
         ?statement pq:P1545 ?author_order. 
     }
     ?statement ps:P50 ?author. 
     ?author rdfs:label ?author_name .  
     FILTER (lang(?author_name) = "en")
     
     # type
     ?author wdt:P31 ?author_type .
     
      
     # ORCID
     OPTIONAL
     {
       ?author wdt:P496 ?orcid .
  		 BIND( IRI (CONCAT (STR(?author), "#orcid")) as ?orcid_author_identifier)
  		 BIND( IRI (CONCAT ("https://orcid.org/", ?orcid)) as ?orcid_uri)
     }
     
     # researchgate
     OPTIONAL
     {
       ?author wdt:P2038 ?researchgate .
       BIND( IRI (CONCAT ("https://www.researchgate.net/profile/", ?researchgate)) as ?researchgate_uri)
     }     
     
    }
  }    
    
  # container
  OPTIONAL {
   ?item wdt:P1433 ?container .
   ?container wdt:P1476 ?container_title .
   OPTIONAL {
     ?container wdt:P236 ?issn .
    }    
  }
  
  # date
   OPTIONAL {
   ?item wdt:P577 ?date .
   BIND(STR(?date) as ?datePublished) 
  }
  

  # scholarly articles -------------------------------------------------------------------
  OPTIONAL {
   ?item wdt:P478 ?volume .
  }   
  
  OPTIONAL {
   ?item wdt:P433 ?issue .
  }  
  
  OPTIONAL {
   ?item wdt:P304 ?page .
  }
  
  # full text
  # OPTIONAL {
  # ?item wdt:P953 ?url .  
  # 
  #}
  
  # full text as PDF we can view
  OPTIONAL {
    {
  		# Wayback machine
  		?item p:P953 ?encoding .
  		?encoding ps:P953 ?fulltext_url . # URL
  		?encoding pq:P2701 wd:Q42332 . # PDF
  		?encoding pq:P1065 ?citation_pdf_url . # Archive URL
  	}
  	UNION
  	{
  		# Internet Archive
  		?item wdt:P724 ?archive .
  		?item p:P724 ?encoding .
  		BIND( IRI(CONCAT("https://archive.org/download/", ?archive, "/", $archive, ".pdf")) as ?citation_pdf_url)

  	}
  }

  # Make DOI lowercase
 OPTIONAL {
   ?item wdt:P356 ?doi_string .   
   BIND( IRI (CONCAT (STR(?item), "#doi")) as ?doi_identifier)
   BIND( LCASE(?doi_string) as ?doi)
   BIND( IRI(CONCAT("https://doi.org/", LCASE(?doi_string))) as ?doi_uri)
  } 
  
 OPTIONAL {
   ?item wdt:P1184 ?handle .   
   BIND( IRI (CONCAT (STR(?item), "#handle")) as ?handle_identifier)
  } 

 OPTIONAL {
   ?item wdt:P888 ?jstor .   
   BIND( IRI (CONCAT (STR(?item), "#jstor")) as ?jstor_identifier)
  } 
  

  OPTIONAL {
   ?item wdt:P724 ?internetarchive .   
   BIND( IRI (CONCAT (STR(?item), "#internetarchive")) as ?internetarchive_identifier)
  }  
  
 OPTIONAL {
   ?item wdt:P6769 ?cnki .   
   BIND( IRI (CONCAT (STR(?item), "#cnki")) as ?cnki_identifier)
  } 
  
  OPTIONAL {
   ?item wdt:P698 ?pmid .   
   BIND( IRI (CONCAT (STR(?item), "#pmid")) as ?pmid_identifier)
  } 
  
  OPTIONAL {
   ?item wdt:P932 ?pmc .   
   BIND( IRI (CONCAT (STR(?item), "#pmc")) as ?pmc_identifier)
  }             
   
  # funder
  OPTIONAL {
   ?item wdt:P859 ?funder .
   ?funder rdfs:label ?funder_name .
   FILTER(LANG(?funder_name) = "en")    
  }  
  
  # license
  OPTIONAL {
  	?item wdt:P275 ?license .
	?license wdt:P856 ?license_url .
  }
  
 # cites
  OPTIONAL {
  	?item wdt:P2860 ?cites .
  }  

} ';

	$filename = wikidata_cache($qid);
	
	//echo $filename . "\n";
	
	if (!file_exists($filename) || $force)
	{
	
		if (0)
		{
			$json = get(
				$config['wikidata_sparql_endpoint']. '?query=' . urlencode($sparql), 
				'application/ld+json'
			);
		}
		else
		{
			$json = post(
				$config['wikidata_sparql_endpoint'], 
				'application/ld+json',
				'query=' . $sparql
			);
		}
		
		file_put_contents($filename, $json);
		
	}
	
	$json = file_get_contents($filename);
	
	if ($json == '')
	{
		return null;
	}
	
	
	// Post process here...
	$graph = new \EasyRdf\Graph();
	$graph->parse($json, 'jsonld');
	
	$format = \EasyRdf\Format::getFormat('ntriples');
	$triples = $graph->serialise($format);
	
	return $triples;
}



		
?>
