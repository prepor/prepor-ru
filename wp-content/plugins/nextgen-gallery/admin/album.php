<?php 

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

global $wpdb;

function nggallery_admin_manage_album()  {
	global $wpdb;
	
	$albumID = (int) $_POST['act_album'];
	
	if ($_POST['update']){
		
		check_admin_referer('ngg_album');
		
		if ($_POST['newalbum']){ 
			$newalbum = attribute_escape($_POST['newalbum']);
			$result = $wpdb->query(" INSERT INTO $wpdb->nggalbum (name, sortorder) VALUES ('$newalbum','0')");
			if ($result) $messagetext = '<font color="green">'.__('Update Successfully','nggallery').'</font>';
		} 
		
		if ($albumID > 0){
			
			// get variable galleryContainer 
			parse_str($_POST['sortorder']); 
			if (is_array($galleryContainer)){ 
				$sortorder = array();
				foreach($galleryContainer as $gallery) {		
					$gid = substr($gallery, 4); // get id from "gid-x"
					$sortorder[] = $gid;
				}
				$serial_sort = serialize($sortorder); 
				$wpdb->query("UPDATE $wpdb->nggalbum SET sortorder = '$serial_sort' WHERE id = $albumID ");
			} else {
				$wpdb->query("UPDATE $wpdb->nggalbum SET sortorder = '0' WHERE id = $albumID ");
			}
			$messagetext = '<font color="green">'.__('Update Successfully','nggallery').'</font>';
		} 
	}
	
	if ($_POST['delete']){
		check_admin_referer('ngg_album');
		
		$result = $wpdb->query("DELETE FROM $wpdb->nggalbum WHERE id = '$albumID' ");
		if ($result) $messagetext = '<font color="green">'.__('Album deleted','nggallery').'</font>';
	}
	
	// message windows
	if(!empty($messagetext)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$messagetext.'</p></div>'; }
?>
<style type="text/css" media="all">@import "<?php echo NGGALLERY_URLPATH ?>css/nggallery.css";</style>
<style type="text/css" media="all">@import "<?php echo NGGALLERY_URLPATH ?>admin/css/nggadmin.css";</style>
<script type="text/javascript">


jQuery(document).ready(
	function()
	{

		jQuery('div.groupWrapper').Sortable(
			{
				accept: 'groupItem',
				helperclass: 'sort_placeholder',
				opacity: 0.7,
				tolerance: 'intersect'
			}
		);
		
		jQuery('a.min').bind('click', toggleContent);

		// Hide used galleries
		jQuery('a#toggle_used').click(function()
			{
				jQuery('#selectContainer div.inUse').toggle();
				return false;
			}
		);	
			
		// Maximize All Portlets (whole site, no differentiation)
		jQuery('a#all_max').click(function()
			{
				jQuery('div.itemContent:hidden').show();
				return false;
			}
		);

		// Minimize All Portlets (whole site, no differentiation)
		jQuery('a#all_min').click(function()
			{
				jQuery('div.itemContent:visible').hide();
				return false;
			}
		);
	   // Auto Minimize if more than 4 (whole site, no differentiation)
	   if(jQuery('a.min').length > 4)
	   {
	   		jQuery('a.min').html('[+]');
	   		jQuery('div.itemContent:visible').hide();
	   		jQuery('#selectContainer div.inUse').toggle();
	   };
	}
);

var toggleContent = function(e)
{
	var targetContent = jQuery('div.itemContent', this.parentNode.parentNode);
	if (targetContent.css('display') == 'none') {
		targetContent.slideDown(300);
		jQuery(this).html('[-]');
	} else {
		targetContent.slideUp(300);
		jQuery(this).html('[+]');
	}
	return false;
}

function ngg_serialize(s)
{
	serial = jQuery.SortSerialize(s);
	jQuery('input[@name=sortorder]').val(serial.hash);
}
</script>
<div class="wrap album" id="wrap" >
	<h2><?php _e('Manage Albums', 'nggallery') ?></h2>
	<form id="selectalbum" method="POST" onsubmit="ngg_serialize('galleryContainer')" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_album') ?>
		<input name="sortorder" type="hidden" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
			<tr>
				<th align="right"><?php _e('Select album', 'nggallery') ?></th>  
				<td>
					<select id="act_album" name="act_album" onchange="this.form.submit();">
						<option value="0" ><?php _e('No album selected', 'nggallery') ?></option>
						<?php
							$albumlist = $wpdb->get_results("SELECT * FROM $wpdb->nggalbum ORDER BY id ASC");
							if(is_array($albumlist)) {
								foreach($albumlist as $album) {
									if ($_POST['act_album'] == $album->id) $selected = 'selected="selected" ';
									else $selected = '';
									echo '<option value="'.$album->id.'" '.$selected.'>'.$album->name.'</option>'."\n";
								}
							}
						?>
					</select>
				</td> 
				<th align="right"><?php _e('Add new album', 'nggallery') ?></th>
				<td><input id="newalbum" name="newalbum" value="" /></td>
				<td><p class="submit">
					<?php if ($_POST['act_album'] > 0){ ?>
						<input type="submit" name="delete" class="button delete" value="<?php _e('Delete') ?> &raquo;" onclick="javascript:check=confirm('<?php _e('Delete album ?','nggallery'); ?>');if(check==false) return false;"/>
					<?php } ?>
					<input type="submit" name="update" value="<?php _e('Update') ?> &raquo;" />
				<p></td>
			</tr>
		</table>
		
	</form>
	<p>
	<div style="float:right;">
	  <a href="#" id="toggle_used"><?php _e('[Show all]', 'nggallery') ?></a>
	| <a href="#" id="all_max"><?php _e('[Maximize]', 'nggallery') ?></a>
	| <a href="#" id="all_min"><?php _e('[Minimize]', 'nggallery') ?></a>
	</div>
	<?php _e('After you create and select a album, you can drag and drop a gallery into your album below','nggallery'); ?>
	</p>

	<br class="clear"/>
	
	<div class="container">
		<div id="selectContainer" class="groupWrapper">
		<h3><?php _e('Select Gallery', 'nggallery') ?></h3>
		<?php
		$gallerylist = $wpdb->get_results("SELECT gid FROM $wpdb->nggallery");
		
		//TODO:Code MUST be optimized, how to flag a used galley better ?
		$used_list = getallusedgalleries();
		
		if(is_array($gallerylist)) {
			if ( ($_POST['act_album'] == 0) or (!isset($_POST['act_album'])) ) {
				foreach($gallerylist as $gallery) {
					if (in_array($gallery->gid,$used_list))
						getgallerycontainer($gallery->gid,true);
					else
						getgallerycontainer($gallery->gid,false);
				}
			} else {
				$act_album = $_POST['act_album'];
				$sortorder = $wpdb->get_var("SELECT sortorder FROM $wpdb->nggalbum WHERE id = '$act_album'");
				$sort_array = unserialize($sortorder);
				// if something went wrong, initialize to empty array
				if (!is_array($sort_array)) $sort_array = array();
				foreach($gallerylist as $gallery) {
					if (!in_array($gallery->gid, $sort_array)) {
						if (in_array($gallery->gid,$used_list))
							getgallerycontainer($gallery->gid,true);
						else
							getgallerycontainer($gallery->gid,false);
					}
				}
			}
		}
		?>
		</div><!-- /#select container -->

		<div id="galleryContainer" class="groupWrapper">
		<?php
			if ($_POST['act_album'] > 0){			
				$act_album = $_POST['act_album'];
				$album = $wpdb->get_row("SELECT * FROM $wpdb->nggalbum WHERE id = '$act_album'");
				echo '<h3>'.__('Album Page ID', 'nggallery').' '.$album->id.' : '.$album->name.'</h3>'."\n";
				if (!empty($album->sortorder)) {
					$sort_array = unserialize($album->sortorder);
					if (is_array($sort_array)) {
						foreach($sort_array as $galleryid) {
							getgallerycontainer($galleryid,false);
						}
					}
				}
			} 
			else
			{	
				echo '<h3>'.__('No album selected!', 'nggallery').'</h3>';
			}
		?> 
		</div><!-- /#gallery container -->

	</div><!-- /#container -->
</div><!-- /#wrap -->

<?php
}
function getgallerycontainer($galleryid = 0, $used = false) {
	global $wpdb;
	
	$gallery = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryid'");

	if ($gallery) {

		// set image url
		$act_thumbnail_url 	= get_option ('siteurl')."/".$gallery->path.nggallery::get_thumbnail_folder($gallery->path, FALSE);
		$act_thumb_prefix   = nggallery::get_thumbnail_prefix($gallery->path, FALSE);
		
		$post= get_post($gallery->pageid); 	
		$pagename = $post->post_title;	
		$filename = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$gallery->previewpic'");
		if ($filename) $img = '<img src="'.$act_thumbnail_url.$act_thumb_prefix.$filename.'" />';
		else $img = '';
		// add class if it's in use in other albums
		$used = $used ? " inUse" : "";
		echo '<div id="gid-'.$gallery->gid.'" class="groupItem'. $used .'">
				<div class="innerhandle">
					<div class="item_top">
						<a href="#" class="min" title="close">[-]</a>
						ID: '.$gallery->gid.' || Title: '.$gallery->title.'
					</div>
					<div class="itemContent">
						<div class="inlinepicture">'.$img.'</div>
							<p><strong>'.__('ID', 'nggallery').' : </strong>'.$gallery->gid.'</p>
							<p><strong>'.__('Name', 'nggallery').' : </strong>'.$gallery->name.'</p>
							<p><strong>'.__('Title', 'nggallery').' : </strong>'.$gallery->title.'</p>
							<p><strong>'.__('Page', 'nggallery').' : </strong>'.$pagename.'</p>
						</div>
				</div>
			   </div>'; 
	}
}

//get all used galleries form all albums
function getallusedgalleries() {
	global $wpdb;
	
	$albumids = $wpdb->get_col("SELECT id FROM $wpdb->nggalbum");
	$used = array();
	
	if ($albumids) {
		foreach($albumids as $albumid) {
			$sortorder = $wpdb->get_var("SELECT sortorder FROM $wpdb->nggalbum WHERE id = '$albumid'");
			if (!empty($sortorder)) {
				$sort_array = unserialize($sortorder);
				foreach($sort_array as $galleryid) {
					if (!in_array($galleryid,$used))
						$used[] = $galleryid;
				}
			}
		}
	}
	return $used;
}
?>