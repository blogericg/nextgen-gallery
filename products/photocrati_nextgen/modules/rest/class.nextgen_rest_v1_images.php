<?php

/*
 * ATTENTION: Update C_NextGen_Rest_V1 when adding any new routes
 */

class C_NextGen_Rest_V1_Images extends WP_REST_Controller
{
    public function register_routes()
    {
        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/galleries/(?P<id>\d+)/images',
            array(
                'args' => array(
                    'id' => array(
                        'description'       => __('Gallery ID', 'nggallery'),
                        'type'              => 'integer',
                        'required'          => TRUE,
                        'validate_callback' => array($this, 'validate_gallery_id')
                    )
                ),
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'images_list'),
                    'permission_callback' => [$this, 'permission_callback']
                ),
                'schema' => array($this, 'images_list_schema')
            )
        );
    }

    public function permission_callback() {
        if (!current_user_can('NextGEN Manage others gallery'))
            return new WP_Error(
                'rest_forbidden',
                esc_html__('Permission denied', 'nggallery'),
                ['status' => 401]
            );
        return TRUE;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function validate_gallery_id($id)
    {
        return is_numeric($id);
    }

    /**
     * @return array
     */
    public function images_list_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'image',
            'type'       => 'array',
            'items'      => array(
                'allOf' => array(
                    array(
                        'type' => 'object'
                    ),
                    array(
                        'required' => array(
                            'id',
                            'alttext',
                            'description',
                            'slug',
                            'post id',
                            'filename',
                            'excluded',
                            'sort order',
                            'date',
                            'image url',
                            'thumbnail url'
                        )
                    ),
                    array(
                        'properties' => array(
                            'id' => array(
                                'description' => __('Unique identifier for the image.', 'nggallery'),
                                'type'        => 'integer',
                                'readOnly'    => TRUE
                            ),
                            'alttext' => array(
                                'description' => __('Titled used for alt attribute when displaying images.', 'nggallery'),
                                'type'        => 'string'
                            ),
                            'description' => array(
                                'description' => __('Long form description of the image.', 'nggallery'),
                                'type'        => array('string', 'null')
                            ),
                            'slug' => array(
                                'description' => __('Slug generated from image alttext attribute.', 'nggallery'),
                                'type'        => 'string',
                                'readOnly'    => TRUE
                            ),
                            'post id' => array(
                                'description' => __('WordPress page or post ID linked to image. Zero for no association.', 'nggallery'),
                                'type'        => 'integer'
                            ),
                            'filename' => array(
                                'description' => __('Image filename.', 'nggallery'),
                                'type'        => 'string',
                                'readOnly'    => TRUE
                            ),
                            'excluded' => array(
                                'description' => __('Whether the image is excluded when displaying its parent gallery.', 'nggallery'),
                                'type'        => 'boolean'
                            ),
                            'sort order' => array(
                                'description' => __('The order in which the image appears when displaying a gallery. Default zero.', 'nggallery'),
                                'type'        => 'integer'
                            ),
                            'date' => array(
                                'description' => __('EXIF date or the time of upload.', 'nggallery'),
                                'type'        => 'string'
                            ),
                            'image url' => array(
                                'description' => __('Publicly accessible URL to the main image file.', 'nggallery'),
                                'type'        => 'string',
                                'readOnly'    => TRUE
                            ),
                            'thumbnail url' => array(
                                'description' => __('Publicly accessible URL to the main image thumbnail.', 'nggallery'),
                                'type'        => 'string',
                                'readOnly'    => TRUE
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @param WP_REST_Request $args
     * @return array|WP_Error
     */
    public function images_list($args)
    {
        $gallery_mapper = C_Gallery_Mapper::get_instance();
        $image_mapper   = C_Image_Mapper::get_instance();
        $storage        = C_Gallery_Storage::get_instance();

        $gallery = $gallery_mapper->find($args->get_param('id'));

        if (!$gallery)
            return new WP_Error(
                'invalid_gallery_id',
                __('Invalid gallery ID', 'nggallery'),
                array('status' => 404)
            );

        $images = $image_mapper->select()->where(array('galleryid = %s', $gallery->gid))->run_query();
        $retval = array();

        foreach ($images as $image) {
            $retval[] = array(
                'id'            => $image->pid,
                'alttext'       => $image->alttext,
                'description'   => $image->description,
                'slug'          => $image->image_slug,
                'post id'       => $image->post_id,
                'filename'      => $image->filename,
                'excluded'      => $image->exclude == 0 ? FALSE : TRUE,
                'sort order'    => $image->sortorder,
                'date'          => $image->imagedate,
                'image url'     => $storage->get_image_url($image, 'full'),
                'thumbnail url' => $storage->get_image_url($image, 'thumb')
            );
        }

        return $retval;
    }
}