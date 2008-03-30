<?php  
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

	function nggallery_admin_options()  {
	
	global $wpdb, $wp_version, $nggRewrite;

	// get the options
	$ngg_options = get_option('ngg_options');	
	$old_state = $ngg_options['usePermalinks'];
	
	// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	$filepath    = get_option('siteurl'). '/wp-admin/admin.php?page='.$_GET['page'];

	if ( isset($_POST['updateoption']) ) {	
		check_admin_referer('ngg_settings');
		// get the hidden option fields, taken from WP core
		if ( $_POST['page_options'] )	
			$options = explode(',', stripslashes($_POST['page_options']));
		if ($options) {
			foreach ($options as $option) {
				$option = trim($option);
				$value = trim($_POST[$option]);
		//		$value = sanitize_option($option, $value); // This does stripslashes on those that need it
				$ngg_options[$option] = $value;
			}
		}
		// Flush ReWrite rules
		if ( $old_state != $ngg_options['usePermalinks'] )
			$nggRewrite->flush();
		// Save options
		update_option('ngg_options', $ngg_options);
	 	$messagetext = '<font color="green">'.__('Update successfully','nggallery').'</font>';
	}		
	
	if ( isset($_POST['clearcache']) ) {
		
		$path = WINABSPATH . $ngg_options['gallerypath'] . "cache/";
		
		if (is_dir($path))
	    	if ($handle = opendir($path)) {
				while (false !== ($file = readdir($handle))) {
			    	if ($file != '.' && $file != '..') {
			          @unlink($path."/".$file);
	          		}
	        	}
	      		closedir($handle);
			}
		
		$messagetext = '<font color="green">'.__('Cache cleared','nggallery').'</font>';
	}
	// message windows
	if(!empty($messagetext)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$messagetext.'</p></div>'; }
	?>
    <!-- Additional IE/Win specific style sheet (Conditional Comments) -->
    <!--[if lte IE 7]>
    <link rel="stylesheet" href="<?php echo NGGALLERY_URLPATH ?>admin/css/jquery.tabs-ie.css" type="text/css" media="projection, screen"/>
    <![endif]-->
	<script type="text/javascript">
		jQuery(function() {
			jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });	
		});
	
		function insertcode(value) {
			var effectcode;
			switch (value) {
			  case "none":
			    effectcode = "";
			    jQuery('#tbImage').hide("slow");
			    break;
			  case "thickbox":
			    effectcode = 'class="thickbox" rel="%GALLERY_NAME%"';
			    jQuery('#tbImage').show("slow");
			    break;
			  case "lightbox":
			    effectcode = 'rel="lightbox[%GALLERY_NAME%]"';
			    jQuery('#tbImage').hide("slow");
			    break;
			  case "highslide":
			    effectcode = 'class="highslide" onclick="return hs.expand(this, { slideshowGroup: %GALLERY_NAME% })"';
			    jQuery('#tbImage').hide("slow");
			    break;
			  case "shutter":
			    effectcode = 'class="shutterset"';
			    jQuery('#tbImage').hide("slow");
			    break;
			  default:
			    break;
			}
			jQuery("#thumbCode").val(effectcode);
		};
		
		function setcolor(fileid,color) {
			jQuery(fileid).css("background", color );
		};
	</script>
	
	<div id="slider" class="wrap">
	
		<ul id="tabs">
			<li><a href="#generaloptions"><?php _e('General Options', 'nggallery') ;?></a></li>
			<li><a href="#thumbnails"><?php _e('Thumbnails', 'nggallery') ;?></a></li>
			<li><a href="#images"><?php _e('Images', 'nggallery') ;?></a></li>
			<li><a href="#gallery"><?php _e('Gallery', 'nggallery') ;?></a></li>
			<li><a href="#effects"><?php _e('Effects', 'nggallery') ;?></a></li>
			<li><a href="#watermark"><?php _e('Watermark', 'nggallery') ;?></a></li>
			<li><a href="#slideshow"><?php _e('Slideshow', 'nggallery') ;?></a></li>
		</ul>

		<!-- General Options -->

		<div id="generaloptions">
			<h2><?php _e('General Options','nggallery'); ?></h2>
			<form name="generaloptions" method="post">
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="gallerypath,scanfolder,deleteImg,usePermalinks,activateTags,appendType,maxImages" />
			<fieldset class="options"> 
				<table class="optiontable editform">
					<tr valign="top">
						<th align="left"><?php _e('Gallery path','nggallery') ?></th>
						<td><input <?php if (IS_WPMU) echo 'readonly = "readonly"'; ?> type="text" size="35" name="gallerypath" value="<?php echo $ngg_options['gallerypath']; ?>" title="TEST" /><br />
						<?php _e('This is the default path for all galleries','nggallery') ?></td>
					</tr>
					<!--TODO:  Later... -->
					<!--
					<tr valign="top">
						<th align="left"><?php //_e('Scan folders during runtime','nggallery') ?></th>
						<td><input type="checkbox" name="scanfolder" value="1" <?php //checked('1', $ngg_options[scanfolder]); ?> /><br />
						<?php //_e('Search automatic in the folders for new images (not working)','nggallery') ?></td>
					</tr>
					-->
					<tr valign="top">
						<th align="left"><?php _e('Delete image files','nggallery') ?></th>
						<td><input <?php if (IS_WPMU) echo 'readonly = "readonly"'; ?> type="checkbox" name="deleteImg" value="1" <?php checked('1', $ngg_options['deleteImg']); ?> /><br />
						<?php _e('Delete files, when removing a gallery in the database','nggallery') ?></td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Activate permalinks','nggallery') ?></th>
						<td><input type="checkbox" name="usePermalinks" value="1" <?php checked('1', $ngg_options['usePermalinks']); ?> /><br />
						<?php _e('When you activate this option, you need to update your permalink structure one time.','nggallery') ?></td>
					</tr>
				</table>
			<legend><?php _e('Tags / Categories','nggallery') ?></legend>
				<table class="optiontable">
					<tr>
						<th valign="top"><?php _e('Activate related images','nggallery') ?>:</th>
						<td><input name="activateTags" type="checkbox" value="1" <?php checked('1', $ngg_options['activateTags']); ?> />
						<?php _e('This option will append related images to every post','nggallery') ?>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Match with','nggallery') ?>:</th>
						<td><label><input name="appendType" type="radio" value="category" <?php checked('category', $ngg_options['appendType']); ?> /> <?php _e('Categories', 'nggallery') ;?></label><br />
						<label><input name="appendType" type="radio" value="tags" <?php checked('tags', $ngg_options['appendType']); ?> /> <?php _e('Tags', 'nggallery') ;?><?php if (version_compare($wp_version, '2.3.alpha', '<')) _e(' (require WordPress 2.3 or higher)', 'nggallery'); ?></label>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Max. number of images','nggallery') ?>:</th>
						<td><input type="text" name="maxImages" value="<?php echo $ngg_options['maxImages'] ?>" size="3" maxlength="3" /><br />
						<?php _e('0 will show all images','nggallery') ?>
						</td>
					</tr>
				</table> 				
			<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</fieldset>	
			</form>	
		</div>	
		
		<!-- Thumbnail settings -->
		
		<div id="thumbnails">
			<h2><?php _e('Thumbnail settings','nggallery'); ?></h2>
			<form name="thumbnailsettings" method="POST" action="<?php echo $filepath.'#thumbnails'; ?>" >
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="thumbwidth,thumbheight,thumbfix,thumbcrop,thumbquality,thumbResampleMode" />
			<fieldset class="options"> 
				<p><?php _e('Please note : If you change the settings, you need to recreate the thumbnails under -> Manage Gallery .', 'nggallery') ?></p>
				<table class="optiontable editform">
					<tr valign="top">
						<th align="left"><?php _e('Width x height (in pixel)','nggallery') ?></th>
						<td><input type="text" size="4" maxlength="4" name="thumbwidth" value="<?php echo $ngg_options['thumbwidth']; ?>" /> x <input type="text" size="4" maxlength="4" name="thumbheight" value="<?php echo $ngg_options['thumbheight']; ?>" /><br />
						<?php _e('These values are maximum values ','nggallery') ?></td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Set fix dimension','nggallery') ?></th>
						<td><input type="checkbox" name="thumbfix" value="1" <?php checked('1', $ngg_options['thumbfix']); ?> /><br />
						<?php _e('Ignore the aspect ratio, no portrait thumbnails','nggallery') ?></td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Crop square thumbnail from image','nggallery') ?></th>
						<td><input type="checkbox" name="thumbcrop" value="1" <?php checked('1', $ngg_options['thumbcrop']); ?> /><br />
						<?php _e('Create square thumbnails, use only the width setting :','nggallery') ?> <?php echo $ngg_options['thumbwidth']; ?> x <?php echo $ngg_options['thumbwidth']; ?></td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Thumbnail quality','nggallery') ?></th>
						<td><input type="text" size="3" maxlength="3" name="thumbquality" value="<?php echo $ngg_options['thumbquality']; ?>" /> %</td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Resample Mode','nggallery') ?></th>
						<td><input type="text" size="1" maxlength="1" name="thumbResampleMode" value="<?php echo $ngg_options['thumbResampleMode']; ?>" /><br />
						<?php _e('Value between 1-5 (higher value, more CPU load)','nggallery') ?></td>
					</tr>
				</table>
			<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</fieldset>	
			</form>	
		</div>
		
		<!-- Image settings -->
		
		<div id="images">
			<h2><?php _e('Image settings','nggallery'); ?></h2>
			<form name="imagesettings" method="POST" action="<?php echo $filepath.'#images'; ?>" >
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="imgResize,imgWidth,imgHeight,imgQuality,imgResampleMode,imgCacheSinglePic" />
			<fieldset class="options"> 
				<table class="optiontable">
					<tr valign="top">
						<th scope="row"><label for="fixratio"><?php _e('Resize Images','nggallery') ?></label></th>
						<!--TODO: checkbox fixratio can be used later -->
						<td><input type="hidden" name="imgResize" value="1" <?php checked('1', $ngg_options['imgResize']); ?> /> </td>
						<td><input type="text" size="5" name="imgWidth" value="<?php echo $ngg_options['imgWidth']; ?>" /> x <input type="text" size="5" name="imgHeight" value="<?php echo $ngg_options['imgHeight']; ?>" /><br />
						<?php _e('Width x height (in pixel). NextGEN Gallery will keep ratio size','nggallery') ?></td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Image quality','nggallery') ?></th>
						<td></td>
						<td><input type="text" size="3" maxlength="3" name="imgQuality" value="<?php echo $ngg_options['imgQuality']; ?>" /> %</td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Resample Mode','nggallery') ?></th>
						<td></td>
						<td><input type="text" size="1" maxlength="1" name="imgResampleMode" value="<?php echo $ngg_options['imgResampleMode']; ?>" /><br />
						<?php _e('Value between 1-5 (higher value, more CPU load)','nggallery') ?></td>
					</tr>
				</table>
				<legend><?php _e('Single picture','nggallery') ?></legend>
				<table class="optiontable">
					<tr valign="top">
						<th align="left"><?php _e('Cache single pictures','nggallery') ?></th>
						<td></td>
						<td><input <?php if (IS_WPMU) echo 'readonly = "readonly"'; ?> type="checkbox" name="imgCacheSinglePic" value="1" <?php checked('1', $ngg_options['imgCacheSinglePic']); ?> />
						<?php _e('Creates a file for each singlepic settings. Reduce the CPU load','nggallery') ?></td>
					</tr>
					<tr valign="top">
						<th align="left"><?php _e('Clear cache folder','nggallery') ?></th>
						<td></td>
						<td><input type="submit" name="clearcache" value="<?php _e('Proceed now','nggallery') ;?> &raquo;"/></td>
					</tr>
				</table>
			<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</fieldset>	
			</form>	
		</div>
		
		<!-- Gallery settings -->
		
		<div id="gallery">
			<h2><?php _e('Gallery settings','nggallery'); ?></h2>
			<form name="galleryform" method="POST" action="<?php echo $filepath.'#gallery'; ?>" >
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="galUsejQuery,galNoPages,galImages,galShowSlide,galTextSlide,galTextGallery,galShowOrder,galShowDesc,galImgBrowser,galSort,galSortDir" />
			<fieldset class="options"> 
				<table class="optiontable">
					<!--TODO:  Do better... -->
					<!--
					<tr>
						<th valign="top"><?php //_e('Activate jQuery navigation','nggallery') ?>:</th>
						<td><input name="galUsejQuery" type="checkbox" value="1" <?php // checked('1', $ngg_options['galUsejQuery']); ?> />
						<?php //_e('Please note : This is still experimental. Requires the Thickbox effect','nggallery') ?>
						</td>
					</tr>
					-->
					<tr>
						<th valign="top"><?php _e('Deactivate gallery page link','nggallery') ?>:</th>
						<td><input name="galNoPages" type="checkbox" value="1" <?php checked('1', $ngg_options['galNoPages']); ?> />
						<?php _e('The album will not link to a gallery subpage. The gallery is shown on the same page.','nggallery') ?>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Number of images per page','nggallery') ?>:</th>
						<td><input type="text" name="galImages" value="<?php echo $ngg_options['galImages'] ?>" size="3" maxlength="3" /><br />
						<?php _e('0 will disable pagination, all images on one page','nggallery') ?>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Integrate slideshow','nggallery') ?>:</th>
						<td><input name="galShowSlide" type="checkbox" value="1" <?php checked('1', $ngg_options['galShowSlide']); ?> />
							<input type="text" name="galTextSlide" value="<?php echo $ngg_options['galTextSlide'] ?>" size="20" />
							<input type="text" name="galTextGallery" value="<?php echo $ngg_options['galTextGallery'] ?>" size="20" />
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Show first','nggallery') ?>:</th>
						<td><label><input name="galShowOrder" type="radio" value="gallery" <?php checked('gallery', $ngg_options['galShowOrder']); ?> /> <?php _e('Thumbnails', 'nggallery') ;?></label><br />
						<label><input name="galShowOrder" type="radio" value="slide" <?php checked('slide', $ngg_options['galShowOrder']); ?> /> <?php _e('Slideshow', 'nggallery') ;?></label>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Show thumbnail description','nggallery') ?>:</th>
						<td><label><input name="galShowDesc" type="radio" value="none" <?php checked('none', $ngg_options['galShowDesc']); ?> /> <?php _e('None', 'nggallery') ;?></label><br />
						<label><input name="galShowDesc" type="radio" value="desc" <?php checked('desc', $ngg_options['galShowDesc']); ?> /> <?php _e('Description text', 'nggallery') ;?></label><br />
						<label><input name="galShowDesc" type="radio" value="alttext" <?php checked('alttext', $ngg_options['galShowDesc']); ?> /> <?php _e('Alt / Title text', 'nggallery') ;?></label>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Show ImageBrowser','nggallery') ?>:</th>
						<td><input name="galImgBrowser" type="checkbox" value="1" <?php checked('1', $ngg_options['galImgBrowser']); ?> />
						<?php _e('The gallery will open the ImageBrowser instead the effect.','nggallery') ?>
						</td>
					</tr>
				</table>
			<legend><?php _e('Sort options','nggallery') ?></legend>
				<table class="optiontable">
					<tr>
						<th valign="top"><?php _e('Sort thumbnails','nggallery') ?>:</th>
						<td>
						<label><input name="galSort" type="radio" value="sortorder" <?php checked('sortorder', $ngg_options['galSort']); ?> /> <?php _e('Custom order', 'nggallery') ;?></label><br />
						<label><input name="galSort" type="radio" value="pid" <?php checked('pid', $ngg_options['galSort']); ?> /> <?php _e('Image ID', 'nggallery') ;?></label><br />
						<label><input name="galSort" type="radio" value="filename" <?php checked('filename', $ngg_options['galSort']); ?> /> <?php _e('File name', 'nggallery') ;?></label><br />
						<label><input name="galSort" type="radio" value="alttext" <?php checked('alttext', $ngg_options['galSort']); ?> /> <?php _e('Alt / Title text', 'nggallery') ;?></label>
						</td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Sort direction','nggallery') ?>:</th>
						<td><label><input name="galSortDir" type="radio" value="ASC" <?php checked('ASC', $ngg_options['galSortDir']); ?> /> <?php _e('Ascending', 'nggallery') ;?></label><br />
						<label><input name="galSortDir" type="radio" value="DESC" <?php checked('DESC', $ngg_options['galSortDir']); ?> /> <?php _e('Descending', 'nggallery') ;?></label>
						</td>
					</tr>
				</table>
			<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</fieldset>	
			</form>	
		</div>
		
		<!-- Effects settings -->
		
		<div id="effects">
			<h2><?php _e('Effects','nggallery'); ?></h2>
			<form name="effectsform" method="POST" action="<?php echo $filepath.'#effects'; ?>" >
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="thumbEffect,thumbCode,thickboxImage" />
			<p><?php _e('Here you can select the thumbnail effect, NextGEN Gallery will integrate the required HTML code in the images. Please note that only the Thickbox effect will automatic added to your theme.','nggallery'); ?>
			<?php _e('With the placeholder','nggallery'); ?><strong> %GALLERY_NAME% </strong> <?php _e('you can activate a navigation through the images (depend on the effect). Change the code line only , when you use a different thumbnail effect or you know what you do.','nggallery'); ?></p>
			<fieldset class="options"> 
				<table class="optiontable">
					<tr valign="top">
						<th><?php _e('JavaScript Thumbnail effect','nggallery') ?>:</th>
						<td>
						<select size="1" id="thumbEffect" name="thumbEffect" onchange="insertcode(this.value)">
							<option value="none" <?php selected('none', $ngg_options['thumbEffect']); ?> ><?php _e('None', 'nggallery') ;?></option>
							<option value="thickbox" <?php selected('thickbox', $ngg_options['thumbEffect']); ?> ><?php _e('Thickbox', 'nggallery') ;?></option>
							<option value="lightbox" <?php selected('lightbox', $ngg_options['thumbEffect']); ?> ><?php _e('Lightbox', 'nggallery') ;?></option>
							<option value="highslide" <?php selected('highslide', $ngg_options['thumbEffect']); ?> ><?php _e('Highslide', 'nggallery') ;?></option>
							<option value="shutter" <?php selected('shutter', $ngg_options['thumbEffect']); ?> ><?php _e('Shutter', 'nggallery') ;?></option>
							<option value="custom" <?php selected('custom', $ngg_options['thumbEffect']); ?> ><?php _e('Custom', 'nggallery') ;?></option>
						</select>
						</td>
					</tr>
					<tr valign="top">
						<th><?php _e('Link Code line','nggallery') ?> :</th>
						<td><textarea id="thumbCode" name="thumbCode" cols="50" rows="5"><?php echo htmlspecialchars(stripslashes($ngg_options['thumbCode'])); ?></textarea></td>
					</tr>
				</table>
				
				<div id="tbImage" <?php if ($ngg_options['thumbEffect'] != 'thickbox') echo 'style="display:none"'?> >
				<table class="optiontable">
					<tr valign="top">
						<th><?php _e('Select loading image','nggallery') ?> :</th>
						<td>
						<label><input name="thickboxImage" id="v2" type="radio" title="Version 2" value="loadingAnimationv2.gif" <?php checked('loadingAnimationv2.gif', $ngg_options['thickboxImage']); ?> /></label> <img src="<?php echo NGGALLERY_URLPATH.'thickbox/loadingAnimationv2.gif' ?>" alt="Version 2" />
						<label><input name="thickboxImage" id="v3" type="radio" title="Version 3" value="loadingAnimationv3.gif" <?php checked('loadingAnimationv3.gif', $ngg_options['thickboxImage']); ?> /></label> <img src="<?php echo NGGALLERY_URLPATH.'thickbox/loadingAnimationv3.gif' ?>" alt="Version 3" />
						</td>
					</tr>
				</table>
				</div>
				
			<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</fieldset>	
			</form>	
		</div>
		
		<!-- Watermark settings -->
		
		<?php
		$imageID = $wpdb->get_var("SELECT MIN(pid) FROM $wpdb->nggpictures");
		$imageID = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '$imageID'");	
		if ($imageID) $imageURL = '<img width="75%" src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$imageID->pid.'&amp;mode=watermark&amp;width=320&amp;height=240" alt="'.$imageID->alttext.'" title="'.$imageID->alttext.'" />';

		?>
		<div id="watermark">
			<h2><?php _e('Watermark','nggallery'); ?></h2>
			<p><?php _e('Please note : You can only activate the watermark under -> Manage Gallery . This action cannot be undone.', 'nggallery') ?></p>
			<form name="watermarkform" method="POST" action="<?php echo $filepath.'#watermark'; ?>" >
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="wmPos,wmXpos,wmYpos,wmType,wmPath,wmFont,wmSize,wmColor,wmText,wmOpaque" />
			<div id="zeitgeist">
				<h3><?php _e('Preview','nggallery') ?></h3>
				<p><center><?php echo $imageURL; ?></center></p>
				<h3><?php _e('Position','nggallery') ?></h3>
			    <table width="80%" border="0">
				<tr>
					<td valign="top">
						<strong><?php _e('Position','nggallery') ?></strong><br />
						<table border="1">
						<tr>
							<td><input type="radio" name="wmPos" value="topLeft" <?php checked('topLeft', $ngg_options['wmPos']); ?> /></td>
							<td><input type="radio" name="wmPos" value="topCenter" <?php checked('topCenter', $ngg_options['wmPos']); ?> /></td>
							<td><input type="radio" name="wmPos" value="topRight" <?php checked('topRight', $ngg_options['wmPos']); ?> /></td>
						</tr>
						<tr>
							<td><input type="radio" name="wmPos" value="midLeft" <?php checked('midLeft', $ngg_options['wmPos']); ?> /></td>
							<td><input type="radio" name="wmPos" value="midCenter" <?php checked('midCenter', $ngg_options['wmPos']); ?> /></td>
							<td><input type="radio" name="wmPos" value="midRight" <?php checked('midRight', $ngg_options['wmPos']); ?> /></td>
						</tr>
						<tr>
							<td><input type="radio" name="wmPos" value="botLeft" <?php checked('botLeft', $ngg_options['wmPos']); ?> /></td>
							<td><input type="radio" name="wmPos" value="botCenter" <?php checked('botCenter', $ngg_options['wmPos']); ?> /></td>
							<td><input type="radio" name="wmPos" value="botRight" <?php checked('botRight', $ngg_options['wmPos']); ?> /></td>
						</tr>
						</table>
					</td>
					<td valign="top">
						<strong><?php _e('Offset','nggallery') ?></strong><br />
						<table border="0">
							<tr>
								<td>x</td>
								<td><input type="text" name="wmXpos" value="<?php echo $ngg_options['wmXpos'] ?>" size="4" /> px</td>
							</tr>
							<tr>
								<td>y</td>
								<td><input type="text" name="wmYpos" value="<?php echo $ngg_options['wmYpos'] ?>" size="4" /> px</td>
							</tr>
						</table>
					</td>
				</tr>
				</table>
			</div> 
			<fieldset class="options">
				<table class="optiontable" border="0">
					<tr>
						<td align="left" colspan="2"><label><input type="radio" name="wmType" value="image" <?php checked('image', $ngg_options['wmType']); ?> /> <?php _e('Use image as watermark','nggallery') ?></label></td>
					</tr>
					<tr>
						<th><?php _e('URL to file','nggallery') ?> :</th>
						<td><input type="text" size="40" name="wmPath" value="<?php echo $ngg_options['wmPath']; ?>" /><br />
						<?php if(!ini_get('allow_url_fopen')) _e('The accessing of URL files is disabled at your server (allow_url_fopen)','nggallery') ?> </td>
					</tr>
					<tr>
						<td colspan="2"><hr /></td>
					</tr>
					<tr>
						<td align="left" colspan="2"><label><input type="radio" name="wmType" value="text" <?php checked('text', $ngg_options['wmType']); ?> /> <?php _e('Use text as watermark','nggallery') ?></label></td>
					</tr>
					<tr>
						<th><?php _e('Font','nggallery') ?>:</th>
						<td><select name="wmFont" size="1">	<?php 
								$fontlist = ngg_get_TTFfont();
								foreach ( $fontlist as $fontfile ) {
									echo "\n".'<option value="'.$fontfile.'" '.ngg_input_selected($fontfile, $ngg_options['wmFont']).' >'.$fontfile.'</option>';
								}
								?>
							</select><br />
							<?php if ( !function_exists(ImageTTFBBox) ) 
									_e('This function will not work, cause you need the FreeType library','nggallery');
								  else 
								  	_e('You can upload more fonts in the folder <strong>nggallery/fonts</strong>','nggallery'); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e('Size','nggallery') ?>:</th>
						<td><input type="text" name="wmSize" value="<?php echo $ngg_options['wmSize'] ?>" size="4" maxlength="2" /> px</td>
					</tr>
					<tr>
						<th><?php _e('Color','nggallery') ?>:</th>
						<td><input type="text" size="6" maxlength="6" id="wmColor" name="wmColor" onchange="setcolor('#previewText', this.value)" value="<?php echo $ngg_options['wmColor'] ?>" />
						<input type="text" size="1" readonly="readonly" id="previewText" style="background-color: #<?php echo $ngg_options['wmColor'] ?>" /> <?php _e('(hex w/o #)','nggallery') ?></td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Text','nggallery') ?>:</th>
						<td><textarea name="wmText" cols="40" rows="4"><?php echo $ngg_options['wmText'] ?></textarea></td>
					</tr>
					<tr>
						<th><?php _e('Opaque','nggallery') ?>:</th>
						<td><input type="text" name="wmOpaque" value="<?php echo $ngg_options['wmOpaque'] ?>" size="3" maxlength="3" /> % </td>
					</tr>
				</table>
			</fieldset>
			<div class="clear"> &nbsp; </div>
			<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</form>	
		</div>
		
		<!-- Slideshow settings -->
		
		<div id="slideshow">
		<form name="player_options" method="POST" action="<?php echo $filepath.'#slideshow'; ?>" >
		<?php wp_nonce_field('ngg_settings') ?>
		<input type="hidden" name="page_options" value="irWidth,irHeight,irShuffle,irLinkfromdisplay,irShownavigation,irShowicons,irWatermark,irOverstretch,irRotatetime,irTransition,irKenburns,irBackcolor,irFrontcolor,irLightcolor,irScreencolor,irAudio,irXHTMLvalid" />
		<h2><?php _e('Slideshow','nggallery'); ?></h2>
		<fieldset class="options">
		<?php if (!NGGALLERY_IREXIST) { ?><p><div id="message" class="error fade"><p><?php _e('The imagerotator.swf is not in the nggallery folder, the slideshow will not work.','nggallery') ?></p></div></p><?php }?>
		<p><?php _e('The settings are used in the JW Image Rotator Version', 'nggallery') ?> 3.15.  
		   <?php _e('See more information for the Flash Player on the web page', 'nggallery') ?> <a href="http://www.jeroenwijering.com/?item=JW_Image_Rotator" target="_blank">JW Image Rotator from Jeroen Wijering</a>.</p>
				<table class="optiontable" border="0" >
					<tr>
						<th><?php _e('Default size (W x H)','nggallery') ?>:</th>
						<td><input type="text" size="3" maxlength="4" name="irWidth" value="<?php echo $ngg_options['irWidth'] ?>" /> x
						<input type="text" size="3" maxlength="4" name="irHeight" value="<?php echo $ngg_options['irHeight'] ?>" /></td>
					</tr>					
					<tr>
						<th><?php _e('Shuffle mode','nggallery') ?>:</th>
						<td><input name="irShuffle" type="checkbox" value="1" <?php checked('1', $ngg_options['irShuffle']); ?> /></td>
					</tr>
					<tr>
						<th><?php _e('Show next image on click','nggallery') ?>:</th>
						<td><input name="irLinkfromdisplay" type="checkbox" value="1" <?php checked('1', $ngg_options['irLinkfromdisplay']); ?> /></td>
					</tr>					
					<tr>
						<th><?php _e('Show navigation bar','nggallery') ?>:</th>
						<td><input name="irShownavigation" type="checkbox" value="1" <?php checked('1', $ngg_options['irShownavigation']); ?> /></td>
					</tr>
					<tr>
						<th><?php _e('Show loading icon','nggallery') ?>:</th>
						<td><input name="irShowicons" type="checkbox" value="1" <?php checked('1', $ngg_options['irShowicons']); ?> /></td>
					</tr>
					<tr>
						<th><?php _e('Use watermark logo','nggallery') ?>:</th>
						<td><input name="irWatermark" type="checkbox" value="1" <?php checked('1', $ngg_options['irWatermark']); ?> />
						<?php _e('You can change the logo at the watermark settings','nggallery') ?></td>
					</tr>
					<tr>
						<th><?php _e('Stretch image','nggallery') ?>:</th>
						<td>
						<select size="1" name="irOverstretch">
							<option value="true" <?php selected('true', $ngg_options['irOverstretch']); ?> ><?php _e('true', 'nggallery') ;?></option>
							<option value="false" <?php selected('false', $ngg_options['irOverstretch']); ?> ><?php _e('false', 'nggallery') ;?></option>
							<option value="fit" <?php selected('fit', $ngg_options['irOverstretch']); ?> ><?php _e('fit', 'nggallery') ;?></option>
							<option value="none" <?php selected('none', $ngg_options['irOverstretch']); ?> ><?php _e('none', 'nggallery') ;?></option>
						</select>
						</td>
					</tr>
					<tr>					
						<th><?php _e('Duration time','nggallery') ?>:</th>
						<td><input type="text" size="3" maxlength="3" name="irRotatetime" value="<?php echo $ngg_options['irRotatetime'] ?>" /> <?php _e('sec.', 'nggallery') ;?></td>
					</tr>					
					<tr>					
						<th><?php _e('Transition / Fade effect','nggallery') ?>:</th>
						<td>
						<select size="1" name="irTransition">
							<option value="fade" <?php selected('fade', $ngg_options['irTransition']); ?> ><?php _e('fade', 'nggallery') ;?></option>
							<option value="bgfade" <?php selected('bgfade', $ngg_options['irTransition']); ?> ><?php _e('bgfade', 'nggallery') ;?></option>
							<option value="slowfade" <?php selected('slowfade', $ngg_options['irTransition']); ?> ><?php _e('slowfade', 'nggallery') ;?></option>
							<option value="circles" <?php selected('circles', $ngg_options['irTransition']); ?> ><?php _e('circles', 'nggallery') ;?></option>
							<option value="bubbles" <?php selected('bubbles', $ngg_options['irTransition']); ?> ><?php _e('bubbles', 'nggallery') ;?></option>
							<option value="blocks" <?php selected('blocks', $ngg_options['irTransition']); ?> ><?php _e('blocks', 'nggallery') ;?></option>
							<option value="fluids" <?php selected('fluids', $ngg_options['irTransition']); ?> ><?php _e('fluids', 'nggallery') ;?></option>
							<option value="flash" <?php selected('flash', $ngg_options['irTransition']); ?> ><?php _e('flash', 'nggallery') ;?></option>
							<option value="lines" <?php selected('lines', $ngg_options['irTransition']); ?> ><?php _e('lines', 'nggallery') ;?></option>
							<option value="random" <?php selected('random', $ngg_options['irTransition']); ?> ><?php _e('random', 'nggallery') ;?></option>
						</select>
					</tr>
					<tr>
						<th><?php _e('Use slow zooming effect','nggallery') ?>:</th>
						<td><input name="irKenburns" type="checkbox" value="1" <?php checked('1', $ngg_options['irKenburns']); ?> /></td>
					</tr>
					<tr>
						<th><?php _e('Background Color','nggallery') ?>:</th>
						<td><input type="text" size="6" maxlength="6" id="irBackcolor" name="irBackcolor" onchange="setcolor('#previewBack', this.value)" value="<?php echo $ngg_options['irBackcolor'] ?>" />
						<input type="text" size="1" readonly="readonly" id="previewBack" style="background-color: #<?php echo $ngg_options['irBackcolor'] ?>" /></td>
					</tr>
					<tr>					
						<th><?php _e('Texts / Buttons Color','nggallery') ?>:</th>
						<td><input type="text" size="6" maxlength="6" id="irFrontcolor" name="irFrontcolor" onchange="setcolor('#previewFront', this.value)" value="<?php echo $ngg_options['irFrontcolor'] ?>" />
						<input type="text" size="1" readonly="readonly" id="previewFront" style="background-color: #<?php echo $ngg_options['irFrontcolor'] ?>" /></td>
					</tr>
					<tr>					
						<th><?php _e('Rollover / Active Color','nggallery') ?>:</th>
						<td><input type="text" size="6" maxlength="6" id="irLightcolor" name="irLightcolor" onchange="setcolor('#previewLight', this.value)" value="<?php echo $ngg_options['irLightcolor'] ?>" />
						<input type="text" size="1" readonly="readonly" id="previewLight" style="background-color: #<?php echo $ngg_options['irLightcolor'] ?>" /></td>
					</tr>
					<tr>					
						<th><?php _e('Screen Color','nggallery') ?>:</th>
						<td><input type="text" size="6" maxlength="6" id="irScreencolor" name="irScreencolor" onchange="setcolor('#previewScreen', this.value)" value="<?php echo $ngg_options['irScreencolor'] ?>" />
						<input type="text" size="1" readonly="readonly" id="previewScreen" style="background-color: #<?php echo $ngg_options['irScreencolor'] ?>" /></td>
					</tr>
					<tr>					
						<th><?php _e('Background music (URL)','nggallery') ?>:</th>
						<td><input type="text" size="50" id="irAudio" name="irAudio" value="<?php echo $ngg_options['irAudio'] ?>" /></td>
					</tr>
					<tr>
						<th><?php _e('Try XHTML validation (with CDATA)','nggallery') ?>:</th>
						<td><input name="irXHTMLvalid" type="checkbox" value="1" <?php checked('1', $ngg_options['irXHTMLvalid']); ?> />
						<?php _e('Important : Could causes problem at some browser. Please recheck your page.','nggallery') ?></td>
					</tr>
					</table>
				<div class="clear"> &nbsp; </div>
				<div class="submit"><input type="submit" name="updateoption" value="<?php _e('Update') ;?> &raquo;"/></div>
			</fieldset>
		</form>
		</div>
	</div>

	<?php
}

