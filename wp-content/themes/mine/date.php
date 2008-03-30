<?php get_header(); ?>
<table width="800px" border="0" align="center" cellpadding="0" cellspacing="10" bordercolor="#000000" bgcolor="#FFFFFF">
      <tr>
        <td>
<?php if (have_posts()) : ?>
	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle">Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category</h2>
	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle">Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</h2>
	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle">Архив за <?php the_time('d.m.Y'); ?></h2>
	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle">Архив за <?php the_time('m.Y'); ?></h2>
	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle">Архив за <?php the_time('Y'); ?> год</h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="pagetitle">Author Archive</h2>
	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle">Blog Archives</h2>
	  <?php } ?>
<?php while (have_posts()) : the_post(); ?>


	<div class="post" id="post-<?php the_ID(); ?>">
		<span  class="category"><a href="<?php the_permalink() ?>" rel="bookmark" title="Постоянная ссылка на запись"><?php the_title(); ?></a></span><br/>
		<small><span class="postdate"><?php the_time('d.m.Y  H:i') ?> <!-- by <?php the_author() ?> --></span></small>
		

		<p>
			<?php the_content('Обязательно прочитайте продолжение записи &raquo;'); ?>
		</p>

		<p class="postmetadata"><?php the_tags('Tags: ', ', ', '<br />'); ?>  | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('нет комментариев &#187;', '1 комментарий &#187;', 'комментариев: % &#187;'); ?></p>
	</div>

<?php endwhile; ?>

<div class="navigation">
	<div class="alignleft"><?php next_posts_link('&laquo; Посты пораньше') ?></div>
	<div class="alignright"><?php previous_posts_link('Newer Посты попозже') ?></div>
</div>

<?php else : ?>

<h2 class="center">Not Found</h2>
<p class="center">Sorry, but you are looking for something that isn't here.</p>
<?php include (TEMPLATEPATH . "/searchform.php"); ?>

<?php endif; ?>
</td>
</tr>


</table>

<?php get_footer(); ?>
