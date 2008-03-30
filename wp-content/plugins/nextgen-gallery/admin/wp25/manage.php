<?php  

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function nggallery_admin_manage_gallery() {
	global $wpdb;

	//TODO:GID & Mode should the hidden post variables

	// GET variables
	$act_gid = (int) $_GET['gid'];
	$act_pid = (int) $_GET['pid'];	
	$mode = trim(attribute_escape($_GET['mode']));

	// get the options
	$ngg_options=get_option('ngg_options');	

	if (isset ($_POST['togglethumbs']))  {
		check_admin_referer('ngg_updategallery');
	// Toggle thumnails, forgive me if it's to complicated
		$hideThumbs = (isset ($_POST['hideThumbs'])) ?  false : true ;
	} else {
		$hideThumbs = (isset ($_POST['hideThumbs'])) ?  true : false ;
	}

	if (isset ($_POST['toggletags']))  {
		check_admin_referer('ngg_updategallery');
	// Toggle tag view
		$showTags = (isset ($_POST['showTags'])) ?  false : true ;
	} else {
		$showTags = (isset ($_POST['showTags'])) ?  true : false ;
	}

	if ($mode == 'delete') {
	// Delete a gallery
	
		check_admin_referer('ngg_editgallery');
	
		// get the path to the gallery
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		if ($gallerypath){
			$thumb_folder = nggallery::get_thumbnail_folder($gallerypath, FALSE);
			$thumb_prefix = nggallery::get_thumbnail_prefix($gallerypath, FALSE);
	
			// delete pictures
			$imagelist = $wpdb->get_col("SELECT filename FROM $wpdb->nggpictures WHERE galleryid = '$act_gid' ");
			if ($ngg_options['deleteImg']) {
				if (is_array($imagelist)) {
					foreach ($imagelist as $filename) {
						@unlink(WINABSPATH.$gallerypath.'/'.$thumb_folder.'/'.$thumb_prefix.$filename);
						@unlink(WINABSPATH.$gallerypath.'/'.$filename);
					}
				}
				// delete folder
					@rmdir(WINABSPATH.$gallerypath.'/'.$thumb_folder);
					@rmdir(WINABSPATH.$gallerypath);
			}
		}

		$delete_pic = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE galleryid = $act_gid");
		$delete_galllery = $wpdb->query("DELETE FROM $wpdb->nggallery WHERE gid = $act_gid");
		
		if($delete_galllery)
			$messagetext = '<font color="green">'.__('Gallery','nggallery').' \''.$act_gid.'\' '.__('deleted successfully','nggallery').'</font>';
	 	$mode = 'main'; // show mainpage
	}

	if ($mode == 'delpic') {
	// Delete a picture
		check_admin_referer('ngg_delpicture');
		$filename = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$act_pid' ");
		if ($filename) {
			$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
			if ($gallerypath){
				$thumb_folder = nggallery::get_thumbnail_folder($gallerypath, FALSE);
				$thumb_prefix = nggallery::get_thumbnail_prefix($gallerypath, FALSE);
				if ($ngg_options['deleteImg']) {
					@unlink(WINABSPATH.$gallerypath.'/'.$thumb_folder.'/'.$thumb_prefix.$filename);
					@unlink(WINABSPATH.$gallerypath.'/'.$filename);
				}
			}		
			$delete_pic = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE pid = $act_pid");
		}
		if($delete_pic)
			$messagetext = '<font color="green">'.__('Picture','nggallery').' \''.$act_pid.'\' '.__('deleted successfully','nggallery').'</font>';
	 	$mode = 'edit'; // show pictures

	}
	
	if (isset ($_POST['bulkaction']) && isset ($_POST['doaction']))  {
		// do bulk update
		
		check_admin_referer('ngg_updategallery');
		
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		$imageslist = array();
		
		if ( is_array($_POST['doaction']) ) {
			foreach ( $_POST['doaction'] as $imageID ) {
				$imageslist[] = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
			}
		}
		
		switch ($_POST['bulkaction']) {
			case 0;
			// No action
				break;
			case 1:
			// Set watermark
				nggAdmin::generateWatermark(WINABSPATH.$gallerypath,$imageslist);
				nggallery::show_message(__('Watermark successfully added',"nggallery"));
				break;
			case 2:
			// Create new thumbnails
				nggAdmin::generateThumbnail(WINABSPATH.$gallerypath,$imageslist);
				nggallery::show_message(__('Thumbnails successfully created. Please refresh your browser cache.',"nggallery"));
				break;
			case 3:
			// Resample images
				nggAdmin::resizeImages(WINABSPATH.$gallerypath,$imageslist);
				nggallery::show_message(__('Images successfully resized',"nggallery"));
				break;
			case 4:
			// Delete images
				if ( is_array($_POST['doaction']) ) {
				if ($gallerypath){
					$thumb_folder = nggallery::get_thumbnail_folder($gallerypath, FALSE);
					$thumb_prefix = nggallery::get_thumbnail_prefix($gallerypath, FALSE);
					foreach ( $_POST['doaction'] as $imageID ) {
						$filename = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
						if ($ngg_options['deleteImg']) {
							@unlink(WINABSPATH.$gallerypath.'/'.$thumb_folder.'/'.$thumb_prefix.$filename);
							@unlink(WINABSPATH.$gallerypath.'/'.$filename);	
						} 
						$delete_pic = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE pid = $imageID");
					}
				}		
				if($delete_pic)
					nggallery::show_message(__('Pictures deleted successfully ',"nggallery"));
				}
				break;
			case 8:
			// Import Metadata
				nggAdmin::import_MetaData($_POST['doaction']);
				nggallery::show_message(__('Import metadata finished',"nggallery"));
				break;
		}
	}
	
	if (isset ($_POST['TB_tagaction']) && isset ($_POST['TB_doaction']))  {
		// do tags update

		check_admin_referer('ngg_form-tags');

		// get the images list		
		$pic_ids = explode(",", $_POST['TB_imagelist']);
		$taglist = explode(",", $_POST['taglist']);
		$taglist = array_map('trim', $taglist);
		$slugarray = array_map('sanitize_title', $taglist);

		// load tag list
		$nggTags = new ngg_Tags();
		
		foreach($pic_ids as $pic_id) {
			
			// which action should be performed ?
			switch ($_POST['TB_tagaction']) {
				case 0;
				// No action
					break;
				case 7:
				// Overwrite tags
					// remove all binding
					$wpdb->query("DELETE FROM $wpdb->nggpic2tags WHERE picid = '$pic_id'");
					// and add now the new tags
				case 5:
				// Add / append tags
					foreach($taglist as $tag) {
						// get the tag id
						$tagid = $nggTags->add_tag($tag);
						if ( $tagid )
							$nggTags->add_relationship($pic_id, $tagid);
					}
					break;
				case 6:
				// Delete tags
					$nggTags->remove_relationship($pic_id, $slugarray, false);
					break;
			}
		}
		
		// remove not longer used tag
		$nggTags->remove_unused_tags();

		nggallery::show_message(__('Tags changed',"nggallery"));
	}

	if (isset ($_POST['updatepictures']))  {
	// Update pictures	
	
		check_admin_referer('ngg_updategallery');
		
		$gallery_title   = attribute_escape($_POST['title']);
		$gallery_path    = attribute_escape($_POST['path']);
		$gallery_desc    = attribute_escape($_POST['gallerydesc']);
		$gallery_pageid  = attribute_escape($_POST['pageid']);
		$gallery_preview = attribute_escape($_POST['previewpic']);
		
		$result = $wpdb->query("UPDATE $wpdb->nggallery SET title= '$gallery_title', path= '$gallery_path', galdesc = '$gallery_desc', pageid = '$gallery_pageid', previewpic = '$gallery_preview' WHERE gid = '$act_gid'");
		if ($showTags)
			$result = ngg_update_tags(attribute_escape($_POST['tags']));			
		else 
			$result = ngg_update_pictures(attribute_escape($_POST['description']), attribute_escape($_POST['alttext']), attribute_escape($_POST['exclude']), $act_gid );

		nggallery::show_message(__('Update successful',"nggallery"));
	}

	if (isset ($_POST['scanfolder']))  {
	// Rescan folder
		check_admin_referer('ngg_updategallery');
	
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		nggAdmin::import_gallery($gallerypath);
	}

	if (isset ($_POST['addnewpage']))  {
	// Add a new page
	
		check_admin_referer('ngg_updategallery');
		
		$parent_id      = attribute_escape($_POST['parent_id']);
		$gallery_title  = attribute_escape($_POST['title']);
		$gallery_name   = $wpdb->get_var("SELECT name FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		
		// Create a WP page
		global $user_ID;

		$page['post_type']    = 'page';
		$page['post_content'] = '[gallery='.$act_gid.']';
		$page['post_parent']  = $parent_id;
		$page['post_author']  = $user_ID;
		$page['post_status']  = 'publish';
		$page['post_title']   = $gallery_title == '' ? $gallery_name : $gallery_title;

		$gallery_pageid = wp_insert_post ($page);
		if ($gallery_pageid != 0) {
			$result = $wpdb->query("UPDATE $wpdb->nggallery SET title= '$gallery_title', pageid = '$gallery_pageid' WHERE gid = '$act_gid'");
			$messagetext = '<font color="green">'.__('New gallery page ID','nggallery'). ' ' . $pageid . ' -> <strong>' . $gallery_title . '</strong> ' .__('created','nggallery').'</font>';
		}
	}
	
	if (isset ($_POST['backToGallery'])) {
		$mode = 'edit';
	}
	
	// show sort order
	if ( ($mode == 'sort') || isset ($_POST['sortGallery'])) {
		$mode = 'sort';
		include_once (dirname (__FILE__). '/sort.php');
		nggallery_sortorder($act_gid);
		return;
	}
	
	// message windows
	if(!empty($messagetext)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$messagetext.'</p></div>'; }

	if (($mode == '') or ($mode == "main"))
		nggallery_manage_gallery_main();
	
	if ($mode == 'edit')
		nggallery_picturelist($hideThumbs,$showTags);
	
}//nggallery_admin_manage_gallery

function nggallery_manage_gallery_main() {
// *** show main gallery list

	global $wpdb;
	
	?>
	<script type="text/javascript"> var tb_pathToImage = '<?php echo NGGALLERY_URLPATH ?>thickbox/loadingAnimationv3.gif';</script>
	<div class="wrap">
		<h2><?php _e('Gallery Overview', 'nggallery') ?></h2>
		<br style="clear: both;"/>
		<table class="widefat">
			<thead>
			<tr>
				<th scope="col" ><?php _e('ID') ?></th>
				<th scope="col" ><?php _e('Gallery name', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Title', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Description', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Page ID', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Quantity', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Action'); ?></th>
			</tr>
			</thead>
			<tbody>
<?php			
$gallerylist = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY gid ASC");
if($gallerylist) {
	foreach($gallerylist as $gallery) {
		$class = ( $class == 'class="alternate"' ) ? '' : 'class="alternate"';
		$gid = $gallery->gid;
		$counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures WHERE galleryid = '$gid'");
		?>
		<tr id="gallery-<?php echo $gid ?>" <?php echo $class; ?> >
			<th scope="row"><?php echo $gid; ?></th>
			<td><?php echo $gallery->name; ?></td>
			<td><?php echo $gallery->title; ?></td>
			<td><?php echo $gallery->galdesc; ?></td>
			<td><?php echo $gallery->pageid; ?></td>
			<td><?php echo $counter; ?></td>
			<td><a href="<?php echo wp_nonce_url("admin.php?page=nggallery-manage-gallery&amp;mode=edit&amp;gid=".$gid, 'ngg_editgallery')?>" class='edit'> <?php _e('Edit') ?></a>
			| <a href="<?php echo wp_nonce_url("admin.php?page=nggallery-manage-gallery&amp;mode=delete&amp;gid=".$gid, 'ngg_editgallery')?>" class="delete" onclick="javascript:check=confirm( '<?php _e("Delete this gallery ?",'nggallery')?>');if(check==false) return false;"><?php _e('Delete') ?></a></td>
		</tr>
		<?php
	}
} else {
	echo '<tr><td colspan="7" align="center"><strong>'.__('No entries found','nggallery').'</strong></td></tr>';
}
?>			
			</tbody>
		</table>
	</div>
<?php
} //nggallery_manage_gallery_main

function nggallery_picturelist($hideThumbs = false,$showTags = false) {
// *** show picture list
	global $wpdb;
	
	// GET variables
	$act_gid = trim(attribute_escape($_GET['gid']));
	
	// get the options
	$ngg_options=get_option('ngg_options');	
	
	//TODO:A unique gallery call must provide me with this information, like $gallery  = new nggGallery($id);
	
	// get gallery values
	$act_gallery = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$act_gid' ");

	// set gallery url
	$act_gallery_url 	= get_option ('siteurl')."/".$act_gallery->path."/";
	$act_thumbnail_url 	= get_option ('siteurl')."/".$act_gallery->path.nggallery::get_thumbnail_folder($act_gallery->path, FALSE);
	$act_thumb_prefix   = nggallery::get_thumbnail_prefix($act_gallery->path, FALSE);

?>

<script type="text/javascript"> 
	function enterTags(form) {

		var elementlist = "";
		for (i = 0, n = form.elements.length; i < n; i++) {
			if(form.elements[i].type == "checkbox") {
				if(form.elements[i].name == "doaction[]")
					if(form.elements[i].checked == true)
						if (elementlist == "")
							elementlist = form.elements[i].value
						else
							elementlist += "," + form.elements[i].value ;
			}
		}
		jQuery("#TB_tagaction").val(jQuery("#bulkaction").val());
		jQuery("#TB_imagelist").val(elementlist);
		// console.log (jQuery("#TB_imagelist").val());
		jQuery.tb_show("", "#TB_inline?width=640&height=110&inlineId=tags&modal=true", false);
	}
</script>
<script type="text/javascript"> var tb_pathToImage = '<?php echo NGGALLERY_URLPATH ?>thickbox/loadingAnimationv3.gif';</script>
<script type="text/javascript">
<!--
function checkAll(form)
{
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]") {
				if(form.elements[i].checked == true)
					form.elements[i].checked = false;
				else
					form.elements[i].checked = true;
			}
		}
	}
}

