<?php get_header(); ?>

<table width="800px" border="0" align="center" cellpadding="0" cellspacing="10" bordercolor="#000000" bgcolor="#FFFFFF">
      <tr>
        <td>

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>


		<div class="post" id="post-<?php the_ID(); ?>">
			<span  class="category"><a href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></span><br/>
			<small><span class="postdate"><?php the_time('d.m.Y  H:i') ?></span></small>

			<div class="entry">
				<?php the_content('<p class="serif">Обязательно прочитайте продолжение записи &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
				<?php the_tags( '<p>Tags: ', ', ', '</p>'); ?>

				<p class="postmetadata alt">
					<small>
			
						<?php edit_post_link('Edit this entry.','',''); ?>

					</small>
				</p>

			</div>
		</div>

	<?php comments_template(); ?>

	<?php endwhile; else: ?>

		<p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>

</td>
</tr>


</table>


<?php get_footer(); ?>
