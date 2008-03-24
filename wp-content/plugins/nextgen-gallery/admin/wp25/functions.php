<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class nggAdmin{

	// **************************************************************
	function create_gallery($gallerytitle, $defaultpath) {
		// create a new gallery & folder
		global $wpdb;

		//cleanup pathname
		$galleryname = apply_filters('ngg_gallery_name', $gallerytitle);
		$nggpath = $defaultpath.$galleryname;
		$nggRoot = WINABSPATH.$defaultpath;
		$txt = "";
		
		// No gallery name ?
		if (empty($galleryname)) {	
			nggallery::show_error( __('No valid gallery name!', 'nggallery') );
			return false;
		}
		
		// check for main folder
		if ( !is_dir($nggRoot) ) {
			if ( !wp_mkdir_p($nggRoot) ) {
				$txt  = __('Directory', 'nggallery').' <strong>'.$defaultpath.'</strong> '.__('didn\'t exist. Please create first the main gallery folder ', 'nggallery').'!<br />';
				$txt .= __('Check this link, if you didn\'t know how to set the permission :', 'nggallery').' <a href="http://codex.wordpress.org/Changing_File_Permissions">http://codex.wordpress.org/Changing_File_Permissions</a> ';
				nggallery::show_error($txt);
				return false;
			}
		}

		// check for permission settings, Safe mode limitations are not taken into account. 
		if ( !is_writeable($nggRoot ) ) {
			$txt  = __('Directory', 'nggallery').' <strong>'.$defaultpath.'</strong> '.__('is not writeable !', 'nggallery').'<br />';
			$txt .= __('Check this link, if you didn\'t know how to set the permission :', 'nggallery').' <a href="http://codex.wordpress.org/Changing_File_Permissions">http://codex.wordpress.org/Changing_File_Permissions</a> ';
			nggallery::show_error($txt);
			return false;
		}
		
		// 1. Create new gallery folder
		if ( !is_dir(WINABSPATH.$nggpath) ) {
			if ( !wp_mkdir_p (WINABSPATH.$nggpath) ) 
				$txt  = __('Unable to create directory ', 'nggallery').$nggpath.'!<br />';
		}
		
		// 2. Check folder permission
		if ( !is_writeable(WINABSPATH.$nggpath ) )
			$txt .= __('Directory', 'nggallery').' <strong>'.$nggpath.'</strong> '.__('is not writeable !', 'nggallery').'<br />';

		// 3. Now create "thumbs" folder inside
		if ( !is_dir(WINABSPATH.$nggpath.'/thumbs') ) {				
			if ( !wp_mkdir_p ( WINABSPATH.$nggpath.'/thumbs') ) 
				$txt .= __('Unable to create directory ', 'nggallery').' <strong>'.$nggpath.'/thumbs !</strong>';
		}
		
		if (SAFE_MODE) {
			$help  = __('The server setting Safe-Mode is on !', 'nggallery');	
			$help .= '<br />'.__('If you have problems, please create directory', 'nggallery').' <strong>'.$nggpath.'</strong> ';	
			$help .= __('and the thumbnails directory', 'nggallery').' <strong>'.$nggpath.'/thumbs</strong> '.__('with permission 777 manually !', 'nggallery');
			nggallery::show_message($help);
		}
		
		// show a error message			
		if ( !empty($txt) ) {
			if (SAFE_MODE) {
			// for safe_mode , better delete folder, both folder must be created manually
				@rmdir(WINABSPATH.$nggpath.'/thumbs');
				@rmdir(WINABSPATH.$nggpath);
			}
			nggallery::show_error($txt);
			return false;
		}
		
		$result=$wpdb->get_var("SELECT name FROM $wpdb->nggallery WHERE name = '$galleryname' ");
		if ($result) {
			nggallery::show_error(__('Gallery', 'nggallery').' <strong>'.$galleryname.'</strong> '.__('already exists', 'nggallery'));
			return false;			
		} else { 
			$result = $wpdb->query("INSERT INTO $wpdb->nggallery (name, path, title) VALUES ('$galleryname', '$nggpath', '$gallerytitle') ");
			if ($result) nggallery::show_message(__('Gallery', 'nggallery').' <strong>'.$wpdb->insert_id." : ".$galleryname.'</strong> '.__('successfully created!','nggallery')."<br />".__('You can show this gallery with the tag','nggallery').'<strong> [gallery='.$wpdb->insert_id.']</strong>'); 
			return true;;
		} 
	}
	
	// **************************************************************
	function import_gallery($galleryfolder) {
		// ** $galleryfolder contains relative path
		
		//TODO: Check permission of existing thumb folder & images
		
		global $wpdb;
		
		$created_msg = "";
		
		// remove trailing slash at the end, if somebody use it
		if (substr($galleryfolder, -1) == '/') $galleryfolder = substr($galleryfolder, 0, -1);
		$gallerypath = WINABSPATH.$galleryfolder;
		
		if (!is_dir($gallerypath)) {
			nggallery::show_error(__('Directory', 'nggallery').' <strong>'.$gallerypath.'</strong> '.__('doesn&#96;t exist!', 'nggallery'));
			return ;
		}
		
		// read list of images
		$new_imageslist = nggAdmin::scandir($gallerypath);
		if (empty($new_imageslist)) {
			nggallery::show_message(__('Directory', 'nggallery').' <strong>'.$gallerypath.'</strong> '.__('contains no pictures', 'nggallery'));
			return;
		}
		// check & create thumbnail folder
		if ( !nggallery::get_thumbnail_folder($gallerypath) )
			return;
		
		// take folder name as gallery name		
		$galleryname = basename($galleryfolder);
		
		// check for existing galleryfolder
		$gallery_id = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE path = '$galleryfolder' ");

		if (!$gallery_id) {
			$result = $wpdb->query("INSERT INTO $wpdb->nggallery (name, path) VALUES ('$galleryname', '$galleryfolder') ");
			if (!$result) {
				nggallery::show_error(__('Database error. Could not add gallery!','nggallery'));
				return;
			}
			$created_msg =__('Gallery','nggallery').' <strong>'.$galleryname.'</strong> '.__('successfully created!','nggallery').'<br />';
			$gallery_id = $wpdb->insert_id;  // get index_id
		}
		
		// Look for existing image list
		$old_imageslist = $wpdb->get_col("SELECT filename FROM $wpdb->nggpictures WHERE galleryid = '$gallery_id' ");
		// if no images are there, create empty array
		if ($old_imageslist == NULL) $old_imageslist = array();
		// check difference
		$new_images = array_diff($new_imageslist, $old_imageslist);
		// now create thumbnails
		nggAdmin::generateThumbnail($gallerypath,$new_images);
		
		// add images to database		
		$count_pic = nggAdmin::add_Images($gallery_id, $gallerypath, $new_images);
				
		nggallery::show_message($created_msg.$count_pic.__(' picture(s) successfully added','nggallery'));
		return;

	}
	// **************************************************************
	function scandir($dirname = ".") { 
		// thx to php.net :-)
		$ext = array("jpeg", "jpg", "png", "gif"); 
		$files = array(); 
		if($handle = opendir($dirname)) { 
		   while(false !== ($file = readdir($handle))) 
		       for($i=0;$i<sizeof($ext);$i++) 
		           if(stristr($file, ".".$ext[$i])) 
		               $files[] = utf8_encode($file); 
		   closedir($handle); 
		} 
		sort($files);
		return ($files); 
	} 
	
	// **************************************************************
	function resizeImages($gallery_absfolder, $pictures) {
		// ** $gallery_absfolder must contain abspath !!

		if(! class_exists('ngg_Thumbnail'))
			require_once(NGGALLERY_ABSPATH.'/lib/thumbnail.inc.php');
		
		$ngg_options = get_option('ngg_options');
		
		if (is_array($pictures)) {
			
			$bar = new wpProgressBar(__('Running... Please wait','nggallery'));
			$bar->setHeader(__('Resize images','nggallery'));
			//total number of elements to process
			$elements = count($pictures); 
			// wait a little bit after finished
			if ($elements > 5) $bar->setSleepOnFinish(2);
			//print the empty bar
			$bar->initialize($elements); 
			
			foreach($pictures as $picture) {
	
				if (!is_writable($gallery_absfolder."/".$picture)) {
					$messagetext .= $gallery_absfolder."/".$picture."<br />";
					$bar->increase();
					continue;
				}
				
				$thumb = new ngg_Thumbnail($gallery_absfolder."/".$picture, TRUE);
				// echo $thumb->errmsg;	
				// skip if file is not there
				if (!$thumb->error) {
					$thumb->resize($ngg_options['imgWidth'],$ngg_options['imgHeight'],$ngg_options['imgResampleMode']);
					if ( $thumb->save($gallery_absfolder."/".$picture,$ngg_options['imgQuality']) ) {
						// do not flush the buffer with useless messages
						if ($elements  < 100)
							$bar->addNote($picture. __(' : Image resized...','nggallery'));
					} else
						$bar->addNote($picture . "  : Error : <strong>".$thumb->errmsg."</strong>");
					$bar->increase();
				}
				$thumb->destruct();
			}
		}
		
		if(!empty($messagetext)) nggallery::show_error('<strong>'.__('Some pictures are not writeable :','nggallery').'</strong><br /><ul>'.$messagetext.'</ul>');
		return;
	}
	
	// **************************************************************
	function generateWatermark($gallery_absfolder, $pictures) {
		// ** $gallery_absfolder must contain abspath !!

		if(! class_exists('ngg_Thumbnail'))
			require_once(NGGALLERY_ABSPATH.'/lib/thumbnail.inc.php');
		
		$ngg_options = get_option('ngg_options');
		
		if (is_array($pictures)) {
			
			$bar = new wpProgressBar(__('Running... Please wait','nggallery'));
			$bar->setHeader(__('Set watermark','nggallery'));
			//total number of elements to process
			$elements = count($pictures); 
			// wait a little bit after finished
			if ($elements > 5) $bar->setSleepOnFinish(2);
			//print the empty bar
			$bar->initialize($elements); 
						
			foreach($pictures as $picture) {
	
			if (!is_writable($gallery_absfolder."/".$picture)) {
				$messagetext .= $gallery_absfolder."/".$picture."<br />";
				$bar->increase();
				continue;
			}
			
			$thumb = new ngg_Thumbnail($gallery_absfolder."/".$picture, TRUE);
			// echo $thumb->errmsg;	
			// skip if file is not there
			if (!$thumb->error) {
				if ($ngg_options['wmType'] == 'image') {
					$thumb->watermarkImgPath = $ngg_options['wmPath'];
					$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']); 
				}
				if ($ngg_options['wmType'] == 'text') {
					$thumb->watermarkText = $ngg_options['wmText'];
					$thumb->watermarkCreateText($ngg_options['wmColor'], $ngg_options['wmFont'], $ngg_options['wmSize'], $ngg_options['wmOpaque']);
					$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']);  
				}
				if ( $thumb->save($gallery_absfolder."/".$picture,$ngg_options['imgQuality']) ) {
					// do not flush the buffer with useless messages
					if ($elements  < 100)
						$bar->addNote($picture. __(' : Watermark created...','nggallery'));
				 } else
					$bar->addNote($picture . "  : Error : <strong>".$thumb->errmsg."</strong>");
				$bar->increase();
			}
			$thumb->destruct();
			}
		}
		
		if(!empty($messagetext)) nggallery::show_error('<strong>'.__('Some pictures are not writeable :','nggallery').'</strong><br /><ul>'.$messagetext.'</ul>');
		return;
	}

	// **************************************************************
	function generateThumbnail($gallery_absfolder, $pictures) {
		// ** $gallery_absfolder must contain abspath !!
		
		if(! class_exists('ngg_Thumbnail'))
			require_once(NGGALLERY_ABSPATH.'/lib/thumbnail.inc.php');
		
		$ngg_options = get_option('ngg_options');
		
		$thumbfolder = nggallery::get_thumbnail_folder($gallery_absfolder);
		$prefix = nggallery::get_thumbnail_prefix($gallery_absfolder);
		
		if (!$thumbfolder)
			return;
		
		if (is_array($pictures)) {
			
			$bar = new wpProgressBar(__('Running... Please wait','nggallery'));
			$bar->setHeader(__('Create new thumbnails','nggallery'));
			//total number of elements to process
			$elements = count($pictures); 
			// wait a little bit after finished
			if ($elements > 10) $bar->setSleepOnFinish(2);
			//print the empty bar
			$bar->initialize($elements); 
		
			foreach($pictures as $picture) {
				// check for existing thumbnail
				if (file_exists($gallery_absfolder.$thumbfolder.$prefix.$picture)) {
					if (!is_writable($gallery_absfolder.$thumbfolder.$prefix.$picture)) {
						$messagetext .= $gallery_absfolder."/".$picture."<br />";
						$bar->increase();
						continue;
					}
				}
	
				$thumb = new ngg_Thumbnail($gallery_absfolder."/".utf8_decode($picture), TRUE);

				// skip if file is not there
				if (!$thumb->error) {
					if ($ngg_options['thumbcrop']) {
						
						// THX to Kees de Bruin, better thumbnails if portrait format
						$width = $ngg_options['thumbwidth'];
						$height = $ngg_options['thumbheight'];
						$curwidth = $thumb->currentDimensions['width'];
						$curheight = $thumb->currentDimensions['height'];
						if ($curwidth > $curheight) {
							$aspect = (100 * $curwidth) / $curheight;
						} else {
							$aspect = (100 * $curheight) / $curwidth;
						}
						$width = intval(($width * $aspect) / 100);
						$height = intval(($height * $aspect) / 100);
						$thumb->resize($width,$height,$ngg_options['thumbResampleMode']);
						$thumb->cropFromCenter($width,$ngg_options['thumbResampleMode']);
					} 
					elseif ($ngg_options['thumbfix'])  {
						// check for portrait format
						if ($thumb->currentDimensions['height'] > $thumb->currentDimensions['width']) {
							$thumb->resize($ngg_options['thumbwidth'], 0,$ngg_options['thumbResampleMode']);
							// get optimal y startpos
							$ypos = ($thumb->currentDimensions['height'] - $ngg_options['thumbheight']) / 2;
							$thumb->crop(0, $ypos, $ngg_options['thumbwidth'],$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
						} else {
							$thumb->resize(0,$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
							// get optimal x startpos
							$xpos = ($thumb->currentDimensions['width'] - $ngg_options['thumbwidth']) / 2;
							$thumb->crop($xpos, 0, $ngg_options['thumbwidth'],$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
						}
					} else {
						$thumb->resize($ngg_options['thumbwidth'],$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
					}
					if ( !$thumb->save($gallery_absfolder.$thumbfolder.$prefix.$picture,$ngg_options['thumbquality'])) {
							$errortext .= $picture . " <strong>(Error : ".$thumb->errmsg .")</strong><br />";
							$bar->addNote($picture . "  : Error : <strong>".$thumb->errmsg)."</strong>";
					}
					nggAdmin::chmod ($gallery_absfolder.$thumbfolder.$prefix.$picture); 
				} else {
					$errortext .= $picture . " <strong>(Error : ".$thumb->errmsg .")</strong><br />";
					$bar->addNote($picture . "  : Error : <strong>".$thumb->errmsg."</strong>");
				}
				$thumb->destruct();
				// do not flush the buffer with useless messages
				if ($elements  < 100)
					$bar->addNote($picture. __(' : Thumbnail created...','nggallery'));
				$bar->increase();
			}
		}

		if(!empty($errortext)) nggallery::show_error('<strong>'.__('Follow thumbnails could not created.','nggallery').'</strong><br /><ul>'.$errortext.'</ul>');		
		if(!empty($messagetext)) nggallery::show_error('<strong>'.__('Some thumbnails are not writeable :','nggallery').'</strong><br /><ul>'.$messagetext.'</ul>');
		
		return;
	}

	// **************************************************************
	function add_Images($galleryID, $gallerypath, $imageslist) {
		// add images to database		
		global $wpdb;
		$count_pic = 0;
		if (is_array($imageslist)) {
			foreach($imageslist as $picture) {
				$result = $wpdb->query("INSERT INTO $wpdb->nggpictures (galleryid, filename, alttext, exclude) VALUES ('$galleryID', '$picture', '$picture', 0) ");
				$pic_id = (int) $wpdb->insert_id;
				if ($result) $count_pic++;

				// add the metadata
				if ($_POST['addmetadata']) 
					nggAdmin::import_MetaData($pic_id);
					
			} 
		} // is_array
		
		return $count_pic;
		
	}

	// **************************************************************
	function import_MetaData($imagesIds) {
		// add images to database		
		global $wpdb;
		
		if (!is_array($imagesIds))
			$imagesIds = array($imagesIds);
		
		foreach($imagesIds as $pic_id) {
			
			$picture  = new nggImage($pic_id );
			if (!$picture->error) {

				$meta = nggAdmin::get_MetaData($picture->absPath);
				
				// get the title
				if (!$alttext = $meta['title'])
					$alttext = $picture->alttext;
				// get the caption / description field
				if (!$description = $meta['caption'])
					$description = $picture->description;
				// update database
				$result=$wpdb->query( "UPDATE $wpdb->nggpictures SET alttext = '$alttext', description = '$description'  WHERE pid = $pic_id");
				// add the tags
				if ($meta['keywords']) {
					$taglist = explode(",", $meta['keywords']);
					$taglist = array_map('trim', $taglist);
					// load tag list
					$nggTags = new ngg_Tags();
					foreach($taglist as $tag) {
						// get the tag id
						$tagid = $nggTags->add_tag($tag);
						if ( $tagid )
							$nggTags->add_relationship($pic_id, $tagid);
					}
				} // add tags
			}// error check
		} // foreach
		
		return true;
		
	}

	// **************************************************************
	function get_MetaData($picPath) {
		// must be Gallery absPath + filename
		
		require_once(NGGALLERY_ABSPATH.'/lib/nggmeta.lib.php');
		
		$meta = array();

		$pdata = new nggMeta($picPath);
		$meta['title'] = $pdata->get_META('title');		
		$meta['caption'] = $pdata->get_META('caption');	
		$meta['keywords'] = $pdata->get_META('keywords');	
		
		return $meta;
		
	}

	// **************************************************************
	function unzip($dir, $file) {
	// thx to Gregor at http://blog.scoutpress.de/forum/topic/45
		
		if(! class_exists('PclZip'))
			require_once(NGGALLERY_ABSPATH.'/lib/pclzip.lib.php');
				
		$archive = new PclZip($file);

		// extract all files in one folder
		if ($archive->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_CB_PRE_EXTRACT, 'ngg_getOnlyImages') == 0) {
			if ($archive->error_code == -22)
				nggallery::show_error(__('The Zip-file is too large. Exceed Memory limit !','nggallery'));
			else
				nggallery::show_error("Error : ".$archive->errorInfo(true));
			return false;
		}

		return true;
	}
 
	// **************************************************************
	function getOnlyImages($p_event, $p_header)	{
		$info = pathinfo($p_header['filename']);
		// check for extension
		$ext = array("jpeg", "jpg", "png", "gif"); 
		if (in_array( strtolower($info['extension']), $ext)) {
			// For MAC skip the ".image" files
			if ($info['basename']{0} ==  "." ) 
				return 0;
			else 
				return 1;
		}
		// ----- all other files are skipped
		else {
		  return 0;
		}
	}

	// **************************************************************
	function import_zipfile($defaultpath) {
		
		if (nggAdmin::check_quota())
			return;
		
		$temp_zipfile = $_FILES['zipfile']['tmp_name'];
		$filename = $_FILES['zipfile']['name']; 
					
		// check if file is a zip file
		if (!eregi('zip', $_FILES['zipfile']['type']))
			// on whatever reason MAC shows "application/download"
			if (!eregi('download', $_FILES['zipfile']['type'])) {
				@unlink($temp_zipfile); // del temp file
				nggallery::show_error(__('Uploaded file was no or a faulty zip file ! The server recognize : ','nggallery').$_FILES['zipfile']['type']);
				return; 
			}
		
		// get foldername if selected
		$foldername = $_POST['zipgalselect'];
		if ($foldername == "0") {	
			//cleanup and take the zipfile name as folder name
			$foldername = sanitize_title(strtok ($filename,'.'));
			//$foldername = preg_replace ("/(\s+)/", '-', strtolower(strtok ($filename,'.')));					
		}

		//TODO:FORM must get the path from the tables not from defaultpath	!!!
		// set complete folder path		
		$newfolder = WINABSPATH.$defaultpath.$foldername;

		if (!is_dir($newfolder)) {
			// create new directories
			if (!wp_mkdir_p ($newfolder)) {
				$message = sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'nggallery'), $newfolder);
				nggallery::show_error($message);
				return false;
			}
			if (!wp_mkdir_p ($newfolder.'/thumbs')) {
				nggallery::show_error(__('Unable to create directory ', 'nggallery').$newfolder.'/thumbs !');
				return false;
			}
		} 
		
		// unzip and del temp file		
		$result = nggAdmin::unzip($newfolder, $temp_zipfile);
		@unlink($temp_zipfile);		

		if ($result) {
			$message = __('Zip-File successfully unpacked','nggallery').'<br />';		

			// parse now the folder and add to database
			$message .= nggAdmin::import_gallery($defaultpath.$foldername);
	
			nggallery::show_message($message);
		}
		
		return;
	}

	// **************************************************************
	function upload_images() {
	// upload of pictures
		
		global $wpdb;
		
		// WPMU action
		if (nggAdmin::check_quota())
			return;

		// Images must be an array
		$imageslist = array();

		// get selected gallery
		$galleryID = (int) $_POST['galleryselect'];

		if ($galleryID == 0) {
			nggallery::show_error(__('No gallery selected !','nggallery'));
			return;	
		}

		// get the path to the gallery	
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

		if (!$gallerypath){
			nggallery::show_error(__('Failure in database, no gallery path set !','nggallery'));
			return;
		} 
				
		// read list of images
		$dirlist = nggAdmin::scandir(WINABSPATH.$gallerypath);
		
		foreach ($_FILES as $key => $value) {
			
			// look only for uploded files
			if ($_FILES[$key]['error'] == 0) {
				$temp_file = $_FILES[$key]['tmp_name'];
				$filepart = pathinfo ( strtolower($_FILES[$key]['name']) );
				// required until PHP 5.2.0
				$filepart['filename'] = substr($filepart["basename"],0 ,strlen($filepart["basename"]) - (strlen($filepart["extension"]) + 1) );
				
				$filename = sanitize_title($filepart['filename']) . "." . $filepart['extension'];

				// check for allowed extension
				$ext = array("jpeg", "jpg", "png", "gif"); 
				if (!in_array($filepart['extension'],$ext)){ 
					nggallery::show_error('<strong>'.$_FILES[$key]['name'].' </strong>'.__('is no valid image file!','nggallery'));
					continue;
				}

				// check if this filename already exist in the folder
				$i = 0;
				while (in_array($filename,$dirlist)) {
					$filename = sanitize_title($filepart['filename']) . "_" . $i++ . "." .$filepart['extension'];
				}
				
				$dest_file = WINABSPATH.$gallerypath."/".$filename;
				
				//check for folder permission
				if (!is_writeable(WINABSPATH.$gallerypath)) {
					$message = sprintf(__('Unable to write to directory %s. Is this directory writable by the server?', 'nggallery'), WINABSPATH.$gallerypath);
					nggallery::show_error($message);
					return;				
				}
				
				// save temp file to gallery
				if (!@move_uploaded_file($_FILES[$key]['tmp_name'], $dest_file)){
					nggallery::show_error(__('Error, the file could not moved to : ','nggallery').$dest_file);
					nggAdmin::check_safemode(WINABSPATH.$gallerypath);		
					continue;
				} 
				if (!nggAdmin::chmod ($dest_file)) {
					nggallery::show_error(__('Error, the file permissions could not set','nggallery'));
					continue;
				}
				
				// add to imagelist & dirlist
				$imageslist[] = $filename;
				$dirlist[] = $filename;

			}
		}
		
		if (count($imageslist) > 0) {
			
			//create thumbnails
			nggAdmin::generatethumbnail(WINABSPATH.$gallerypath,$imageslist);
		
			// add images to database		
			$count_pic = nggAdmin::add_Images($galleryID, $gallerypath, $imageslist);
		
			nggallery::show_message($count_pic.__(' Image(s) successfully added','nggallery'));
		}
		
		return;

	} // end function
	
	// **************************************************************
	function swfupload_image($galleryID = 0) {
		// This function is called by the swfupload
		global $wpdb;
		$ngg_options = get_option('ngg_options');
		
		if ($galleryID == 0) {
			@unlink($temp_file);		
			return __('No gallery selected !','nggallery');;
		}

		// WPMU action
		if (nggAdmin::check_quota())
			return;

		// Check the upload
		if (!isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0) {
			return __('Invalid upload. Error Code : ','nggallery').$_FILES["Filedata"]["error"];
		}

		// get the filename and extension
		$temp_file = $_FILES["Filedata"]['tmp_name'];
		$filepart = pathinfo ( strtolower($_FILES["Filedata"]['name']) );
		// required until PHP 5.2.0
		$filepart['filename'] = substr($filepart["basename"],0 ,strlen($filepart["basename"]) - (strlen($filepart["extension"]) + 1) );
		$filename = sanitize_title($filepart['filename']).".".$filepart['extension'];

		// check for allowed extension
		$ext = array("jpeg", "jpg", "png", "gif"); 
		if (!in_array($filepart['extension'],$ext)){ 
			return $_FILES[$key]['name'].__('is no valid image file!','nggallery');
		}

		// get the path to the gallery	
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$galleryID' ");
		if (!$gallerypath){
			@unlink($temp_file);		
			return __('Failure in database, no gallery path set !','nggallery');
		} 

		// read list of images
		$imageslist = nggAdmin::scandir(WINABSPATH.$gallerypath);

		// check if this filename already exist
		$i = 0;
		while (in_array($filename,$imageslist)) {
			$filename = sanitize_title($filepart['filename']) . "_" . $i++ . "." .$filepart['extension'];
		}
		
		$dest_file = WINABSPATH.$gallerypath."/".$filename;
				
		// save temp file to gallery
		if ( !@move_uploaded_file($_FILES["Filedata"]['tmp_name'], $dest_file) ){
			nggAdmin::check_safemode(WINABSPATH.$gallerypath);	
			return __('Error, the file could not moved to : ','nggallery').$dest_file;
		} 
		
		if ( !nggAdmin::chmod($dest_file) ) {
			return __('Error, the file permissions could not set','nggallery');
		}
		
		return "0";
	}	
	
	// **************************************************************
	function check_quota() {
		// Only for WPMU
			if ( (IS_WPMU) && wpmu_enable_function('wpmuQuotaCheck'))
				if( $error = upload_is_user_over_quota( false ) ) {
					nggallery::show_error( __( 'Sorry, you have used your space allocation. Please delete some files to upload more files.','nggallery' ) );
					return true;
				}
			return false;
	}
	
	// **************************************************************
	function chmod($filename = "") {
		// Set correct file permissions (taken from wp core)
		$stat = @ stat(dirname($filename));
		$perms = $stat['mode'] & 0007777;
		$perms = $perms & 0000666;
		if ( @chmod($filename, $perms) )
			return true;
			
		return false;
	}
	
	function check_safemode($foldername) {
		// Check UID in folder and Script
		// Read http://www.php.net/manual/en/features.safe-mode.php to understand safe_mode
		if ( SAFE_MODE ) {
			
			$script_uid = ( ini_get('safe_mode_gid') ) ? getmygid() : getmyuid();
			$folder_uid = fileowner($foldername);

			if ($script_uid != $folder_uid) {
				$message  = sprintf(__('SAFE MODE Restriction in effect! You need to create the folder <strong>%s</strong> manually','nggallery'), $foldername);
				$message .= '<br />' . sprintf(__('When safe_mode is on, PHP checks to see if the owner (%s) of the current script matches the owner (%s) of the file to be operated on by a file function or its directory','nggallery'), $script_uid, $folder_uid );
				nggallery::show_error($message);
				return false;
			}
		}
		
		return true;
	}

} // END class nggAdmin

/**
 * Class wpProgressBar for WordPress & NextGEN Gallery 
 * Easy to use progress bar in html and css.
 *
 * @author Based on ProgressBar from David Bongard (mail@bongard.net | www.bongard.net)
 *		   and Phillip Berndt (standards.webmasterpro.de)
 * @mixed by Alex Rabe
 * @version 1.0 - 20071201
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @copyright Copyright &copy; 2007, David Bongard , Phillip Berndt
 *
 */
class wpProgressBar {

	/**
	 * Constructor
	 *
	 * @param str $message Message shown above the bar eg. "Please wait...". Default: ''
	 * @param bool $hide Hide the bar after completion (with JavaScript). Default: true
	 * @param int $sleepOnFinish Seconds to sleep after bar completion. Default: 0
	 * @param int $barLength Length in percent. Default: 100
	 * @param str $domID Html-Attribute "id" for the bar
	 * @param str $header the header title
	 */
    function wpProgressBar($message='', $hide=true, $sleepOnFinish=0, $domID='progressbar', $header='')
    {
		global $pb_instance;
    	$this->instance = $pb_instance++;
    	$this->setAutohide($hide);
    	$this->setSleepOnFinish($sleepOnFinish);
		$this->setDomIDs($domID);
    	$this->setMessage($message);
		$this->setheader($header);
    }


	/**
	 * Print the empty progress bar
	 * @param int $numElements Number of Elements to be processed and number of times $bar->initialize() will be called while processing
	 */
	function initialize($numElements)
	{
		$this->StepCount = 0;
		$this->ListCount = 0;

		$numElements = (int) $numElements ;

    	if($numElements == 0)
    		return;
    	
    	$this->numSteps = $numElements;
		
		//calculate the % per Step
		$this->percentPerStep = round (100 / $numElements, 2); 

		//stop buffering
    	ob_end_flush();
    	//start buffering
    	ob_start();
		
		echo '<div id="'.$this->domID.'_container" class="wrap">
				  <h2>'.$this->header.'</h2>
				  <div id="'.$this->domID.'" class="progressborder"><div class="progressbar"><span>0%</span></div></div>
			  	  <div class="progressbar_message"><span style="display:block" id="'.$this->domIDMessage.'">'.$this->message.'</span></div>
			  	  <ul id="'.$this->domIDProgressNote.'">&nbsp;</ul>
			  </div>
			  
			  <script type="text/javascript">
				<!--
				oProgressbar = document.getElementById("'.$this->domID.'").firstChild;
				function progress(value)
				{
				  oProgressbar.firstChild.firstChild.nodeValue = oProgressbar.style.width = value + "%";
				}
				// -->
			  </script>';	

		ob_flush();
		flush();

		$this->initialized = true;
	}

	/**
	 * Count steps and increase bar length
	 *
	 */
	function increase()
	{
		if($this->StepCount < $this->numSteps) {
			//add a step
			$this->StepCount++;

			$value = $this->StepCount * $this->percentPerStep;
			echo('<script type="text/javascript">progress('.intval($value).');</script>');

			ob_flush();
			flush();
		}

		if(!$this->finished && $this->StepCount == $this->numSteps){
			// to be sure that based on round we reached 100%
			if ($value != 100){
				echo('<script type="text/javascript">progress('.intval(100).');</script>');
				ob_flush();
				flush();
			}
			$this->stop();
		}
	}

	function stop($error=false)
	{

			//sleep x seconds before ending the script
			if(!$error){
				if($this->sleepOnFinish > 0){
					sleep($this->sleepOnFinish);
				}

				//hide the bar
				if($this->hide){
					echo '<script type="text/javascript">document.getElementById("'.$this->domID.'_container").style.display = "none";</script>';
					ob_flush();
					flush();
				}
			}
			$this->finished = true;
	}

	function setMessage($text)
	{
		if($this->initialized){
			echo '<script type="text/javascript">document.getElementById("'.$this->domIDMessage.'").innerHTML = "'.$text.'";</script>';
			ob_flush();flush();
		}else{
			$this->message = $text;
		}
	}

	function addNote($text)
	{
		if($this->initialized){
			echo '<script type="text/javascript">
					var newLI = document.createElement("li");
					var note = document.createTextNode("'.$text.'");
					document.getElementById("'.$this->domIDProgressNote.'").appendChild(newLI);
  					document.getElementById("'.$this->domIDProgressNote.'").getElementsByTagName("li")['.$this->ListCount.'].appendChild(note);
			      </script>';      
			$this->ListCount++;
			if ($this->numSteps < 150) {
				ob_flush();
				flush();
			}
		}
	}

	function setAutohide($hide)
	{
    	$this->hide = (bool) $hide;
    }

	function setHeader($header)
	{
    	$this->header = $header;
    }

    function setSleepOnFinish($sleepOnFinish)
    {
    	$this->sleepOnFinish = (int) $sleepOnFinish;
    }

    function setDomIDs($domID)
    {
    	$this->domID = strip_tags($domID).$this->instance;
    	$this->domIDMessage = $this->domID.'_message';
    	$this->domIDProgressNote = $this->domID.'_note';
    }

}

// **************************************************************
//TODO: Cannot be member of a class ? Check PCLZIP later...
function ngg_getOnlyImages($p_event, $p_header)	{
	
	return nggAdmin::getOnlyImages($p_event, $p_header);
	
}

?>