function getNumChecked(form)
{
	var num = 0;
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]")
				if(form.elements[i].checked == true)
					num++;
		}
	}
	return num;
}
//-->
</script>
<div class="wrap">
<h2><?php _e('Gallery', 'nggallery') ?> : <?php echo $act_gallery->title; ?></h2>
<p id="ngg-inlinebutton">
	<input type="submit" class="button-secondary" title="<?php _e('Edit gallery', 'nggallery') ?>" value="<?php _e('Edit gallery', 'nggallery') ?>" onclick="jQuery('#manage-gallery').toggle()" />
</p>

<form id="updategallery" method="POST" action="<?php echo 'admin.php?page=nggallery-manage-gallery&amp;mode=edit&amp;gid='.$act_gid ?>" accept-charset="utf-8">
<?php wp_nonce_field('ngg_updategallery') ?>

<?php if ($showTags) { ?><input type="hidden" name="showTags" value="true" /><?php } ?>
<?php if ($hideThumbs) { ?><input type="hidden" name="hideThumbs" value="true" /><?php } ?>
<div id="manage-gallery" style="display:none;">
	<table class="form-table" >
		<tr>
			<th align="left"><?php _e('Title') ?>:</th>
			<th align="left"><input type="text" size="50" name="title" value="<?php echo $act_gallery->title; ?>"  /></th>
			<th align="right"><?php _e('Page Link to', 'nggallery') ?>:</th>
			<th align="left">
			<select name="pageid" style="width:95%">
				<option value="0" ><?php _e('Not linked', 'nggallery') ?></option>
			<?php
				$pageids = get_all_page_ids();
				foreach($pageids as $pageid) {
					$post= get_post($pageid); 				
					if ($pageid == $act_gallery->pageid) $selected = 'selected="selected" ';
					else $selected = '';
					echo '<option value="'.$pageid.'" '.$selected.'>'.$post->post_title.'</option>'."\n";
				}
			?>
			</select>
			</th>
		</tr>
		<tr>
			<th align="left"><?php _e('Description') ?>:</th> 
			<th align="left"><textarea name="gallerydesc" cols="30" rows="3" style="width: 95%"  ><?php echo $act_gallery->galdesc; ?></textarea></th>
			<th align="right"><?php _e('Preview image', 'nggallery') ?>:</th>
			<th align="left">
				<select name="previewpic" >
					<option value="0" ><?php _e('No Picture', 'nggallery') ?></option>
					<?php
						$picturelist = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = '$act_gid' ORDER BY $ngg_options[galSort] $ngg_options[galSortDir]");
						if(is_array($picturelist)) {
							foreach($picturelist as $picture) {
								if ($picture->pid == $act_gallery->previewpic) $selected = 'selected="selected" ';
								else $selected = '';
								echo '<option value="'.$picture->pid.'" '.$selected.'>'.$picture->filename.'</option>'."\n";
							}
						}
					?>
				</select>
			</th>
		</tr>
		<tr>
			<th align="left"><?php _e('Path', 'nggallery') ?>:</th> 
			<th align="left"><input <?php if (IS_WPMU) echo 'readonly = "readonly"'; ?> type="text" size="50" name="path" value="<?php echo $act_gallery->path; ?>"  /></th>
		
			<th align="right"><?php _e('Create new page', 'nggallery') ?>:</th>
			<th align="left"> 
			<select name="parent_id" style="width:95%">
				<option value="0"><?php _e ('Main page (No parent)', 'nggallery'); ?></option>
				<?php parent_dropdown ($group->page_id); ?>
			</select>
			<input type="submit" name="addnewpage" value="<?php _e ('Add page', 'nggallery'); ?>" id="group"/>
			</th>
		</tr>

	</table>
	
	<div class="submit">
		<input type="submit" name="scanfolder" value="<?php _e("Scan Folder for new images",'nggallery')?> " />
		<input type="submit" name="updatepictures" value="<?php _e("Save Changes",'nggallery')?> &raquo;" />
	</div>
	
