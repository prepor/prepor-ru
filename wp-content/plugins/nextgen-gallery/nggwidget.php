<?php
/*
Plugin Name: NextGEN Gallery Widget
Description: Adds a sidebar widget support to your NextGEN Gallery
Author: NextGEN DEV-Team
Version: 1.21
Author URI: http://alexrabe.boelinger.com/
Plugin URI: http://alexrabe.boelinger.com/?page_id=80

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/ 

/**********************************************************/
/* Slidehow widget function
/**********************************************************/
function nggSlideshowWidget($galleryID,$irWidth,$irHeight) {
	
	// Check for NextGEN Gallery
	if ( !function_exists('nggShowSlideshow') )
		return;	
	
	global $wpdb;
	$ngg_options = get_option('ngg_options');
	
	if (empty($irWidth) ) $irWidth = $ngg_options['irWidth'];
	if (empty($irHeight)) $irHeight = $ngg_options['irHeight'];
	
	$out .= "\n".'<div class="ngg-widget-slideshow" id="ngg_widget_slideshow'.$galleryID.'">';
	$out .= '<a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see the slideshow.</div>';
    $out .= "\n\t".'<script type="text/javascript" defer="defer">';
    $out .= "\n\t".'<!--';
	$out .= "\n\t".'//<![CDATA[';
	$out .= "\n\t\t".'var sbsl = new SWFObject("'.NGGALLERY_URLPATH.'imagerotator.swf", "ngg_slideshow'.$galleryID.'", "'.$irWidth.'", "'.$irHeight.'", "7", "#'.$ngg_options['irBackcolor'].'");';
	$out .= "\n\t\t".'sbsl.addParam("wmode", "opaque");';
	$out .= "\n\t\t".'sbsl.addVariable("file", "'.NGGALLERY_URLPATH.'nggextractXML.php?gid='.$galleryID.'");';
	$out .= "\n\t\t".'sbsl.addVariable("linkfromdisplay", "false");';
	$out .= "\n\t\t".'sbsl.addVariable("shownavigation", "false");';
	// default value changed in 3.15 : linkfromdisplay, shownavigation, showicons
	if (!$ngg_options['irShuffle']) $out .= "\n\t\t".'sbsl.addVariable("shuffle", "false");';
	if (!$ngg_options['irShowicons']) $out .= "\n\t\t".'sbsl.addVariable("showicons", "false");';
	if ($ngg_options['irShowicons']) $out .= "\n\t\t".'sbsl.addVariable("showicons", "true");';
	$out .= "\n\t\t".'sbsl.addVariable("overstretch", "'.$ngg_options['irOverstretch'].'");';
	$out .= "\n\t\t".'sbsl.addVariable("backcolor", "0x'.$ngg_options['irBackcolor'].'");';
	$out .= "\n\t\t".'sbsl.addVariable("frontcolor", "0x'.$ngg_options['irFrontcolor'].'");';
	$out .= "\n\t\t".'sbsl.addVariable("lightcolor", "0x'.$ngg_options['irLightcolor'].'");';
	$out .= "\n\t\t".'sbsl.addVariable("rotatetime", "'.$ngg_options['irRotatetime'].'");';
	$out .= "\n\t\t".'sbsl.addVariable("transition", "'.$ngg_options['irTransition'].'");';
	$out .= "\n\t\t".'sbsl.addVariable("width", "'.$irWidth.'");';
	$out .= "\n\t\t".'sbsl.addVariable("height", "'.$irHeight.'");'; 
	$out .= "\n\t\t".'sbsl.write("ngg_widget_slideshow'.$galleryID.'");';
	$out .= "\n\t".'//]]>';
	$out .= "\n\t".'-->';
	$out .= "\n\t".'</script>';
		
	echo $out;
}


