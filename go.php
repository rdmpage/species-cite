<?php

error_reporting(E_ALL);

require_once('vendor/autoload.php');

require_once(dirname(__FILE__) . '/wikidata.php');
require_once(dirname(__FILE__) . '/csl.php');

//----------------------------------------------------------------------------------------
function get_lsid_triples($lsid)
{
	$data = '';
	
	$url = '';
	
	$parts = explode(':', $lsid);
	
	switch ($parts[2])
	{
		case 'marinespecies.org':
			$url = 'https://lsid-two.herokuapp.com/' . $lsid . '/ntriples';
			break;

		case 'ubio.org':
			$url = 'https://lsid-two.herokuapp.com/' . $lsid . '/ntriples';
			//$url = 'http://localhost/~rpage/lsid-cache-two/' . $lsid . '/ntriples';
			break;
	
		default:
			$url = 'https://lsid.herokuapp.com/' . $lsid . '/ntriples';
			break;
	}
	

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code == 200)
	{
		$data = $response;
	}
	

	return $data;
}


//----------------------------------------------------------------------------------------
// Binary search of disk file to find name
function search ($filename, $query)
{
	$result = new stdclass;

	// the search initially spans the contents of the file (start - end) 
	// and pivot is where we split the current span in two as we search for a name
	$start 	= 0;
	$end 	= filesize($filename) - 1;
	$pivot 	= 0;


	// How much of the file do we read in each time?
	$CHUNK_SIZE = 1024;
	
	// timing
	$executionStartTime = microtime(true);

	// open the file
	$fp = fopen($filename, 'r');
	
	// The string we are looking for
	$target = $query;

	// Make safe for regular expression
	$target = preg_replace('/([\(|\)|\[|\]|\.])/', '\\\$1', $target);

	// Keep track of how many operations it takes to find the query string
	$result->count = 0;
	$result->query = $query;
	$result->hits = array();

	$done = false;
	while (!$done)
	{
		$result->count++;
	
		$pivot = floor(($start + $end) / 2);
	
		fseek($fp, $pivot);
		$data = fread($fp, $CHUNK_SIZE);
	
		// ensure chunk starts and ends cleanly on a row of data
		preg_match('/^(?<prefix>[^\n]*)\n(?<middle>.*)\n(?<suffix>[^\n]*)$/s', $data, $m);

		// after trimming off start and end we search on this fragment of the file
		$search_text = "\n" . $m['middle'];
		
		
		// do we have the target string?
		if (preg_match_all('/\n(?<string>' . $target . ')\t(?<id>\w+:\d+(-\d+)?)\t(?<higher>[^\t]+)?\t(?<wikidata>Q\d+)?\t(?<bhl>\d+)?\t(?<fragment>\d+)?/', $search_text, $m))
		{
			// we have one or more matches
			$n = count($m[0]);
			
			for ($i = 0; $i < $n; $i++)
			{
				$hit = new stdclass;
				$hit->text = $m['string'][$i];
				$hit->id = $m['id'][$i];
				
				if ($m['higher'][$i] != '')
				{
					$hit->higher = $m['higher'][$i];
				}				
				
				if ($m['wikidata'][$i] != '')
				{
					$hit->wikidata = $m['wikidata'][$i];
				}
				
				if ($m['bhl'][$i] != '')
				{
					$hit->bhl = $m['bhl'][$i];
				}

				if ($m['fragment'][$i] != '')
				{
					$hit->fragment = $m['fragment'][$i];
				}
				
				
				$result->hits[] = $hit;
			}

			$done = true;
		}
		else
		{
			// none of the rows match our target string, so we split the current search span
			// and try again.
			
			// get the names at the start and the end of the current chunk
			$rows = explode("\n", $search_text);
			
			// first row will be empty
		
			$start_row = explode("\t", $rows[1]);
			$end_row = explode("\t", $rows[count($rows) - 1]);
		

			// Use lexical ordering of strings to determine if target is < or > current chunk		
			// if (strnatcmp($target, $start_row[0]) < 0)
			if ($target < $start_row[0])
			{
				$end = $pivot - 1; 
			}
			else
			{
				// if (strnatcmp($target, $end_row[0]) > 0)
				if ($target > $end_row[0])
				{
					$start = $pivot + 1; 
				}
				else
				{
					// If we arrive here then we don't have the name
					
					$done = true;
				
					if (0)
					{
						echo "Badness\n";		
									
						echo "Search text=$search_text\n";					
						print_r($rows);
						echo "Start row |" . $start_row[0] . "|\n";
						echo "   Target |$target|\n";	
						echo "  End row |" . $end_row[0] . "|\n";	
						
						echo strnatcmp($target, $start_row[0]) . "\n";
						echo strnatcmp($target, $end_row[0]) . "\n";
										
						exit();
					}					
				}
		
			}
			if ($start > $end)
			{
				$done = true;
			}

		}
	}
	
	$executionEndTime = microtime(true);
	
	$result->time = $executionEndTime - $executionStartTime;

	return $result;

}

