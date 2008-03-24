<?php
// ************************************
// ** Admin Section for NextGEN Gallery
// ** by Alex Rabe
// ************************************

// add to header in admin area
add_action('admin_head', 'ngg_header');
function ngg_header() {
	global $wp_version;
	echo "\n".'<meta name="NextGEN" content="'.NGGVERSION.'" />';
	echo "\n".'<meta http-equiv="pragma" content="no-cache" />'."\n";

	// since WP2.5 redesign we need a different CSS
	if ($wp_version < "2.4")
		echo '<link rel="stylesheet" href="'.NGGALLERY_URLPATH.'admin/css/nggadmin.wp23.css" type="text/css" media="screen" />'."\n"; 
	else {
		echo '<link rel="stylesheet" href="'.NGGALLERY_URLPATH.'admin/css/nggadmin.wp25.css" type="text/css" media="screen" />'."\n";
		wp_admin_css( 'css/dashboard' );
	}
}

include_once (dirname (__FILE__)."/uploadtab.php");

// load script files depend on page
add_action('init', 'ngg_add_admin_js',1);
function ngg_add_admin_js() {
	global $wp_version;
	
	if ($wp_version < "2.5") {
		wp_deregister_script('jquery'); 
		wp_register_script('jquery', NGGALLERY_URLPATH .'admin/js/jquery.js', FALSE, '1.2.2');
	} 
	switch ($_GET['page']) {
		case "nggallery-manage-gallery" :
			if ($wp_version < "2.5") wp_deregister_script('interface');
			wp_enqueue_script('interface', NGGALLERY_URLPATH .'admin/js/interface.js', array('jquery'), '1.2.1');
			wp_enqueue_script('thickbox', NGGALLERY_URLPATH .'thickbox/thickbox-pack.js', array('jquery'), '3.1.1');
		break;
		case "nggallery-manage-album" :
			if ($wp_version < "2.5") wp_deregister_script('interface');
			wp_enqueue_script('interface', NGGALLERY_URLPATH .'admin/js/interface.js', array('jquery'), '1.2.1');
		break;
		case "nggallery-options" :
			wp_enqueue_script('tabs', NGGALLERY_URLPATH .'admin/js/jquery.tabs.pack.js', array('jquery'), '2.7.4');
		break;		
		case "nggallery-add-gallery" :
			wp_enqueue_script('tabs', NGGALLERY_URLPATH .'admin/js/jquery.tabs.pack.js', array('jquery'), '2.7.4');
			wp_enqueue_script('mutlifile', NGGALLERY_URLPATH .'admin/js/jquery.MultiFile.js', array('jquery'), '1.1.1');
			wp_enqueue_script('swfupload', NGGALLERY_URLPATH .'admin/js/swfupload.js', array('jquery'), '2.0.1');
			wp_enqueue_script('swfupload-handler', NGGALLERY_URLPATH .'admin/js/swfupload.handler.js', array('swfupload'), '1.0.0');
		break;
	}
	// required for upload tab
	if ( ($_GET['tab'] == 'ngg_gallery') && ($_GET['style'] != 'inline') )
		 wp_enqueue_script('thickbox', NGGALLERY_URLPATH .'thickbox/thickbox-pack.js', array('jquery'), '3.1.2');
}
	
