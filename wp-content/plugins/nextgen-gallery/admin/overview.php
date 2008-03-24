<?php  
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function nggallery_admin_overview()  {	
	global $wpdb;
	
	// get feed_messages
	require_once(ABSPATH . WPINC . '/rss.php');
	
	// init PluginChecker
	$nggCheck 			= new CheckPlugin();	
	$nggCheck->URL 		= NGGURL;
	$nggCheck->version 	= NGGVERSION;
	$nggCheck->name 	= "ngg";

?>
  <div class="wrap" style="overflow:hidden;">
    <h2><?php _e('NextGEN Gallery Overview', 'nggallery') ?></h2>
        
    <div id="zeitgeist">
    	  <h2><?php _e('Summary', 'nggallery') ?></h2>
      <p>
         <?php
          $replace = array
          (
            '<strong>'.$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures").'</strong>',
            '<strong>'.$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggallery").'</strong>',
            '<strong>'.$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggalbum").'</strong>'
           );              
          vprintf(__('There are totally %1$s pictures in %2$s galleries, which are spread across %3$s albums.', 'nggallery'), $replace);
        ?>
       </p>
	  <?php if ( $nggCheck->startCheck() && (!IS_WPMU) ) { ?>
		<h3><font color="red"><?php _e('New Version available', 'nggallery') ?></font></h3>
	   	<p><?php _e('The server reports that a new NextGEN Gallery Version is now available. Please visit the plugin homepage for more information.', 'nggallery') ?></p>
		<p><a href="http://wordpress.org/extend/plugins/nextgen-gallery/download/" target="_blank"> <?php _e('Download here', 'nggallery') ?> </a></p>
	  <?php } ?>
	  <?php if (IS_WPMU) {
	  	if (wpmu_enable_function('wpmuQuotaCheck'))
			echo ngg_SpaceManager::details();
	  } else { ?>
	  	<h3><?php _e('Server Settings', 'nggallery') ?></h3>
      <ul>
      	<?php ngg_get_serverinfo(); ?>
	   </ul>
		<?php ngg_gd_info(); ?>
	  <?php } ?>
    </div>
    
    <h3><?php _e('Welcome', 'nggallery') ?></h3>
    
    <p>
      <?php
        $userlevel = '<strong>' . (current_user_can('manage_options') ? __('gallery administrator', 'nggallery') : __('gallery editor', 'nggallery')) . '</strong>';
        printf(__('Welcome to NextGEN Gallery. Here you can control your images, galleries and albums. You currently have %s rights.', 'nggallery'), $userlevel);
      ?>
    </p>
    
    <ul>
      <?php if(current_user_can('NextGEN Upload images')): ?><li><a href="admin.php?page=nggallery-add-gallery"><?php _e('Add a new gallery or import pictures', 'nggallery') ?></a></li><?php endif; ?>
      <?php if(current_user_can('NextGEN Manage gallery')): ?><li><a href="admin.php?page=nggallery-manage-gallery"><?php _e('Manage galleries and images', 'nggallery') ?></a></li><?php endif; ?>
      <?php if(current_user_can('NextGEN Edit album')): ?><li><a href="admin.php?page=nggallery-manage-album"><?php _e('Create and manage albums', 'nggallery') ?></a></li><?php endif; ?>
      <?php if(current_user_can('NextGEN Change options')): ?><li><a href="admin.php?page=nggallery-options"><?php _e('Change the settings of NextGEN Gallery', 'nggallery') ?></a></li><?php endif; ?>
    </ul>
    <div id="devnews">
    <h3><?php _e('Latest News', 'nggallery') ?></h3>
    
    <?php
      $rss = fetch_rss('http://alexrabe.boelinger.com/?tag=nextgen-gallery&feed=rss2');
      
      if ( isset($rss->items) && 0 != count($rss->items) )
      {
        $rss->items = array_slice($rss->items, 0, 3);
        foreach ($rss->items as $item)
        {
        ?>
          <h4><a href='<?php echo wp_filter_kses($item['link']); ?>'><?php echo wp_specialchars($item['title']); ?></a> &#8212; <?php echo human_time_diff(strtotime($item['pubdate'], time())); ?></h4>
          <p><?php echo '<strong>'.date("F, jS", strtotime($item['pubdate'])).'</strong> - '.$item['description']; ?></p>
        <?php
        }
      }
      else
      {
        ?>
        <p><?php printf(__('Newsfeed could not be loaded.  Check the <a href="%s">front page</a> to check for updates.', 'nggallery'), 'http://alexrabe.boelinger.com/') ?></p>
        <?php
      }
    ?>
    </div>
    <br style="clear: both" />
   </div>
<?php
}