//----------------------------------------------------------------------------------------
function build_search_graph($query)
{
	// find name and LSID
	$filename = 'names.tsv';
	
	// test example
	$filename = 'test.tsv';
	
	$obj = search($filename, $query);
	
	// print_r($obj);

	// store any additional URIs to resolve and add to result	
	$to_resolve = array();
	
	// URL of this query
	$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . "$_SERVER[REQUEST_URI]";
	
	// Construct a graph of the results		
	$graph = new \EasyRdf\Graph();
	$feed = $graph->resource($url, 'schema:DataFeed');
	$feed->set('schema:name', 'Search');	
	
	// add the hit(s)
	$counter = 1;
	foreach ($obj->hits as $hit)
	{
		// taxon name identifier in file is a QName so expand to LSID
		if (preg_match('/(?<prefix>\w+):(?<id>.*)$/', $hit->id, $m))
		{
			switch ($m['prefix'])
			{
				case 'if':
					$id = 'urn:lsid:indexfungorum.org:names:' . $m['id'];
					break;

				case 'ion':
					$id = 'urn:lsid:organismnames.com:name:' . $m['id'];
					break;

				case 'ipni':
					$id = 'urn:lsid:ipni.org:names:' . $m['id'];
					break;
					
				case 'nz':
					$id = 'urn:lsid:ubio.org:nz:' . $m['id'];
					break;

				case 'worms':
					$id = 'urn:lsid:marinespecies.org:taxname:' . $m['id'];
					break;
			
				// we shouldn't arrive here
				default:
					$id = $url . '/' . $hit->id;
					break;
			}
		}
		else
		{
			// real badness, somehow we have an identifier with no namespace
			$id = $url . '#' . $counter++;
		}
		
		// If we have a LSID we will want to resolve it to add more informaiton to our results
		if (preg_match('/^urn:lsid/', $id))
		{
			$to_resolve[] = $id;
		}
		
		$item = $graph->resource($id, 'http://schema.org/TaxonName');
		$feed->add('schema:dataFeedElement', $item);
		
		$item->add('schema:name', $hit->text);
				
		if (isset($hit->wikidata))
		{
			$wikidata = $graph->resource('http://www.wikidata.org/entity/' . $hit->wikidata);
			$item->add('schema:isBasedOn', $wikidata );

			$to_resolve[] = $hit->wikidata;
		}
		
		if (isset($hit->bhl))
		{
			// Use isBasedOnUrl as a temporary hack until I figure out annotations
			$item->add('schema:isBasedOnUrl', 'https://www.biodiversitylibrary.org/page/' . $hit->bhl );		
		}
		
		if (isset($hit->higher))
		{
			// Treat the higher level taxon as the parent just so we don't clash with anything in the LSID
			// We just want a single taxon higher up the tree
			$item->add('schema:parentTaxon', $hit->higher );		
		}
		
		if (isset($hit->fragment))
		{
			// Do we have a fragment selector such as the page within a PDF?
			/*
			{
			  "@context": "http://www.w3.org/ns/anno.jsonld",
			  "id": "http://example.org/anno20",
			  "type": "Annotation",
			  "body": {
				"source": "http://example.org/video1",
				"purpose": "describing",
				"selector": {
				  "type": "FragmentSelector",
				  "conformsTo": "http://www.w3.org/TR/media-frags/",
				  "value": "t=30,60"
				}
			  },
			  "target": "http://example.org/image1"
			}
			*/
			
			// This needs to reall be part of an annotation on the PDF, but for now keep things simple
			$item->add('schema:position', $hit->fragment );	
		
		}
		
		
	}
	
	// Serialise the graph as triples, this makes it easier for us to append
	// additional data
	$format = \EasyRdf\Format::getFormat('ntriples');
	$triples = $graph->serialise($format);
	
	if (1)
	{
		// OK, get additional RDF for names, publications, etc. as triples
		// and add them	
		while (count($to_resolve) > 0)
		{
			$uri = array_pop($to_resolve);
		
			if (preg_match('/^urn:lsid/', $uri))
			{
				$lsid_triples = get_lsid_triples($uri);
				
				/*
				echo '<pre>';
				echo htmlentities($lsid_triples);
				echo '</pre>';
				*/
			
				$triples .= $lsid_triples;
				
				
			}
			
			if (preg_match('/^Q/', $uri))
			{			
				$wikidata_triples = get_work($uri);
				
				$triples .= $wikidata_triples;
			}
		
		}
	}
	
	// echo $triples . "\n";	
		
	// Convert triples back into a graph
	$g = new \EasyRdf\Graph();
	$g->parse($triples);
	
	return $g;
}

