<?php

// test things

error_reporting(E_ALL);

require_once('vendor/autoload.php');

require_once(dirname(__FILE__) . '/wikidata.php');
require_once(dirname(__FILE__) . '/csl.php');


$qid = 'Q29464102';

$triples = get_work($qid);

echo $triples;

	$g = new \EasyRdf\Graph();
	$g->parse($triples);

	// Output
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';

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
	
	// Frame document
	$frame = (object)array(
		'@context' => $context,
		'@type' => 'http://www.wikidata.org/entity/Q13442814'
	);	
	
	$options = array();
			
	$options['context'] = $context;
	$options['compact'] = true;
	$options['frame']= $frame;	
	
	$format = \EasyRdf\Format::getFormat('jsonld');
	$data = $g->serialise($format, $options);
	
	// convert to object
	$obj = json_decode($data);
	
	print_r($obj);
	
	$csl = schema_to_csl($obj);
	
	print_r($csl);
	

?>

