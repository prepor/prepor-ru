<?php

/**
 * @author Alex Rabe
 * @copyright 2008
 */

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function nggallery_sortorder($galleryID = 0){
	global $wpdb;
	
	if ($galleryID == 0) return;

	$galleryID = (int) $galleryID;
	
	// get the options
	$ngg_options=get_option('ngg_options');	

	if (isset ($_POST['updateSortorder']))  {
		check_admin_referer('ngg_updatesortorder');
		// get variable new sortorder 
		parse_str($_POST['sortorder']);
		if (is_array($sortArray)){ 
			$neworder = array();
			foreach($sortArray as $pid) {		
				$pid = substr($pid, 4); // get id from "pid-x"
				$neworder[] = (int) $pid;
			}
			$sortindex = 1;
			foreach($neworder as $pic_id) {
				$wpdb->query("UPDATE $wpdb->nggpictures SET sortorder = '$sortindex' WHERE pid = $pic_id");
				$sortindex++;
			}
			nggallery::show_message(__('Sort order changed','nggallery'));
		} 
	}
		
	// get gallery values
	$act_gallery = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

	// set gallery url
	$act_gallery_url 	= get_option ('siteurl')."/".$act_gallery->path."/";
	$act_thumbnail_url 	= get_option ('siteurl')."/".$act_gallery->path.nggallery::get_thumbnail_folder($act_gallery->path, FALSE);
	$act_thumb_prefix   = nggallery::get_thumbnail_prefix($act_gallery->path, FALSE);

	$picturelist = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' ORDER BY sortorder ASC");

?>
	<script type='text/javascript' src='<?php echo NGGALLERY_URLPATH ?>admin/js/sorter.js'></script>
	<style type="text/css" media="all">@import "<?php echo NGGALLERY_URLPATH ?>admin/css/nggSorter.css";</style>
	<div class="wrap" style="overflow:hidden;">
		<h2><?php _e('Sort Gallery', 'nggallery') ?></h2>
			<form id="sortGallery" method="POST" action="<?php echo 'admin.php?page=nggallery-manage-gallery&amp;mode=sort&amp;gid='.$galleryID ?>" onsubmit="saveImageOrder()" accept-charset="utf-8">
				<?php wp_nonce_field('ngg_updatesortorder') ?>
				<input name="sortorder" type="hidden" />
				<p class="submit">
					<input class="button" type="submit" name="backToGallery" value="<?php _e('Back to gallery', 'nggallery') ?>" />
					<input class="button" type="submit" name="updateSortorder" onclick="saveImageOrder()" value="<?php _e('Update Sort Order', 'nggallery') ?> &raquo;" />
				</p>
			</form>
		<?php 
		if($picturelist) {
			foreach($picturelist as $picture) {
				?>
					<div class="imageBox" id="pid-<?php echo $picture->pid ?>">
					<div class="imageBox_theImage" style="background-image:url('<?php echo $act_thumbnail_url.$act_thumb_prefix.$picture->filename ?>')"></div>	
					<div class="imageBox_label"><span><?php echo stripslashes($picture->alttext) ?></span></div>
				</div>
				<?php
			}
		}
		?>
		<div id="insertionMarker">
			<img src="<?php echo NGGALLERY_URLPATH ?>admin/images/marker_top.gif"/>
			<img src="<?php echo NGGALLERY_URLPATH ?>admin/images/marker_middle.gif" id="insertionMarkerLine"/>
			<img src="<?php echo NGGALLERY_URLPATH ?>admin/images/marker_bottom.gif"/>
		</div>
		<div id="dragDropContent"></div>
	</div>
	
<?php

}

?>