<?php
/**
 * THUMB GENERATION SCRIPT
 * By Simon East, Surface Digital, July 2012
 * 
 * To use, just refer to the image using the following URL format:
 * 
 * 	/thumb/200x100/fit/files/images/image.jpg
 * 	/thumb/200x100/crop/files/images/image.jpg
 * 	/thumb/200x100/cropFromLeft/files/images/image.jpg
 * 	/thumb/200x100/cropFromRight/files/images/image.jpg
 * 	/thumb/200x100/cropFromTop/files/images/image.jpg
 * 	/thumb/200x100/cropFromBottom/files/images/image.jpg
 * 			  ^           ^          ^
 * 			  |           |          |
 *            |           |          Path under webroot to the original image
 *            |           |          
 *            |           Whether to resize image to fit *inside* those dimensions, or to crop to exact size
 *            |
 *            Size of thumbnail
 * 
 * The thumbnailer can also load images from a remote server by appending the FULL URL but dropping the colon:
 * 
 *  /thumb/200x100/fit/https//remote-site.com/images/myimage.jpg
 *  
 * The domain MUST be placed in the $ALLOWED_SITES array below for this to work.
 * 
 * The first time a thumb is requested, the file will not be present so .htaccess will call this script
 * which generates the image and saves it in the correct location.  Future calls don't load PHP at all
 * (for best performance).
 * 
 * TimThumb parameters are explained here:
 * http://www.binarymoon.co.uk/2012/02/complete-timthumb-parameters-guide/
 */


//---------------------------------- SETTINGS ---------------------------------------------------
// Normally this script does NOT permit loading images from a remote server,
// but if this functionality is required, a whitelist of domain names can be specified here
$ALLOWED_SITES = [
	// Example:
	// 'thankyou-tyi.s3-ap-southeast-2.amazonaws.com',
];
//-----------------------------------------------------------------------------------------------

// We originally used the $_GET querystring to find the URL, however filenames with an "&" sign got truncated
// 'QUERY_STRING' gives us the full thing, but with "url=" at the start which we strip out
// Our URL should then equal something like "180x180/crop/img/briefcase-icon.png"
$url = $_SERVER['QUERY_STRING'];
$url = preg_replace('/^url=/i', '', $url);

// For debugging 
// die($url);

$url = explode('/', $url);						// split URL on slashes
$size = explode('x', $url[0]);					// eg. 180x180
$method = $url[1];								// eg. crop
$isRemoteUrl = preg_match('/^https?$/', $url[2]);
$path = $isRemoteUrl
	? $url[2] . '://' . implode('/', array_slice($url, 3))	// eg. 'https://domain/img/briefcase-icon.png'
	: '/' . implode('/', array_slice($url, 2));				// eg. /themes/default/img/briefcase-icon.png

//---------- TimThumb Resizing/Cropping (if file actually exists) -------------------------------
if ($isRemoteUrl || is_file("..$path")) {
	
	// Set $_GET parameters correctly (how TimThumb likes them)
	$_GET['src'] = $path;
	$_GET['w'] = $size[0];
	$_GET['h'] = $size[1];
	$_GET['q'] = 93;								// quality
	$_GET['s'] = true;								// sharpen images a little
	$_GET['zc'] = ($method == 'fit' ? 3 : 1);		// zoom/crop method
	if ($method == 'cropFromLeft')   $_GET['a'] = 'l';
	if ($method == 'cropFromRight')  $_GET['a'] = 'r';
	if ($method == 'cropFromTop') 	 $_GET['a'] = 't';
	if ($method == 'cropFromBottom') $_GET['a'] = 'b';

	// TimThumb settings
	define('ALLOW_EXTERNAL', $isRemoteUrl);
	
	if ($isRemoteUrl)
		set_time_limit(150);
	
	// define ('DEBUG_ON', true);
	// define ('DEBUG_LEVEL', 2);
	// define('FILE_CACHE_ENABLED', false);

	// We're implementing our own caching, so don't need TimThumb's cache (which will just consume disk space)
	define('FILE_CACHE_MAX_FILE_AGE', 60);
	define('FILE_CACHE_TIME_BETWEEN_CLEANS', 100);

	// Run TimThumb and save result into image file
	ob_start();
	require 'timthumb.class.php';
	$imageData = ob_get_flush();
	$folderPath = implode('/', array_slice($url, 0, -1));		// chop off filename from path
	if (!is_dir($folderPath)) mkdir($folderPath, 0755, true);
	file_put_contents($_GET['url'], $imageData);
	
//-------- If file is missing, download placeholder image from placehold.it and cache it locally -------------------
} else {
	
	$size = $url[0];
	$localGifLocation = "$size/placeholder.gif";
	if (file_exists($localGifLocation)) {
		header('Content-Type: image/gif'); 
		echo file_get_contents($localGifLocation);
	} else {
		// $gif = file_get_contents("http://placehold.it/$size");
		// file_get_contents doesn't work for remote URLs on Ilisys (allow_url_fopen is off), so try curl instead
		$gif = getUrl("http://placehold.it/$size");
		if ($gif) {
			if (!is_dir($size)) mkdir($size, 0755, true);
			file_put_contents($localGifLocation, $gif);			// cache placeholder for later use
			header('Content-Type: image/gif'); 
			echo $gif;
		} else {
			echo 'Oops, image was NOT FOUND and we also couldn\'t load a placeholder from placehold.it';
		}
	}
	
}


// Returns the result of a remote URL (using curl)
// (sucks that curl requires so many commands just for a single request...)
function getUrl($url) {
	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1); 	// do not output directly, use variable
	curl_setopt($handle, CURLOPT_BINARYTRANSFER, 1); 	// do a binary transfer
	curl_setopt($handle, CURLOPT_FAILONERROR, 1); 	 	// fail if error code is > 400
	curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1); 	// follow redirects (although this is *also* disallowed on Ilisys)
	$result = curl_exec($handle);
	curl_close($handle);
	return $result;
}
