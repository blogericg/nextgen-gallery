<?php

/**
 * Class A_I18N_Displayed_Gallery_Translation
 * @mixin C_Displayed_Gallery
 * @adapts I_Displayed_Gallery
 */
class A_I18N_Displayed_Gallery_Translation extends Mixin
{
    function _get_image_entities($source_obj, $limit, $offset, $id_only, $returns)
    {
        $results = $this->call_parent('_get_image_entities', $source_obj, $limit, $offset, $id_only, $returns);

        if (!is_admin() && in_array('image', $source_obj->returns))
        {
            foreach ($results as $entity) {
                if (!empty($entity->description))
                    $entity->description = M_I18N::translate($entity->description, 'pic_' . $entity->pid . '_description');
                if (!empty($entity->alttext))
                    $entity->alttext = M_I18N::translate($entity->alttext, 'pic_' . $entity->pid . '_alttext');
            }
        }

        return $results;
    }
}