/**********************************************************/
/* Slidehow widget control
/**********************************************************/
function widget_ngg_slideshow() {
 
 	// Check for the required plugin functions. 
	if ( !function_exists('register_sidebar_widget') )
		return;
		
	// Check for NextGEN Gallery
	if ( !class_exists('nggallery') )
		return;	
	
	function widget_show_ngg_slideshow($args) {
	 
	    extract($args);
   
    	// Each widget can store its own options. We keep strings here.
		$options = get_option('widget_nggslideshow');

		// These lines generate our output. 
		echo $before_widget . $before_title . $options['title'] . $after_title;
		nggSlideshowWidget($options['galleryid'] , $options['width'] , $options['height']);
		echo $after_widget;
		
	}	

	// Admin section
	function widget_control_ngg_slideshow() {
	 	global $wpdb;
	 	$options = get_option('widget_nggslideshow');
	 	if ( !is_array($options) )
			$options = array('title'=>'Slideshow', 'galleryid'=>'0','height'=>'120','width'=>'160',);
			
		if ( $_POST['ngg-submit'] ) {

			$options['title'] = strip_tags(stripslashes($_POST['ngg-title']));
			$options['galleryid'] = $_POST['ngg-galleryid'];
			$options['height'] = $_POST['ngg-height'];
			$options['width'] = $_POST['ngg-width'];
			update_option('widget_nggslideshow', $options);
		}
		
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$height = $options['height'];
		$width = $options['width'];
		
		// The Box content
		echo '<p style="text-align:right;"><label for="ngg-title">' . __('Title:', 'nggallery') . ' <input style="width: 200px;" id="ngg-title" name="ngg-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ngg-galleryid">' . __('Select Gallery:', 'nggallery'). ' </label>';
		echo '<select size="1" name="ngg-galleryid" id="ngg-galleryid">';
			echo '<option value="0" ';
			if ($table->gid == $options['galleryid']) echo "selected='selected' ";
			echo '>'.__('All images', 'nggallery').'</option>'."\n\t"; 
			$tables = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY 'name' ASC ");
			if($tables) {
				foreach($tables as $table) {
				echo '<option value="'.$table->gid.'" ';
				if ($table->gid == $options['galleryid']) echo "selected='selected' ";
				echo '>'.$table->name.'</option>'."\n\t"; 
				}
			}
		echo '</select></p>';
		echo '<p style="text-align:right;"><label for="ngg-height">' . __('Height:', 'nggallery') . ' <input style="width: 50px;" id="ngg-height" name="ngg-height" type="text" value="'.$height.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ngg-width">' . __('Width:', 'nggallery') . ' <input style="width: 50px;" id="ngg-width" name="ngg-width" type="text" value="'.$width.'" /></label></p>';
		echo '<input type="hidden" id="ngg-submit" name="ngg-submit" value="1" />';
	 		
	}
	
	register_sidebar_widget(array('NextGEN Slideshow', 'widgets'), 'widget_show_ngg_slideshow');
	register_widget_control(array('NextGEN Slideshow', 'widgets'), 'widget_control_ngg_slideshow', 300, 200);
}

add_action('widgets_init', 'widget_ngg_slideshow');

/**
 * nggWidget - The widget control for NextGEN Gallery ( require WP2.2 or hiogher)
 *
 * @package NextGEN Gallery
 * @author Alex Rabe
 * @copyright 2008
 * @version 1.00
 * @access public
 */
 
class nggWidget {
	
	function nggWidget() {
	
		// Run our code later in case this loads prior to any required plugins.
		add_action('widgets_init', array(&$this, 'ngg_widget_register'));
		
	}
	