</div>
<br style="clear: both;"/>
<?php wp_nonce_field('ngg_updategallery') ?>
<div class="tablenav ngg-tablenav">
	<div style="float: left;">
	<select id="bulkaction" name="bulkaction">
		<option value="0" ><?php _e("No action",'nggallery')?></option>
	<?php if (!$showTags) { ?>
		<option value="1" ><?php _e("Set watermark",'nggallery')?></option>
		<option value="2" ><?php _e("Create new thumbnails",'nggallery')?></option>
		<option value="3" ><?php _e("Resize images",'nggallery')?></option>
		<option value="4" ><?php _e("Delete images",'nggallery')?></option>
		<option value="8" ><?php _e("Import metadata",'nggallery')?></option>
	<?php } else { ?>	
		<option value="5" ><?php _e("Add tags",'nggallery')?></option>
		<option value="6" ><?php _e("Delete tags",'nggallery')?></option>
		<option value="7" ><?php _e("Overwrite tags",'nggallery')?></option>
	<?php } ?>	
	</select>
	
	<?php if (!$showTags) { ?> <input class="button-secondary" type="submit" name="doaction" value="<?php _e("OK",'nggallery')?>" onclick="var numchecked = getNumChecked(document.getElementById('updategallery')); if(numchecked < 1) { alert('<?php echo js_escape(__("No images selected",'nggallery')); ?>'); return false } return confirm('<?php echo sprintf(js_escape(__("You are about to start the bulk edit for %s images \n \n 'Cancel' to stop, 'OK' to proceed.",'nggallery')), "' + numchecked + '") ; ?>')" /><?php } else {?>
	<input class="button-secondary" type="submit" name="showThickbox" value="<?php _e("OK",'nggallery')?>" onclick="enterTags(document.getElementById('updategallery')); return false;" /><?php } ?>
	<?php if (!$hideThumbs) { ?> <input class="button-secondary" type="submit" name="togglethumbs" value="<?php _e("Hide thumbnails ",'nggallery')?>" /> <?php } else {?>
	<input class="button-secondary" type="submit" name="togglethumbs" value="<?php _e("Show thumbnails ",'nggallery')?>" /><?php } ?>
	<?php if (!$showTags) { ?><input class="button-secondary" type="submit" name="toggletags" value="<?php _e("Show tags",'nggallery')?>" /> <?php } else {?>
	<input class="button-secondary" type="submit" name="toggletags" value="<?php _e("Hide tags",'nggallery')?>" /><?php } ?>
	<?php if ($ngg_options['galSort'] == "sortorder") { ?>
	<input class="button-secondary" type="submit" name="sortGallery" value="<?php _e("Sort gallery",'nggallery')?>" />
	<?php } ?>
	</div>
	<span style="float:right;"><input type="submit" name="updatepictures" class="button-secondary"  value="<?php _e("Save Changes",'nggallery')?> &raquo;" /></span>
