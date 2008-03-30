<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function searchnggallerytags($content) {

	global $wpdb;
	
	//TODO:Refactor this to a class
	$ngg_options = nggallery::get_option('ngg_options');
	
	if ( stristr( $content, '[singlepic' )) {
		
		$search = "@\[singlepic=(\d+)(|,\d+|,)(|,\d+|,)(|,watermark|,web20|,)(|,right|,center|,left|,)\]@i";
		
		if	(preg_match_all($search, $content, $matches)) {
			
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					// check for correct id
					$result = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$v0' ");
					if($result){
						$search = $matches[0][$key];
						$replace= nggSinglePicture($v0,$matches[2][$key],$matches[3][$key],$matches[4][$key],$matches[5][$key]);
						$content= str_replace ($search, $replace, $content);
					}
				}	
			}
		}
	}// end singelpic

	if ( stristr( $content, '[album' )) {
		
		$search = "@(?:<p>)*\s*\[album\s*=\s*(\w+|^\+)(|,extend|,compact)\]\s*(?:</p>)*@i";
		
		if	(preg_match_all($search, $content, $matches)) {
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					// check for album id
					$albumID = $wpdb->get_var("SELECT id FROM $wpdb->nggalbum WHERE id = '$v0' ");
					if(!$albumID) $albumID = $wpdb->get_var("SELECT id FROM $wpdb->nggalbum WHERE name = '$v0' ");
					if($albumID) {
						$search = $matches[0][$key];
						$replace= nggShowAlbum($albumID,$matches[2][$key]);
						$content= str_replace ($search, $replace, $content);
					}
				}	
			}
		}
	}// end album
	
	if ( stristr( $content, '[gallery' )) {
	
		$search = "@(?:<p>)*\s*\[gallery\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
		
		if	(preg_match_all($search, $content, $matches)) {
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					// check for gallery id
					$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$v0' ");
					if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$v0' ");
					if($galleryID) {
						$search = $matches[0][$key];
						$replace= nggShowGallery($galleryID);
						$content= str_replace ($search, $replace, $content);
					}
				}	
			}
		}
	}// end gallery
	
	if ( stristr( $content, '[imagebrowser' )) {

		$search = "@(?:<p>)*\s*\[imagebrowser\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
		
		if	(preg_match_all($search, $content, $matches)) {
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					// check for gallery id
					$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$v0' ");
					if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$v0' ");
					if($galleryID) {
						$search = $matches[0][$key];
						$replace= nggShowImageBrowser($galleryID);
						$content= str_replace ($search, $replace, $content);
					}
				}	
			}
		}
	}// end gallery
	
	if ( stristr( $content, '[slideshow' )) {
	
		$search = "@(?:<p>)*\s*\[slideshow\s*=\s*(\w+|^\+)(|,(\d+)|,)(|,(\d+))\]\s*(?:</p>)*@i";
	
		if	(preg_match_all($search, $content, $matches)) {
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					// check for gallery id
					$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$v0' ");
					if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$v0' ");
					if( $galleryID || $v0 == 0 ) {
						$search = $matches[0][$key];
						// get the size if they are set
				 		$irWidth  =  $matches[3][$key]; 
						$irHeight =  $matches[5][$key];
						$replace= nggShowSlideshow($galleryID,$irWidth,$irHeight);
						$content= str_replace ($search, $replace, $content);
					}
				}	
			}
		}
	}// end slideshow
	
	if ( stristr( $content, '[tags' )) {
	
		$search = "@(?:<p>)*\s*\[tags\s*=\s*(.*?)\s*\]\s*(?:</p>)*@i";
	
		if	(preg_match_all($search, $content, $matches)) {
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					$search = $matches[0][$key];
					$replace= nggShowGalleryTags($v0);
					$content= str_replace ($search, $replace, $content);
				}	
			}
		}
	}// end gallery tags 

	if ( stristr( $content, '[albumtags' )) {

		$search = "@(?:<p>)*\s*\[albumtags\s*=\s*(.*?)\s*\]\s*(?:</p>)*@i";
	
		if	(preg_match_all($search, $content, $matches)) {
			if (is_array($matches)) {
				foreach ($matches[1] as $key =>$v0) {
					$search = $matches[0][$key];
					$replace= nggShowAlbumTags($v0);
					$content= str_replace ($search, $replace, $content);
				}	
			}
		}
	}// end album tags 
	
	// attach related images based on category or tags
	if ($ngg_options['activateTags']) 
		$content .= nggShowRelatedImages();
	
	return $content;
}// end search content

