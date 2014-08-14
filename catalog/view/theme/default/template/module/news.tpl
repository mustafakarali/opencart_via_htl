

<span class="wztit fz14"><?php echo $heading_title; ?></span>
<ul>
    <?php foreach ($all_news as $news) { ?>
    <li>
        <a href="<?php echo $news['view']; ?>"><?php echo $news['title']; ?></a>
    </li>
    <?php } ?>
</ul>
