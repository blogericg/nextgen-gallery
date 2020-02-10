<?php

/**
 * Class A_I18N_Gallery_Translation
 * @mixin C_Gallery_Mapper
 * @adapts I_Gallery_Mapper
 */
class A_I18N_Gallery_Translation extends Mixin
{
    function set_defaults($entity)
    {
        $this->call_parent('set_defaults', $entity);

        if (!is_admin()) {
            if (!empty($entity->title))
                $entity->title = M_I18N::translate($entity->title, 'gallery_' . $entity->{$entity->id_field} . '_name');
            if (!empty($entity->galdesc))
                $entity->galdesc = M_I18N::translate($entity->galdesc, 'gallery_' . $entity->{$entity->id_field} . '_description');
        }
    }

}
