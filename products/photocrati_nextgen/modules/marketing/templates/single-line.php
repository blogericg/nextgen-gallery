<?php
/**
 * @var C_Marketing_Single_Line $line
 * @var string $link_text
 */
?>
<div class="ngg-marketing-single-line">
    <p>
       <?php print $line->title; ?>
        <a class="ngg-marketing-single-line-link"
           href="<?php print esc_attr($line->get_upgrade_link()); ?>"
           target="_blank"
           rel="noreferrer noopener">
            <?php print $link_text; ?>
        </a>
    </p>
</div>