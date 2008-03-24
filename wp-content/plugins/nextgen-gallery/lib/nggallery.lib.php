<?php

/**
 * Image PHP class for the WordPress plugin NextGEN Gallery
 * nggallery.lib.php
 * 
 * @author 		Alex Rabe 
 * @copyright 	Copyright 2007-2008
 * 
 */
	  
class nggImage{
	
	/**** Public variables ****/
	
	var $errmsg			=	"";			// Error message to display, if any
    var $error			=	FALSE; 		// Error state
    var $imagePath		=	"";			// URL Path to the image
    var $thumbPath		=	"";			// URL Path to the thumbnail
    var $absPath		=	"";			// Server Path to the image
    var $thumbPrefix	=	"";			// FolderPrefix to the thumbnail
    var $thumbFolder	=	"";			// Foldername to the thumbnail
    var $href			=	"";			// A href link code
	
	/**** Image Data ****/
    var $galleryid		=	0;			// Gallery ID
    var $imageID		=	0;			// Image ID	
    var $filename		=	"";			// Image filename
    var $description	=	"";			// Image description	
    var $alttext		=	"";			// Image alttext	
    var $exclude		=	"";			// Image exclude
    var $thumbcode		=	"";			// Image effect code

	/**** Gallery Data ****/
    var $name			=	"";			// Gallery name
	var $path			=	"";			// Gallery path	
	var $title			=	"";			// Gallery title
	var $pageid			=	0;			// Gallery page ID
	var $previewpic		=	0;			// Gallery preview pic				
	
 	function nggImage($imageID = '0') {
 		
 		global $wpdb;
 		
 		//initialize variables
        $this->imageID              = (int) $imageID;
 		
 		// get image values
 		$imageData = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '$this->imageID' ") or $this->error = true;
		if($this->error == false)
			foreach ($imageData as $key => $value)
				$this->$key = $value ;

		// get gallery values
		$galleryData = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$this->galleryid' ") or $this->error = true;
		if($this->error == false)
			foreach ($galleryData as $key => $value)
				$this->$key = $value ;	
		
		if($this->error == false) {
			// set gallery url
			$this->get_thumbnail_folder($this->path, FALSE);
			$this->imagePath 	= get_option ('siteurl')."/".$this->path."/".$this->filename;
			$this->thumbPath 	= get_option ('siteurl')."/".$this->path.$this->thumbFolder.$this->thumbPrefix.$this->filename;
			$this->absPath 		= WINABSPATH.$this->path."/".$this->filename;
 		}
 	}
	
	/**********************************************************/
	function get_thumbnail_folder($gallerypath, $include_Abspath = TRUE) {
		//TODO:Double coded, see also class nggallery, fix it !
		if (!$include_Abspath) 
			$gallerypath = WINABSPATH.$gallerypath;
			
		if (!file_exists($gallerypath))
			return FALSE;
			
		if (is_dir($gallerypath."/thumbs")) {
			$this->thumbFolder 	= "/thumbs/";
			$this->thumbPrefix 	= "thumbs_";		
			return TRUE;
		}
		// old mygallery check
		if (is_dir($gallerypath."/tumbs")) {
			$this->thumbFolder	= "/tumbs/";
			$this->thumbPrefix 	= "tmb_";
			return TRUE;
		}
		
		if (is_admin()) {
			if (!is_dir($gallerypath."/thumbs")) {
				if ( !wp_mkdir_p($gallerypath."/thumbs") )
					return FALSE;
				$this->thumbFolder	= "/thumbs/";
				$this->thumbPrefix 	= "thumbs_";			
				return TRUE;
			}
		}
		
		return FALSE;

	}
	
	function get_thumbcode($galleryname) {
		// read the option setting
		$ngg_options = get_option('ngg_options');
		
		// get the effect code
		if ($ngg_options['thumbEffect'] != "none") $this->thumbcode = stripslashes($ngg_options['thumbCode']);
		if ($ngg_options['thumbEffect'] == "highslide") $this->thumbcode = str_replace("%GALLERY_NAME%", "'".$galleryname."'", $this->thumbcode);
		else $this->thumbcode = str_replace("%GALLERY_NAME%", $galleryname, $this->thumbcode);
		
		return $this->thumbcode;
	}
	
