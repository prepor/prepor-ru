<?php
/*
Plugin Name: LiveJournal Crossposter
Plugin URI: http://ebroder.net/livejournal-crossposter/
Description: Automatically copies all posts to a LiveJournal or other LiveJournal-based blog. Editing or deleting a post will be replicated as well. This plugin was inspired by <a href="http://blog.mytechaid.com/">Scott Buchanan's</a> <a href="http://blog.mytechaid.com/archives/2005/01/10/xanga-crossposter/">Xanga Crossposter</a>
Version: 2.0
Author: Evan Broder
Author URI: http://ebroder.net/

	Copyright (c) 2007 Evan Broder

	Permission is hereby granted, free of charge, to any person obtaining a
	copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation
	the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
	DEALINGS IN THE SOFTWARE.
*/

define('LJXP_DOMAIN', '/ljxp/lang/ljxp');
load_plugin_textdomain(LJXP_DOMAIN);

require_once(ABSPATH . '/wp-includes/class-IXR.php');
if(version_compare($wp_version, "2.1", "<")) {
	require_once(ABSPATH . '/wp-includes/template-functions-links.php');
}

// Create the LJXP Options Page
function ljxp_add_pages() {
	add_options_page("LiveJournal", "LiveJournal", 6, __FILE__, 'ljxp_display_options');
}