	function ngg_widget_register() {
		
		if ( !class_exists('nggallery') )
			return;

		// For K2 Sidebar manager we do different 
		if(class_exists('K2SBM') && K2_USING_SBM ) {
			
			K2SBM::register_sidebar_module('NextGEN Gallery', 'ngg_sbm_widget_output', 'sb-ngg-widget');
			K2SBM::register_sidebar_module_control('NextGEN Gallery', 'ngg_sbm_widget_control');

		} else {
			
			// test for widget plugin > 2.2
			if ( !function_exists('wp_register_sidebar_widget') )
				return;
			
			$options = get_option('ngg_widget');
			$number = $options['number'];
			if ( $number < 1 ) $number = 1;
			if ( $number > 9 ) $number = 9;
			$dims = array('width' => 410, 'height' => 300);
			$class = array('classname' => 'ngg_widget');
			for ($i = 1; $i <= 9; $i++) {
				$name = sprintf(__('NextGEN Gallery %d','nggallery'), $i);
				$id = "ngg-widget-$i"; // Never never never translate an id
				wp_register_sidebar_widget($id, $name, $i <= $number ? array(&$this, 'ngg_widget_output') : /* unregister */ '', $class, $i);
				wp_register_widget_control($id, $name, $i <= $number ? array(&$this, 'ngg_widget_control') : /* unregister */ '', $dims, $i);
			}
			add_action('sidebar_admin_setup', array(&$this, 'ngg_widget_admin_setup'));
			add_action('sidebar_admin_page', array(&$this, 'ngg_widget_admin_page'));	
		}
	 }

	function ngg_widget_admin_page() {
		
		$options = get_option('ngg_widget');
		?>
			<div class="wrap">
				<form method="POST">
					<h2><?php _e('NextGEN Gallery Widgets','nggallery'); ?></h2>
					<p style="line-height: 30px;"><?php _e('How many NextGEN Gallery widgets would you like?','nggallery'); ?>
					<select id="ngg-number" name="ngg-number">
		<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
					</select>
					<span class="submit"><input type="submit" name="ngg-number-submit" id="ngg-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
				</form>
			</div>
		<?php
	}

