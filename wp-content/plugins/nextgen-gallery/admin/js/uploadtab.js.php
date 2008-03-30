<?php

/**
 * @title  Uplaod-tab, required for Rich text editor
 * @author Alex Rabe
 * @copyright 2008
 */

require_once('../../../../../wp-config.php');
require_once('../../../../../wp-admin/admin.php'); 
cache_javascript_headers(); 
$ngg_options = get_option('ngg_options');

// get the effect code
if ($ngg_options['thumbEffect'] != "none") $thumbcode = stripslashes($ngg_options['thumbCode']);
if ($ngg_options['thumbEffect'] == "highslide") $thumbcode = 'class="highslide" onclick="return hs.expand(this)"';
else $thumbcode = str_replace("%GALLERY_NAME%", "", $thumbcode);

?>
addLoadEvent( function() {
	theFileList = {
		currentImage: {ID: 0},
		nonce: '',
		tab: '',
		gal: '',
		postID: 0,

		initializeVars: function() {
			this.urlData  = document.location.href.split('?');
			this.params = this.urlData[1].toQueryParams();
			this.postID = this.params['post_id'];
			this.tab = this.params['tab'];
			this.gal = this.params['select_gal'];
			this.style = this.params['style'];
			this.ID = this.params['ID'];
			if ( !this.style )
				this.style = 'default';
			var nonceEl = $('nonce-value');
			if ( nonceEl )
				this.nonce = nonceEl.value;
			if ( this.ID ) {
				this.grabImageData( this.ID );
				this.imageView( this.ID );
			}
		},

		initializeLinks: function() {
			if ( this.ID )
				return;
			$$('a.file-link').each( function(i) {
				var id = i.id.split('-').pop();
				i.onclick = function(e) { theFileList[ 'inline' == theFileList.style ? 'imageView' : 'editView' ](id, e); }
			} );
		},

		grabImageData: function(id) {
			if ( id == this.currentImage.ID )
				return;

			this.currentImage.src = ( 0 == id ? '' : $('nggimage-url-' + id).value );
			this.currentImage.thumb = ( 0 == id ? '' : $('nggimage-thumb-url-' + id).value );
			this.currentImage.title = ( 0 == id ? '' : $('nggimage-title-' + id).value );
			this.currentImage.alttext = ( 0 == id ? '' : $('nggimage-alttext-' + id).value );
			this.currentImage.description = ( 0 == id ? '' : $('nggimage-description-' + id).value );
			var widthEl = $('nggimage-width-' + id);
			if ( widthEl ) {
				this.currentImage.width = ( 0 == id ? '' : widthEl.value );
				this.currentImage.height = ( 0 == id ? '' : $('nggimage-height-' + id).value );
			} else {
				this.currentImage.width = false;
				this.currentImage.height = false;
			}
			this.currentImage.isImage = 1;
			this.currentImage.ID = id;
		},

		imageView: function(id, e) {
			this.prepView(id);
			var h = '';

			h += "<div id='upload-file'>"
			if ( this.ID ) {
				var params = $H(this.params);
				params.ID = '';
				params.action = '';
				h += "<a href='" + this.urlData[0] + '?' + params.toQueryString() + "' title='<?php echo attribute_escape(__('Browse your files')); ?>' class='back'><?php echo attribute_escape(__('&laquo; Back')); ?></a>";
			} else {
				h += "<a href='#' onclick='return theFileList.cancelView();'  title='<?php echo attribute_escape(__('Browse your files')); ?>' class='back'><?php echo attribute_escape(__('&laquo; Back')) ?></a>";
			}
			h += "<div id='file-title'>"
			h += "<h2>" + this.currentImage.title + "</h2>";
			h += " &#8212; <span>";
			h += "<a href='#' onclick='return theFileList.editView(" + id + ");'><?php echo attribute_escape(__('Edit')); ?></a>"
			h += "</span>";
			h += '</div>'
			h += "<div id='upload-file-view' class='alignleft'>";
			h += "<a href='" + this.currentImage.src + "' onclick='return false;' title='<?php echo attribute_escape(__('Direct link to file')); ?>'>";
			h += "<img src='" + ( this.currentImage.thumb ? this.currentImage.thumb : this.currentImage.src ) + "' alt='" + this.currentImage.title + "' width='" + this.currentImage.width + "' height='" + this.currentImage.height + "' />";
			h += "</a>";
			h += "</div>";

			h += "<form name='uploadoptions' id='uploadoptions' class='alignleft'>";
			h += "<table>";
			var display = [];
			var checked = 'display-thumb';
			display.push("<label for='display-thumb'><input type='radio' name='display' id='display-thumb' value='thumb' /> <?php echo attribute_escape(__('Thumbnail')); ?></label><br />");
			display.push("<label for='display-full'><input type='radio' name='display' id='display-full' value='full' /> <?php echo attribute_escape(__('Full size')); ?></label>");
			if ( display.length ) {
				display.push("<br /><label for='display-title'><input type='radio' name='display' id='display-title' value='title' /> <?php echo attribute_escape(__('Title')); ?></label>");
				h += "<tr><th style='padding-bottom:.5em'><?php echo attribute_escape(__('Show:')); ?></th><td style='padding-bottom:.5em'>";
				$A(display).each( function(i) { h += i; } );
				h += "</td></tr>";
			}

			h += "<tr><th><?php echo attribute_escape(__('Link to:')); ?></th><td>";
			h += "<label for='link-file'><input type='radio' name='link' id='link-file' value='file' checked='checked'/> <?php echo attribute_escape(__('File')); ?></label><br />";
			h += "<label for='link-none'><input type='radio' name='link' id='link-none' value='none' /> <?php echo attribute_escape(__('None')); ?></label>";
			h += "</td></tr>";

			h += "<tr><td colspan='2'><p class='submit'>";
			h += "<input type='button' class='button' name='send' onclick='theFileList.sendToEditor(" + id + ")' value='<?php echo attribute_escape(__('Send to editor &raquo;')); ?>' />";
			h += "</p></td></tr></table>";
			h += "</form>";

			h += "</div>";

			new Insertion.Top('upload-content', h);
			var displayEl = $(checked);
			if ( displayEl )
				displayEl.checked = true;

			if (e) Event.stop(e);
			return false;
		},

		editView: function(id, e) {
			this.prepView(id);
			var h = '';

			var action = 'upload.php?style=' + this.style + '&amp;tab=' + this.tab;
			if ( this.postID )
				action += '&amp;post_id=' + this.postID;

			h += "<form id='upload-file' method='post' action='" + action + "'>";
			if ( this.ID ) {
				var params = $H(this.params);
				params.ID = '';
				params.action = '';
				h += "<a href='" + this.urlData[0] + '?' + params.toQueryString() + "'  title='<?php echo attribute_escape(__('Browse your files')); ?>' class='back'><?php echo attribute_escape(__('&laquo; Back')); ?></a>";
			} else {
				h += "<a href='#' onclick='return theFileList.cancelView();'  title='<?php echo attribute_escape(__('Browse your files')); ?>' class='back'><?php echo attribute_escape(__('&laquo; Back')); ?></a>";
			}
			h += "<div id='file-title'>"
			h += "<h2>" + this.currentImage.title + "</h2>";
			h += " &#8212; <span>";
			h += "<a href='#' onclick='return theFileList.imageView(" + id + ");'><?php echo attribute_escape(__('Insert')); ?></a>"
			h += "</span>";
			h += '</div>'
			h += "<div id='upload-file-view' class='alignleft'>";
			h += "<a href='" + this.currentImage.src + "' onclick='return false;' title='<?php echo wp_specialchars(__('Direct link to file')); ?>'>";
			h += "<img src='" + ( this.currentImage.thumb ? this.currentImage.thumb : this.currentImage.src ) + "' alt='" + this.currentImage.title + "' width='" + this.currentImage.width + "' height='" + this.currentImage.height + "' />";
			h += "</a>";
			h += "</div>";

			h += "<table><col /><col class='widefat' /><tr>"
			h += "<th scope='row'><label for='url'><?php echo attribute_escape(__('URL')); ?></label></th>";
			h += "<td><input type='text' id='url' class='readonly' value='" + this.currentImage.src + "' readonly='readonly' /></td>";
			h += "</tr><tr>";
			h += "<th scope='row'><label for='image_title'><?php echo attribute_escape(__('Alt &amp; Title Text','nggallery')); ?></label></th>";
			h += "<td><input type='text' id='image_title' name='image_title' value='" + this.currentImage.alttext + "' /></td>";
			h += "</tr><tr>";
			h += "<th scope='row'><label for='image_desc'><?php echo attribute_escape(__('Description')); ?></label></th>";
			h += "<td><textarea name='image_desc' id='image_desc'>" + this.currentImage.description + "</textarea></td>";
			h += "</tr><tr id='buttons' class='submit'><td colspan='2'><input type='button' id='delete' name='delete' class='delete alignleft' value='<?php echo attribute_escape(__('Delete File')); ?>' onclick='theFileList.deleteFile(" + id + ");' />";
			h += "<input type='hidden' name='from_tab' value='" + this.tab + "' />";
			h += "<input type='hidden' name='action' id='action-value' value='update' />";
			h += "<input type='hidden' name='ID' value='" + id + "' />";
			h += "<input type='hidden' name='from_gal' id='from_gal' value='" + this.gal + "' />";
			h += "<input type='hidden' name='_wpnonce' value='" + this.nonce + "' />";
			h += "<div class='submit'><input type='submit' name='save' id='save' value='<?php echo attribute_escape(__('Save &raquo;')); ?>' /></div>";
			h += "</td></tr></table></form>";

			new Insertion.Top('upload-content', h);
			if (e) Event.stop(e);
			return false;		
		},

		prepView: function(id) {
			this.cancelView( true );
			var filesEl = $('upload-files');
			if ( filesEl )
				filesEl.hide();
			var navEl = $('current-tab-nav');
			if ( navEl )
				navEl.hide();
			var selGAL = $('select-gallery');
			if ( selGAL )
				selGAL.hide();
			this.grabImageData(id);
		},

		cancelView: function( prep ) {
			if ( !prep ) {
				var filesEl = $('upload-files');
				if ( filesEl )
					Element.show(filesEl);
				var navEl = $('current-tab-nav');
				if ( navEl )
					Element.show(navEl);
				var selGAL = $('select-gallery');
				if ( selGAL )
					Element.show(selGAL);
			}
			if ( !this.ID )
				this.grabImageData(0);
			var div = $('upload-file');
			if ( div )
				Element.remove(div);
			return false;
		},

		sendToEditor: function(id) {
			this.grabImageData(id);
			var link = '';
			var display = '';
			var h = '';

			link = $A(document.forms.uploadoptions.elements.link).detect( function(i) { return i.checked; } ).value;
			displayEl = $A(document.forms.uploadoptions.elements.display).detect( function(i) { return i.checked; } )
			if ( displayEl )
				display = displayEl.value;
			else if ( 1 == this.currentImage.isImage )
				display = 'full';

			if ( 'none' != link )
				h += '<a href="' + this.currentImage.src + '" <?php echo $thumbcode; ?> title="' + this.currentImage.alttext + '">';
			if ( display && 'title' != display )
				h += "<img src='" + ( 'thumb' == display ? ( this.currentImage.thumb ) : ( this.currentImage.src ) ) + "' alt='" + this.currentImage.title + "' title='" + this.currentImage.alttext + "' />";
			else
				h += this.currentImage.alttext;
			if ( 'none' != link )
				h += '</a>';

			var win = window.opener ? window.opener : window.dialogArguments;
			if ( !win )
				win = top;
			tinyMCE = win.tinyMCE;
			if ( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
				tinyMCE.selectedInstance.getWin().focus();
				tinyMCE.execCommand('mceInsertContent', false, h);
			} else
				win.edInsertContent(win.edCanvas, h);
			if ( !this.ID )
				this.cancelView();
			return false;
		},

		deleteFile: function(id) {
			if ( confirm("<?php printf(js_escape(__("Are you sure you want to delete the file '%s'?\nClick ok to delete or cancel to go back.")), '" + this.currentImage.title + "'); ?>") ) {
				$('action-value').value = 'delete';
				$('upload-file').submit();
				return true;
			}
			return false;
		}
			
	};
	theFileList.initializeVars();
	theFileList.initializeLinks();
} );