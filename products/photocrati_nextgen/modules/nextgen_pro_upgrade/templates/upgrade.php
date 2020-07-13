<?php
/**
 * @var stdClass $i18n
 * @var string $header_image_url
 * @var array $marketing_blocks
 */ ?>
<div id="ngg_page_content">

    <div class="ngg_page_content_header">
        <img src="<?php print esc_attr($header_image_url); ?>"
             alt=""/>
        <h3>
            <?php print $i18n->page_title; ?>
        </h3>
    </div>

    <div id="ngg_upgrade_to_pro_page_wrapper">
        <?php
        foreach ($marketing_blocks as $block) {
            /** @var C_Marketing_Block_Card $block */
            print $block->render();
        } ?>
    </div>

</div>
