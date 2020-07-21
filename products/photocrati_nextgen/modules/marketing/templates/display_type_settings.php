<?php
/**
 * @var string[] $i18n
 * @var C_Display_Type $display_type
 */

$name = esc_attr($display_type->name);
?>
<tr>
    <td>
        <label for="<?php print $name; ?>_marketing">
            <?php print $i18n['label']; ?>
        </label>
    </td>
    <td>
        <input id="<?php print $name; ?>_marketing"
               class="ngg_display_type_setting_ecommerce_marketing"
               type="radio"
               name="<?php print $name; ?>_marketing"/>
        <label for="<?php print $name; ?>_marketing">
            <?php print __('Yes'); ?>
        </label>

        <input id="<?php print $name; ?>_marketing_no"
               class="ngg_display_type_setting_ecommerce_marketing"
               type="radio"
               name="<?php print $name; ?>_marketing"
               checked="checked"/>
        <label for="<?php print $name; ?>_marketing_no">
            <?php print __('No'); ?>
        </label>
    </td>
</tr>