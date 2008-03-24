<?php  
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function nggallery_admin_import()  {

	global $wpdb;
	
   	$ngg_mygallery					= $wpdb->prefix . 'mygallery';
	$ngg_mygprelation				= $wpdb->prefix . 'mygprelation';
	$ngg_mypictures					= $wpdb->prefix . 'mypictures';
	
	// GET variables
	$gid = trim(attribute_escape($_GET['gid']));
	$mode = trim(attribute_escape($_GET['mode']));
		
	// do the import
	if ($mode == 'import') {
		
		// load path setting
		$mygoptions = get_option('mygalleryoptions');
		$mygbasepath = $mygoptions['gallerybasepath'];
		
		$gallery = $wpdb->get_row("SELECT * FROM $ngg_mygallery WHERE id = '$gid'");
		if ($gallery) {
			$galleryfolder = $mygbasepath . $gallery->name;
			$result = $wpdb->query("INSERT INTO $wpdb->nggallery (name, path, title, galdesc, pageid) VALUES ('$gallery->name', '$galleryfolder', '$gallery->longname', '$gallery->galdescrip' ,'$gallery->pageid') ");
			if ($result) {
				$newgid = $wpdb->insert_id;  // get new index_id
				$pictures = $wpdb->get_results("SELECT * FROM $ngg_mypictures, $ngg_mygprelation WHERE $ngg_mygprelation.gid = '$gid' AND $ngg_mypictures.id = $ngg_mygprelation.pid ORDER BY $ngg_mygprelation.pid");
				// import each picture
				if (is_array($pictures)) {
					foreach ($pictures as $picture) {
						$wpdb->query("INSERT INTO $wpdb->nggpictures (galleryid, filename, description, alttext, exclude) VALUES ('$newgid', '$picture->picturepath', '$picture->description', '$picture->alttitle' ,'$picture->picexclude') ");
					}
					nggallery::show_message(__('Gallery ',"nggallery"). $gallery->name . __(' : Import successfull',"nggallery"));	
				}
			} else {
				nggallery::show_error(__('Database error. Could not add gallery!',"nggallery")); 
			}
		}
	}
	
	?>
	<div class="wrap">
		<h2><?php _e('myGallery Import', 'nggallery') ?></h2>
		<table id="the-list-x" width="100%" cellspacing="3" cellpadding="3" >
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
$gallerylist = $wpdb->get_results("SELECT * FROM $ngg_mygallery ORDER BY id ASC");
if($gallerylist) {
	foreach($gallerylist as $gallery) {
		$class = ( $class == 'class="alternate"' ) ? '' : 'class="alternate"';
		$gid = $gallery->id;
		$counter = $wpdb->get_var("SELECT COUNT(*) FROM $ngg_mygprelation WHERE gid = '$gid'");
		?>
		<tr id="gallery-<?php echo $gid ?>" <?php echo $class; ?> style="text-align:center">
			<th scope="row" style="text-align: center"><?php echo $gid; ?></th>
			<td><?php echo $gallery->name; ?></td>
			<td><?php echo $gallery->longname; ?></td>
			<td><?php echo $gallery->galdescrip; ?></td>
			<td><?php echo $gallery->pageid; ?></td>
			<td><?php echo $counter; ?></td>
			<td><a href="admin.php?page=nggallery-import&amp;mode=import&amp;gid=<?php echo $gid; ?>" class="edit" onclick="javascript:check=confirm( '<?php _e("Import this gallery ?",'nggallery')?>');if(check==false) return false;"><?php _e('Import','nggallery') ?></a></td>
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

}

?>