/**********************************************************/
function nggShowSlideshow($galleryID,$irWidth,$irHeight) {
	
	global $wpdb;
	
	$ngg_options = nggallery::get_option('ngg_options');
	
	//TODO: bad intermediate solution until refactor to class
	$obj = 'so' . $galleryID . rand(10,1000);
	
	if (empty($irWidth) ) $irWidth = (int) $ngg_options['irWidth'];
	if (empty($irHeight)) $irHeight = (int) $ngg_options['irHeight'];

	$out  = "\n".'<div class="slideshow" id="ngg_slideshow'.$galleryID.'">';
	$out .= '<p>The <a href="http://www.macromedia.com/go/getflashplayer">Flash Player</a> and <a href="http://www.mozilla.com/firefox/">a browser with Javascript support</a> are needed..</p></div>';
    $out .= "\n\t".'<script type="text/javascript" defer="defer">';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n\t".'<!--';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n\t".'//<![CDATA[';
	$out .= "\n\t\t".'var '. $obj .' = new SWFObject("'.NGGALLERY_URLPATH.'imagerotator.swf", "ngg_slideshow'.$galleryID.'", "'.$irWidth.'", "'.$irHeight.'", "7", "#'.$ngg_options[irBackcolor].'");';
	$out .= "\n\t\t".$obj.'.addParam("wmode", "opaque");';
	$out .= "\n\t\t".$obj.'.addVariable("file", "'.NGGALLERY_URLPATH.'nggextractXML.php?gid='.$galleryID.'");';
	if (!$ngg_options['irShuffle']) $out .= "\n\t\t".$obj.'.addVariable("shuffle", "false");';
	// default value changed in 3.15 : linkfromdisplay, shownavigation, showicons
	if (!$ngg_options['irLinkfromdisplay']) $out .= "\n\t\t".$obj.'.addVariable("linkfromdisplay", "false");';
	if (!$ngg_options['irShownavigation']) $out .= "\n\t\t".$obj.'.addVariable("shownavigation", "false");';
	if (!$ngg_options['irShowicons']) $out .= "\n\t\t".$obj.'.addVariable("showicons", "false");';
	// keep compatible to older version, remove later
	if ($ngg_options['irLinkfromdisplay']) $out .= "\n\t\t".$obj.'.addVariable("linkfromdisplay", "true");';
	if ($ngg_options['irShownavigation']) $out .= "\n\t\t".$obj.'.addVariable("shownavigation", "true");';
	if ($ngg_options['irShowicons']) $out .= "\n\t\t".$obj.'.addVariable("showicons", "true");';
	// hidden feature since 3.14
	if ($ngg_options['irKenburns']) $out .= "\n\t\t".$obj.'.addVariable("kenburns", "true");';
	if ($ngg_options['irWatermark']) $out .= "\n\t\t".$obj.'.addVariable("logo", "'.$ngg_options['wmPath'].'");';
	if (!empty($ngg_options['irAudio'])) $out .= "\n\t\t".$obj.'.addVariable("audio", "'.$ngg_options['irAudio'].'");';
	$out .= "\n\t\t".$obj.'.addVariable("overstretch", "'.$ngg_options['irOverstretch'].'");';
	$out .= "\n\t\t".$obj.'.addVariable("backcolor", "0x'.$ngg_options['irBackcolor'].'");';
	$out .= "\n\t\t".$obj.'.addVariable("frontcolor", "0x'.$ngg_options['irFrontcolor'].'");';
	$out .= "\n\t\t".$obj.'.addVariable("lightcolor", "0x'.$ngg_options['irLightcolor'].'");';
	if (!empty($ngg_options['irScreencolor'])) $out .= "\n\t\t".$obj.'.addVariable("screencolor", "0x'.$ngg_options['irScreencolor'].'");';
	$out .= "\n\t\t".$obj.'.addVariable("rotatetime", "'.$ngg_options['irRotatetime'].'");';
	$out .= "\n\t\t".$obj.'.addVariable("transition", "'.$ngg_options['irTransition'].'");';	
	$out .= "\n\t\t".$obj.'.addVariable("width", "'.$irWidth.'");';
	$out .= "\n\t\t".$obj.'.addVariable("height", "'.$irHeight.'");'; 
	$out .= "\n\t\t".$obj.'.write("ngg_slideshow'.$galleryID.'");';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n\t".'//]]>';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n\t".'-->';
	$out .= "\n\t".'</script>';

	$out = apply_filters('ngg_show_slideshow_content', $out);		
	return $out;
}

