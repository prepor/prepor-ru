<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function nggallery_install () {
	
   	global $wpdb , $wp_roles, $wp_version;
   	
	// Check for capability
	if ( !current_user_can('activate_plugins') ) 
		return;
	
	// Set the capabilities for the administrator
	$role = get_role('administrator');
	// We need this role, no other chance
	if ( empty($role) ) {
		update_option( "ngg_init_check", __('Sorry, NextGEN Gallery works only with a role called administrator',"nggallery") );
		return;
	}
	$role->add_cap('NextGEN Gallery overview');
	$role->add_cap('NextGEN Use TinyMCE');
	$role->add_cap('NextGEN Upload images');
	$role->add_cap('NextGEN Manage gallery');
	$role->add_cap('NextGEN Edit album');
	$role->add_cap('NextGEN Change style');
	$role->add_cap('NextGEN Change options');
	
	// upgrade function changed in WordPress 2.3	
	if (version_compare($wp_version, '2.3', '>='))		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	else
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	
	// add charset & collate like wp core
	$charset_collate = '';

	if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}
		
   	$nggpictures					= $wpdb->prefix . 'ngg_pictures';
	$nggallery						= $wpdb->prefix . 'ngg_gallery';
	$nggalbum						= $wpdb->prefix . 'ngg_album';
	$nggtags						= $wpdb->prefix . 'ngg_tags';
	$nggpic2tags					= $wpdb->prefix . 'ngg_pic2tags';
   
	if($wpdb->get_var("show tables like '$nggpictures'") != $nggpictures) {
      
		$sql = "CREATE TABLE " . $nggpictures . " (
		pid BIGINT(20) NOT NULL AUTO_INCREMENT ,
		galleryid BIGINT(20) DEFAULT '0' NOT NULL ,
		filename VARCHAR(255) NOT NULL ,
		description MEDIUMTEXT NULL ,
		alttext MEDIUMTEXT NULL ,
		exclude TINYINT NULL DEFAULT '0' ,
		sortorder BIGINT(20) DEFAULT '0' NOT NULL ,
		PRIMARY KEY pid (pid)
		) $charset_collate;";
	
      dbDelta($sql);
 
 		ngg_default_options();
		add_option("ngg_db_version", NGG_DBVERSION);
   }

	if($wpdb->get_var("show tables like '$nggallery'") != $nggallery) {
      
		$sql = "CREATE TABLE " . $nggallery . " (
		gid BIGINT(20) NOT NULL AUTO_INCREMENT ,
		name VARCHAR(255) NOT NULL ,
		path MEDIUMTEXT NULL ,
		title MEDIUMTEXT NULL ,
		galdesc MEDIUMTEXT NULL ,
		pageid BIGINT(20) NULL DEFAULT '0' ,
		previewpic BIGINT(20) NULL DEFAULT '0' ,
		PRIMARY KEY gid (gid)
		) $charset_collate;";
	
      dbDelta($sql);
   }

	if($wpdb->get_var("show tables like '$nggalbum'") != $nggalbum) {
      
		$sql = "CREATE TABLE " . $nggalbum . " (
		id BIGINT(20) NOT NULL AUTO_INCREMENT ,
		name VARCHAR(255) NOT NULL ,
		sortorder LONGTEXT NOT NULL,
		PRIMARY KEY id (id)
		) $charset_collate;";
	
      dbDelta($sql);
    }
    
    // new since version 0.70
    if($wpdb->get_var("show tables like '$nggtags'") != $nggtags) {
      
		$sql = "CREATE TABLE " . $nggtags . " (
		id BIGINT(20) NOT NULL AUTO_INCREMENT ,
		name VARCHAR(55) NOT NULL ,
		slug VARCHAR(200) NOT NULL,
		PRIMARY KEY id (id),
		UNIQUE KEY slug (slug)
		) $charset_collate;";
	
      dbDelta($sql);
    }
    
    if($wpdb->get_var("show tables like '$nggpic2tags'") != $nggpic2tags) {
      
		$sql = "CREATE TABLE " . $nggpic2tags . " (
		 picid BIGINT(20) NOT NULL DEFAULT 0,
		 tagid BIGINT(20) NOT NULL DEFAULT 0,
		 PRIMARY KEY  (picid, tagid),
		 KEY tagid (tagid)
		) $charset_collate;";
	
      dbDelta($sql);
    }

	// check one table again, to be sure
	if($wpdb->get_var("show tables like '$nggpictures'")!= $nggpictures) {
		update_option( "ngg_init_check", __('NextGEN Gallery : Tables could not created, please check your database settings',"nggallery") );
		return;
	}

}
	