// add to menu
add_action('admin_menu', 'add_nextgen_gallery_menu');

  function add_nextgen_gallery_menu()
  {
    add_menu_page(__('Gallery', 'nggallery'), __('Gallery', 'nggallery'), 'NextGEN Gallery overview', NGGFOLDER, 'show_menu');
    add_submenu_page( NGGFOLDER , __('Add Gallery', 'nggallery'), __('Add Gallery', 'nggallery'), 'NextGEN Upload images', 'nggallery-add-gallery', 'show_menu');
    add_submenu_page( NGGFOLDER , __('Manage Gallery', 'nggallery'), __('Manage Gallery', 'nggallery'), 'NextGEN Manage gallery', 'nggallery-manage-gallery', 'show_menu');
    add_submenu_page( NGGFOLDER , __('Album', 'nggallery'), __('Album', 'nggallery'), 'NextGEN Edit album', 'nggallery-manage-album', 'show_menu');
    add_submenu_page( NGGFOLDER , __('Options', 'nggallery'), __('Options', 'nggallery'), 'NextGEN Change options', 'nggallery-options', 'show_menu');
    if (wpmu_enable_function('wpmuStyle'))
	add_submenu_page( NGGFOLDER , __('Style', 'nggallery'), __('Style', 'nggallery'), 'NextGEN Change style', 'nggallery-style', 'show_menu');
    add_submenu_page( NGGFOLDER , __('Setup Gallery', 'nggallery'), __('Setup', 'nggallery'), 'activate_plugins', 'nggallery-setup', 'show_menu');
    if (wpmu_enable_function('wpmuRoles'))
	add_submenu_page( NGGFOLDER , __('Roles', 'nggallery'), __('Roles', 'nggallery'), 'activate_plugins', 'nggallery-roles', 'show_menu');
    add_submenu_page( NGGFOLDER , __('About this Gallery', 'nggallery'), __('About', 'nggallery'), 'NextGEN Gallery overview', 'nggallery-about', 'show_menu');
	if (wpmu_site_admin())
	add_submenu_page( 'wpmu-admin.php' , __('NextGEN Gallery', 'nggallery'), __('NextGEN Gallery', 'nggallery'), 'activate_plugins', 'nggallery-wpmu', 'show_menu');

}
  
  /************************************************************************/
  
  	// reduce footprint
  	// Thx to http://weblogtoolscollection.com/archives/2007/07/09/reduce-the-size-of-your-wordpress-plugin-footprint/
  	
  	function  show_menu() {
  		global $wp_version;
  		switch ($_GET["page"]){
			case "nggallery-add-gallery" :
				include_once (dirname (__FILE__). '/functions.php');	// admin functions
				include_once (dirname (__FILE__). '/addgallery.php');	// nggallery_admin_add_gallery
				nggallery_admin_add_gallery();
				break;
			case "nggallery-manage-gallery" :
				include_once (dirname (__FILE__). '/functions.php');	// admin functions
				include_once (dirname (__FILE__). '/manage.php');		// nggallery_admin_manage_gallery
				nggallery_admin_manage_gallery();
				break;
			case "nggallery-manage-album" :
				include_once (dirname (__FILE__). '/album.php');		// nggallery_admin_manage_album
				nggallery_admin_manage_album();
				break;				
			case "nggallery-options" :
				include_once (dirname (__FILE__). '/settings.php');		// nggallery_admin_options
				nggallery_admin_options();
				break;
			case "nggallery-style" :
				include_once (dirname (__FILE__). '/style.php');		// nggallery_admin_style
				nggallery_admin_style();
				break;
			case "nggallery-setup" :
				include_once (dirname (__FILE__). '/setup.php');		// nggallery_admin_setup
				nggallery_admin_setup();
				break;
			case "nggallery-roles" :
				include_once (dirname (__FILE__). '/roles.php');		// nggallery_admin_roles
				nggallery_admin_roles();
				break;
			case "nggallery-import" :
				include_once (dirname (__FILE__). '/myimport.php');		// nggallery_admin_import
				nggallery_admin_import();
				break;
			case "nggallery-about" :
				include_once (dirname (__FILE__). '/about.php');		// nggallery_admin_about
				nggallery_admin_about();
				break;
			case "nggallery-wpmu" :
				include_once (dirname (__FILE__). '/style.php');		
				include_once (dirname (__FILE__). '/wpmu.php');			// nggallery_wpmu_admin
				nggallery_wpmu_setup();
				break;
			case "nggallery" :
			default :
				if ($wp_version < "2.4")
					include_once (dirname (__FILE__). '/overview.php'); 	// nggallery_admin_overview
				else
					include_once (dirname (__FILE__). '/wp25/overview.php'); 	// nggallery_admin_overview	
				nggallery_admin_overview();
				break;
		}

	} 

	/**************************************************************************/
	function wpmu_site_admin() {
		// Check for site admin
		if (function_exists(is_site_admin))
			if (is_site_admin())
				return true;
				
		return false;
	}
	
	function wpmu_enable_function($value) {
		if (IS_WPMU) {
			$ngg_options = get_site_option('ngg_options');
			return $ngg_options[$value];
		}
		// if this is not WPMU, enable it !
		return true;
	}
?>