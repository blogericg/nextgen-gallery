<?php

/**
 * Class A_I18N_Image_Translation
 * @mixin C_Image_Mapper
 * @adapts I_Image_Mapper
 */
class A_I18N_Image_Translation extends Mixin
{
    function set_defaults($entity)
    {
        $this->call_parent('set_defaults', $entity);

        if (!is_admin()) {
            if (!empty($entity->description))
                $entity->description = M_I18N::translate($entity->description, 'pic_' . $entity->{$entity->id_field} . '_description');
            if (!empty($entity->alttext))
                $entity->alttext = M_I18N::translate($entity->alttext, 'pic_' . $entity->{$entity->id_field} . '_alttext');
        }
    }

}
