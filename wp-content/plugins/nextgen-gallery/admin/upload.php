<?php

require_once('../../../../wp-config.php');

// Flash often fails to send cookies with the POST or upload, so we need to pass it in GET or POST instead
if ( empty($_COOKIE[USER_COOKIE]) && !empty($_REQUEST['user_cookie']) )
	$_COOKIE[USER_COOKIE] = $_REQUEST['user_cookie'];

if ( empty($_COOKIE[PASS_COOKIE]) && !empty($_REQUEST['pass_cookie']) )
	$_COOKIE[PASS_COOKIE] = $_REQUEST['pass_cookie'];

// don't ask me why, sometime needed, taken from wp core
unset($current_user);

// admin.php require a proper login cookie
require_once(ABSPATH . '/wp-admin/admin.php');

header('Content-Type: text/plain');

//check for correct capability
if ( !is_user_logged_in() )
	die('Login failure. -1');

//check for correct capability
if ( !current_user_can('NextGEN Manage gallery') ) 
	die('You do not have permission to upload files. -2');

function get_out_now() { exit; }
add_action( 'shutdown', 'get_out_now', -1 );

//check for correct nonce 
check_admin_referer('ngg_swfupload');

//check for nggallery
if ( !defined('NGGALLERY_ABSPATH') )
	die('NextGEN Gallery not available. -3');
	
include_once (NGGALLERY_ABSPATH. 'admin/functions.php');

// get the gallery
$galleryID = (int) $_POST['galleryselect'];

echo nggAdmin::swfupload_image($galleryID);

?>