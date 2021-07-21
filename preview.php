<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/vendor/autoload.php');

use Sunra\PhpSimple\HtmlDomParser;


$callback = '';
if (isset($_GET['callback']))
{	
	$callback = $_GET['callback'];
}


$url = 'https://www.tandfonline.com/doi/abs/10.1080/00837792.2014.961731';

$url = 'https://doi.org/10.1080/00837792.2014.961731';
$url = 'https://doi.org/10.20531/tfb.2016.44.2.09';
// $url = 'https://www.jstor.org/stable/25083798';
// $url = 'https://doi.org/10.1111/j.1096-3642.2008.00466.x';

//$url = 'https://academic.oup.com/zoolinnean/article/155/2/374/2596011';

$url = 'https://www.biotaxa.org/Zootaxa/article/view/zootaxa.4830.3.2';

if (isset($_GET['url']))
{
	$url = $_GET['url'];
}


//----------------------------------------------------------------------------------------
function get($url)
{	
	$data = null;
	
	$headers = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
		'Accept-Language: en-gb',
	);

	$opts = array(
		CURLOPT_URL =>$url,
		CURLOPT_FOLLOWLOCATION => TRUE,
		CURLOPT_RETURNTRANSFER => TRUE,

		CURLOPT_SSL_VERIFYHOST=> FALSE,
		CURLOPT_SSL_VERIFYPEER=> FALSE,	  
		
		CURLOPT_COOKIEJAR => 'cookie.txt',
		CURLOPT_HTTPHEADER => $headers,
		
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	
	// print_r($info);
	
	curl_close($ch);
	
	return $data;
}


//----------------------------------------------------------------------------------------

$obj = new stdclass;
$obj->ok = false;

$html = get($url);

if ($html == '')
{
	$obj->message = "no html";
}
else
{
	//echo $html;
	
	$html = substr($html, 0, 20000);
	
	// echo $html;

	$dom = HtmlDomParser::str_get_html($html);
	
	if (!$dom)
	{
		$obj->message = "no DOM";
	}
	else
	{
		$obj->ok = true;
		
		$metas = $dom->find('meta');

		foreach ($metas as $meta)
		{
			$key = '';
			if (isset($meta->property))
			{
				$key = $meta->property;
			}
			if (isset($meta->name))
			{
				$key = $meta->name;
			}
			switch ($key)
			{
				// Facebook tags
				case 'og:title':
				case 'og:type':
				case 'og:url':
				case 'og:image':
				case 'og:site_name':
				case 'og:description':
					$k = str_replace('og:', '', $key);
					$obj->{$k} = $meta->content;
					break;
				
				default:
					break;
			}
			//echo $meta->property . " " . $meta->content . "\n";
			//echo $meta->name . " " . $meta->content . "\n";
		}
		
		// other tags if needed

		// special cases...
		
		// <title>
		if (!isset($obj->title))
		{			
			foreach ($dom->find('title') as $title)
			{
				$obj->title = $title->plaintext;
			}
		}
		
		// site icon
		if (!isset($obj->image))
		{			
			foreach ($dom->find('link[rel=apple-touch-icon]') as $link)
			{
				$obj->image = $link->href;
			}
		}

		/*
		if (!isset($obj->image))
		{			
			foreach ($dom->find('link[rel=apple-touch-icon-precomposed]') as $link)
			{
				if (!isset($obj->image))
				{
					$obj->image = $link->href;
				}
			}
		}
		*/
		
		// Zootaxa OJS
		if (!isset($obj->image))
		{			
			foreach ($dom->find('div[class=item cover_image] div a img') as $img)
			{
				$obj->image = $img->src;
			}
		}
		
		
		if (!isset($obj->url))
		{			
			$obj->url = $url;
		}
		
		
		// clean up
		if (isset($obj->description))
		{			
			$obj->description = html_entity_decode($obj->description);
		}
		
		// https://bioone.org
		if (isset($obj->image) && preg_match('/^\//', $obj->image))
		{			
			if (isset($obj->site_name))
			{
				switch ($obj->site_name)
				{
					case 'BIOONE':
						$obj->image = 'https://bioone.org' . $obj->image;
						break;
				
					default:
						break;
				}
			}
		}
		
		
		
		
	}



}

if ($callback != '')
{
	echo $callback . '(';
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($callback != '')
{
	echo ')';
}

?>