// ***************************************************************
function ngg_gd_info() {
	if(function_exists("gd_info")){
		echo '<div><h3>'.__('GD support', 'nggallery').'</h3><ul>';
		$info = gd_info();
		$keys = array_keys($info);
		for($i=0; $i<count($keys); $i++) {
			if(is_bool($info[$keys[$i]]))
				echo "<li> " . $keys[$i] ." : <strong>" . ngg_gd_yesNo($info[$keys[$i]]) . "</strong></li>\n";
			else
				echo "<li> " . $keys[$i] ." : <strong>" . $info[$keys[$i]] . "</strong></li>\n";
		}
	}
	else {
		echo '<div><h3>'.__('No GD support', 'nggallery').'!</h3><ul>';
	}
	echo '</ul></div>';
}

// ***************************************************************		
function ngg_gd_yesNo($bool){
	if($bool) return __('Yes', 'nggallery');
	else return __('No', 'nggallery');
}

// ***************************************************************
function ngg_get_serverinfo() {
// thx to GaMerZ for WP-ServerInfo	
// http://www.lesterchan.net

	global $wpdb;
	// Get MYSQL Version
	$sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
	// GET SQL Mode
	$mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
	if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
	if (empty($sql_mode)) $sql_mode = __('Not set', 'nggallery');
	// Get PHP Safe Mode
	if(ini_get('safe_mode')) $safe_mode = __('On', 'nggallery');
	else $safe_mode = __('Off', 'nggallery');
	// Get PHP allow_url_fopen
	if(ini_get('allow_url_fopen')) $allow_url_fopen = __('On', 'nggallery');
	else $allow_url_fopen = __('Off', 'nggallery'); 
	// Get PHP Max Upload Size
	if(ini_get('upload_max_filesize')) $upload_max = ini_get('upload_max_filesize');	
	else $upload_max = __('N/A', 'nggallery');
	// Get PHP Max Post Size
	if(ini_get('post_max_size')) $post_max = ini_get('post_max_size');
	else $post_max = __('N/A', 'nggallery');
	// Get PHP Max execution time
	if(ini_get('max_execution_time')) $max_execute = ini_get('max_execution_time');
	else $max_execute = __('N/A', 'nggallery');
	// Get PHP Memory Limit 
	if(ini_get('memory_limit')) $memory_limit = ini_get('memory_limit');
	else $memory_limit = __('N/A', 'nggallery');
	// Get actual memory_get_usage
	if (function_exists('memory_get_usage')) $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte', 'nggallery');
	else $memory_usage = __('N/A', 'nggallery');
	// required for EXIF read
	if (is_callable('exif_read_data')) $exif = __('Yes', 'nggallery'). " ( V" . substr(phpversion('exif'),0,4) . ")" ;
	else $exif = __('No', 'nggallery');
	// required for meta data
	if (is_callable('iptcparse')) $iptc = __('Yes', 'nggallery');
	else $iptc = __('No', 'nggallery');
	// required for meta data
	if (is_callable('xml_parser_create')) $xml = __('Yes', 'nggallery');
	else $xml = __('No', 'nggallery');
	
?>
	<li><?php _e('Operating System', 'nggallery'); ?> : <strong><?php echo PHP_OS; ?></strong></li>
	<li><?php _e('Server', 'nggallery'); ?> : <strong><?php echo $_SERVER["SERVER_SOFTWARE"]; ?></strong></li>
	<li><?php _e('Memory usage', 'nggallery'); ?> : <strong><?php echo $memory_usage; ?></strong></li>
	<li><?php _e('MYSQL Version', 'nggallery'); ?> : <strong><?php echo $sqlversion; ?></strong></li>
	<li><?php _e('SQL Mode', 'nggallery'); ?> : <strong><?php echo $sql_mode; ?></strong></li>
	<li><?php _e('PHP Version', 'nggallery'); ?> : <strong><?php echo PHP_VERSION; ?></strong></li>
	<li><?php _e('PHP Safe Mode', 'nggallery'); ?> : <strong><?php echo $safe_mode; ?></strong></li>
	<li><?php _e('PHP Allow URL fopen', 'nggallery'); ?> : <strong><?php echo $allow_url_fopen; ?></strong></li>
	<li><?php _e('PHP Memory Limit', 'nggallery'); ?> : <strong><?php echo $memory_limit; ?></strong></li>
	<li><?php _e('PHP Max Upload Size', 'nggallery'); ?> : <strong><?php echo $upload_max; ?></strong></li>
	<li><?php _e('PHP Max Post Size', 'nggallery'); ?> : <strong><?php echo $post_max; ?></strong></li>
	<li><?php _e('PHP Max Script Execute Time', 'nggallery'); ?> : <strong><?php echo $max_execute; ?>s</strong></li>
	<li><?php _e('PHP Exif support', 'nggallery'); ?> : <strong><?php echo $exif; ?></strong></li>
	<li><?php _e('PHP IPTC support', 'nggallery'); ?> : <strong><?php echo $iptc; ?></strong></li>
	<li><?php _e('PHP XML support', 'nggallery'); ?> : <strong><?php echo $xml; ?></strong></li>
<?php
}

