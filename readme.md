# ![WordPress](wp-admin/images/wordpress-logo.png)  
Version 2.5

Semantic Personal Publishing Platform

# First Things First

Welcome. WordPress is a very special project to me. Every developer and contributor adds something unique to the mix, and together we create something beautiful that I'm proud to be a part of. Thousands of hours have gone into WordPress, and we're dedicated to making it better every day. Thank you for making it part of your world.

— Matt Mullenweg

# Installation: Famous 5-minute install

1.  Unzip the package in an empty directory.
2.  Open up `wp-config-sample.php` with a text editor like WordPad or similar and fill in your database connection details.
3.  Save the file as `wp-config.php`
4.  Upload everything.
5.  Open <span class="file">[/wp-admin/install.php](wp-admin/install.php)</span> in your browser. This should setup the tables needed for your blog. If there is an error, double check your <span class="file">wp-config.php</span> file, and try again. If it fails again, please go to the [support forums](http://wordpress.org/support/) with as much data as you can gather.
6.  **Note the password given to you.**
7.  The install script should then send you to the [login page](wp-login.php). Sign in with the username `admin` and the password generated during the installation. You can then click on 'Profile' to change the password.

# Upgrading

Before you upgrade anything, make sure you have backup copies of any files you may have modified such as `index.php`.

## Upgrading from any previous WordPress to 2.5:

1.  Delete your old WP files, saving ones you've modified.
2.  Upload the new files.
3.  Point your browser to <span class="file">[/wp-admin/upgrade.php](wp-admin/upgrade.php).</span>
4.  You wanted more, perhaps? That's it!

## Template Changes

If you have customized your templates you will probably have to make some changes to them. If you're converting your 1.2 or earlier templates, [we've created a special guide for you](http://codex.wordpress.org/Upgrade_1.2_to_1.5).

# Online Resources

If you have any questions that aren't addressed in this document, please take advantage of WordPress' numerous online resources:

<dl>

<dt>[The WordPress Codex](http://codex.wordpress.org/)</dt>

<dd>The Codex is the encyclopedia of all things WordPress. It is the most comprehensive source of information for WordPress available.</dd>

<dt>[The Development Blog](http://wordpress.org/development/)</dt>

<dd>This is where you'll find the latest updates and news related to WordPress. Bookmark and check often.</dd>

<dt>[WordPress Planet](http://planet.wordpress.org/)</dt>

<dd>The WordPress Planet is a news aggregator that brings together posts from WordPress blogs around the web.</dd>

<dt>[WordPress Support Forums](http://wordpress.org/support/)</dt>

<dd>If you've looked everywhere and still can't find an answer, the support forums are very active and have a large community ready to help. To help them help you be sure to use a descriptive thread title and describe your question in as much detail as possible.</dd>

<dt>[WordPress IRC Channel](http://codex.wordpress.org/IRC)</dt>

<dd>Finally, there is an online chat channel that is used for discussion among people who use WordPress and occasionally support topics. The above wiki page should point you in the right direction. ([irc.freenode.net #wordpress](irc://irc.freenode.net/wordpress))</dd>

</dl>

# System Recommendations

*   PHP version **4.3** or higher.
*   MySQL version **4.0** or higher.
*   ... and a link to [http://wordpress.org](http://wordpress.org/) on your site.

WordPress is the official continuation of [b2/cafélog](http://cafelog.com/), which came from Michel V. The work has been continued by the [WordPress developers](http://wordpress.org/about/). If you would like to support WordPress, please consider [donating](http://wordpress.org/donate/).

# Upgrading from another system

WordPress can [import from a number of systems](http://codex.wordpress.org/Importing_Content). First you need to get WordPress installed and working as described above.

# XML-RPC and Atom Interface

You can now post to your WordPress blog with tools like [Windows Live Writer](http://windowslivewriter.spaces.live.com/), [Ecto](http://ecto.kung-foo.tv/), [Bloggar](http://bloggar.com/), [Radio Userland](http://radio.userland.com) (which means you can use Radio's email-to-blog feature), [NewzCrawler](http://www.newzcrawler.com/), and other tools that support the Blogging APIs! :) You can read more about [XML-RPC support on the Codex](http://codex.wordpress.org/XML-RPC_Support).

# Post via Email

You can post from an email client! To set this up go to your "Writing" options screen and fill in the connection details for your secret POP3 account. Then you need to set up `wp-mail.php` to execute periodically to check the mailbox for new posts. You can do it with Cron-jobs, or if your host doesn't support it you can look into the various website-monitoring services, and make them check your `wp-mail.php` URL.

Posting is easy: Any email sent to the address you specify will be posted, with the subject as the title. It is best to keep the address discrete. The script will _delete_ emails that are successfully posted.

# User Roles

We've eliminated user levels in order to make way for the much more flexible roles system introduced in 2.0\. You can [read more about Roles and Capabilities on the Codex](http://codex.wordpress.org/Roles_and_Capabilities).

# Final notes

*   If you have any suggestions, ideas, comments, or if you (gasp!) found a bug, join us in the [Support Forums](http://wordpress.org/support/).
*   WordPress now has a robust plugin API that makes extending the code easy. If you are a developer interested in utilizing this see the [plugin documentation in the Codex](http://codex.wordpress.org/Plugin_API). In most all cases you shouldn't modify any of the core code.

# Share the Love

WordPress has no multi-million dollar marketing campaign or celebrity sponsors, but we do have something even better—you. If you enjoy WordPress please consider telling a friend, setting it up for someone less knowledgable than yourself, or writing the author of a media article that overlooks us.

# Copyright

WordPress is released under the <abbr title="GNU Public License">GPL</abbr> (see [license.txt](license.txt)).