/**********************************************************/
function nggShowGallery($galleryID) {
	
	global $wpdb, $nggRewrite;
	
	$ngg_options = nggallery::get_option('ngg_options');

	// $_GET from wp_query
	$show    = get_query_var('show');
	$pid     = get_query_var('pid');
	$pageid  = get_query_var('pageid');
	
	// set $show if slideshow first
	if ( empty( $show ) AND ($ngg_options['galShowOrder'] == 'slide')) {
		if (is_home()) $pageid = get_the_ID();
		$show = 'slide';
	}

	// go on only on this page
	if ( !is_home() || $pageid == get_the_ID() ) { 
			
		// 1st look for ImageBrowser link
		if (!empty( $pid))  {
			$out = nggShowImageBrowser($galleryID);
			return $out;
		}
		
		// 2nd look for slideshow
		if ( $show == 'slide' ) {
			$args['show'] = "gallery";
			$out  = '<div class="ngg-galleryoverview">';
			$out .= '<div class="slideshowlink"><a class="slideshowlink" href="' . $nggRewrite->get_permalink($args) . '">'.$ngg_options['galTextGallery'].'</a></div>';
			$out .= nggShowSlideshow($galleryID,$ngg_options['irWidth'],$ngg_options['irHeight']);
			$out .= '</div>'."\n";
			$out .= '<div class="ngg-clear"></div>'."\n";
			return $out;
		}
	}
	
	//Set sort order value, if not used (upgrade issue)
	$ngg_options['galSort'] = ($ngg_options['galSort']) ? $ngg_options['galSort'] : "pid";
	$ngg_options['galSortDir'] = ($ngg_options['galSortDir'] == "DESC") ? "DESC" : "ASC";

	// get all picture with this galleryid
	$galleryID = $wpdb->escape($galleryID);
	$picturelist = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$galleryID' AND tt.exclude != 1 ORDER BY tt.$ngg_options[galSort] $ngg_options[galSortDir] ");
	if (is_array($picturelist)) { 
		$out = nggCreateGallery($picturelist,$galleryID);
	}
	
	$out = apply_filters('ngg_show_gallery_content', $out, intval($galleryID));
	return $out;
}

