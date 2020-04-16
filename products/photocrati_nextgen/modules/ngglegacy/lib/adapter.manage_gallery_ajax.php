<?php
/**
 * @property C_Ajax_Controller $object
 */
class A_NGGLegacy_Manage_Gallery_Ajax extends Mixin
{
    public function ngglegacy_manage_gallery_rotate_image_action()
    {
        if (!is_user_logged_in() || !current_user_can('NextGEN Manage gallery'))
            die();

        $id = (int)$this->object->param('id');
        $preview_url = C_Gallery_Storage::get_instance()->get_image_url($id, 'full');

        $this->object->render_view(
            'photocrati-nextgen-legacy#manage_gallery_rotate_image',
            [
                'id'          => $id,
                'preview_url' => $preview_url
            ]
        );
        die();
    }

    public function ngglegacy_manage_gallery_rotate_image_backend_action()
    {
        if (!is_user_logged_in() || !current_user_can('NextGEN Manage gallery'))
            die();

        $storage = C_Gallery_Storage::get_instance();

        $image_id  = (int)$_POST['id'];
        $retval    = [];

        $params = ['watermark' => FALSE, 'reflection' => FALSE];

        $angle = $this->object->param('ra');

        switch ($angle) {
            case 'cw' :
                $params['rotation'] = 90;
                break;
            case 'ccw' :
                $params['rotation'] = -90;
                break;
            case 'fv' :
                $params['flip'] = 'h';
                break;
            case 'fh' :
                $params['flip'] = 'v';
                break;
        }

        if (C_CDN_Providers::is_cdn_configured())
        {
            $cdn = C_CDN_Providers::get_current();

            // Prevent these two generate_image_size() calls from (if offloading is enabled) from emitting an action
            // that will cause the image files to be removed from the server before the browser can see their preview
            M_CDN_Jobs::$_run_ngg_generated_image_action = FALSE;

            if ($cdn->is_offload_enabled())
            {
                $cdn->download($image_id, 'full');

                // generate_image_size() will make a new _backup file if this setting is on
                $settings = C_NextGen_Settings::get_instance();
                $settings->imgBackup = 0;
            }
        }

        $result = $storage->generate_image_size($image_id, 'full', $params);

        if ($result)
        {
            // Prevent a second rotation from being applied to the thumbnail
            $params = ['watermark' => FALSE, 'reflection' => FALSE];
            $storage->generate_image_size($image_id, 'thumbnail', $params);

            if (C_CDN_Providers::is_cdn_configured())
            {
                $cdn->upload($image_id, 'full');
                $cdn->upload($image_id, 'thumbnail');
            }

            $retval['image_url'] = $storage->get_cdn_url_for($image_id, 'full');

            // TODO: clear up orphaned files if offloading is enabled

            return $retval;
        }

        return ['error' => TRUE];
    }

}