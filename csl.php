<?php

error_reporting(E_ALL);

// Convert JSON-LD to CSL

//----------------------------------------------------------------------------------------
// get root of JSON-LD document. If we have @graph we assume document is framed so there 
// is only one root (what could possibly go wrong?)
function get_root($obj)
{
	$root = $obj;
	if (is_array($root))
	{
		$root = $root[0];
	}
	if (isset($root->{'@graph'}))
	{
		$root = $root->{'@graph'};
	
		if (is_array($root))
		{
			$root = $root[0];
		}
	}
	
	return $root;
}

//----------------------------------------------------------------------------------------
// Return the value for a given propertyValue
function get_property_value($key, $propertyID)
{
	$value = '';
	
	if (is_object($key) && !is_array($key))
	{
		if ($key->propertyID == $propertyID)
		{
			$value = $key->value;
		}			
	}
	else
	{
		if (is_array($key))
		{
			foreach ($key as $k)
			{
				if ($k->propertyID == $propertyID)
				{
					$value = $k->value;
				}				
			}
		}
	
	}
	
	// we may have badness if we have more than one value (e.g., multiple DOis)
	if (is_array($value))
	{
		$value = $value[0];
	}

	return $value;
}

//----------------------------------------------------------------------------------------
// Literals may be strings, objects (e.g., a @language, @value] pair), or an array.
// Handle this and return a string
function get_literal($key, $language='en')
{
	$literal = '';
	
	if (is_string($key))
	{
		$literal = $key;
	}
	else
	{
		if (is_object($key) && !is_array($key))
		{
			$literal = $key->{'@value'};
		}
		else
		{
			if (is_array($key))
			{
				$values = array();
				
				foreach ($key as $k)
				{
					if (is_object($k))
					{
						$values[] = $k->{'@value'};
					}
				}
				
				$literal = join(" / ", $values);
			}
		}
	}
		
	return $literal;
}

//----------------------------------------------------------------------------------------
// Given a string with multiple values and a delimiter, split it
function pick_one_string($str, $delimiter = "/")
{
	$parts = preg_split('/\s*\/\s*/', $str);
	
	return $parts[0];
}

