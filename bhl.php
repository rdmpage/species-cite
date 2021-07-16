<?php


$config['cache']   = dirname(__FILE__) . '/cache/bhl';
$config['api_key'] = '0d4f0303-712e-49e0-92c5-2113a5959159';


//----------------------------------------------------------------------------------------
function get($url)
{
	$data = '';
	
	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	curl_setopt ($ch, CURLOPT_HEADER,		  1);  
	
	// timeout (seconds)
	curl_setopt ($ch, CURLOPT_TIMEOUT, 120);

	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST,		  0);  
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,		  0);  
	
	$curl_result = curl_exec ($ch); 
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		// print_r($info);		
		 
		$header = substr($curl_result, 0, $info['header_size']);
		
		// echo $header;
		
		//exit();
		
		$data = substr($curl_result, $info['header_size']);
		
	}
	return $data;
}



//----------------------------------------------------------------------------------------
function get_item($ItemID, $force = false)
{
	global $config;

	// get BHL item
	$filename = $config['cache'] . '/item-' . $ItemID . '.json';

	if (!file_exists($filename) || $force)
	{
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemMetadata&itemid=' 
			. $ItemID . '&ocr=f&pages=t&apikey=' . $config['api_key'] . '&format=json';
			
		// echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$item_data = json_decode($json);
	
	return $item_data;

}

//----------------------------------------------------------------------------------------
function get_item_from_page($PageID, $force = false)
{
	global $config;
	
	$ItemID = 0;

	// get BHL item
	$filename = $config['cache'] . '/page-' . $PageID . '.json';

	if (!file_exists($filename) || $force)
	{
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetPageMetadata&pageid=' 
			. $PageID . '&ocr=f&names=f&apikey=' . $config['api_key'] . '&format=json';
			
		//echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$page_data = json_decode($json);
	
	if (isset($page_data->Result->ItemID))
	{
		$ItemID = $page_data->Result->ItemID;
	}
	
	return $ItemID;
}

//----------------------------------------------------------------------------------------

$PageID = 19367984;
$PageID = 5031712;
$PageID = 13958696;
// $PageID = 15329584;

$PageID = 34566033	;

$ItemID = get_item_from_page($PageID);

// echo $ItemID . "\n";

$item = get_item($ItemID);

//print_r($item);

// generate html

$page_image_width = 700;


echo 
'<!DOCTYPE html>
<html>
<head>
  <title></title>
	<!-- Load the polyfill first. -->
	<!-- https://github.com/w3c/IntersectionObserver/tree/master/polyfill -->
	<script src="js/intersection-observer.js"></script>
  
  	<style>
  	</style>
</head>
<body style="padding:0px;margin:0px;">';

echo '<div id="viewport" style="background-color:#e5e5e5;width:100%;height:500px;overflow:hidden;">' . "\n";
echo '	<div id="overflow-scrolling" style="overflow-y:auto;position:relative;height:100%;">' . "\n";  
echo '		<div id="surface" style="cursor:grab;">' . "\n";

foreach ($item->Result->Pages as $page)
{
	// page image
	echo '<div style="margin: 0 auto;width:80%;position:relative;min-height:500px;">' . "\n";	
	echo '	<a name="' . $page->PageID . '"></a>' . "\n";
	
	echo '	<img class="lazy" style="background-color:white;min-height:500px;width:100%;border:1px solid #ccc;" data-width="' . $page_image_width  . '" data-src="' . $page->FullSizeImageUrl . '">'  . "\n";		
	echo '</div>' . "\n";
	
	// page number/spacer
	echo '<div style="margin: 0 auto;width:80%;position:relative;height:1em;">' . "\n";
	echo '</div>' . "\n";

}

echo '		</div> <!-- surface -->' . "\n";
echo '	</div> <!-- overflow-scrolling -->' . "\n";
echo '</div> <!-- viewport -->' . "\n";

?>

  <script>
	//------------------------------------------------------------------------------------
	// Initialise object to create viewer
	function viewer_data(element_id) {
		
		// Click and drag scrolling
		this.startx;
		this.starty;
		this.diffx;
		this.diffy;
		this.drag;
		
		// Store id of DOM element that encloses the viewer
		this.element_id = element_id;
		
		// Intersection observers
		this.io;
		this.io_info;
	}
	


	//------------------------------------------------------------------------------------
	viewer_data.prototype.actions = function() {
		if (!this.scroller) {
			this.scroller = document.querySelector('#overflow-scrolling');
		}
		
		// add event listeners
		this.scroller.addEventListener('mousedown', this.onMouseDown.bind(this));
		this.scroller.addEventListener('mousemove', this.onMouseMove.bind(this));
		this.scroller.addEventListener('mouseup', this.onMouseUp.bind(this));
		
		window.addEventListener('resize', this.resize.bind(this));
		

		
		// intersection observers for lazy loading of images and tracking which page is being displayed
		
		const images = document.querySelectorAll('img.lazy');
		
		// const images_info = document.querySelectorAll('div.image');
				
		var options_lazyload = {
  			root: document.getElementById('overflow-scrolling'),
			  // rootMargin: top, right, bottom, left margins
			  // added to the bounding box of the root element (viewport if not defined)
			  // see https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver  				
  			rootMargin: '500px 0px 500px 0px',
  			
  			// threshold: how much of the target visible for the callback to be invoked
  			// includes padding, 1.0 means 100%  			
  			threshold: 0.5
  		};

		var options_info= {
  			root: document.getElementById('overflow-scrolling'),
  			 // match the page dimensions
  			rootMargin: '0px 0px 0px 0px',
  			
			// we want a big chunk of the page to be visible so we don't trigger events if just a bit appears  		  			
  			threshold: 0.5 
  		};
		
		if (window.IntersectionObserver) {
		
			// lazy loading of images
  			this.io = new IntersectionObserver(
				(function callback(entries) {
				  for (const entry of entries) {
					if (entry.isIntersecting) {
					  let lazyImage = entry.target;
					  if (!lazyImage.src && lazyImage.hasAttribute('data-src')) {
					  
						lazyImage.src = lazyImage.dataset.src;
					
						// presence of "width" is a flag that we will use an image CDN
						if (lazyImage.hasAttribute('data-width')) {
							lazyImage.src = 'https://aipbvczbup.cloudimg.io/s/width/' + lazyImage.dataset.width + '/' + lazyImage.src;
						}
					  }
					}
				  }
				}).bind(this) // bind(this) gives us access to "this"
				, 
				options_lazyload
			);
  			
  			/*
  			// page information
   			this.io_info = new IntersectionObserver(
				function callback(entries) {
				  for (const entry of entries) {
					if (entry.isIntersecting) {
					  let item = entry.target;
					  if (item.hasAttribute('id')) {
		
						var html = '';
		
						if (item.hasAttribute('title')) {
							html += ' ' + item.title;
							html = item.title;
						}
		
						
						// figure out how best to display what page we are on
						// document.getElementById('info').innerHTML = html;
						
					  }
				  
					}
				  }
				}
				, 
				options_info
			);
			
			*/
			
  		}
  		
  	
  		
  		
		
		for (const image of images) {
		  if (window.IntersectionObserver) {
			this.io.observe(image);
		  } else {
			console.log('Intersection Observer not supported');
			image.src = image.getAttribute('data-src');
		  }
		}  
		/*		

		for (const image of images_info) {
		  if (window.IntersectionObserver) {
			this.io_info.observe(image);
			} 
		} 	
		*/			
		
	}
	
	//------------------------------------------------------------------------------------
	viewer_data.prototype.onMouseDown = function(e) {
		if (!e) { e = window.event; }
		if (e.target && e.target.nodeName === 'IMG') {
			e.preventDefault();
		} else if (e.srcElement && e.srcElement.nodeName === 'IMG') {
			e.returnValue = false;
		}
		this.startx = e.clientX + this.scroller.scrollLeft;
		this.starty = e.clientY + this.scroller.scrollTop;
		this.diffx = 0;
		this.diffy = 0;
		this.drag = true;
	}

	//------------------------------------------------------------------------------------
	viewer_data.prototype.onMouseMove = function(e) {
		if (this.drag === true) {
		
			// https://stackoverflow.com/a/47295954/9684
			// ensure dragging cursor is not text cursor
			document.onselectstart = function(){ return false; }
	
			if (!e) { e = window.event; }
			this.diffy = (this.starty - (e.clientY + this.scroller.scrollTop));
			this.scroller.scrollTop += this.diffy;
		}
    }

	//------------------------------------------------------------------------------------
	viewer_data.prototype.onMouseUp = function(e) {
		if (!e) { e = window.event; }
		this.drag = false;
	
		// https://stackoverflow.com/a/47295954/9684
		document.onselectstart = function(){ return true; }  
		
		// make sure these variables will be in scope for the function animate()
		var el =  this.scroller;
		var diffy = this.diffy;
	
		var start = 1,
			animate = function () {
				var step = Math.sin(start);
				if (step <= 0) {
					window.cancelAnimationFrame(animate);
				} else {
					el.scrollTop += diffy * step;
					start -= 0.02;
					window.requestAnimationFrame(animate);
				}
			};
		animate();
    }
    
	//------------------------------------------------------------------------------------
	// resize viewer to match size of containing element
	viewer_data.prototype.resize = function() {
		var width = window.innerWidth;
		var height = window.innerHeight;
		
		var d = document.getElementById('viewport');		
		d.style.height= height + 'px';

	
	}
    


	
	// Create and setup the viewer
	
	v = new viewer_data('viewport');
		
	// add event handlers and observers
	v.actions();
	v.resize();
	</script>  

<?php

echo '
</body>
</html>';



?>

