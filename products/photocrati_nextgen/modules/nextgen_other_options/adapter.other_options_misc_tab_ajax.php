<?php

/**
 * @property A_Other_Options_Misc_Tab_Ajax $object
 */
class A_Other_Options_Misc_Tab_Ajax extends Mixin
{

    function get_legacy_featured_images_count_action()
    {
        if (!current_user_can('administrator'))
            return ['error' => __('This request requires an authenticated administrator', 'nggallery')];

        global $wpdb;

        $query = "SELECT COUNT(`post_id`)
                  FROM  `{$wpdb->postmeta}`
                  WHERE `meta_key` = '_thumbnail_id'
                  AND   `meta_value` LIKE 'ngg-%'";

        return [
            'remaining' => (int)$wpdb->get_var($query)
        ];
    }

    function update_legacy_featured_images_action()
    {
        if (!current_user_can('administrator'))
            return ['error' => __('This request requires an authenticated administrator', 'nggallery')];

        global $wpdb;

        $query = "SELECT `post_id`, `meta_value`
                  FROM   `{$wpdb->postmeta}`
                  WHERE  `meta_key` = '_thumbnail_id'
                  AND    `meta_value` LIKE 'ngg-%'
                  LIMIT  1";

        $results = $wpdb->get_results($query);

        $storage = C_Gallery_Storage::get_instance();

        // There's only at most one entry in $results
        foreach ($results as $post) {
            $image_id = str_replace('ngg-', '', $post->meta_value);
            $storage->set_post_thumbnail($post->post_id, $image_id, FALSE);
        }

        return $this->object->get_legacy_featured_images_count_action();
    }

}