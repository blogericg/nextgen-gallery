<?php

/**
 * Class A_Gallery_Storage_Frame_Event
 * @mixin C_Gallery_Storage
 * @adapts I_Gallery_Storage
 */
class A_Gallery_Storage_Frame_Event extends Mixin
{
    function generate_thumbnail($image, $params = null, $skip_defaults = false)
    {
        $retval = $this->call_parent('generate_thumbnail', $image, $params, $skip_defaults);

        if (is_admin() && ($image = C_Image_Mapper::get_instance()->find($image))) {
            $controller = C_Display_Type_Controller::get_instance();
            $storage    = C_Gallery_Storage::get_instance();
            $app        = C_Router::get_instance()->get_routed_app();

            $image->thumb_url = $controller->set_param_for(
                $app->get_routed_url(TRUE),
                'timestamp',
                time(),
                NULL,
                $storage->get_thumb_url($image)
            );

            $event = new stdClass();
            $event->pid = $image->{$image->id_field};
            $event->id_field = $image->id_field;
            $event->thumb_url = $image->thumb_url;

            C_Frame_Event_Publisher::get_instance('attach_to_post')->add_event(
                array(
                    'event' => 'thumbnail_modified',
                    'image' => $event,
                )
            );
        }

        return $retval;
    }
}