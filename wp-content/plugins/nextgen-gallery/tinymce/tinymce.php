<?php

/**
 * @title TinyMCE Button Integration
 * @author Alex Rabe
 */

// Load the Script for the Button
function insert_nextgen_script() {	
 
 	//TODO: Do with WP2.1 Script Loader
 	// Thanks for this idea to www.jovelstefan.de
	echo "\n"."
	<script type='text/javascript'> 
		function ngg_buttonscript()	{ 
		if(window.tinyMCE) {

			var template = new Array();
	
			template['file'] = '".NGGALLERY_URLPATH."tinymce/window.php';
			template['width'] = 360;
			template['height'] = 210;
	
			args = {
				resizable : 'no',
				scrollbars : 'no',
				inline : 'yes'
			};
	
			tinyMCE.openWindow(template, args);
			return true;
		} 
	} 
	</script>"; 
	return;
}

function ngg_addbuttons() {
 
	global $wp_db_version;

	// Don't bother doing this stuff if the current user lacks permissions
	if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;
	
	// Check for NextGEN capability
	if ( !current_user_can('NextGEN Use TinyMCE') ) return;
	 
	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
	 
	// add the button for wp21 in a new way
		add_filter("mce_plugins", "nextgen_button_plugin", 5);
		add_filter('mce_buttons', 'nextgen_button', 5);
		add_action('tinymce_before_init','nextgen_button_script');
		}
}

// used to insert button in wordpress 2.1x editor
function nextgen_button($buttons) {

	array_push($buttons, "separator", "NextGEN");
	return $buttons;

}

// Tell TinyMCE that there is a plugin (wp2.1)
function nextgen_button_plugin($plugins) {    

	array_push($plugins, "-NextGEN");    
	return $plugins;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.1)
function nextgen_button_script() {	
 
	echo 'tinyMCE.loadPlugin("NextGEN", "'.NGGALLERY_URLPATH.'tinymce/");' . "\n"; 
	return;
}

// init process for button control
add_action('init', 'ngg_addbuttons');
add_action('edit_page_form', 'insert_nextgen_script');
add_action('edit_form_advanced', 'insert_nextgen_script');

?>