	function ngg_widget_admin_setup() {
		
		$options = $newoptions = get_option('ngg_widget');
		
		if ( isset($_POST['ngg-number-submit']) ) {
			$number = (int) $_POST['ngg-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('ngg_widget', $options);
			$this->ngg_widget_register($options['number']);
		}
		
	}

	function ngg_widget_control($number, $is_K2_SMB = false) {
	
		$options = $newoptions = get_option('ngg_widget');

		// These post parameter are expected
		$params = array('title','items','show','type','width','height','exclude','list');
		
		// get the parameter from POST
		if ( $_POST["ngg-submit-$number"] ) {
			
			foreach ($params as $parameter) {
				$value = trim(strip_tags(stripslashes($_POST["ngg-$parameter-$number"])));
				// remove all non numeric values from the list
				if ($parameter == 'list') {
					$numeric_ids = array();
					$ids = explode(',',$value);
					if (is_array($ids)) {
						foreach ($ids as $id) {
							$id = trim($id);
							if (is_numeric($id))
								$numeric_ids[] = $id;
						}
						$value = implode(',', $numeric_ids);
					}
				}
				$newoptions[$number][$parameter] = $value;
			}
		
		}
		
		// save the parameter
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('ngg_widget', $options);
		}

		// Init parameters check
		if (empty($options[$number]))
			$options[$number] = array('title'=>'Gallery', 'items'=>4,'show'=>'thumbnail' ,'type'=>'random','width'=>75, 'height'=>50, 'exclude'=>'all');

		foreach ($params as $parameter) {
			$post[$parameter] = attribute_escape($options[$number][$parameter]);
		}		
		
		// get the parameter from options -> POST	
		$items = (int) $options[$number]['items'];
		if ( empty($items) || $items < 1 ) $items = 10;
		
		// Here comes the form (Not for K2 Style)
	 	if (!$is_K2_SMB) {
	 	?>	
		<style>
		div .ngg-widget p {
			text-align:left;
		}
		div .ngg-widget label {
			float:left;
			margin:0.4em 0px;
			padding:0pt 10px;
			text-align:right;
			width:120px;
		}		
		</style>
		<?php
		}
		?>
		<div class="ngg-widget">
		<p>
			<label for="ngg-title-<?php echo "$number"; ?>"><?php _e('Title :','nggallery'); ?></label>
			<input style="width: 250px;" id="ngg-title-<?php echo "$number"; ?>" name="ngg-title-<?php echo "$number"; ?>" type="text" value="<?php echo $post['title']; ?>" />
		</p>
			
		<p>
			<label for="ngg-items-<?php echo "$number"; ?>"><?php _e('Show :','nggallery'); ?></label>
			<select id="ngg-items-<?php echo "$number"; ?>" name="ngg-items-<?php echo "$number"; ?>">
				<?php for ( $i = 1; $i <= 10; ++$i ) echo "<option value='$i' ".($items==$i ? "selected='selected'" : '').">$i</option>"; ?>
			</select>
			<select id="ngg-show-<?php echo "$number"; ?>" name="ngg-show-<?php echo "$number"; ?>" >
				<option <?php selected("thumbnail" , $post['show']); ?> value="thumbnail"><?php _e('Thumbnails','nggallery'); ?></option>
				<option <?php selected("orginal" , $post['show']); ?> value="orginal"><?php _e('Orginal images','nggallery'); ?></option>
			</select>
		</p>

		<p>
			<label for="ngg-type-<?php echo "$number"; ?>">&nbsp;</label>
			<input name="ngg-type-<?php echo "$number"; ?>" type="radio" value="random" <?php checked("random" , $post['type']); ?> /> <?php _e('random','nggallery'); ?>
			<input name="ngg-type-<?php echo "$number"; ?>" type="radio" value="recent" <?php checked("recent" , $post['type']); ?> /> <?php _e('recent added ','nggallery'); ?>
		</p>

		<p>
			<label for="ngg-width-<?php echo "$number"; ?>"><?php _e('Width x Height :','nggallery'); ?></label>
			<input style="width: 50px;" id="ngg-width-<?php echo "$number"; ?>" name="ngg-width-<?php echo "$number"; ?>" type="text" value="<?php echo (int)$post['width']; ?>" /> x
			<input style="width: 50px;" id="ngg-height-<?php echo "$number"; ?>" name="ngg-height-<?php echo "$number"; ?>" type="text" value="<?php echo (int) $post['height']; ?>" /> (px)
		</p>

		<p>
			<label for="ngg-exclude-<?php echo "$number"; ?>"><?php _e('Select :','nggallery'); ?></label>
			<select id="ngg-exclude-<?php echo "$number"; ?>" name="ngg-exclude-<?php echo "$number"; ?>">
				<option <?php selected("all" , $post['exclude']); ?>  value="all" ><?php _e('All galleries','nggallery'); ?></option>
				<option <?php selected("denied" , $post['exclude']); ?> value="denied" ><?php _e('Only which are not listed','nggallery'); ?></option>
				<option <?php selected("allow" , $post['exclude']); ?>  value="allow" ><?php _e('Only which are listed','nggallery'); ?></option>
			</select>
		</p>

		<p>
			<label for="ngg-list-<?php echo "$number"; ?>"><?php _e('Gallery ID :','nggallery'); ?></label>
			<input style="width: 250px;" id="ngg-list-<?php echo "$number"; ?>" name="ngg-list-<?php echo "$number"; ?>" type="text" value="<?php echo $post['list'] ?>" />
			<br/><small><?php _e('Gallery IDs, separated by commas.','nggallery'); ?></small>
		</p>

		<input type="hidden" id="ngg-submit-<?php echo "$number"; ?>" name="ngg-submit-<?php echo "$number"; ?>" value="1" />
		</div>
		
	<?php
	
	}

