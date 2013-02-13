<?php
define('BASE_URL', 'http://www.thesite.com');
define('IMAGE_BASE_URL', 'http://images.thesite.com');
define('TRAP_BASE_URL', 'http://cacheimages.thesite.com');

define('PATH_WATERMARK', './overlay-mask.png');
define('PATH_ERROR_IMG', './logo.png');
define('PATH_CACHE_DIR', './cached/');

function is_google_bot() {
	return (strpos($_SERVER['HTTP_USER_AGENT'], 'Googlebot') !== FALSE);
}

function error_photo(){
	header('Content-Type: image/png');
	header('X-Robots-Tag: noindex');
	readfile(PATH_ERROR_IMG);
	exit();
}

if(!isset($_SERVER['HTTP_USER_AGENT']))
	$_SERVER['HTTP_USER_AGENT'] = '';

if(!isset($_SERVER['HTTP_REFERER']))
	$_SERVER['HTTP_REFERER'] = '';


$paramP = (isset($_GET['p']));
$file = (isset($_GET['p'])?$_GET['p']:$_SERVER["REQUEST_URI"]);
if(
	//tricksy we are ?
	(!isset($file) || strpos($file, '..') !== FALSE) ||
	//expect image URL format
	(!preg_match('@^/full-images/[^/]+/.*\.(jpg|gif|png|jpeg)$@i',$file,$matches))
) {
	error_photo();
}

if( is_google_bot()) {
	header('Content-Type: image/jpeg');
	readfile(IMAGE_BASE_URL . $file);
	exit();
} else {
	// TODO add check with the Accept headers (?)
	if(preg_match('@google.[a-z\.]+/@i', $_SERVER['HTTP_REFERER'], $matches)){
		//user visits from google - redirect to image post

		//This part depends on the structure of your URLs,
		//if you can get the page URL from the image url, you're king
		//otherwise make a middleware script to help you with that
		preg_match('@/full-images/.*-(\d+)\.[a-z]{3,4}$@',$file,$matches);
		header('Location: '.BASE_URL.'/gotopage.php?photoid='.$matches[1]);
		exit();
	} else if(preg_match('@^http\:\/\/www\.thesite\.com\/.*@i',$_SERVER['HTTP_REFERER'],$matches)){
		//if image requested from your site, show the raw image
		header('Content-Type: image/jpeg');
		readfile(IMAGE_BASE_URL . $file);
		exit();
	} else {
		//image viewed from some other place - show watermarked image
		if(!$paramP){
			header('Location: '.TRAP_BASE_URL.'/img.php?p=' . $file . '&rn=' . rand(1000,9999));
		}
	}
}


try {
	preg_match('@/create/[^/]+/(.*)\.[a-z]{3,4}$@',$file,$matches);
	$m = $matches[1];

	//check cache
	$cachedImgPath = PATH_CACHE_DIR . $m[0] . '/' . $m . '.jpg';
	if(file_exists($cachedImgPath)){
		// cached already
		header('Content-Type: image/png');
		header('X-Robots-Tag: noindex');
		readfile($cachedImgPath);
		exit();
	}

	// Overlay/Watermark the full-sized image for Google Images results
	$handle = fopen(IMAGE_BASE_URL . $file, 'rb');
	$img = new Imagick();
	$img->readImageFile($handle);

	// Fit watermark image for source image
	$wimg = new Imagick(PATH_WATERMARK);
	$wimg->scaleImage($img->getImageWidth(), 0);

	// Create the overlay mask and add on top of the source
	$bimg = new Imagick();
	$bimg->newPseudoImage($img->getImageWidth(), $img->getImageHeight(), 'xc:black');
	$bimg->setImageOpacity(0.8);
	$img->compositeImage($bimg, Imagick::COMPOSITE_OVER, 0, 0);

	// Add watermark above all
	$img->compositeImage($wimg, Imagick::COMPOSITE_OVER, 0, 0);

	// Cache the generated image
	if (mkdir($cacheDir.$m[0], 0777, true)) {
		$img->writeImage($cachedImgPath);
	}

	header('Content-Type: image/' . $img->getImageFormat());
	header('X-Robots-Tag: noindex');
	echo $img->getImageBlob();
	exit();

}catch(Exception $e) {
	//show static image
	error_photo();
	header('Content-Type: image/png');
}

?>