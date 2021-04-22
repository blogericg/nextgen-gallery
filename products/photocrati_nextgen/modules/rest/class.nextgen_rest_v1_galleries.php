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
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'galleries_list'),
                    'permission_callback' => [$this, 'permission_callback']
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
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'gallery_info'),
                    'permission_callback' => [$this, 'permission_callback']
                ),
                'schema' => array($this, 'gallery_info_schema')
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
                    'methods'  => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'edit_gallery'),
                    'permission_callback' => '__return_true' // [$this, 'permission_callback']
                ),
                'schema' => array($this, 'gallery_info_schema')
            )
        );
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|WP_REST_Response
     */
    function edit_gallery($args)
    {
        $mapper  = C_Gallery_Mapper::get_instance();
        $gallery = $mapper->find($args->get_param('id'));

        if (!$gallery)
            return new WP_Error(
                'invalid_gallery_id',
                __('Invalid gallery ID', 'nggallery'),
                array('status' => 404)
            );

        // Allow only the properties described by the gallery schema to be editable and disallow readOnly items
        $properties = $this->get_gallery_schema_properties();
        $params = $args->get_params();
        foreach ($params as $param => $value) {
            if (!in_array($param, array_keys($properties)) || !empty($properties[$param]['readOnly']))
                continue;
            $gallery->{$param} = $value;
        }

        if (!$mapper->save($gallery))
        {
            return new WP_Error(
                'invalid_gallery_properties',
                __('Could not save gallery', 'nggallery'),
                ['status' => 500]
            );
        }
        else {
            return rest_ensure_response($this->format_gallery_output($gallery));
        }
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
    public function get_gallery_schema_properties()
    {
        return array(
            'id' => array(
                'description' => __('Unique identifier for the gallery.', 'nggallery'),
                'type'        => 'integer',
                'readOnly'    => TRUE
            ),
            'title' => array(
                'description' => __('Gallery title.', 'nggallery'),
                'type'        => 'string'
            ),
            'slug' => array(
                'description' => __('Slug used to identify galleries in URL.', 'nggallery'),
                'type'        => 'string',
                'readOnly'    => TRUE
            ),
            'path' => array(
                'description' => __('Gallery path under the site document root.', 'nggallery'),
                'type'        => 'string',
                'readOnly'    => TRUE
            ),
            'galdesc' => array(
                'description' => __('Gallery description.', 'nggallery'),
                'type'        => array('string', 'null')
            ),
            'pageid' => array(
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
     * @return WP_Error|WP_REST_Response
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

        return rest_ensure_response($this->format_gallery_output($gallery));
    }

    /**
     * @param stdClass $gallery
     * @return array
     */
    public function format_gallery_output($gallery)
    {
        return [
            'id'         => $gallery->gid,
            'title'      => $gallery->title,
            'slug'       => $gallery->slug,
            'path'       => $gallery->path,
            'galdesc'    => $gallery->galdesc,
            'pageid'     => $gallery->pageid,
            'previewpic' => $gallery->previewpic,
            'author'     => $gallery->author,
            '_links' => array(
                'self' => array(
                    'href' => get_rest_url(NULL, 'ngg/v1/galleries/' . $gallery->gid)
                ),
                'children' => array(
                    'href' => get_rest_url(NULL, 'ngg/v1/galleries/' . $gallery->gid . '/images')
                )
            )
        ];
    }

    /**
     * @return array
     */
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
                            'galdesc',
                            'pageid',
                            'previewpic',
                            'author',
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

    /**
     * @return WP_Error|WP_REST_Response
     */
    public function galleries_list()
    {
        $mapper  = C_Gallery_Mapper::get_instance();
        $retval = array();
        foreach ($mapper->find_all() as $gallery) {
            $retval[] = $this->format_gallery_output($gallery);
        }

        return rest_ensure_response($retval);
    }
}