<?php
/**
 * @var stdClass $i18n
 * @var C_Display_Type $display_type
 */

$name = esc_attr($display_type->name);
?>
<tr>
    <td>
        <label for="<?php print $name; ?>_ecommerce_marketing">
            <?php print $i18n->enable_ecommerce; ?>
        </label>
    </td>
    <td>
        <input id="<?php print $name; ?>_ecommerce_marketing"
               name="<?php print $name; ?>_ecommerce_marketing"
               data-upsell="ecommerce"
               class="ngg_display_type_setting_marketing"
               type="radio"
               />
        <label for="<?php print $name; ?>_ecommerce_marketing">
            <?php print $i18n->yes ?>
        </label>

        <input id="<?php print $name; ?>_ecommerce_marketing_no"
               name="<?php print $name; ?>_ecommerce_marketing"
               data-upsell="ecommerce"
               class="ngg_display_type_setting_marketing"
               type="radio"
               checked="checked"/>
        <label for="<?php print $name; ?>_ecommerce_marketing_no">
            <?php print $i18n->no ?>
        </label>
    </td>
</tr>

<tr>
    <td>
        <label for="<?php print $name; ?>_proofing_marketing">
            <?php print $i18n->enable_proofing; ?>
        </label>
    </td>
    <td>
        <input id="<?php print $name; ?>_proofing_marketing"
               name="<?php print $name; ?>_proofing_marketing"
               data-upsell="proofing"
               class="ngg_display_type_setting_marketing"
               type="radio"
               />
        <label for="<?php print $name; ?>_proofing_marketing">
            <?php print $i18n->yes ?>
        </label>

        <input id="<?php print $name; ?>_proofing_marketing_no"
               name="<?php print $name; ?>_proofing_marketing"
               data-upsell="proofing"
               class="ngg_display_type_setting_marketing"
               type="radio"
               checked="checked"/>
        <label for="<?php print $name; ?>_proofing_marketing_no">
            <?php print $i18n->no ?>
        </label>
    </td>
</tr>