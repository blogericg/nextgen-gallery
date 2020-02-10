<?php

/*
 * ATTENTION: Update C_NextGen_Rest_V1 when adding any new routes
 */

class C_NextGen_Rest_V1_Galleries
{
    public function register_routes()
    {
        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/galleries',
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'galleries_list')
                ),
                'schema' => array($this, 'galleries_list_schema')
            )
        );

        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/galleries/(?P<id>\d+)',
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
                    'callback' => array($this, 'gallery_info')
                ),
                'schema' => array($this, 'gallery_info_schema')
            )
        );
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
    public function get_gallery_schema_properties()
    {
        return array(
            'id' => array(
                'description' => __('Unique identifier for the gallery.', 'nggallery'),
                'type'        => 'integer',
                'readonly'    => TRUE
            ),
            'title' => array(
                'description' => __('Gallery title.', 'nggallery'),
                'type'        => 'string'
            ),
            'slug' => array(
                'description' => __('Slug used to identify galleries in URL.', 'nggallery'),
                'type'        => 'string',
                'readonly'    => TRUE
            ),
            'path' => array(
                'description' => __('Gallery path under the site document root.', 'nggallery'),
                'type'        => 'string',
                'readonly'    => TRUE
            ),
            'description' => array(
                'description' => __('Gallery description.', 'nggallery'),
                'type'        => array('string', 'null')
            ),
            'page id' => array(
                'description' => __('WordPress page or post ID linked to gallery. Zero for no association.', 'nggallery'),
                'type'        => 'integer'
            ),
            'preview image id' => array(
                'description'  => __('Image ID used for previews of the gallery.', 'nggallery'),
                'type'         => 'integer'
            ),
            'author id' => array(
                'description' => __('WordPress user ID that created the gallery.', 'nggallery'),
                'type'        => 'integer'
            ),
            '_links' => array(
                'description' => __('Related resources', 'nggallery'),
                'type'        => 'object'
            )
        );
    }

    /**
     * @return array
     */
    public function gallery_info_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'gallery',
            'type'       => 'object',
            'properties' => $this->get_gallery_schema_properties()
        );
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|array
     */
    public function gallery_info($args)
    {
        $mapper  = C_Gallery_Mapper::get_instance();
        $gallery = $mapper->find($args->get_param('id'));

        if (!$gallery)
            return new WP_Error(
                'invalid_gallery_id',
                __('Invalid gallery ID', 'nggallery'),
                array('status' => 404)
            );

        return $this->format_gallery_output($gallery);
    }

    /**
     * @param stdClass $gallery
     * @return array
     */
    public function format_gallery_output($gallery)
    {
        return array(
            'id'               => $gallery->gid,
            'title'            => $gallery->title,
            'slug'             => $gallery->slug,
            'path'             => $gallery->path,
            'description'      => $gallery->galdesc,
            'page id'          => $gallery->pageid,
            'preview image id' => $gallery->previewpic,
            'author id'        => $gallery->author,
            '_links' => array(
                'self' => array(
                    'href' => get_rest_url(NULL, 'ngg/v1/galleries/' . $gallery->gid)
                ),
                'children' => array(
                    'href' => get_rest_url(NULL, 'ngg/v1/galleries/' . $gallery->gid . '/images')
                )
            )
        );
    }

    public function galleries_list_schema()
    {
        return array(
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title'   => 'gallery',
            'type'    => 'array',
            'items'   => array(
                'allOf' => array(
                    array(
                        'type' => 'object'
                    ),
                    array(
                        'required' => array(
                            'id',
                            'title',
                            'slug',
                            'path',
                            'description',
                            'page id',
                            'preview image id',
                            'author id',
                            '_links'
                        )
                    ),
                    array(
                        'properties' => $this->get_gallery_schema_properties()
                    )
                )
            )
        );
    }

    public function galleries_list()
    {
        $mapper  = C_Gallery_Mapper::get_instance();
        $retval = array();
        foreach ($mapper->find_all() as $gallery) {
            $retval[] = $this->format_gallery_output($gallery);
        }

        return $retval;
    }
}