</div>
<br style="clear: both;"/>
<table id="ngg-listimages" class="widefat" >
	<thead>
	<tr>
		<th scope="col" class="check-column" ><input name="checkall" type="checkbox" onclick="checkAll(document.getElementById('updategallery'));" /></th>
		<th scope="col" style="text-align: center"><?php _e('ID') ?></th>
		<th scope="col" style="text-align: center"><?php _e('File name', 'nggallery') ?></th>
		<?php if (!$hideThumbs) { ?>
		<th scope="col" style="text-align: center"><?php _e('Thumbnail', 'nggallery') ?></th>
		<?php } ?>
		<?php if (!$showTags) { ?>
		<th scope="col" style="text-align: center"><?php _e('Description', 'nggallery') ?></th>
		<th scope="col" style="text-align: center"><?php _e('Alt &amp; Title Text', 'nggallery') ?></th>
		<th scope="col" style="text-align: center"><?php _e('exclude', 'nggallery') ?></th>
		<?php } else {?>
		<th scope="col" style="width:70%"><?php _e('Tags (comma separated list)', 'nggallery') ?></th>
		<?php } ?>
		<th scope="col" colspan="3" style="text-align: center"><?php _e('Action') ?></th>
	</tr>
	</thead>
	<tbody>
<?php
// load tags
if ($showTags) $nggTags = new ngg_Tags();
if($picturelist) {
	foreach($picturelist as $picture) {
		//TODO: Ajax delete version , looks better
		//TODO: Use effect for inactive pic : style="filter:alpha(opacity=30); -moz-opacity:0.3"

		$pid     = $picture->pid;
		$class   = ( $class == 'class="alternate"' ) ? '' : 'class="alternate"';	
		$exclude = ( $picture->exclude ) ? 'checked="checked"' : '';

		?>
		<tr id="picture-<?php echo $pid ?>" <?php echo $class ?> style="text-align:center">
			<td class="check-column"><input name="doaction[]" type="checkbox" value="<?php echo $pid ?>" /></td>
			<th scope="row" style="text-align: center"><?php echo $pid ?></th>
			<td class="media-icon" ><?php echo $picture->filename ?></td>
			<?php if (!$hideThumbs) { ?>
			<td><img class="thumb" src="<?php echo $act_thumbnail_url.$act_thumb_prefix.$picture->filename ?>" /></td>
			<?php } ?>
			<?php if (!$showTags) { ?>
			<td><textarea name="description[<?php echo $pid ?>]" class="textarea1" cols="30" rows="3" ><?php echo stripslashes($picture->description) ?></textarea></td>
			<td><input name="alttext[<?php echo $pid ?>]" type="text" size="20"   value="<?php echo stripslashes($picture->alttext) ?>" /></td>
			<td><input name="exclude[<?php echo $pid ?>]" type="checkbox" value="1" <?php echo $exclude ?> /></td>
			<?php } else {?>
			<td ><input name="tags[<?php echo $pid ?>]" type="text" style="width:95%" value="<?php echo $nggTags->get_tags_from_image($pid); ?>" /></td>
			<?php } ?>
			<td><a href="<?php echo $act_gallery_url.$picture->filename ?>" class="thickbox" title="<?php echo $picture->alttext ?>" ><?php _e('View') ?></a></td>
			<td><a href="<?php echo NGGALLERY_URLPATH."admin/showmeta.php?id=".$pid ?>" class="thickbox" title="<?php _e("Show Meta data",'nggallery')?>" ><?php _e('Meta') ?></a></td>
			<td><a href="<?php echo wp_nonce_url("admin.php?page=nggallery-manage-gallery&amp;mode=delpic&amp;gid=".$act_gid."&amp;pid=".$pid, 'ngg_delpicture')?>" class="delete" onclick="javascript:check=confirm( '<?php _e("Delete this file ?",'nggallery')?>');if(check==false) return false;" ><?php _e('Delete') ?></a></td>
		</tr>
		<?php
	}
} else {
	echo '<tr><td colspan="8" align="center"><strong>'.__('No entries found','nggallery').'</strong></td></tr>';
}
?>
	
		</tbody>
	</table>
	<p class="submit"><input type="submit" name="updatepictures" value="<?php _e("Save Changes",'nggallery')?> &raquo;" /></p>
	</form>	
	<br class="clear"/>
	</div><!-- /#wrap -->

	<!-- #entertags -->
	<div id="tags" style="display: none;" >
		<form id="form-tags" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_form-tags') ?>
		<?php if ($showTags) { ?><input type="hidden" name="showTags" value="true" /><?php } ?>
		<?php if ($hideThumbs) { ?><input type="hidden" name="hideThumbs" value="true" /><?php } ?>
		<input type="hidden" id="TB_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="TB_tagaction" name="TB_tagaction" value="" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
		  	<tr>
		    	<th><?php _e("Enter the tags",'nggallery')?> : <input name="taglist" type="text" style="width:99%" value="" /></td>
		  	</tr>
		  	<tr align="right">
		    	<td class="submit"><input type="submit" name="TB_doaction" value="<?php _e("OK",'nggallery')?>" onclick="var numchecked = getNumChecked(document.getElementById('updategallery')); if(numchecked < 1) { alert('<?php echo js_escape(__("No images selected",'nggallery')); ?>'); jQuery.tb_remove(); return false } return confirm('<?php echo sprintf(js_escape(__("You are about to start the bulk edit for %s images \n \n 'Cancel' to stop, 'OK' to proceed.",'nggallery')), "' + numchecked + '") ; ?>')" />&nbsp;<input type="reset" value="&nbsp;<?php _e("Cancel",'nggallery')?>&nbsp;" onclick="jQuery.tb_remove()"/></td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#entertags -->

	<?php
			
} //nggallery_pciturelist