	function get_href_link() {
		// create the a href link from the picture
		$this->href  = "\n".'<a href="'.$this->imagePath.'" title="'.stripslashes($this->description).'" '.$this->get_thumbcode($this->name).'>'."\n\t";
		$this->href .= '<img alt="'.$this->alttext.'" src="'.$this->imagePath.'"/>'."\n".'</a>'."\n";

		return $this->href;
	}

	function get_href_thumb_link() {
		// create the a href link with the thumbanil
		$this->href  = "\n".'<a href="'.$this->imagePath.'" title="'.stripslashes($this->description).'" '.$this->get_thumbcode($this->name).'>'."\n\t";
		$this->href .= '<img alt="'.$this->alttext.'" src="'.$this->thumbPath.'"/>'."\n".'</a>'."\n";

		return $this->href;
	}
	
	function cached_singlepic_file($width, $height, $mode = "" ) {
		// This function creates a cache for all singlepics to reduce the CPU load
		$ngg_options = get_option('ngg_options');
		
		include_once(NGGALLERY_ABSPATH.'/lib/thumbnail.inc.php');
		
		// cache filename should be unique
		$cachename   	= $this->imageID. "_". $mode . "_". $width. "x". $height ."_". $this->filename;
		$cachefolder 	= WINABSPATH .$ngg_options['gallerypath'] . "cache/";
		$cached_url  	= get_option ('siteurl') ."/". $ngg_options['gallerypath'] . "cache/" . $cachename;
		$cached_file	 = $cachefolder . $cachename;
		
		// check first for the file
		if ( file_exists($cached_file) ) 
			return $cached_url;
		
		// create folder if needed
		if ( !file_exists($cachefolder) )
			if ( !wp_mkdir_p($cachefolder) )
				return false;
		
		// get the filepath on the server
		$filepath = WINABSPATH . "/" . $this->path ."/" . $this->filename;
		$thumb = new ngg_Thumbnail($filepath, TRUE);
		// echo $thumb->errmsg;
		
		if (!$thumb->error) {	
			$thumb->resize($width,$height);
			
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
			// save the new cache picture
			$thumb->save($cached_file,$ngg_options['imgQuality']);
		}
		$thumb->destruct();
		
		// check again for the file
		if ( file_exists($cached_file) ) 
			return $cached_url;
		
		return false;
	}
}

/**
 * Main PHP class for the WordPress plugin NextGEN Gallery
 * nggallery.lib.php
 * 
 * @author 		Alex Rabe 
 * @copyright 	Copyright 2007
 * 
 */

class nggallery {
	
	/**********************************************************/
	// remove page break
	/**********************************************************/
	function ngg_nl2br($string) {
		
		$string = str_replace(array("\r\n", "\r", "\n"), "<br />", $string);
		
		return $string;

	}
	
	/**********************************************************/
	// Show a error messages
	/**********************************************************/
	function show_error($message) {
		echo '<div class="fade error" id="message"><p>'.$message.'</p></div>'."\n";
	}
	
	/**********************************************************/
	// Show a system messages
	/**********************************************************/
	function show_message($message)
	{
		echo '<div class="fade updated" id="message"><p>'.$message.'</p></div>'."\n";
	}

	/**********************************************************/
	// remove some umlauts - deprecated
	/**********************************************************/
	function remove_umlauts($filename) {
	
		$cleanname = str_replace(
		array('ä',   'ö',   'ü',   'Ä',   'Ö',   'Ü',   'ß',   ' '), 
		array('%E4', '%F6', '%FC', '%C4', '%D6', '%DC', '%DF', '%20'),
		utf8_decode($filename)
		);
		
		return $cleanname;
	}
	