// update routine
function ngg_upgrade() {
	
	global $wpdb;
	
	$nggpictures					= $wpdb->prefix . 'ngg_pictures';
	$nggallery						= $wpdb->prefix . 'ngg_gallery';

	// Be sure that the tables exist
	if($wpdb->get_var("show tables like '$nggpictures'") == $nggpictures) {

		$installed_ver = get_option( "ngg_db_version" );

		// v0.33 -> v.071
		if (version_compare($installed_ver, '0.71', '<')) {
			$wpdb->query("ALTER TABLE ".$nggpictures." CHANGE pid pid BIGINT(20) NOT NULL AUTO_INCREMENT ");
			$wpdb->query("ALTER TABLE ".$nggpictures." CHANGE galleryid galleryid BIGINT(20) NOT NULL ");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE gid gid BIGINT(20) NOT NULL AUTO_INCREMENT ");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE pageid pageid BIGINT(20) NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE previewpic previewpic BIGINT(20) NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE gid gid BIGINT(20) NOT NULL AUTO_INCREMENT ");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE description galdesc MEDIUMTEXT NULL");
		}
		// v0.71 -> v0.84
		if (version_compare($installed_ver, '0.84', '<')) {
			$wpdb->query("ALTER TABLE ".$nggpictures." ADD sortorder BIGINT(20) DEFAULT '0' NOT NULL AFTER exclude");
		}

		update_option( "ngg_db_version", NGG_DBVERSION );
	}
}