/**************************************************************************/
function ngg_update_pictures( $nggdescription, $nggalttext, $nggexclude, $nggalleryid ) {
// update all pictures
	
	global $wpdb;
	
	if (is_array($nggdescription)) {
		foreach($nggdescription as $key=>$value) {
			$desc = $wpdb->escape($value);
			$result=$wpdb->query( "UPDATE $wpdb->nggpictures SET description = '$desc' WHERE pid = $key");
			if($result) $update_ok = $result;
		}
	}
	if (is_array($nggalttext)){
		foreach($nggalttext as $key=>$value) {
			$alttext = $wpdb->escape($value);
			$result=$wpdb->query( "UPDATE $wpdb->nggpictures SET alttext = '$alttext' WHERE pid = $key");
			if($result) $update_ok = $result;
		}
	}
	
	$nggpictures = $wpdb->get_results("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '$nggalleryid'");

	if (is_array($nggpictures)){
		foreach($nggpictures as $picture){
			if (is_array($nggexclude)){
				if (array_key_exists($picture->pid, $nggexclude)) {
					$result=$wpdb->query("UPDATE $wpdb->nggpictures SET exclude = 1 WHERE pid = '$picture->pid'");
					if($result) $update_ok = $result;
				} 
				else {
					$result=$wpdb->query("UPDATE $wpdb->nggpictures SET exclude = 0 WHERE pid = '$picture->pid'");
					if($result) $update_ok = $result;
				}
			} else {
				$result=$wpdb->query("UPDATE $wpdb->nggpictures SET exclude = 0 WHERE pid = '$picture->pid'");
				if($result) $update_ok = $result;
			}   
		}
	}
	
	return $update_ok;
}