//----------------------------------------------------------------------------------------
// Take a framed JSON-LD object for a bibliographic record and convert it to CSL
function schema_to_csl($obj)
{

	// can we be clever about languages?

	$csl = new stdclass;
	
	$author_index = -1;
	
	foreach ($obj as $k => $v)
	{
		switch ($k)
		{		
			case '@id':
				$csl->WIKIDATA = str_replace('http://www.wikidata.org/entity/', '', $v);
				break;
		
			case '@type':
				switch ($v)
				{				
					case 'Book':
						$csl->type = 'book';
						break;

					case 'Chapter':
						$csl->type = 'chapter';
						break;						

					case 'ScholarlyArticle':
						$csl->type = 'article-journal';
						break;
												
					default:
						$csl->type = 'article-journal';
						break;
				}
				break;
				
			// SciGraph
			case 'genre':
				switch ($v)
				{
					case 'research_article':
						$csl->type = 'article-journal';
						break;
						
					default:
						$csl->type = 'article-journal';
						break;
				}
				break;
	
			case 'author':
			case 'creator':
				$csl->author = array();
				if (is_array($v))
				{
					foreach ($v as $value)
					{
						$author = new stdclass;
						if (isset($value->name))
						{
							// In the CSL schema but seemingly problematic for citeproc PHP
							$author->literal = get_literal($value->name);
							

							if (!isset($value->familyName))
							{
								// We need to handle author names where there has been a clumsy attempt
								// (mostly by me) to include multiple language strings
							
								// 大橋広好(Hiroyoshi Ohashi)
								// 韦毅刚/WEI Yi-Gang
								if (preg_match('/^(.*)\s*[\/|\(]([^\)]+)/', $author->literal, $m))
								{
									// print_r($m);
									
									if (preg_match('/\p{Han}+/u', $m[1]))
									{
										$author->literal = $m[2];									
									}
									if (preg_match('/\p{Han}+/u', $m[2]))
									{
										$author->literal = $m[1];									
									}
									
								}							
							
								$parts = preg_split('/,\s+/', get_literal($author->literal));
								
								if (count($parts) == 2)
								{
									$author->family = $parts[0];
									$author->given = $parts[1];
								}
								else
								{
									$parts = preg_split('/\s+/', get_literal($author->literal));
									
									if (count($parts) > 1)
									{
										$author->family = array_pop($parts);
										$author->given = join(' ', $parts);
									}
									
								}
							}
						
						}
						
						// CSL only works in PHP if we split names into parts
						if (isset($value->familyName))
						{
							// SciGraph
							if (isset($value->familyName))
							{
								$author->family = $value->familyName;
							}

							if (isset($value->givenName))
							{
								$author->given = $value->givenName;						
							}
						}
						
						// Need to ensure authors are ordered correctly
						// so use "position" if it is available
						if (isset($value->position))
						{
							$author_index = ($value->position - 1);
						}
						else
						{
							$author_index++;
						}
						
						// ORCID?
						if (isset($value->sameAs))
						{
							foreach ($value->sameAs as $url)
							{
								if (preg_match('/orcid.org/', $url))
								{
									$author->ORCID = $url;
								}
							}
						}					
												
						$csl->author[$author_index] = $author;
					}
					
					// ensure authors are ordered correctly by sorting on the array index
					if (isset($csl->author))
					{
						ksort($csl->author, SORT_NUMERIC);
					}					
				}
				else
				{
					// ORCID JSON-LD has only one value
					if (is_object($v))
					{
						$author = new stdclass;
						
						if (isset($v->familyName))
						{
							if (isset($v->familyName))
							{
								$author->family = $v->familyName;
							}

							if (isset($v->familyName))
							{
								$author->given = $v->givenName;						
							}
						}
						
						if (isset($v->{'@id'}))
						{
							if (preg_match('/orcid.org/', $v->{'@id'}))
							{
								$author->ORCID = $v->{'@id'};
							}
						}						
						
						$csl->author[] = $author;
						
					
					}
				}
				break;
				
			case 'headline': // ResearchGate
			case 'name':
				$csl->title = get_literal($v);
				break;
				
			case 'isPartOf':
				if (is_object($v))
				{
					if (isset($v->issn))
					{
						$csl->ISSN = $v->issn;
					}		
					if (isset($v->name))
					{
						$csl->{'container-title'} =  get_literal($v->name);
					}
					
					// OUP
					if (isset($v->issueNumber))
					{
						$csl->issue = $v->issueNumber;
					}	
					
					if (isset($v->isPartOf))
					{
						if (isset($v->isPartOf->issn))
						{
							$csl->ISSN = $v->isPartOf->issn;
						}	
						if (isset($v->isPartOf->name))
						{
							$csl->{'container-title'} =  get_literal($v->isPartOf->name);
						}	
						
					}
											
				}
				
				// SciGraph
				if (is_array($v))
				{
					foreach ($v as $part)
					{
						if (isset($part->issn))
						{
							$csl->ISSN = $part->issn;
						}

						if (isset($part->name))
						{
							$csl->{'container-title'} = get_literal($part->name);
						}
						
						if (isset($part->issueNumber))
						{
							$csl->issue = $part->issueNumber;
						}

						if (isset($part->volumeNumber))
						{
							$csl->volume = $part->volumeNumber;
						}
						
					}
				}
				
				// Clean up
				if (isset($csl->{'container-title'}))
				{
					$csl->{'container-title'} = pick_one_string($csl->{'container-title'}, "/");
				}
				break;				
				
			case 'datePublished':
				$v = preg_replace('/^\+/', '', $v);
				$v = preg_replace('/T.*$/', '', $v);
			
				$parts = explode('-', $v);
			
				$csl->issued = new stdclass;
				$csl->issued->{'date-parts'} = array();
				$csl->issued->{'date-parts'}[0] = array();

				$csl->issued->{'date-parts'}[0][] = (Integer)$parts[0];

				if (count($parts) > 1 && $parts[1] != '00')
				{		
					$csl->issued->{'date-parts'}[0][] = (Integer)$parts[1];
				}

				if (count($parts) > 2 && $parts[2] != '00')
				{		
					$csl->issued->{'date-parts'}[0][] = (Integer)$parts[2];
				}
				break;
			
			case 'volumeNumber':
				$csl->volume = $v;
				break;			
			
			case 'issueNumber':
				$csl->issue = $v;
				break;
				
			case 'pagination':
				$csl->page = $v;
				break;				
				
			// add some identifiers to CSL
			case 'identifier':
				if (is_array($v) || is_object($v))
				{
					$doi = get_property_value($v, 'doi');
					if ($doi != '')
					{
						$csl->DOI = strtolower($doi);
					}
					
					$handle = get_property_value($v, 'handle');
					if ($handle != '')
					{
						$csl->HANDLE = $handle;
					}
					
					$archive = get_property_value($v, 'internetarchive');
					if ($archive != '')
					{
						$csl->ARCHIVE = $archive;
					}
					
					$jstor = get_property_value($v, 'jstor');
					if ($jstor != '')
					{
						$csl->JSTOR = strtolower($jstor);
					}					

					$pmid = get_property_value($v, 'pmid');
					if ($pmid != '')
					{
						$csl->PMID = $pmid;
					}

					$pmc = get_property_value($v, 'pmc');
					if ($pmc != '')
					{
						$csl->PMC = $pmc;
					}
										
				}
				else
				{
					if (preg_match('/https?:\/\/(dx.)?doi.org\/(?<doi>.*)/', $v, $m))
					{
						$csl->DOI = $m['doi'];
					}
				}
				break;
				
			// SciGraph
			case 'productId':
				if (is_array($v))
				{
					foreach ($v as $productId)
					{
						if ($productId->name == 'doi')
						{
							$csl->DOI = strtolower($productId->value[0]);
						}
					}
				
				}
				break;
				
			// SciGraph
			// Zenodo
			/*
			case 'description':
				$csl->abstract = $v;
				break;	
			*/			
				
			case 'mainEntityOfPage': // ResearchGate
			case 'url':
				if (is_string($v))
				{
					$csl->URL = $v;
					
					if (preg_match('/https?:\/\/(dx.)?doi.org\/(?<doi>.*)/', $v, $m))
					{
						$csl->DOI = $m['doi'];
					}
					
				}
				break;
				
			case 'encoding':
				if (is_array($v))
				{
					foreach ($v as $encoding)
					{
						$link = new stdclass;
						$link->URL = $encoding->contentUrl;
						$link->{'content-type'} = $encoding->fileFormat;
						
						if (!isset($csl->link))
						{
							$csl->link = array();
						}
						$csl->link[] = $link;
					}				
				}
				break;				
			
			default:
				break;
		}
	}
	

	
	return $csl;

}

?>
