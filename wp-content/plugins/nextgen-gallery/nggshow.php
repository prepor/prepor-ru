<?php

$wpconfig = realpath("../../../wp-config.php");
if (!file_exists($wpconfig)) die; // stop when wp-config is not there
require_once($wpconfig);

global $wpdb;

// get the options
$ngg_options=get_option('ngg_options');	

//reference thumbnail class
include_once('lib/thumbnail.inc.php');
include_once('lib/nggallery.lib.php');

$pictureID = (int) $_GET['pid'];
$mode = attribute_escape($_GET['mode']);

// let's get the image data
$picture  = new nggImage($pictureID);

$thumb = new ngg_Thumbnail($picture->absPath);
if ( isset($_GET['height']) and isset($_GET['width']))
	$thumb->resize($_GET['width'],$_GET['height']);
if ($mode == 'watermark') {
	if ($ngg_options['wmType'] == 'image') {
		$thumb->watermarkImgPath = $ngg_options['wmPath'];
		$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']); 
	}
	if ($ngg_options['wmType'] == 'text') {
		$thumb->watermarkText = $ngg_options['wmText'];
		$thumb->watermarkCreateText($ngg_options['wmColor'], $ngg_options['wmFont'], $ngg_options['wmSize'], $ngg_options['wmOpaque']);
		$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']);  
	}
}
if ($mode == 'web20')
	$thumb->createReflection(40,40,50,false,'#a4a4a4');
	
$thumb->show();
$thumb->destruct();

exit;

/*
createReflection($percent,$reflection,$white,$border,$borderColor)

i.e. $thumb->createReflection(40,40,80,true,'#a4a4a4');

Creates an Apple™-style reflection (it’s more of a web 2.0 thing now, I know…) 
from an image. This one’s a bit weird to explain, but here goes:

$percent - What percentage of the image to create the reflection from 
$reflection - What percentage of the image height should the reflection height be. 
i.e. If your image is 100 pixels high, and you set reflection to 40, the reflection would be 40 pixels high. 

$white - How transparent (using white as the background) the reflection should be, as a percent 
$border - Whether a border should be drawn around the original image (default is true) 
$borderColor - The hex value of the color you would like your border to be (default is #a4a4a4)
*/
?>