/**********************************************************/
function nggCreateGallery($picturelist,$galleryID = false) {
	/** 
	* @array  	$picturelist
	* @int		$galleryID
    **/
    
    global $nggRewrite;
    
    
    $ngg_options = nggallery::get_option('ngg_options');
    
    // $_GET from wp_query
	$nggpage  = get_query_var('nggpage');
	$pageid   = get_query_var('pageid');
    
    if (!is_array($picturelist))
		$picturelist = array($picturelist);
	
	$maxElement = $ngg_options['galImages'];
	$thumbwidth = $ngg_options['thumbwidth'];
	$thumbheight = $ngg_options['thumbheight'];
	
	// set thumb size 
	$thumbsize = "";
	if ($ngg_options['thumbfix'])  $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbheight.'px;"';
	if ($ngg_options['thumbcrop']) $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbwidth.'px;"';
	
	// get the effect code
	if ($galleryID)
		$thumbcode = ($ngg_options['galImgBrowser']) ? "" : nggallery::get_thumbcode($picturelist[0]->name);
	else
		$thumbcode = ($ngg_options['galImgBrowser']) ? "" : nggallery::get_thumbcode(get_the_title());
	
 	// check for page navigation
 	if ($maxElement > 0) {
	 	if ( !is_home() || $pageid == get_the_ID() ) {
			if ( !empty( $nggpage ) )	
				$page = (int) $nggpage;
			else
				 $page = 1;
		}
		else $page = 1;
		 
	 	$start = $offset = ( $page - 1 ) * $maxElement;
	 	
	 	$total = count($picturelist);
	 	
		// remove the element if we didn't start at the beginning
		if ($start > 0 ) array_splice($picturelist, 0, $start);
		// return the list of images we need
		array_splice($picturelist, $maxElement);
	
		$navigation = nggallery::create_navigation($page, $total, $maxElement);
	} 	
	
	if (is_array($picturelist)) {
	$out  = '<div class="ngg-galleryoverview" id="ngg-gallery-'. $galleryID .'">';
	
	// show slideshow link
	if ($galleryID)
		if (($ngg_options['galShowSlide']) AND (NGGALLERY_IREXIST)) {
			$args['show'] = "slide";
			$out .= '<div class="slideshowlink"><a class="slideshowlink" href="' . $nggRewrite->get_permalink($args) . '">'.$ngg_options['galTextSlide'].'</a></div>';
		}
	
	// a description below the picture, require fixed width
	if (!$ngg_options['galShowDesc'])
		$ngg_options['galShowDesc'] = "none";
	$setwidth = ($ngg_options['galShowDesc'] != "none") ? 'style="width:'.($thumbwidth).'px;"' : '';
	$class_desc = ($ngg_options['galShowDesc'] != "none") ? 'desc' : '';
	
	foreach ($picturelist as $picture) {
		// set image url
		$folder_url 	= get_option ('siteurl')."/".$picture->path."/";
		$thumbnailURL 	= get_option ('siteurl')."/".$picture->path.nggallery::get_thumbnail_folder($picture->path, FALSE);
		$thumb_prefix   = nggallery::get_thumbnail_prefix($picture->path, FALSE);
		// choose link between imagebrowser or effect

		$link =($ngg_options['galImgBrowser']) ? $nggRewrite->get_permalink(array('pid'=>$picture->pid)) : $folder_url.$picture->filename;
		// create output
		$out .= '<div id="ngg-image-'. $picture->pid .'" class="ngg-gallery-thumbnail-box '. $class_desc .'">'."\n\t";
		$out .= '<div class="ngg-gallery-thumbnail" '.$setwidth.' >'."\n\t";
		$out .= '<a href="'.$link.'" title="'.stripslashes($picture->description).'" '.$thumbcode.' >';
		$out .= '<img title="'.stripslashes($picture->alttext).'" alt="'.stripslashes($picture->alttext).'" src="'.$thumbnailURL.$thumb_prefix.$picture->filename.'" '.$thumbsize.' />';
		$out .= '</a>'."\n";
		if ($ngg_options['galShowDesc'] == "alttext")
			$out .= '<span>'.html_entity_decode(stripslashes($picture->alttext)).'</span>'."\n";
		if ($ngg_options['galShowDesc'] == "desc")
			$out .= '<span>'.html_entity_decode(stripslashes($picture->description)).'</span>'."\n";
		$out .= '</div>'."\n".'</div>'."\n";
		}
	$out .= '</div>'."\n";
 	$out .= ($maxElement > 0) ? $navigation : '<div class="ngg-clear"></div>'."\n";
	}		
	
	return $out;
}

/**********************************************************/
function nggShowAlbum($albumID,$mode = "extend") {
	
	global $wpdb;
	
	// $_GET from wp_query
	$gallery  = get_query_var('gallery');
	$album    = get_query_var('album');

	// look for gallery variable 
	if (!empty( $gallery ))  {
		
		if ( $albumID != $album ) 
			return;

		$galleryID = (int) $gallery;
		$out = nggShowGallery($galleryID);
		return $out;
	} 

	$mode = ltrim($mode,',');
	$albumID = $wpdb->escape($albumID);
	$sortorder = $wpdb->get_var("SELECT sortorder FROM $wpdb->nggalbum WHERE id = '$albumID' ");
	if (!empty($sortorder)) {
		$gallery_array = unserialize($sortorder);
	} 

	$out = '<div class="ngg-albumoverview">';
	
	if (is_array($gallery_array)) {
	foreach ($gallery_array as $galleryID) {
		$out .= nggCreateAlbum($galleryID,$mode,$albumID);	
		}
	}
	
	$out .= '</div>'."\n";
	$out .= '<div class="ngg-clear"></div>'."\n";
	
	$out = apply_filters('ngg_show_album_content', $out, intval($albumID));
	return $out;
}