	function ngg_widget_output($args, $number = 1 , $options = false) {

		global $wpdb;
				
		extract($args);

		// We could get this also as parameter
		if (!$options)				
			$options = get_option('ngg_widget');
	
		// get the effect code
		$thumbcode = nggallery::get_thumbcode("sidebar_".$number);
		
		$items 	= $options[$number]['items'];
		$exclude = $options[$number]['exclude'];
		$list = $options[$number]['list'];

		$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures WHERE exclude != 1 ");
		if ($count < $options[$number]['items']) 
			$options[$number]['items'] = $count;

		$exclude_list = "";

		// THX to Kay Germer for the idea & addon code
		if ( (!empty($list)) && ($exclude != "all") ) {
			$list = explode(',',$list);
			// Prepare for SQL
			$list = "'" . implode("', '", $list) . "'";
			
			if ($exclude == "denied")	
				$exclude_list = "AND NOT galleryid IN ($list)";

			if ($exclude == "allow")	
				$exclude_list = "AND galleryid IN ($list)";
		}

		if ( $options[$number]['type'] == "random" ) 
			$imageList = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE exclude != 1 $exclude_list ORDER by rand() limit $items");
		else
			$imageList = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE exclude != 1 $exclude_list ORDER by pid DESC limit 0,$items");

		echo $before_widget . $before_title . $options[$number]['title'] . $after_title;
		echo "\n".'<div class="ngg-widget">'."\n";
	
		if (is_array($imageList)){
			foreach($imageList as $image) {
	
				$out = '<a href="'.nggallery::get_image_url($image->pid).'" title="'.stripslashes($image->description).'" '.$thumbcode.'>';
				if ( $options[$number]['show'] == "orginal" )
					$out .= '<img src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$image->pid.'&amp;width='.$options[$number]['width'].'&amp;height='.$options[$number]['height'].'" title="'.$image->alttext.'" alt="'.$image->alttext.'" />';
				else	
					$out .= '<img src="'.nggallery::get_thumbnail_url($image->pid).'" style="width:'.$options[$number]['width'].'px;height:'.$options[$number]['height'].'px;" title="'.$image->alttext.'" alt="'.$image->alttext.'" />';			
				
				echo $out . '</a>'."\n";
				
			}
		}
		
		echo '</div>'."\n";
		echo $after_widget;
		
	}

}
// let's show it
$nggWidget = new nggWidget;	


/**
 * ngg_sbm_widget_control()
 * ONLY required for K2 Theme (tested with K2 RC4)
 *
 * @return return widget admin
 */
function ngg_sbm_widget_control() {
	
	if ( !function_exists('checked') ) {
		function checked( $checked, $current) {
			if ( $checked == $current)
				echo ' checked="checked"';
		}
	}
	
	$number = 1;
	
	// Check for Module id
	if(isset($_POST['module_id'])) 
		$number = $_POST['module_id'];
		
	nggWidget::ngg_widget_control($number, true);

}

/**
 * ngg_sbm_widget_output($args)
 * ONLY required for K2 Theme
 *
 * @return widget content
 */
function ngg_sbm_widget_output($args) {
	global $k2sbm_current_module;
	
	$number = $k2sbm_current_module->id;
	
	nggWidget::ngg_widget_output($args, $number , false);
}

/**
 * nggDisplayRandomImages($number,$width,$height,$exclude,$list)
 * Function for templates without widget support
 *
 * @return echo the widget content
 */
function nggDisplayRandomImages($number, $width = "75", $height = "50", $exclude = "all", $list = "") {
	
	$options[1] = array('title'=>'', 
						'items'=>$number,
						'show'=>'thumbnail' ,
						'type'=>'random',
						'width'=>$width, 
						'height'=>$height, 
						'exclude'=>'all',
						'list'=>$list   );
	
	nggWidget::ngg_widget_output($args = array(), 1, $options);
}

/**
 * nggDisplayRecentImages($number,$width,$height,$exclude,$list)
 * Function for templates without widget support
 *
 * @return echo the widget content
 */
function nggDisplayRecentImages($number, $width = "75", $height = "50", $exclude = "all", $list = "") {

	$options[1] = array('title'=>'', 
						'items'=>$number,
						'show'=>'thumbnail' ,
						'type'=>'recent',
						'width'=>$width, 
						'height'=>$height, 
						'exclude'=>'all',
						'list'=>$list   );
	
	nggWidget::ngg_widget_output($args = array(), 1, $options);
}

?>