// Display the options page
function ljxp_display_options() {
	global $wpdb;

	// Just in case they don't exist, create the options
	add_option('ljxp_host');
	add_option('ljxp_username');
	add_option('ljxp_password');
	add_option('ljxp_custom_name_on');
	add_option('ljxp_custom_name');
	add_option('ljxp_privacy');
	add_option('ljxp_comments');
	add_option('ljxp_tag');
	add_option('ljxp_more');
	add_option('ljxp_community');
	add_option('ljxp_skip_cats');
	add_option('ljxp_header_loc');
	add_option('ljxp_custom_header');

	// Retrieve these for the form
	$old_host = stripslashes(get_option('ljxp_host'));
	$old_username = stripslashes(get_option('ljxp_username'));
	$old_name = stripslashes(get_option('ljxp_custom_name'));
	$old_name_on = get_option('ljxp_custom_name_on');
	$old_privacy = get_option('ljxp_privacy');
	$old_comments = get_option('ljxp_comments');
	$old_tag = get_option('ljxp_tag');
	$old_more = get_option('ljxp_more');
	$old_community = stripslashes(get_option('ljxp_community'));
	// Categories to be crossposted are stored not stored; categories to be
	// "skipped" are instead. It's inverted so that new categories can be
	// assumed to be in the clear
	$old_skip_cats = get_option('ljxp_skip_cats');
	$old_header_loc = get_option('ljxp_header_loc');
	$old_custom_header = stripslashes(get_option('ljxp_custom_header'));

	// host should default to LJ - it's what most people use anyway
	if("" == $old_host) {
		// This sets up a default value. If we don't store it, the default val
		// will never get stored to the database
		update_option('ljxp_host', 'www.livejournal.com');
		$old_host = "www.livejournal.com";
	}

	// I think that we should default to just using the name of the blog, so
	// let's set it - same reason as above
	if("" == $old_name_on) {
		update_option('ljxp_custom_name_on', '0');
		$old_name_on = "0";
	}

	// We're going to default to public posts - just because I say so
	if("" == $old_privacy) {
		update_option('ljxp_privacy', 'public');
		$old_privacy = "public";
	}

	// Defaulting to no comments on LJ - makes more sense
	if("" == $old_comments) {
		update_option('ljxp_comments', '0');
		$old_comments = '0';
	}

	// Default to allowing tags - only in strange i18n situations would you
	// want them disabled
	if("" == $old_tag) {
		update_option('ljxp_tag', '1');
		$old_tag = '1';
	}

	// The default option is to link back to the original site if there is a
	// <!--more--> tag
	if("" == $old_more) {
		update_option('ljxp_more', 'link');
		$old_more = 'link';
	}

	if("" == $old_skip_cats) {
		update_option('ljxp_skip_cats', array());
		$old_skip_cats = array();
	}

	// 0 means top, 1 means bottom
	if("" == $old_header_loc) {
		update_option('ljxp_header_loc', 0);
		$old_header_loc = 0;
	}

	// If we're handling a submission, save the data
	if(isset($_REQUEST['update_lj_options']) || isset($_REQUEST['crosspost_all'])) {
		// If certain values get changed, we want to recrosspost all entries,
		// so just in case, grab a list of all entries that have been
		// crossposted
		$repost_ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='ljID'");
		// This is just a tracking variable. If changes are made to multiple
		// settings that would each require reposting everything, we don't want
		// to spam LJ's servers, so we just keep track of whether or not we
		// need to do a massive update at the end. This isn't necessary with
		// deletions, as the function will just give up after the first time
		$need_update = 0;

		// Avoiding useless queries - confirming that the value changed
		if($old_host != $_REQUEST['host']) {
			// So that we don't have crossposted residue left behind on the old
			// server, get rid of all posts. This also eliminates potential
			// problems when any crosspotsed entry is edited - before, LJXP
			// would have tried to edit a nonexistent entry on the new server
			ljxp_delete_all($repost_ids);
			update_option('ljxp_host', $_REQUEST['host']);
			// So that the new value shows up in the form
			$old_host = $_REQUEST['host'];
			// Then repost everything
			$need_update = 1;
		}

		if($old_username != $_REQUEST['username']) {
			ljxp_delete_all($repost_ids);
			update_option('ljxp_username', $_REQUEST['username']);
			$old_username = $_REQUEST['username'];
			$need_update = 1;
		}

		if($old_name_on != $_REQUEST['custom_name_on']) {
			update_option('ljxp_custom_name_on', $_REQUEST['custom_name_on']);
			$old_name_on = $_REQUEST['custom_name_on'];
			$need_update = 1;
		}

		if($old_name != $_REQUEST['custom_name']) {
			update_option('ljxp_custom_name', $_REQUEST['custom_name']);
			$old_name = $_REQUEST['custom_name'];
			if($old_name_on) {
				$need_update = 1;
			}
		}

		if($old_privacy != $_REQUEST['privacy']) {
			update_option('ljxp_privacy', $_REQUEST['privacy']);
			$old_privacy = $_REQUEST['privacy'];
			$need_update = 1;
		}

		if($old_comments != $_REQUEST['comments']) {
			update_option('ljxp_comments', $_REQUEST['comments']);
			$old_comments = $_REQUEST['comments'];
			$need_update = 1;
			ljxp_post_all($repost_ids);
		}

		if($old_tag != $_REQUEST['tag']) {
			update_option('ljxp_tag', $_REQUEST['tag']);
			$old_tag = $_REQUEST['tag'];
		}

		if($old_more != $_REQUEST['more']) {
			update_option('ljxp_more', $_REQUEST['more']);
			$old_more = $_REQUEST['more'];
			$need_update = 1;
		}

		if($old_community != $_REQUEST['community']) {
			ljxp_delete_all($repost_ids);
			update_option('ljxp_community', $_REQUEST['community']);
			$old_community = $_REQUEST['community'];
			$need_update = 1;
		}

		if($old_header_loc != $_REQUEST['header_loc']) {
			update_option('ljxp_header_loc', $_REQUEST['header_loc']);
			$old_header_loc = $_REQUEST['header_loc'];
		}

		if($old_custom_header != $_REQUEST['custom_header']) {
			update_option('ljxp_custom_header', $_REQUEST['custom_header']);
			$old_custom_header = $_REQUEST['custom_header'];
		}

		sort($old_skip_cats);
		$new_skip_cats = array_diff(get_all_category_ids(), (array)$_REQUEST['post_category']);
		sort($new_skip_cats);
		if($old_skip_cats != $new_skip_cats) {
			update_option('ljxp_skip_cats', $new_skip_cats);
			$old_skip_cats = $new_skip_cats;
		}

		// If a password value is entered, md5 it for security and store to the
		// database
		// LJ challenge authentication works with only knowing the md5 of the
		// password
		if($_REQUEST['password'] != "") {
			update_option('ljxp_password', md5($_REQUEST['password']));
		}

		if($need_update && isset($_REQUEST['update_lj_options'])) {
			@set_time_limit(0);
			ljxp_post_all($repost_ids);
		}

		if(isset($_REQUEST['crosspost_all'])) {
			@set_time_limit(0);
			ljxp_post_all($wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status='publish' AND post_type='post'"));
		}

		// Copied from another options page
		echo '<div id="message" class="updated fade"><p><strong>';
		_e('Options saved.', LJXP_DOMAIN);
		echo '</strong></p></div>';
	}

	// And, finally, output the form
	// May add some Javascript to disable the custom_name field later - don't
	// feel like it now, though
?>
<div class="wrap">
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<h2><?php _e('LiveJournal Crossposter Options', LJXP_DOMAIN); ?></h2>
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">
			<tr valign="top">
				<th width="33%" scope="row"><?php _e('LiveJournal-compliant host:', LJXP_DOMAIN) ?></th>
				<td><input name="host" type="text" id="host" value="<?php echo htmlentities($old_host); ?>" size="40" /><br />
				<?php

				_e('If you are using a LiveJournal-compliant site other than LiveJournal (like DeadJournal), enter the domain name here. LiveJournal users can use the default value', LJXP_DOMAIN);

				?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('LJ Username', LJXP_DOMAIN); ?></th>
				<td><input name="username" type="text" id="username" value="<?php echo htmlentities($old_username); ?>" size="40" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('LJ Password', LJXP_DOMAIN); ?></th>
				<td><input name="password" type="password" id="password" value="" size="40" /><br />
				<?php

				_e('Only enter a value if you wish to change the stored password. Leaving this field blank will not erase any passwords already stored.', LJXP_DOMAIN);

				?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Community', LJXP_DOMAIN); ?></th>
				<td><input name="community" type="text" id="community" value="<?php echo htmlentities($old_community); ?>" size="40" /><br />
				<?php

				_e("If you wish your posts to be copied to a community, enter the community name here. Leaving this space blank will copy the posts to the specified user's journal instead", LJXP_DOMAIN);

				?>
				</td>
			</tr>
		</table>
		<fieldset class="options">
			<legend><?php _e('Blog Header', LJXP_DOMAIN); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e('Crosspost header/footer location', LJXP_DOMAIN); ?></th>
					<td><label><input name="header_loc" type="radio" value="0" <?php
					if(0 == $old_header_loc) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Top of post', LJXP_DOMAIN); ?></label><br />
					<label><input name="header_loc" type="radio" value="1" <?php
					if(1 == $old_header_loc) {
						echo 'checked="checked" ';
					}
					?>/> <? _e('Bottom of post', LJXP_DOMAIN); ?></label></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Set blog name for crosspost header/footer', LJXP_DOMAIN); ?></th>
					<td><label><input name="custom_name_on" type="radio" value="0" <?php
					if(0 == $old_name_on) {
						echo 'checked="checked" ';
					}
					?>/> <?php printf(__('Use the title of your blog (%s)', LJXP_DOMAIN), get_settings('blogname')); ?></label><br />
					<label><input name="custom_name_on" type="radio" value="1" <?php
					if(1 == $old_name_on) {
						echo 'checked="checked" ';
					}
					?>/> <? _e('Use a custom title', LJXP_DOMAIN); ?></label></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Custom blog title', LJXP_DOMAIN); ?></th>
					<td><input name="custom_name" type="text" id="custom_name" value="<?php echo htmlentities($old_name); ?>" size="40" /><br />
					<?php

					_e('If you chose to use a custom title above, enter the title here. This will be used in the header which links back to this site at the top of each post on the LiveJournal.', LJXP_DOMAIN);

					?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Custom crosspost header/footer', LJXP_DOMAIN); ?></th>
					<td><textarea name="custom_header" id="custom_name" rows="3" cols="40"><?php echo htmlentities($old_custom_header); ?></textarea><br />
					<?php

					_e("If you wish to use LJXP's dynamically generated post header/footer, you can ignore this setting. If you don't like the default crosspost header/footer, specify your own here. For flexibility, you can choose from a series of case-sensitive substitution strings, listed below:", LJXP_DOMAIN);

					?>
					<dl>
						<dt>[blog_name]</dt>
						<dd><?php _e('The title of your blog, as specified above', LJXP_DOMAIN); ?></dd>

						<dt>[blog_link]</dt>
						<dd><?php _e("The URL of your blog's homepage", LJXP_DOMAIN); ?></dd>

						<dt>[permalink]</dt>
						<dd><?php _e('A permanent URL to the post being crossposted', LJXP_DOMAIN); ?></dd>

						<dt>[comments_link]</dt>
						<dd><?php _e('The URL for comments. Generally this is the permalink URL with #comments on the end', LJXP_DOMAIN); ?></dd>
					</dl>
					</td>
			</table>
		</fieldset>
		<fieldset class="options">
			<legend><?php _e('Post Privacy', LJXP_DOMAIN); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e('Privacy level for all posts to LiveJournal', LJXP_DOMAIN); ?></th>
					<td><label><input name="privacy" type="radio" value="public" <?php
					if("public" == $old_privacy) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Public', LJXP_DOMAIN); ?></label><br />
					<label><input name="privacy" type="radio" value="private" <?php
					if("private" == $old_privacy) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Private', LJXP_DOMAIN); ?></label><br />
					<label><input name="privacy" type="radio" value="friends" <?php
					if("friends" == $old_privacy) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Friends only', LJXP_DOMAIN); ?></label><br />
				</tr>
			</table>
		</fieldset>
		<fieldset class="options">
			<legend><?php _e('LiveJournal Comments', LJXP_DOMAIN); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e('Should comments be allowed on LiveJournal?', LJXP_DOMAIN); ?></th>
					<td><label><input name="comments" type="radio" value="0" <?php
					if(0 == $old_comments) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Require users to comment on WordPress', LJXP_DOMAIN); ?></label><br />
					<label><input name="comments" type="radio" value="1" <?php
					if("1" == $old_comments) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Allow comments on LiveJournal', LJXP_DOMAIN); ?></label><br />
				</tr>
			</table>
		</fieldset>
		<fieldset class="options">
			<legend><?php _e('LiveJournal Tags', LJXP_DOMAIN); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33% scope="row"><?php _e('Tag entries on LiveJournal?', LJXP_DOMAIN); ?></th>
					<td><label><input name="tag" type="radio" value="1" <?php
					if(1 == $old_tag) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Tag LiveJournal entries with WordPress categories', LJXP_DOMAIN); ?></label><br />
					<label><input name="tag" type="radio" value="0" <?php
					if(0 == $old_tag) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Do not tag LiveJournal entries', LJXP_DOMAIN); ?></label><br />
					<?php

					_e('You may with to disable this feature if you are posting in an alphabet other than the Roman alphabet. LiveJournal does not seem to support non-Roman alphabets in tag names.', LJXP_DOMAIN);

					?>
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset class="options">
			<legend><?php _e('Handling of &lt;!--More--&gt;', LJXP_DOMAIN); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e('How should LJXP handle More tags?', LJXP_DOMAIN); ?></th>
					<td><label><input name="more" type="radio" value="link" <?php
					if("link" == $old_more) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Link back to WordPress', LJXP_DOMAIN); ?></label><br />
					<label><input name="more" type="radio" value="lj-cut" <?php
					if("lj-cut" == $old_more) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Use an lj-cut', LJXP_DOMAIN); ?></label><br />
					<label><input name="more" type="radio" value="copy" <?php
					if("copy" == $old_more) {
						echo 'checked="checked" ';
					}
					?>/> <?php _e('Copy the entire entry to LiveJournal', LJXP_DOMAIN); ?></label><br />
				</tr>
			</table>
		</fieldset>
		<fieldset class="options">
			<legend><?php _e('Category Selection', LJXP_DOMAIN); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e('Select which categories should be crossposted', LJXP_DOMAIN); ?></th>
					<td>
					<?php

					write_nested_categories(ljxp_cat_select(get_nested_categories(), $old_skip_cats));

					?><br />
					<?php

					_e('Any post that has <em>at least one</em> of the above categories selected will be crossposted.');

					?>
					</td>
				</tr>
			</table>
		</fieldset>
		<p class="submit">
			<input type="submit" name="crosspost_all" value="<?php _e('Update Options and Crosspost All WordPress entries', LJXP_DOMAIN); ?>" />
			<input type="submit" name="update_lj_options" value="<?php _e('Update Options'); ?>" style="font-weight: bold;" />
		</p>
	</form>
</div>
<?php
}

function ljxp_cat_select($cats, $selected_cats) {
	foreach((array)$cats as $key=>$cat) {
		$cats[$key]['checked'] = !in_array($cat['cat_ID'], $selected_cats);
		$cats[$key]['children'] = ljxp_cat_select($cat['children'], $selected_cats);
	}
	return $cats;
}

function ljxp_post($post_id) {
	global $wpdb;

	// If the post was manually set to not be crossposted, give up now
	if(get_post_meta($post_id, 'no_lj', true)) {
		return $post_id;
	}

	// Get the relevent info out of the database
	$host = stripslashes(get_option('ljxp_host'));
	$user = stripslashes(get_option('ljxp_username'));
	$pass = get_option('ljxp_password');
	$custom_name_on = get_option('ljxp_custom_name_on');
	$custom_name = stripslashes(get_option('ljxp_custom_name'));
	$privacy = get_option('ljxp_privacy');
	$comments = get_option('ljxp_comments');
	$tag = get_option('ljxp_tag');
	$more = get_option('ljxp_more');
	$community = stripslashes(get_option('ljxp_community'));
	$skip_cats = get_option('ljxp_skip_cats');
	$copy_cats = array_diff(get_all_category_ids(), $skip_cats);
	$header_loc = get_option('ljxp_header_loc');
	$custom_header = stripslashes(get_option('ljxp_custom_header'));

	// Override the default options if done for this specific post
	// The subtraction bit just converts the values for the post_meta to the
	// bool for the $comments var
	if(0 != get_post_meta($post_id, 'ljxp_comments', true)) {
		$comments = 2 - get_post_meta($post_id, 'ljxp_comments', true);
	}

	if("" != get_post_meta($post_id, 'ljxp_privacy', true)) {
		$privacy = get_post_meta($post_id, 'ljxp_privacy', true);
	}

	// If the post shows up in the forbidden category list and it has been
	// crossposted before (so the forbidden category list must have changed),
	// delete the post. Otherwise, just give up now
	$do_crosspost = 0;
	foreach(wp_get_post_cats(1, $post_id) as $cat) {
		if(in_array($cat, $copy_cats)) {
			$do_crosspost = 1;
		}
	}
	if(!$do_crosspost) {
		return ljxp_delete($post_id);
	}

	// And create our connection
	$client = new IXR_Client($host, '/interface/xmlrpc');

	// Get the challenge string
	// Using challenge for the most security. Allows pwd hash to be stored
	// instead of pwd
	if (!$client->query('LJ.XMLRPC.getchallenge')) {
		wp_die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
	}

	// And retrieve the challenge string
	$response = $client->getResponse();
	$challenge = $response['challenge'];

	$post = & get_post($post_id);

	// Insert the name of the page we're linking back to based on the options set
	if(!$custom_name_on) {
		$blogName .= get_option("blogname");
	}
	else {
		$blogName .= $custom_name;
	}

	if('' == $custom_header) {
		$postHeader = '<p style="border: 1px solid black; padding: 3px;"><b>';

		// If the post is not password protected, follow standard procedure
		if(!$post->post_password) {
			$postHeader .= __('Originally published at', LJXP_DOMAIN);
			$postHeader .= ' <a href="'.get_permalink($post_id).'">';
			$postHeader .= $blogName;
			$postHeader .= '</a>.';
		}
		// If the post is password protected, put up a special message
		else {
			$postHeader .= __('This post is password protected. You can read it at', LJXP_DOMAIN);
			$postHeader .= ' <a href="'.get_permalink($post_id).'">';
			$postHeader .= $blogName;
			$postHeader .= '</a>, ';
			$postHeader .= __('where it was originally posted', LJXP_DOMAIN);
			$postHeader .= '.';
		}

		// Depending on whether comments or allowed or not, alter the header
		// appropriately
		if($comments) {
			$postHeader .= sprintf(__(' You can comment here or <a href="%s#comments">there</a>.', LJXP_DOMAIN), get_permalink($post_id));
		}
		else {
			$postHeader .= sprintf(__(' Please leave any <a href="%s#comments">comments</a> there.', LJXP_DOMAIN), get_permalink($post_id));
		}

		$postHeader .= '</b></p>';
	}
	else {
		$postHeader = $custom_header;

		$find = array('[blog_name]', '[blog_link]', '[permalink]', '[comments_link]');
		$replace = array($blogName, get_settings('home'), get_permalink($post_id), get_permalink($post_id).'#comments');
		$postHeader = str_replace($find, $replace, $postHeader);
	}

	// $the_event will eventually be passed to the LJ XML-RPC server.
	$the_event = "";

	// and if the post isn't password protected, we need to put together the
	// actual post
	if(!$post->post_password) {
		// and if there's no <!--more--> tag, we can spit it out and go on our
		// merry way
		if(strpos($post->post_content, "<!--more-->") === false) {
			$the_event .= apply_filters('the_content', $post->post_content);
		}
		else {
			$content = explode("<!--more-->", $post->post_content, 2);
			$the_event .= apply_filters('the_content', $content[0]);
			switch($more) {
			case "copy":
				$the_event .= apply_filters('the_content', $content[1]);
				break;
			case "link":
				$the_event .= sprintf('<p><a href="%s#more-%s">', get_permalink($post_id), $post_id) .
					__('Read the rest of this entry &raquo;', LJXP_DOMAIN) .
					'</a></p>';
				break;
			case "lj-cut":
				$the_event .= '<lj-cut text="' .
					__('Read the rest of this entry &amp;raquo;', LJXP_DOMAIN) .
					'">' . apply_filters('the_content', $content[1]) . '</lj-cut>';
				break;
			}
		}
	}

	// Either prepend or append the header to $the_event, depending on the
	// config setting
	// Remember that 0 is at the top, 1 at the bottom
	if($header_loc) {
		$the_event .= $postHeader;
	}
	else {
		$the_event = $postHeader.$the_event;
	}

	// Retrieve the categories that the post is marked as - for LJ tagging
	$cats = wp_get_post_cats('', $post_id);
	// I need them in an array for my next trick to work
	if(!is_array($cats)) {
		$cats = array($cats);
	}
	// Convert the category IDs of all categories to their text names
	$cat_names = array_map("get_cat_name", $cats);
	// Turn them into a comma-seperated list for the API
	$cat_string = implode(", ", $cat_names);

	// Get the most recent post (to see if this is it - it it's not, backdate)
	$recent_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_date DESC LIMIT 1");

	// Get a timestamp for retrieving dates later
	$date = strtotime($post->post_date);

	$args = array();
	$args['username'] = $user;
	$args['auth_method'] = 'challenge';
	$args['auth_challenge'] = $challenge;
	// Formula for challenge response is
	// md5(challenge + md5(pwd))
	$args['auth_response'] = md5($challenge . $pass);

	// Makes LJ expect UTF-8 text instead of ISO-8859-1
	$args['ver'] = "1";

	// The filters run the WP texturization - cleans up the code
	$args['event'] = $the_event;
	$args['subject'] = apply_filters('the_title', $post->post_title);

	// All of the relevent dates and times
	$args['year'] = date('Y', $date);
	$args['mon'] = date('n', $date);
	$args['day'] = date('j', $date);
	$args['hour'] = date('G', $date);
	$args['min'] = date('i', $date);
						// Enable or disable comments as specified by the
						// settings
	$args['props'] = array("opt_nocomments" => !$comments,
						// Tells LJ to not run it's formatting (replacing \n
						// with <br>, etc) because it's already been done by
						// the texturization
						"opt_preformatted" => true,
						// If the most recent post is not the one being dealt
						// with now, mark it as backdated so it doesn't jump to
						// the top of friendlists and such
						"opt_backdated" => !($post_id == $recent_id));

	// If tagging is enabled,
	if($tag) {
		// Set tags
		$args['props']['taglist'] = $cat_string;
	}

	// Set the privacy level according to the settings
	switch($privacy) {
	case "public":
		$args['security'] = "public";
		break;
	case "private":
		$args['security'] = "private";
		break;
	case "friends":
		$args['security'] = "usemask";
		$args['allowmask'] = 1;
	}

	// If crossposting to a community, specify that
	if("" != $community) {
		$args['usejournal'] = $community;
	}

	// Assume this is a new post
	$method = 'LJ.XMLRPC.postevent';

	// But check to see if there's an LJ post associated with our WP post
	if(get_post_meta($post_id, 'ljID', true)) {
		// If there is, add the itemid attribute and change from posting to editing
		$args['itemid'] = get_post_meta($post_id, 'ljID', true);
		$method = 'LJ.XMLRPC.editevent';
	}

	// And awaaaayyy we go!
	if (!$client->query($method, $args)) {
		wp_die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
	}

	// If we were making a new post on LJ, we need the itemid for future reference
	if('LJ.XMLRPC.postevent' == $method) {
		$response = $client->getResponse();
		// Store it to the metadata
		add_post_meta($post_id, 'ljID', $response['itemid']);
	}
	// If you don't return this, other plugins and hooks won't work
	return $post_id;
}

function ljxp_delete($post_id) {
	// Pull the post_id
	$ljxp_post_id = get_post_meta($post_id, 'ljID', true);

	// Ensures that there's actually a value. If the post was never
	// cross-posted, the value wouldn't be set, and there's no point in
	// deleting entries that don't exist
	if($ljxp_post_id == 0) {
		return $post_id;
	}

	// Get the necessary login info
	$host = get_option('ljxp_host');
	$user = get_option('ljxp_username');
	$pass = get_option('ljxp_password');

	// And open the XMLRPC interface
	$client = new IXR_Client($host, '/interface/xmlrpc');

	// Request the challenge for authentication
	if (!$client->query('LJ.XMLRPC.getchallenge')) {
		wp_die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
	}

	// And retrieve the challenge that LJ returns
	$response = $client->getResponse();
	$challenge = $response['challenge'];

	// Most of this is the same as before. The important difference is the
	// value of $args[event]. By setting it to a null value, LJ deletes the
	// entry. Really rather klunky way of doing things, but not my code!
	$args = array();
	$args['username'] = $user;
	$args['auth_method'] = 'challenge';
	$args['auth_challenge'] = $challenge;
	$args['auth_response'] = md5($challenge . $pass);
	$args['itemid'] = $ljxp_post_id;
	$args['event'] = "";
	$args['subject'] = "Delete this entry";
	// I probably don't need to set these, but, hell, I've got it working
	$args['year'] = date('Y');
	$args['mon'] = date('n');
	$args['day'] = date('j');
	$args['hour'] = date('G');
	$args['min'] = date('i');

	// And awaaaayyy we go!
	if (!$client->query('LJ.XMLRPC.editevent', $args)) {
		wp_die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
	}

	delete_post_meta($post_id, 'ljID');

	return $post_id;
}

function ljxp_edit($post_id) {
	// This function will delete a post from LJ if it's changed from the
	// published status or if crossposting was just disabled on this post

	// Pull the post_id
	$ljxp_post_id = get_post_meta($post_id, 'ljID', true);

	// Ensures that there's actually a value. If the post was never
	// cross-posted, the value wouldn't be set, so we're done
	if(0 == $ljxp_post_id) {
		return $post_id;
	}

	$post = & get_post($post_id);

	// See if the post is currently published. If it's been crossposted and its
	// state isn't published, then it should be deleted
	// Also, if it has been crossposted but it's set to not crosspost, then
	// delete it
	if('publish' != $post->post_status || 1 == get_post_meta($post_id, 'no_lj', true)) {
		ljxp_delete($post_id);
	}

	return $post_id;
}

function ljxp_sidebar() {
	global $post;
?>
	<fieldset class="dbx-box">
		<h3 class="dbx-handle"><?php _e('LiveJournal', LJXP_DOMAIN); ?>:</h3>
		<div class="dbx-content">
			<label for="ljxp_crosspost_go" class="selectit">
			<input id="ljxp_crosspost_go" type="radio" name="ljxp_crosspost" value="1"<?php checked(get_post_meta($post->ID, 'no_lj', true), 0); ?>/>
			<?php _e('Crosspost', LJXP_DOMAIN); ?>
			</label>
			<label for="ljxp_crosspost_nogo" class="selectit">
			<input id="ljxp_crosspost_nogo" type="radio" name="ljxp_crosspost" value="0"<?php checked(get_post_meta($post->ID, 'no_lj', true), 1); ?>/>
			<?php _e('Do not crosspost', LJXP_DOMAIN); ?>
			</label>

			<br />

			<label for="ljxp_comments_default" class="selectit">
			<input id="ljxp_comments_default" type="radio" name="ljxp_comments" value="0"<?php checked(get_post_meta($post->ID, 'ljxp_comments', true), 0); ?>/>
			<?php _e('Default comments setting', LJXP_DOMAIN); ?>
			</label>
			<label for="ljxp_comments_on" class="selectit">
			<input id="ljxp_comments_on" type="radio" name="ljxp_comments" value="1"<?php checked(get_post_meta($post->ID, 'ljxp_comments', true), 1); ?>/>
			<?php _e('Comments on', LJXP_DOMAIN); ?>
			</label>
			<label for="ljxp_comments_off" class="selectit">
			<input id="ljxp_comments_off" type="radio" name="ljxp_comments" value="2"<?php checked(get_post_meta($post->ID, 'ljxp_comments', true), 2); ?>/>
			<?php _e('Comments off', LJXP_DOMAIN); ?>
			</label>

			<br />

			<label for="ljxp_privacy_default" class="selectit">
			<input id="ljxp_privacy_default" type="radio" name="ljxp_privacy" value="0"<?php checked(get_post_meta($post->ID, 'ljxp_privacy', true), 0); ?>/>
			<?php _e('Default post privacy setting', LJXP_DOMAIN); ?>
			</label>
			<label for="ljxp_privacy_public" class="selectit">
			<input id="ljxp_privacy_public" type="radio" name="ljxp_privacy" value="public"<?php checked(get_post_meta($post->ID, 'ljxp_privacy', true), 'public'); ?>/>
			<?php _e('Public post', LJXP_DOMAIN); ?>
			</label>
			<label for="ljxp_privacy_private" class="selectit">
			<input id="ljxp_privacy_private" type="radio" name="ljxp_privacy" value="private"<?php checked(get_post_meta($post->ID, 'ljxp_privacy', true), 'private'); ?>/>
			<?php _e('Private post', LJXP_DOMAIN); ?>
			</label>
			<label for="ljxp_privacy_friends" class="selectit">
			<input id="ljxp_privacy_friends" type="radio" name="ljxp_privacy" value="friends"<?php checked(get_post_meta($post->ID, 'ljxp_privacy', true), 'friends'); ?>/>
			<?php _e('Friends only', LJXP_DOMAIN); ?>
			</label>
		</div>
	</fieldset>
<?php
}

function ljxp_save($post_id) {
	// If the magic crossposting variable isn't equal to 'crosspost', then the
	// box wasn't checked
	// Using publish_post hook for the case of a state change---this will
	// be called before crossposting occurs
	// Using save_post for the case where it's draft or private - the value
	// still needs to be saved
	// Using edit_post for the case in which it's changed from crossposted to
	// not crossposted in an edit

	// At least one of those hooks is probably unnecessary, but I can't figure
	// out which one
	if(isset($_POST['ljxp_crosspost'])) {
		delete_post_meta($post_id, 'no_lj');
		if(0 == $_POST['ljxp_crosspost']) {
			add_post_meta($post_id, 'no_lj', '1');
		}
	}
	if(isset($_POST['ljxp_comments'])) {
		delete_post_meta($post_id, 'ljxp_comments');
		if("0" != $_POST['ljxp_comments']) {
			add_post_meta($post_id, 'ljxp_comments', $_POST['ljxp_comments']);
		}
	}
	if(isset($_POST['ljxp_privacy'])) {
		delete_post_meta($post_id, 'ljxp_privacy');
		if("0" != $_POST['ljxp_privacy']) {
			add_post_meta($post_id, 'ljxp_privacy', $_POST['ljxp_privacy']);
		}
	}
}

function ljxp_delete_all($repost_ids) {
	foreach((array)$repost_ids as $id) {
		ljxp_delete($id);
	}
}

function ljxp_post_all($repost_ids) {
	foreach((array)$repost_ids as $id) {
		ljxp_post($id);
	}
}

add_action('admin_menu', 'ljxp_add_pages');
if(get_option('ljxp_username') != "") {
	add_action('publish_post', 'ljxp_post');
	add_action('edit_post', 'ljxp_edit');
	add_action('delete_post', 'ljxp_delete');
	add_action('dbx_post_sidebar', 'ljxp_sidebar');
	add_action('publish_post', 'ljxp_save', 1);
	add_action('save_post', 'ljxp_save', 1);
	add_action('edit_post', 'ljxp_save', 1);
}

?>