//----------------------------------------------------------------------------------------
// Serialise search graph as JSON-LD
function serialise_search_graph($g)
{
	
	// Output
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
		
	$context->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	$context->rdfs = 'http://www.w3.org/2000/01/rdf-schema#';
	
	// LSIDs
	$context->tcom = 'http://rs.tdwg.org/ontology/voc/Common#';
	$context->tn = 'http://rs.tdwg.org/ontology/voc/TaxonName#';
	$context->tpub = 'http://rs.tdwg.org/ontology/voc/PublicationCitation#';
	$context->tteam = 'http://rs.tdwg.org/ontology/voc/Team#';
	$context->dwc 	= 'http://rs.tdwg.org/dwc/terms/';
	
	$context->dc = 'http://purl.org/dc/elements/1.1/';	
	$context->dcterms = 'http://purl.org/dc/terms/';	
	$context->owl = 'http://www.w3.org/2002/07/owl#';
	
	$context->wd = 'http://www.wikidata.org/entity/';
	
	// feed	
	$dataFeedElement = new stdclass;
	$dataFeedElement->{'@id'} = "dataFeedElement";
	$dataFeedElement->{'@container'} = "@set";
	$context->{'dataFeedElement'} = $dataFeedElement;
	
	// publication
	$author = new stdclass;
	$author->{'@id'} = "author";
	$author->{'@container'} = "@set"; 

	$context->{'author'} = $author;
		
	// ISSN is always an array
	$issn = new stdclass;
	$issn->{'@id'} = "issn";
	$issn->{'@container'} = "@set";
	
	$context->{'issn'} = $issn;
	
	// encoding is an array
	$encoding = new stdclass;
	$encoding->{'@id'} = "encoding";
	$encoding->{'@container'} = "@set";
	
	$context->{'encoding'} = $encoding;
	
	
	// contentUrl
	$contentUrl = new stdclass;
	$contentUrl->{'@id'} = "contentUrl";
	$contentUrl->{'@type'} = "@id";
	
	$context->{'contentUrl'} = $contentUrl;	
	
	// sameAs
	$sameas = new stdclass;
	$sameas->{'@id'} = "sameAs";
	$sameas->{'@type'} = "@id";
	$sameas->{'@container'} = "@set";
	
	$context->{'sameAs'} = $sameas;
	
	$x = new stdclass;
	$x->{'@reverse'} = "http://www.w3.org/ns/oa#hasBody";
	$context->{'children'} = $x;
	
	
	// Frame document
	$frame = (object)array(
		'@context' => $context,
		'@type' => 'http://schema.org/DataFeed'
	);
	
	$options = array();
			
	$options['context'] = $context;
	$options['compact'] = true;
	$options['frame']= $frame;	
	
	$format = \EasyRdf\Format::getFormat('jsonld');
	$data = $g->serialise($format, $options);
	
	// convert to object
	$obj = json_decode($data);

	return $obj;
}

