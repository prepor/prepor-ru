<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> &raquo; Blog Archive <?php } ?> <?php wp_title(); ?></title>

<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<style type="text/css" media="screen">

<?php
// Checks to see whether it needs a sidebar or not
if ( !$withcomments && !is_single() ) {
?>
	#page { background: url("<?php bloginfo('stylesheet_directory'); ?>/images/kubrickbg-<?php bloginfo('text_direction'); ?>.jpg") repeat-y top; border: none; }
<?php } else { // No sidebar ?>
	#page { background: url("<?php bloginfo('stylesheet_directory'); ?>/images/kubrickbgwide.jpg") repeat-y top; border: none; }
<?php } ?>

  <style type="text/css">
    body {
	background-color: #666666;
}
  .style1 {
	font-size: 5em;
	color: #ffffff;
	margin: 10px;
	
}
  .style2 {
	font-size: 2em;
	color: #DB4105;
}
  .meta {
	color: #FFFFFF;
	
	text-align: center;
}
	.meta a {
		color:#FFFFFF
	}
  </style>
</style>

<?php wp_head(); ?>
</head>
<body>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td><h1 align="center" class="style1">prepor.ru <a href="<?=get_bloginfo('rss2_url')?>" title="RSS для всего сайта"><img src="<?=get_template_directory_uri()?>/images/feed-icon-28x28.png" width="28" height="28" /></a></h1></td>
	  </tr>
	  <tr>
	    <td><table width="70%" border="0" align="center" cellpadding="0" cellspacing="5" bordercolor="#000000" bgcolor="#FFFFFF">
	      <tr>
	        <td>
	          	<? $cat=get_cat_ID('lytdybr'); ?>
		          <p><a href="<?=get_category_link($cat)?>"><span class="style2">lytdybr</span></a>  <a href="<?=get_category_feed_link($cat, '')?>" title="RSS для этой категории"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a></p>
	          <p>Эта часть может быть интересна мне, родным, друзьям и сочувствующим. А может быть никому не интересна.</p>
			 <?
			 $lastposts = get_posts('numberposts=5&category='.$cat);
			 foreach($lastposts as $post) :?>
			    <p><a href="<?php the_permalink() ?>"><?php the_time('d.m.Y') ?> <?=the_title()?> &gt;&gt;</a></p>
			 <? endforeach; ?>
	          	          
	        </td>
	        <td width="50%" rowspan="2" valign="top">
	          	<? $cat=get_cat_ID('photos'); ?>
		          <p><a href="<?=get_category_link($cat)?>"><span class="style2">photos</span></a>  <a href="<?=get_category_feed_link( $cat, '')?>" title="RSS для этой категории"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a></p>
	          <p>Претендующие на художественность фотки. Типа таких:</p>
	          <p><?=apply_filters('the_content',"[gallery=1]")?></p>
	          </td>
	      </tr>
	      <tr>
	        <td><? $cat=get_cat_ID('tech'); ?>
	          <p><a href="<?=get_category_link($cat)?>"><span class="style2">tech</span></a>  <a href="<?=get_category_feed_link($cat, '')?>" title="RSS для этой категории"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a></p>
	          <p>Ключевые слова: Ruby и RoR. Ну а еще я люблю маки.</p>
			 <?
			 $lastposts = get_posts('numberposts=5&category='.$cat);
			 foreach($lastposts as $post) :?>
			    <p><a href="<?php the_permalink() ?>"><?php the_time('d.m.Y') ?> <?=the_title()?> &gt;&gt;</a></p>
			 <? endforeach; ?>
	        </td>
	        </tr>

	    </table>


<?php get_footer(); ?>