/**********************************************************/
function nggCreateAlbum($galleryID,$mode = "extend",$albumID = 0) {
	// create a gallery overview div
	
	global $wpdb, $nggRewrite;
	
	$ngg_options = nggallery::get_option('ngg_options');
	
	$galleryID = $wpdb->escape($galleryID);
	$gallerycontent = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

	// choose between variable and page link
	if ($ngg_options['galNoPages']) {
		$args['album'] = $albumID; 
		$args['gallery'] = $galleryID;
		$link = $nggRewrite->get_permalink($args);
	} else {
		$link = get_permalink($gallerycontent->pageid);
	}
	
	if ($gallerycontent) {
		$counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1");
 		if ($mode == "compact") {
			if ($gallerycontent->previewpic != 0)
				$insertpic = '<img class="Thumb" alt="'.$gallerycontent->title.'" src="'.nggallery::get_thumbnail_url($gallerycontent->previewpic).'"/>';
			else 
				$insertpic = __('Watch gallery', 'nggallery');
 			$out = '	
				<div class="ngg-album-compact">
					<div class="ngg-album-compactbox">
						<div class="ngg-album-link">
							<a class="Link" href="'.$link.'">'.$insertpic.'</a>
						</div>
					</div>
					<h4><a class="ngg-album-desc" title="'.$gallerycontent->title.'" href="'.$link.'">'.$gallerycontent->title.'</a></h4>
					<p><strong>'.$counter.'</strong> '.__('Photos', 'nggallery').'</p></div>';
		} else {
			// mode extend
			if ($gallerycontent->previewpic != 0)
				$insertpic = '<img src="'.nggallery::get_thumbnail_url($gallerycontent->previewpic).'" alt="'.$gallerycontent->title.'" title="'.$gallerycontent->title.'"/>';
			else 
				$insertpic = __('Watch gallery', 'nggallery');
			$out = '
			<div class="ngg-album">
				<div class="ngg-albumtitle"><a href="'.$link.'">'.$gallerycontent->title.'</a></div>
				<div class="ngg-albumcontent">
					<div class="ngg-thumbnail"><a href="'.$link.'">'.$insertpic.'</a></div>
					<div class="ngg-description"><p>'.html_entity_decode(stripslashes($gallerycontent->galdesc)).'</p><p><strong>'.$counter.'</strong> '.__('Photos', 'nggallery').'</p></div>'."\n".'</div>'."\n".'</div>';

		}
	}

	return $out;
}

/**********************************************************/
function nggShowImageBrowser($galleryID) {
	/** 
	* show the ImageBrowser
	* @galleryID	int / gallery id
	*/
	
	global $wpdb;
	
	$ngg_options = nggallery::get_option('ngg_options');
	
	// get the pictures
	$galleryID = $wpdb->escape($galleryID);
	$picturelist = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir]");	
	if (is_array($picturelist)) { 
		$out = nggCreateImageBrowser($picturelist);
	}
	
	$out = apply_filters('ngg_show_imagebrowser_content', $out, intval($galleryID));
	return $out;
	
}

/**********************************************************/
function nggCreateImageBrowser($picarray) {
	/** 
	* @array  	$picarray with pid
    **/

	global $nggRewrite;
	
	// $_GET from wp_query
	$pid  = get_query_var('pid');

    if (!is_array($picarray))
		$picarray = array($picarray);

	$total = count($picarray);

	// look for gallery variable 
	if ( !empty( $pid )) {
		$act_pid = (int) $pid;
	} else {
		reset($picarray);
		$act_pid = current($picarray);
	}
	
	// get ids for back/next
	$key = array_search($act_pid,$picarray);
	if (!$key) {
		$act_pid = reset($picarray);
		$key = key($picarray);
	}
	$back_pid = ( $key >= 1 ) ? $picarray[$key-1] : end($picarray) ;
	$next_pid = ( $key < ($total-1) ) ? $picarray[$key+1] : reset($picarray) ;
	
	// get the picture data
	$picture = new nggImage($act_pid);
	
	if ($picture) {
		$out = '
		<div class="ngg-imagebrowser" >
			<h3>'.html_entity_decode(stripslashes($picture->alttext)).'</h3>
			<div class="pic">'.$picture->get_href_link().'</div>
			<div class="ngg-imagebrowser-nav">';
		if 	($back_pid) {
			$backlink['pid'] = $back_pid;
			$out .='<div class="back"><a href="'.$nggRewrite->get_permalink($backlink).'">'.'&#9668; '.__('Back', 'nggallery').'</a></div>';
		}
		if 	($next_pid) {
			$nextlink['pid'] = $next_pid;
			$out .='<div class="next"><a href="'.$nggRewrite->get_permalink($nextlink).'">'.__('Next', 'nggallery').' &#9658;'.'</a></div>';
		}
		$out .='
				<div class="counter">'.__('Picture', 'nggallery').' '.($key+1).' '.__('from', 'nggallery').' '.$total.'</div>
				<div class="ngg-imagebrowser-desc"><p>'.html_entity_decode(stripslashes($picture->description)).'</p></div>
			</div>	
		</div>';
	}
	
	return $out;
	
}