//----------------------------------------------------------------------------------------
function do_search($q)
{
	$result = array();


	$g = build_search_graph($q);
	
	$obj = serialise_search_graph($g);
	
	// print_r($obj);
	
	// echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	// Make a simple object for us to consume for the web app
	if (isset($obj->dataFeedElement))
	{
		
		foreach ($obj->dataFeedElement as $item)
		{
			$name = new stdclass;
			
			$name->id = $item->{'@id'};
			
			foreach ($item as $k => $v)
			{
				switch ($k)
				{
					case 'tn:nameComplete':
					case 'tn:authorship':
					case 'tn:uninomial':
					case 'tn:genusPart':
					case 'tn:infragenericEpithet':
					case 'tn:specificEpithet':
					case 'tn:infraspecificEpithet':
						$key = str_replace('tn:', '', $k);
						$name->{$key} = $v;
						break;
						
					case 'tcom:microreference':
					case 'tcom:publishedIn':
						$key = str_replace('tcom:', '', $k);
						$name->{$key} = $v;
						break;

					case 'tcom:PublishedIn': // ION
						$key = 'publishedIn';
						$name->{$key} = $v;
						break;
						
					case 'dc:title': // WoRMS
						$name->{$k} = $v;
						break;
						
					case 'dwc:namePublishedIn': // WoRMS
						$key = 'publishedIn';
						$name->{$key} = $v;
						break;
						
					case 'tn:authorship':
						$name->authorship = $v;
						break;
					
					default:
						break;
				}
			
			}
			
			// handle LSID-specific stuff
			
			// Name might be in dc:title
			if (!isset($name->nameComplete) && isset($name->{'dc:title'}))
			{
				$name->nameComplete = $name->{'dc:title'};
			}
			
			// people
			$name->people = array();
						
			// publication (CSL)
			if (isset($item->isBasedOn))
			{
				$name->publication = new stdclass;
				$name->publication->id = $item->isBasedOn->{'@id'};
				
				$citeproc = schema_to_csl($item->isBasedOn);
				
				if (isset($citeproc->DOI))
				{
					$name->publication->doi = $citeproc->DOI;
				}				
				
				$name->publication->citeproc = json_encode($citeproc , JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);				
				
				// Can we get a PDF to display?
				$name->publication->pdf = array();
				if (isset($item->isBasedOn->encoding))
				{
					foreach ($item->isBasedOn->encoding as $encoding)
					{
						if ($encoding->fileFormat ==  "application/pdf")
						{
							$name->publication->pdf[] = $encoding->contentUrl;
						}
					
					}					
				}
				
				// If no PDF then unset
				if (count($name->publication->pdf) == 0)
				{
					unset($name->publication->pdf);
				}				
				
				// people
				if (isset($item->isBasedOn->author))
				{
					foreach ($item->isBasedOn->author as $author)
					{
						$a = new stdclass;
						
						$a->name = get_literal($author->name);
					
						if (preg_match('/http:\/\/www.wikidata.org\/entity\/(?<id>Q\d+)$/', $author->{'@id'}, $m))
						{
							$a->wikidata = $m['id'];
						}
						
						
						if (isset($author->sameAs))
						{
							foreach ($author->sameAs as $sameAs)
							{
								if (preg_match('/researchgate/', $sameAs))
								{
									$a->researchgate = str_replace('https://www.researchgate.net/profile/', '', $sameAs);
								}
								if (preg_match('/orcid/', $sameAs))
								{
									$a->orcid = str_replace('https://orcid.org/', '', $sameAs);
								}
							}
						}
						
						
						$name->people[] = $a;					
					}					
				}
			}
			
			// BHL		
			// Use isBasedOnUrl as a temporary hack
			if (isset($item->isBasedOnUrl))
			{
				$name->bhl = str_replace('https://www.biodiversitylibrary.org/page/', '', $item->isBasedOnUrl);
			}
			
			if (isset($item->parentTaxon))
			{
				$name->parentTaxon = $item->{'parentTaxon'};
			}

			// fragment selector
			if (isset($item->position))
			{
				$name->fragment_selector = $item->{'position'};
			}
			
			
			$result[] = $name;

		}
		
		

	}
	
	return $result;	
}


if (1)
{
	$q = 'Mitrula brevispora';
	
	$q= 'Elaeagnus xichouensis';
	
	$q = 'Kalidos dautzenbergianus';
	
	$q = 'Henckelia wijesundarae';
	
	$q = 'Adenophyllum glandulosum';

	$result = do_search($q);

	//print_r($result);
}

?>