// ***************************************************************	

/**
 * WordPress PHP class to check for a new version.
 * @author Alex Rabe & Joern Kretzschmar
 * @orginal from Per Søderlind
 *
 // Dashboard update notification example
	function myPlugin_update_dashboard() {
	  $Check = new CheckPlugin();	
	  $Check->URL 	= "YOUR URL";
	  $Check->version = "1.00";
	  $Check->name 	= "myPlugin";
	  if ($Check->startCheck()) {
 	    echo '<h3>Update Information</h3>';
	    echo '<p>A new version is available</p>';
	  } 
	}
	
	add_action('activity_box_end', 'myPlugin_update_dashboard', '0');
 *
 */
if ( !class_exists( "CheckPlugin" ) ) {  
	class CheckPlugin {
		/**
		 * URL with the version of the plugin
		 * @var string
		 */
		var $URL = 'myURL';
		/**
		 * Version of thsi programm or plugin
		 * @var string
		 */
		var $version = '1.00';
		/**
		 * Name of the plugin (will be used in the options table)
		 * @var string
		 */
		var $name = 'myPlugin';
		/**
		 * Waiting period until the next check in seconds
		 * @var int
		 */
		var $period = 86400;					
					
		function startCheck() {
			/**
			 * check for a new version, returns true if a version is avaiable
			 */
			
			// use wordpress snoopy class
			require_once(ABSPATH . WPINC . '/class-snoopy.php');
			
			$check_intervall = get_option( $this->name."_next_update" );

			if ( ($check_intervall < time() ) or (empty($check_intervall)) ) {
				if (class_exists(snoopy)) {
					$client = new Snoopy();
					$client->agent = 'NextGEN Gallery Version Checker (+http://www.nextgen.boelinger.com/)';
					$client->_fp_timeout = 10;
					if (@$client->fetch($this->URL) === false) {
						return false;
					}
					
				   	$remote = $client->results;
				   	
					$server_version = unserialize($remote);
					if (is_array($server_version)) {
						if ( version_compare($server_version[$this->name], $this->version, '>') )
						 	return true;
					} 
					
					$check_intervall = time() + $this->period;
					update_option( $this->name."_next_update", $check_intervall );
					return false;
				}				
			}
		}
	}
}
// ***************************************************************	

