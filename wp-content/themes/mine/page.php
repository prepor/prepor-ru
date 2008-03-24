<?php get_header(); ?>

<table width="90%" border="0" align="center" cellpadding="0" cellspacing="5" bordercolor="#000000" bgcolor="#FFFFFF">
      <tr>
        <td>

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<div class="post" id="post-<?php the_ID(); ?>">
		<span  class="category"><?php the_title(); ?></span>
			<p>
				<?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

			</p>
		</div>
		<?php endwhile; endif; ?>
	<?php edit_post_link('Edit this entry.', '<p>', '</p>'); ?>
</td>
</tr>


</table>


<?php get_footer(); ?>