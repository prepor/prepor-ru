</td>
  </tr>
  <tr>
    <td>
      <div class="meta">
        <div align="right">
          <table width="40%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td><br />
                <? if(!is_home()): ?><a href="<?=get_bloginfo('rss2_url')?>"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a> <a href="<?=get_bloginfo('rss2_url')?>">всего сайта</a><br />
<? endif; ?>
                <? if(!is_home()): ?>
<? $link=get_category_feed_link(get_cat_ID('lytdybr'), ''); ?><a href="<?=$link?>" title="RSS для этой категории"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a> <a href="<?=$link?>">lytdybr</a><br />
<? $link=get_category_feed_link(get_cat_ID('tech'), ''); ?><a href="<?=$link?>" title="RSS для этой категории"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a> <a href="<?=$link?>">tech</a><br />
<? $link=get_category_feed_link(get_cat_ID('photos'), ''); ?><a href="<?=$link?>" title="RSS для этой категории"><img src="<?=get_template_directory_uri()?>/images/feed-icon-14x14.png" width="14" height="14" /></a> <a href="<?=$link?>">photos</a><br />
<? endif; ?>
Автор: <a href="1">Андрей Руденко</a> <br />
Движок: <a href="1">WordPress</a></td>
            </tr>
          </table>
          <br />
        </div>
      </div></td>
  </tr>
</table>
