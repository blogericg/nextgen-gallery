<?php
/**
 * @var C_Marketing_Card $block
 * @var string $link_text
 */
?>
<div class="ngg-marketing-card-large">

    <h3 class="ngg-marketing-card-title">
        <?php print $block->title; ?>
    </h3>

    <img class="ngg-marketing-card-thumbnail"
         src="<?php print esc_attr($block->thumb_url); ?>"/>

    <p class="ngg-marketing-card-description">
        <?php print $block->description; ?>
    </p>

    <div class="ngg-marketing-card-button-wrapper">

        <a class="ngg-marketing-card-button"
           href="<?php print esc_attr($block->get_upgrade_link()); ?>"
           target="_blank">
            <?php print $link_text; ?>
        </a>

    </div>

</div>