	/**********************************************************/
	// get the thumbnail url to the image
	//TODO:Combine in one class
	/**********************************************************/
	function get_thumbnail_url($imageID){
		// get the complete url to the thumbnail
		global $wpdb;
		
		// get gallery values
		$galleryID = $wpdb->get_var("SELECT galleryid FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
		$fileName = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
		$picturepath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$galleryID' ");
	
		// set gallery url
		$folder_url 	= get_option ('siteurl')."/".$picturepath.nggallery::get_thumbnail_folder($picturepath, FALSE);
		$thumb_prefix   = nggallery::get_thumbnail_prefix($picturepath, FALSE);
		$thumbnailURL	= $folder_url.$thumb_prefix.$fileName;
		
		return $thumbnailURL;
	}
	
	/**********************************************************/
	// get the complete url to the image
	/**********************************************************/
	function get_image_url($imageID){
		
		global $wpdb;
		
		// get gallery values
		$galleryID = $wpdb->get_var("SELECT galleryid FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
		$fileName = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
		$picturepath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$galleryID' ");
	
		// set gallery url
		$imageURL 	= get_option ('siteurl')."/".$picturepath."/".$fileName;
		
		return $imageURL;	
	}

	/**********************************************************/
	// get the thumbnail folder
	/**********************************************************/
	function get_thumbnail_folder($gallerypath, $include_Abspath = TRUE) {
		//required for myGallery import :-)
		
		if (!$include_Abspath) $gallerypath = WINABSPATH.$gallerypath;
		if (!file_exists($gallerypath))
			return FALSE;
		if (is_dir($gallerypath."/thumbs")) return "/thumbs/";
		// old mygallery check
		if (is_dir($gallerypath."/tumbs")) return "/tumbs/";
		
		if (is_admin()) {
			if (!is_dir($gallerypath."/thumbs")) {
				if ( !wp_mkdir_p($gallerypath."/thumbs") ) {
					if (SAFE_MODE)
						nggAdmin::check_safemode($gallerypath."/thumbs");	
					else
						nggallery::show_error(__('Unable to create directory ', 'nggallery').$gallerypath.'/thumbs !');
					return FALSE;
				}
				return "/thumbs/";
			}
		}
		
		return FALSE;
		
	}
	
	/**********************************************************/
	// get the thumbnail prefix
	/**********************************************************/
	function get_thumbnail_prefix($gallerypath, $include_Abspath = TRUE) {
		//required for myGallery import :-)
	
		if (!$include_Abspath) $gallerypath = WINABSPATH.$gallerypath;
		if (is_dir($gallerypath."/thumbs")) return "thumbs_";
		// old mygallery check
		if (is_dir($gallerypath."/tumbs")) return "tmb_";
	
		return FALSE;
		
	}

	/**********************************************************/
	// get the effect code
	/**********************************************************/
	function get_thumbcode($groupname) {

		$ngg_options = get_option('ngg_options');
		
		// get the effect code
		if ($ngg_options['thumbEffect'] != "none") $thumbcode = stripslashes($ngg_options['thumbCode']);
		if ($ngg_options['thumbEffect'] == "highslide") $thumbcode = str_replace("%GALLERY_NAME%", "'".$groupname."'", $thumbcode);
		else $thumbcode = str_replace("%GALLERY_NAME%", $groupname, $thumbcode);
	
		return $thumbcode;
		
	}
	
	/**********************************************************/
	// create the complete navigation
	/**********************************************************/
	function create_navigation($page, $totalElement, $maxElement = 0) {
		global $nggRewrite;
		
	 	$navigation = "";
	 	
		 	if ($maxElement > 0) {
			$total = $totalElement;
					
			// create navigation	
			if ( $total > $maxElement ) {
				$total_pages = ceil( $total / $maxElement );
				$r = '';
				if ( 1 < $page ) {
					$args['nggpage'] = ( 1 == $page - 1 ) ? FALSE : $page - 1;
					$r .=  '<a class="prev" href="'. $nggRewrite->get_permalink( $args ) . '">&#9668;</a>';
				}
				if ( ( $total_pages = ceil( $total / $maxElement ) ) > 1 ) {
					for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) {
						if ( $page == $page_num ) {
							$r .=  '<span>' . $page_num . '</span>';
						} else {
							$p = false;
							if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) {
								$args['nggpage'] = ( 1 == $page_num ) ? FALSE : $page_num;
								$r .= '<a class="page-numbers" href="' . $nggRewrite->get_permalink( $args ) . '">' . ( $page_num ) . '</a>';
								$in = true;
							} elseif ( $in == true ) {
								$r .= '<span>...</span>';
								$in = false;
							}
						}
					}
				}
				if ( ( $page ) * $maxElement < $total || -1 == $total ) {
					$args['nggpage'] = $page + 1;
					$r .=  '<a class="next" href="' . $nggRewrite->get_permalink ( $args ) . '">&#9658;</a>';
				}
				
				$navigation = "<div class='ngg-navigation'>$r</div>";
			} else {
				$navigation = "<div class='ngg-clear'></div>"."\n";
			}
		}
		
		return $navigation;
	}
	
