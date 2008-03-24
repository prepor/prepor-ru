<?php get_header(); ?>

		<table width="90%" border="0" align="center" cellpadding="0" cellspacing="5" bordercolor="#000000" bgcolor="#FFFFFF">
		      <tr>
		        <td>
	<?php if (have_posts()) : ?>

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
			<div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
			<div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
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