/**************************************************************************/
function ngg_update_tags( $taglist ) {
// update all tags
//TODO:Move to class nggTags
	
	global $wpdb;
	
	// load tag list
	$nggTags = new ngg_Tags();
	
	// the taglist contain as key the pic_id
	if (is_array($taglist)){
		foreach($taglist as $key=>$value) {
			
			// First, get all of the original tags
			$nggTags->get_tags_from_image($key);
			
			$tags = explode(",", $value);
			$new_slugarray = array();
			
			foreach($tags as $tag) {
				if ( !empty($tag) ) {
					// create the slug
					$tag = trim($tag);
					$slug = sanitize_title($tag);
					// do not proceed empty slugs
					if ( !empty($slug) ) {
						$new_slugarray[] = $slug;
						// look if we have a new tag in POST
						if (!in_array($slug, $nggTags->img_slugs )) {
							$tagid = $nggTags->add_tag($tag);
							$nggTags->add_relationship($key, $tagid);
							// add now to image list 
							$nggTags->img_slugs[] = $slug;
						}
					}
				}
			}
								
			//do we need to remove some tags?
			$delete_tags = array_diff($nggTags->img_slugs, $new_slugarray);
			$nggTags->remove_relationship($key, $delete_tags, TRUE);
		}
		
		// remove not longer used tag
		$nggTags->remove_unused_tags();
	}
	
	return;
}

?>