 /**
   * nggallery::get_option() - get the options and overwrite them with custom meta settings
   *
   * @param string $key
   * @return array $options
   */
	function get_option($key) {
		// get first the options from the database 
		$options = get_option($key);
		// Get all key/value data for the current post. 
		$meta_array = get_post_custom();
		// Ensure that this is a array
		if (!is_array($meta_array))
			$meta_array = array($meta_array);
		// assign meta key to db setting key
		$meta_tags = array(
			'string' => array(
			'ngg_gal_ShowOrder' 		=> 'galShowOrder',
			'ngg_gal_Sort' 				=> 'galSort',
			'ngg_gal_SortDirection' 	=> 'galSortDir',
			'ngg_gal_ShowDescription'	=> 'galShowDesc',
			'ngg_ir_Audio' 				=> 'irAudio',
			'ngg_ir_Overstretch'		=> 'irOverstretch',
			'ngg_ir_Transition'			=> 'irTransition',
			'ngg_ir_Backcolor' 			=> 'irBackcolor',
			'ngg_ir_Frontcolor' 		=> 'irFrontcolor',
			'ngg_ir_Lightcolor' 		=> 'irLightcolor'
			),

			'int' => array(
			'ngg_gal_Images' 			=> 'galImages',
			'ngg_gal_Sort' 				=> 'galSort',
			'ngg_ir_Width' 				=> 'irWidth',
			'ngg_ir_Height' 			=> 'irHeight',
			'ngg_ir_Rotatetime' 		=> 'irRotatetime'
			),

			'bool' => array(
			'ngg_gal_ShowSlide'			=> 'galShowSlide',
			'ngg_gal_ImgageBrowser' 	=> 'galImgBrowser',
			'ngg_ir_Shuffle' 			=> 'irShuffle',
			'ngg_ir_LinkFromDisplay' 	=> 'irLinkfromdisplay',
			'ngg_ir_ShowNavigation'		=> 'irShownavigation',
			'ngg_ir_ShowWatermark' 		=> 'irWatermark',
			'ngg_ir_Overstretch'		=> 'irOverstretch',
			'ngg_ir_Kenburns' 			=> 'irKenburns'
			)
		);
		
		foreach ($meta_tags as $typ => $meta_keys){
			foreach ($meta_keys as $key => $db_value){
				// if the kex exist overwrite it with the custom field
				if (array_key_exists($key, $meta_array)){
					switch ($typ) {
						case "string":
							$options[$db_value] = (string) attribute_escape($meta_array[$key][0]);
							break;
						case "int":
							$options[$db_value] = (int) $meta_array[$key][0];
							break;
						case "bool":
							$options[$db_value] = (bool) $meta_array[$key][0];
							break;	
					}
				}
			}
		}
		
		return $options;
	}
}

/**
 * Tag PHP class for the WordPress plugin NextGEN Gallery
 * nggallery.lib.php
 * 
 * @author 		Alex Rabe 
 * @copyright 	Copyright 2007
 * 
 */
 
class ngg_Tags {
	
	var $sluglist = array ();
	var $img_slugs = array ();
	var $img_tags = array ();
	var $taglist = "";
	
	function ngg_Tags() {
		return $this->__construct();
	}

	function __construct() {
		// First get all slugs in a array
		$this->get_sluglist();
	}
	
	function __destruct() {
		// Clean varlist
		unset ($this->sluglist, $this->img_slugs, $this->img_tags, $this->taglist );
	}

	function get_sluglist() {
		// read the slugs and cache the array
		global $wpdb;
		
		$slugarray = $wpdb->get_results("SELECT id, slug FROM $wpdb->nggtags");
		if (is_array($slugarray)){
			foreach($slugarray as $element)
				$this->sluglist[$element->id] = $element->slug;
		}
		
		return $this->sluglist;
	}
	
	function get_tags_from_image($id) {
		// read the tags and slugs
		global $wpdb;
		
		$this->taglist = "";
		$this->img_slugs = $this->img_tags = array();
	
		$tagarray = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggpic2tags AS t INNER JOIN $wpdb->nggtags AS tt ON t.tagid = tt.id WHERE t.picid = '$id' ORDER BY tt.slug ASC ");
	
		if (is_array($tagarray)){
			foreach($tagarray as $element) {
				$this->img_slugs[$element->id] = $element->slug;
				$this->img_tags[$element->id] = $element->name;
			}
			$this->taglist = implode(", ", $this->img_tags);
		}
		
		return $this->taglist;
	}
	
