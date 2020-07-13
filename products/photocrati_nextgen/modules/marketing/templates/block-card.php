<?php
/**
 * @var C_Marketing_Block_Card $block
 * @var string $link_text
 */
?>
<div class="ngg-marketing-block-card">

    <h3 class="ngg-marketing-block-card-title">
        <?php print $block->title; ?>
    </h3>

    <img class="ngg-marketing-block-card-thumbnail"
         src="<?php print esc_attr($block->thumb_url); ?>"/>

    <p class="ngg-marketing-block-card-description">
        <?php print $block->description; ?>
    </p>

    <div class="ngg-marketing-block-card-button-wrapper">

        <a class="ngg-marketing-block-card-button"
           href="<?php print esc_attr($block->get_upgrade_link()); ?>"
           target="_blank">
            <?php print $link_text; ?>
        </a>

    </div>

</div>