function ngg_default_options() {
	
	global $blog_id;

	$ngg_options['gallerypath']			= "wp-content/gallery/";  		// set default path to the gallery
	$ngg_options['scanfolder']			= false;						// search for new images  (not used)
	$ngg_options['deleteImg']			= true;							// delete Images
	$ngg_options['swfUpload']			= false;						// activate the batch upload
	$ngg_options['usePermalinks']		= false;						// use permalinks for parameters
	
	// Tags / categories
	$ngg_options['activateTags']		= false;						// append related images
	$ngg_options['appendType']			= "category";					// look for category or tags
	$ngg_options['maxImages']			= 7;  							// number of images toshow
	
	// Thumbnail Settings
	$ngg_options['thumbwidth']			= 100;  						// Thumb Width
	$ngg_options['thumbheight']			= 75;  							// Thumb height
	$ngg_options['thumbfix']			= true;							// Fix the dimension
	$ngg_options['thumbcrop']			= false;						// Crop square thumbnail
	$ngg_options['thumbquality']		= 100;  						// Thumb Quality
	$ngg_options['thumbResampleMode']	= 3;  							// Resample speed value 1 - 5 
		
	// Image Settings
	$ngg_options['imgResize']			= false;						// Activate resize (not used)
	$ngg_options['imgWidth']			= 800;  						// Image Width
	$ngg_options['imgHeight']			= 600;  						// Image height
	$ngg_options['imgQuality']			= 85;							// Image Quality
	$ngg_options['imgResampleMode']		= 3;  							// Resample speed value 1 - 5
	$ngg_options['imgCacheSinglePic']	= false;						// cached the singlepic	
	
	// Gallery Settings
	$ngg_options['galImages']			= "20";		  					// Number Of images per page
	$ngg_options['galShowSlide']		= true;							// Show slideshow
	$ngg_options['galTextSlide']		= __('[Show as slideshow]','nggallery'); // Text for slideshow
	$ngg_options['galTextGallery']		= __('[Show picture list]','nggallery'); // Text for gallery
	$ngg_options['galShowOrder']		= "gallery";					// Show order
	$ngg_options['galSort']				= "sortorder";					// Sort order
	$ngg_options['galSortDir']			= "ASC";						// Sort direction
	$ngg_options['galUsejQuery']   		= false;						// use the jQuery plugin
	$ngg_options['galNoPages']   		= true;							// use no subpages for gallery
	$ngg_options['galShowDesc']			= "none";						// Show a text below the thumbnail
	$ngg_options['galImgBrowser']   	= false;						// Show ImageBrowser, instead effect

	// Thumbnail Effect
	$ngg_options['thumbEffect']			= "thickbox";  					// select effect
	$ngg_options['thumbCode']			= "class=\"thickbox\" rel=\"%GALLERY_NAME%\""; 
	$ngg_options['thickboxImage']		= "loadingAnimationv3.gif";  	// thickbox Loading Image

	// Watermark settings
	$ngg_options['wmPos']				= "botRight";					// Postion
	$ngg_options['wmXpos']				= 5;  							// X Pos
	$ngg_options['wmYpos']				= 5;  							// Y Pos
	$ngg_options['wmType']				= "text";  						// Type : 'image' / 'text'
	$ngg_options['wmPath']				= "";  							// Path to image
	$ngg_options['wmFont']				= "arial.ttf";  				// Font type
	$ngg_options['wmSize']				= 10;  							// Font Size
	$ngg_options['wmText']				= get_option('blogname');		// Text
	$ngg_options['wmColor']				= "000000";  					// Font Color
	$ngg_options['wmOpaque']			= "100";  						// Font Opaque

	// Image Rotator settings
	$ngg_options['irXHTMLvalid']		= false;
	$ngg_options['irAudio']				= "";
	$ngg_options['irWidth']				= 320; 
	$ngg_options['irHeight']			= 240;
 	$ngg_options['irShuffle']			= true;
 	$ngg_options['irLinkfromdisplay']	= true;
	$ngg_options['irShownavigation']	= false;
	$ngg_options['irShowicons']			= false;
	$ngg_options['irWatermark']			= false;
	$ngg_options['irOverstretch']		= "true";
	$ngg_options['irRotatetime']		= 10;
	$ngg_options['irTransition']		= "random";
	$ngg_options['irKenburns']			= false;
	$ngg_options['irBackcolor']			= "000000";
	$ngg_options['irFrontcolor']		= "FFFFFF";
	$ngg_options['irLightcolor']		= "CC0000";
	$ngg_options['irScreencolor']		= "000000";		

	// CSS Style
	$ngg_options['activateCSS']			= true;							// activate the CSS file
	$ngg_options['CSSfile']				= "nggallery.css";  			// set default css filename
	
	// special overrides for WPMU	
	if (IS_WPMU) {
		// get the site options
		$ngg_wpmu_options=get_site_option('ngg_options');
		
		// get the default value during installation
		if (!is_array($ngg_wpmu_options)) {
			$ngg_wpmu_options['gallerypath'] = "wp-content/blogs.dir/%BLOG_ID%/files/";
			$ngg_wpmu_options['wpmuCSSfile'] = "nggallery.css";
			update_site_option('ngg_options', $ngg_wpmu_options);
		}
		
		$ngg_options['gallerypath']  		= str_replace("%BLOG_ID%", $blog_id , $ngg_wpmu_options['gallerypath']);
		$ngg_options['CSSfile']				= $ngg_wpmu_options['wpmuCSSfile'];
		$ngg_options['imgCacheSinglePic']	= true; 					// under WPMU this should be enabled
	} 
	
	update_option('ngg_options', $ngg_options);

}

?>