	function add_tag($tag) {
		// add a tag if not exist and return the id
		global $wpdb;

		$tagid = false;
		
		$tag = trim($tag);
		$slug = sanitize_title($tag);

		// look for tag in the cached list and get id
		$tagid = array_search($slug, $this->sluglist);
		
		// if tag is not found add to database
		if (!$tagid) {
			if (!empty ($tag)) {
				$wpdb->query("INSERT INTO $wpdb->nggtags (name, slug) VALUES ('$tag', '$slug')");
				$tagid = (int) $wpdb->insert_id;
				// Update also sluglist
				if ($tagid)	$this->sluglist[$tagid] = $slug;
			}
		}
		
		return $tagid;
	}
	
	function add_relationship($pic_id = 0, $tag_id = 0) {
		// add the relation between image and tag
		global $wpdb;

		if (($pic_id != 0) && ($tag_id != 0)){
			// checkfor duplicate first
			$exist = $wpdb->get_var("SELECT picid FROM $wpdb->nggpic2tags WHERE picid = '$pic_id' AND tagid = '$tag_id' ");
			if (!$exist)
				$wpdb->query("INSERT INTO $wpdb->nggpic2tags (picid, tagid) VALUES ('$pic_id', '$tag_id')");
		}
	}

	function remove_relationship($pic_id = 0, $slugarray, $cached = false) {
		// remove the relation between image and tag
		global $wpdb;

		if (!is_array($slugarray))
			$slugarray = array($slugarray);
		
		// get all tags if we didnt chaed them already
		if (!$cached)
			$this->get_tags_from_image($pic_id);
		
		$delete_ids = array();
			
		foreach ($slugarray as $slug) {
			// look for tag in the cached list and get ids
			// require frst get_tags_from_image()
			$tagid = array_search($slug, $this->sluglist);
			if ($tagid)
				$delete_ids[] = $tagid;
		}

		$delete_list = "'" . implode("', '", $delete_ids) . "'";
		$wpdb->query("DELETE FROM $wpdb->nggpic2tags WHERE picid = '$pic_id' AND tagid IN ($delete_list)");

	}

	function remove_unused_tags() {
		// remove tags which are not longer used
		global $wpdb;
		
		// get all used tags
		$tagarray = $wpdb->get_results("SELECT tt.* FROM $wpdb->nggpic2tags AS t INNER JOIN $wpdb->nggtags AS tt ON t.tagid = tt.id ");
		if (is_array($tagarray)){
			// remove used items from sluglist
			foreach($tagarray as $element)
				unset ($this->sluglist[$element->id]);
			// remove now all unused tags	
			$delete_ids = array();
			foreach($this->sluglist as $key=>$value)
				$delete_ids[] = $key;
			$delete_list = "'" . implode("', '", $delete_ids) . "'";
			$wpdb->query("DELETE FROM $wpdb->nggtags WHERE id IN ($delete_list)");		
		}
	}
	
	function get_images($taglist) {
		// return the images based on the tag
		global $wpdb;
		
		// extract it into a array
		$taglist = explode(",", $taglist);
		
		if (!is_array($taglist))
			$taglist = array($taglist);
	
		$taglist = array_map('trim', $taglist);
		$new_slugarray = array_map('sanitize_title', $taglist);
	
		$sluglist   = "'" . implode("', '", $new_slugarray) . "'";
			
		$picarray = array();
		
		// first get all picture with this tag //
		$picids = $wpdb->get_col("SELECT t.picid FROM $wpdb->nggpic2tags AS t INNER JOIN $wpdb->nggtags AS tt ON t.tagid = tt.id WHERE tt.slug IN ($sluglist) ORDER BY t.picid ASC ");

		if (is_array($picids)){
			// now get all pictures
			$piclist = "'" . implode("', '", $picids) . "'";
			//TODO: Use thumbnail sort order ? v0.80 Use now random function
			//$picarray = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggpictures AS t INNER JOIN $wpdb->nggallery AS tt ON t.galleryid = tt.gid WHERE t.pid IN ($piclist) ORDER BY t.pid ASC ");
			$picarray = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggpictures AS t INNER JOIN $wpdb->nggallery AS tt ON t.galleryid = tt.gid WHERE t.pid IN ($piclist) ORDER BY rand() ");			
		}
		
		return $picarray;
	}
	