/**
 * WPMU feature taken from Z-Space Upload Quotas
 * @author Dylan Reeve
 * @url http://dylan.wibble.net/
 *
 */

class ngg_SpaceManager {
 
 	function getQuota() {
		if (function_exists(get_space_allowed))
			$quota = get_space_allowed();
		else
			$quota = get_site_option( "blog_upload_space" );
			
		return $quota;
	}
	 
	function details() {
		
		// take default seetings
		$settings = array(

			'remain'	=> array(
			'color_text'	=> 'white',
			'color_bar'		=> '#0D324F',
			'color_bg'		=> '#a0a0a0',
			'decimals'		=> 2,
			'unit'			=> 'm',
			'display'		=> true,
			'graph'			=> false
			),

			'used'		=> array(
			'color_text'	=> 'white',
			'color_bar'		=> '#0D324F',
			'color_bg'		=> '#a0a0a0',
			'decimals'		=> 2,
			'unit'			=> 'm',
			'display'		=> true,
			'graph'			=> true
			)
		);

		$quota = ngg_SpaceManager::getQuota() * 1024 * 1024;
		$used = get_dirsize( constant( "ABSPATH" ) . constant( "UPLOADS" ) );
//		$used = get_dirsize( ABSPATH."wp-content/blogs.dir/".$blog_id."/files" );
		
		if ($used > $quota) $percentused = '100';
		else $percentused = ( $used / $quota ) * 100;

		$remaining = $quota - $used;
		$percentremain = 100 - $percentused;

		$out = "";
		$out .= '<div id="spaceused"> <h3>'.__('Storage Space','nggallery').'</h3>';

		if ($settings['used']['display']) {
			$out .= __('Upload Space Used:','nggallery') . "\n";
			$out .= ngg_SpaceManager::buildGraph($settings['used'], $used,$quota,$percentused);
			$out .= "<br />";
		}

		if($settings['remain']['display']) {
			$out .= __('Upload Space Remaining:','nggallery') . "\n";
			$out .= ngg_SpaceManager::buildGraph($settings['remain'], $remaining,$quota,$percentremain);

		}

		$out .= "</div>";

		echo $out;
	}

	function buildGraph($settings, $size, $quota, $percent) {
		$color_bar = $settings['color_bar'];
		$color_bg = $settings['color_bg'];
		$color_text = $settings['color_text'];
		
		switch ($settings['unit']) {
			case "b":
				$unit = "B";
				break;
				
			case "k":
				$unit = "KB";
				$size = $size / 1024;
				$quota = $quota / 1024;
				break;
				
			case "g":   // Gigabytes, really?
				$unit = "GB";
				$size = $size / 1024 / 1024 / 1024;
				$quota = $quota / 1024 / 1024 / 1024;
				break;
				
			default:
				$unit = "MB";
				$size = $size / 1024 / 1024;
				$quota = $quota / 1024 / 1024;
				break;
		}

		$size = round($size, (int)$settings['decimals']);

		$pct = round(($size / $quota)*100);

		if ($settings['graph']) {

			$out = '<div style="display: block; margin: 0; padding: 0; height: 15px; border: 1px inset; width: 100%; background-color: '.$color_bg.';">'."\n";
			$out .= '<div style="display: block; height: 15px; border: none; background-color: '.$color_bar.'; width: '.$pct.'%;">'."\n";
			$out .= '<div style="display: inline; position: relative; top: 0; left: 0; font-size: 10px; color: '.$color_text.'; font-weight: bold; padding-bottom: 2px; padding-left: 5px;">'."\n";
			$out .= $size.$unit;
			$out .= "</div>\n</div>\n</div>\n";
		} else {
			$out = "<strong>".$size.$unit." ( ".number_format($percent)."%)"."</strong><br />";
		}

		return $out;
	}

}

?>