/**********************************************************/
function nggSinglePicture($imageID,$width=250,$height=250,$mode="",$float="") {
	/** 
	* create a gallery based on the tags
	* @imageID		db-ID of the image
	* @width 		width of the image
	* @height 		height of the image
	* @mode 		none, watermark, web20
	* @float 		none, left, right
	*/
	global $wpdb, $post;
	
	$ngg_options = nggallery::get_option('ngg_options');
	
	// remove the comma
	$float = ltrim($float,',');
	$mode = ltrim($mode,',');
	$width = ltrim($width,',');
	$height = ltrim($height,',');

	// get picturedata
	$picture = new nggImage($imageID);
	
	// add float to img
	if (!empty($float)) {
		switch ($float) {
		
		case 'left': $float=' ngg-left';
		break;
		
		case 'right': $float=' ngg-right';
		break;

		case 'center': $float=' ngg-center';
		break;
		
		default: $float='';
		break;
		}
	}
	
	// check fo cached picture
	if ( ($ngg_options['imgCacheSinglePic']) && ($post->post_status == 'publish') )
		$cache_url = $picture->cached_singlepic_file($width, $height, $mode );

	// add fullsize picture as link
	$out  = '<div class="ngg-singlepic-wrapper'. $float .'"><a href="'.$picture->imagePath.'" title="'.stripslashes($picture->description).'" '.$picture->get_thumbcode("singlepic".$imageID).' >';
	if (!$cache_url)
		$out .= '<img class="ngg-singlepic" src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$imageID.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$mode.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" />';
	else
		$out .= '<img class="ngg-singlepic" src="'.$cache_url.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" />';
	$out .= '</a></div>';
	
	$out = apply_filters('ngg_show_singlepic_content', $out, intval( $imageID ) );
	
	return $out;
}

/**********************************************************/
function nggShowGalleryTags($taglist) {
	/** 
	* create a gallery based on the tags
	* @taglist		list of tags as csv
	*/
	
	global $wpdb;
	
	// $_GET from wp_query
	$pid  	= get_query_var('pid');
	$pageid = get_query_var('pageid');
	
	// get now the related images
	$picturelist = ngg_Tags::get_images($taglist);
	
	// look for ImageBrowser 
	if ( $pageid == get_the_ID() || !is_home() )  
		if (!empty( $pid ))  {
			foreach ($picturelist as $picture)
				$picarray[] = $picture->pid;
			$out = nggCreateImageBrowser($picarray);
			return $out;
		}

	// go on if not empty
	if (empty($picturelist))
		return;
	
	// show gallery
	if (is_array($picturelist)) { 
		$out = nggCreateGallery($picturelist,false);
	}
	
	$out = apply_filters('ngg_show_gallery_tags_content', $out, $taglist);
	return $out;
}

/**********************************************************/
function nggShowRelatedGallery($taglist, $maxImages = 0) {
	/** 
	* create a gallery based on the tags
	* @taglist		list of tags as csv
	* @maxImages	limit the number of images to show
	*/
	
	global $wpdb;
	
	$ngg_options = nggallery::get_option('ngg_options');
	
	// get now the related images
	$picturelist = ngg_Tags::get_images($taglist);
	
	// go on if not empty
	if (empty($picturelist))
		return;

	// get the effect code
	$thumbcode = nggallery::get_thumbcode("Related images for ".get_the_title());

	// cut the list to maxImages
	if ($maxImages > 0 ) array_splice($picturelist, $maxImages);
	
 	// *** build the gallery output
	$out   = '<div class="ngg-related-gallery">';
	
	foreach ($picturelist as $picture) {
		// set gallery url
		$folder_url 	= get_option ('siteurl')."/".$picture->path."/";
		$thumbnailURL 	= get_option ('siteurl')."/".$picture->path.nggallery::get_thumbnail_folder($picture->path, FALSE);
		$thumb_prefix   = nggallery::get_thumbnail_prefix($picture->path, FALSE);

		$out .= '<a href="'.$folder_url.$picture->filename.'" title="'.stripslashes($picture->description).'" '.$thumbcode.' >';
		$out .= '<img title="'.stripslashes($picture->alttext).'" alt="'.stripslashes($picture->alttext).'" src="'.$thumbnailURL.$thumb_prefix.$picture->filename.'" '.$thumbsize.' />';
		$out .= '</a>'."\n";
	}

	$out .= '</div>'."\n";

	$out = apply_filters('ngg_show_related_gallery_content', $out, $taglist);
	return $out;
}