	function get_album_images($taglist) {
		// return one images based on the tag
		// required for a tag based album overview
		global $wpdb;
		
		// extract it into a array
		$taglist = explode(",", $taglist);
		
		if (!is_array($taglist))
			$taglist = array($taglist);
	
		$taglist = array_map('trim', $taglist);
		$new_slugarray = array_map('sanitize_title', $taglist);
		
		$picarray = array();
		
		foreach($new_slugarray as $slug) {
			// get random picture of tag
			$picture = $wpdb->get_row("SELECT t.picid, t.tagid, tt.name, tt.slug FROM $wpdb->nggpic2tags AS t INNER JOIN $wpdb->nggtags AS tt ON t.tagid = tt.id WHERE tt.slug = '$slug' ORDER BY rand() limit 1 ");	
			if ($picture) {
				$picdata = $wpdb->get_row("SELECT t.*, tt.* FROM $wpdb->nggpictures AS t INNER JOIN $wpdb->nggallery AS tt ON t.galleryid = tt.gid WHERE t.pid = $picture->picid");		
				$picarray[] = array_merge((array)$picdata, (array)$picture);
			}
		}

		return $picarray;

	}

}

/**
 * nggRewrite - First version of Rewrite Rules
 *
 * sorry wp-guys I didn't understand this at all. 
 * I tried it a couple of hours : this is the only pooooor result
 *
 * @package NextGEN Gallery
 * @author Alex Rabe
 * @copyright 2008
 *	
 */
class nggRewrite {

	// default value
	var $slug	=	"nggallery";	

	function nggRewrite() {
		
		// read the option setting
		$this->options = get_option('ngg_options');
		
		// get later from the options
		$this->slug = "nggallery";

		/*WARNING: Do nothook rewrite rule regentation on the init hook for anything other than dev. */
		//add_action('init',array(&$this, 'flush'));
		
		add_filter('query_vars', array(&$this, 'add_queryvars') );
		if ($this->options['usePermalinks'])
		add_action('generate_rewrite_rules', array(&$this, 'RewriteRules'));
		   
	} // end of initialization

	function get_permalink( $args ) {
		global $wp_rewrite, $wp_query;

		if ($wp_rewrite->using_permalinks() && $this->options['usePermalinks'] ) {
			
			$post = &get_post(get_the_ID());

			// $_GET from wp_query
			$album = get_query_var('album');
			if ( !empty( $album ) )
				$args ['album'] = $album;
			$gallery = get_query_var('gallery');
			if ( !empty( $gallery ) )
				$args ['gallery'] = $gallery;
			$gallerytag = get_query_var('gallerytag');
			if ( !empty( $gallerytag ) )
				$args ['gallerytag'] = $gallerytag;
			
			/* urlconstructor =  slug | type | tags | [nav] | [show]
				type : 	page | post
			    tags : 	album, gallery 	-> /album-([0-9]+)/gallery-([0-9]+)/
						pid 			-> /page/([0-9]+)/
						gallerytag		-> /tags/([^/]+)/
				nav	 : 	nggpage			-> /page-([0-9]+)/
				show : 	show=slide		-> /slideshow/
						show=gallery	-> /images/	
			*/

			// 1. Blog url + main slug
			$url = get_option('home'). "/". $this->slug;
			// 2. Post or page ?
			if ( $post->post_type == 'page' )
				$url .= "/page-".$post->ID; // Pagnename is nicer but how to handle /parent/pagename ? Confused...
			else
				$url .= "/post/".$post->post_name;
			// 3. Album, pid or tags
			if  (isset ($args['album']) && isset ($args['gallery']) )
				$url .= "/album-".$args['album']."/gallery-".$args['gallery'];
			if  (isset ($args['gallerytag']))
				$url .= "/tags/".$args['gallerytag'];
			if  (isset ($args['pid']))
				$url .= "/page/".$args['pid'];				
			// 4. Navigation
			if  (isset ($args['nggpage']) && ($args['nggpage']) )
				$url .= "/page-".$args['nggpage'];
			// 5. Show images or Slideshow
			if  (isset ($args['show']))
				$url .= ( $args['show'] == 'slide' ) ? "/slideshow" : "/images";

			return $url;
	
		} else {
			
			// we need to add the page/post id at the start_page otherwise we don't know which gallery is clicked
			if (is_home())
				$args['pageid'] = get_the_ID();
			
			// taken from is_frontpage plugin, required for static homepage
			$show_on_front = get_option('show_on_front');
			$page_on_front = get_option('page_on_front');
		
			if ( ($show_on_front == 'page') && ($page_on_front == get_the_ID()) )
				$args['page_id'] = get_the_ID();

			$query = htmlspecialchars(add_query_arg( $args));
				 
			return $query;
		}
	}