function ngg_get_TTFfont() {
	
	$ttf_fonts = array ();
	
	// Files in wp-content/plugins/nggallery/fonts directory
	$plugin_root = NGGALLERY_ABSPATH."fonts";
	
	$plugins_dir = @ dir($plugin_root);
	if ($plugins_dir) {
		while (($file = $plugins_dir->read()) !== false) {
			if (preg_match('|^\.+$|', $file))
				continue;
			if (is_dir($plugin_root.'/'.$file)) {
				$plugins_subdir = @ dir($plugin_root.'/'.$file);
				if ($plugins_subdir) {
					while (($subfile = $plugins_subdir->read()) !== false) {
						if (preg_match('|^\.+$|', $subfile))
							continue;
						if (preg_match('|\.ttf$|', $subfile))
							$ttf_fonts[] = "$file/$subfile";
					}
				}
			} else {
				if (preg_match('|\.ttf$|', $file))
					$ttf_fonts[] = $file;
			}
		}
	}

	return $ttf_fonts;
}

/**********************************************************/
// taken from WP Core

function ngg_input_selected( $selected, $current) {
	if ( $selected == $current)
		return ' selected="selected"';
}
	
function ngg_input_checked( $checked, $current) {
	if ( $checked == $current)
		return ' checked="checked"';
}
?>