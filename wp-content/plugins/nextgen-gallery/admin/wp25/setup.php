<?php  
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

	function nggallery_admin_setup()  {	
		global $wpdb;
				
		if (isset($_POST['resetdefault'])) {	
			check_admin_referer('ngg_uninstall');
			
			ngg_default_options();

		 	$messagetext = '<font color="green">'.__('Reset all settings to default parameter','nggallery').'</font>';
		}

		if (isset($_POST['uninstall'])) {	
			
			check_admin_referer('ngg_uninstall');

			$wpdb->query("DROP TABLE $wpdb->nggpictures");
			$wpdb->query("DROP TABLE $wpdb->nggallery");
			$wpdb->query("DROP TABLE $wpdb->nggalbum");
			$wpdb->query("DROP TABLE $wpdb->nggtags");
			$wpdb->query("DROP TABLE $wpdb->nggpic2tags");
		
			delete_option( "ngg_options" );
			delete_option( "ngg_db_version");

			// now remove the capability
			ngg_remove_capability("NextGEN Gallery overview");
			ngg_remove_capability("NextGEN Use TinyMCE");
			ngg_remove_capability("NextGEN Upload images");
			ngg_remove_capability("NextGEN Manage gallery");
			ngg_remove_capability("NextGEN Edit album");
			ngg_remove_capability("NextGEN Change style");
			ngg_remove_capability("NextGEN Change options");
		 	
			$messagetext = '<font color="green">'.__('Uninstall sucessfull ! Now delete the plugin and enjoy your life ! Good luck !','nggallery').'</font>';
		}

	// message windows
	if(!empty($messagetext)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$messagetext.'</p></div>'; }

	?>
	
	<div class="wrap">
	<h2><?php _e('Reset options', 'nggallery') ;?></h2>
		<form name="resetsettings" method="post">
			<?php wp_nonce_field('ngg_uninstall') ?>
			<p><?php _e('Reset all options/settings to the default installation.', 'nggallery') ;?></p>
			<div align="center"><input type="submit" class="button" name="resetdefault" value="<?php _e('Reset settings', 'nggallery') ;?>" onclick="javascript:check=confirm('<?php _e('Reset all options to default settings ?\n\nChoose [Cancel] to Stop, [OK] to proceed.\n','nggallery'); ?>');if(check==false) return false;" /></div>
		</form>
	</div>
	<?php if (!IS_WPMU || wpmu_site_admin() ) : ?>
	<div class="wrap">
	<h2><?php _e('Uninstall plugin tables', 'nggallery') ;?></h2>
		
		<form name="resetsettings" method="post">
		<div class="tablenav">
			<?php wp_nonce_field('ngg_uninstall') ?>
			<p><?php _e('You don\'t like NextGEN Gallery ?', 'nggallery') ;?></p>
			<p><?php _e('No problem, before you deactivate this plugin press the Uninstall Button, because deactivating NextGEN Gallery does not remove any data that may have been created. ', 'nggallery') ;?>
			</div>
			<p><font color="red"><strong><?php _e('WARNING:', 'nggallery') ;?></strong><br />
			<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to backup all the tables first. NextGEN gallery is stored in the tables', 'nggallery') ;?> <strong><?php echo $wpdb->nggpictures; ?></strong>, <strong><?php echo $wpdb->nggalbum; ?></strong>, <strong><?php echo $wpdb->nggtags; ?></strong>, <strong><?php echo $wpdb->nggpic2tags; ?></strong> <?php _e('and', 'nggallery') ;?> <strong><?php echo $wpdb->nggalbum; ?></strong>.</font></p>
			<div align="center">
			<input type="submit" name="uninstall" class="button delete" value="<?php _e('Uninstall plugin', 'nggallery') ?>" onclick="javascript:check=confirm('<?php _e('You are about to Uninstall this plugin from WordPress.\nThis action is not reversible.\n\nChoose [Cancel] to Stop, [OK] to Uninstall.\n','nggallery'); ?>');if(check==false) return false;"/>
			</div>
		</form>
	</div>
	<?php endif; ?>

	<?php
}

function ngg_remove_capability($capability){
	// This function remove the $capability
	$check_order = array("subscriber", "contributor", "author", "editor", "administrator");

	foreach ($check_order as $role) {
		
		$role = get_role($role);
		$role->remove_cap($capability) ;
	}
	
}


?>