	// The permalinks needs to be flushed after activation
	function flush() { 
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	// add some more vars to the big wp_query
	function add_queryvars( $query_vars ){
		
	    $query_vars[] = 'pid';
	    $query_vars[] = 'pageid';
	    $query_vars[] = 'nggpage';
	    $query_vars[] = 'gallery';
	    $query_vars[] = 'album';
	    $query_vars[] = 'gallerytag';
	    $query_vars[] = 'show';

	    return $query_vars;
	}
	
	function RewriteRules($wp_rewrite) {
	
		$rewrite_rules = array
		  (
		  	// rewrite rules for pages
			$this->slug.'/page-([0-9]+)/?$' => 'index.php?page_id=$matches[1]',
			$this->slug.'/page-([0-9]+)/page-([0-9]+)/?$' => 'index.php?page_id=$matches[1]&nggpage=$matches[2]',
			$this->slug.'/page-([0-9]+)/page/([0-9]+)/?$' => 'index.php?page_id=$matches[1]&pid=$matches[2]',
			$this->slug.'/page-([0-9]+)/slideshow/?$' => 'index.php?page_id=$matches[1]&show=slide',
		    $this->slug.'/page-([0-9]+)/images/?$' => 'index.php?page_id=$matches[1]&show=gallery',
			$this->slug.'/page-([0-9]+)/tags/([^/]+)/?$' => 'index.php?page_id=$matches[1]&gallerytag=$matches[2]',
			$this->slug.'/page-([0-9]+)/tags/([^/]+)/page-([0-9]+)/?$' => 'index.php?page_id=$matches[1]&gallerytag=$matches[2]&nggpage=$matches[3]',
		    $this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]',
			$this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/slideshow/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]&show=slide',
		    $this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/images/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]&show=gallery',
			$this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/page/([0-9]+)/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]&pid=$matches[4]',
			$this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/page-([0-9]+)/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]&nggpage=$matches[4]',
		    $this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/page-([0-9]+)/slideshow/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]&nggpage=$matches[4]&show=slide',
		    $this->slug.'/page-([0-9]+)/album-([0-9]+)/gallery-([0-9]+)/page-([0-9]+)/images/?$' => 'index.php?page_id=$matches[1]&album=$matches[2]&gallery=$matches[3]&nggpage=$matches[4]&show=gallery',
			// rewrite rules for posts
			$this->slug.'/post/([^/]+)/?$' => 'index.php?name=$matches[1]',
			$this->slug.'/post/([^/]+)/page-([0-9]+)/?$' => 'index.php?name=$matches[1]&nggpage=$matches[2]',
			$this->slug.'/post/([^/]+)/page/([0-9]+)/?$' => 'index.php?name=$matches[1]&pid=$matches[2]',
			$this->slug.'/post/([^/]+)/slideshow/?$' => 'index.php?name=$matches[1]&show=slide',
		    $this->slug.'/post/([^/]+)/images/?$' => 'index.php?name=$matches[1]&show=gallery',
			$this->slug.'/post/([^/]+)/tags/([^/]+)/?$' => 'index.php?name=$matches[1]&gallerytag=$matches[2]',
			$this->slug.'/post/([^/]+)/tags/([^/]+)/page-([0-9]+)/?$' => 'index.php?name=$matches[1]&gallerytag=$matches[2]&nggpage=$matches[3]',
		    $this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]',
			$this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/slideshow/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]&show=slide',
		    $this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/images/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]&show=gallery',
			$this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/page/([0-9]+)/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]&pid=$matches[4]',
			$this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/page-([0-9]+)/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]&nggpage=$matches[4]',
		    $this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/page-([0-9]+)/slideshow/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]&nggpage=$matches[4]&show=slide',
		    $this->slug.'/post/([^/]+)/album-([0-9]+)/gallery-([0-9]+)/page-([0-9]+)/images/?$' => 'index.php?name=$matches[1]&album=$matches[2]&gallery=$matches[3]&nggpage=$matches[4]&show=gallery',
		  );

		$wp_rewrite->rules = $wp_rewrite->rules + $rewrite_rules;
		
	}
	
}  // of nggRewrite CLASS

?>