/**********************************************************/
function nggShowAlbumTags($taglist) {
	/** 
	* create a gallery based on the tags
	* @taglist		list of tags as csv
	*/
	
	global $wpdb, $nggRewrite;

	// $_GET from wp_query
	$tag  			= get_query_var('gallerytag');
	$pageid 		= get_query_var('pageid');
	
	// look for gallerytag variable 
	if ( $pageid == get_the_ID() || !is_home() )  {
		if (!empty( $tag ))  {
	
			// avoid this evil code $sql = 'SELECT name FROM wp_ngg_tags WHERE slug = \'slug\' union select concat(0x7c,user_login,0x7c,user_pass,0x7c) from wp_users WHERE 1 = 1';
			$galleryTag = attribute_escape( $tag );
			$galleryID = $wpdb->escape($galleryID);
			$tagname  = $wpdb->get_var("SELECT name FROM $wpdb->nggtags WHERE slug = '$galleryTag' ");		
			$out  = '<div id="albumnav"><span><a href="'.get_permalink().'" title="'.__('Overview', 'nggallery').'">'.__('Overview', 'nggallery').'</a> | '.$tagname.'</span></div>';
			$out .=  nggShowGalleryTags($galleryTag);
			return $out;
	
		} 
	}
	
	// get now the related images
	$picturelist = ngg_Tags::get_album_images($taglist);

	// go on if not empty
	if (empty($picturelist))
		return;

	$out = '<div class="ngg-albumoverview">';
	foreach ($picturelist as $picture) {
		$args['gallerytag'] = $picture["slug"];
		$link = $nggRewrite->get_permalink($args);
		
		$insertpic = '<img class="Thumb" alt="'.$picture["name"].'" src="'.nggallery::get_thumbnail_url($picture["pid"]).'"/>';
		$tagid = $picture['tagid'];
		$counter  = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpic2tags WHERE tagid = '$tagid' ");
		$out .= '	
			<div class="ngg-album-compact">
				<div class="ngg-album-compactbox">
					<div class="ngg-album-link">
						<a class="Link" href="'.$link.'">'.$insertpic.'</a>
					</div>
				</div>
				<h4><a class="ngg-album-desc" title="'.$picture["name"].'" href="'.$link.'">'.$picture["name"].'</a></h4>
				<p><strong>'.$counter.'</strong> '.__('Photos', 'nggallery').'</p></div>';
	}
	$out .= '</div>'."\n";
	$out .= '<div class="ngg-clear"></div>'."\n";

	$out = apply_filters('ngg_show_album_tags_content', $out, $taglist);
	
	return $out;
}

/**********************************************************/
function nggShowRelatedImages($type = '', $maxImages = 0) {
	// return related images based on category or tags
		
		$ngg_options = nggallery::get_option('ngg_options');

		if ($type == '') {
			$type = $ngg_options['appendType'];
			$maxImages = $ngg_options['maxImages'];
		}
	
		$sluglist = array();
		switch ($type) {
			
		case "tags":
			if (function_exists('get_the_tags')) { 
				$taglist = get_the_tags();
				
				if (is_array($taglist)) 
				foreach ($taglist as $tag)
					$sluglist[] = $tag->slug;
			}
			break;
		case "category":
			$catlist = get_the_category();
			
			if (is_array($catlist)) 
			foreach ($catlist as $cat)
				$sluglist[] = $cat->category_nicename;
		}
		
		$sluglist = implode(",", $sluglist);
		$out = nggShowRelatedGallery($sluglist, $maxImages);
		
		return $out;
}

/**********************************************************/
function the_related_images($type = 'tags', $maxNumbers = 7) {
	// function for theme authors
	echo nggShowRelatedImages($type, $maxNumbers);
}

?>
