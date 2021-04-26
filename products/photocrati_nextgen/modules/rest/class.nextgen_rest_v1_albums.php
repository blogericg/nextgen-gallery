<?php

/*
 * ATTENTION: Update C_NextGen_Rest_V1 when adding any new routes
 */

class C_NextGen_Rest_V1_Albums
{
    public function register_routes()
    {
        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/albums',
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'albums_list'),
                    'permission_callback' => [$this, 'permission_callback']
                ),
                'schema' => array($this, 'album_list_schema')
            )
        );
    }

    public function permission_callback() {
        if (!current_user_can('NextGEN Edit album'))
            return new WP_Error(
                'rest_forbidden',
                esc_html__('Permission denied', 'nggallery'),
                ['status' => 401]
            );
        return TRUE;
    }

    /**
     * @return array
     */
    public function album_list_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'album',
            'type'       => 'array',
            'items'   => array(
                'allOf' => array(
                    array(
                        'type' => 'object'
                    ),
                    array(
                        'properties' => array(
                            'id' => array(
                                'description' => __('Unique identifier for the album.', 'nggallery'),
                                'type'        => 'integer',
                                'readOnly'    => TRUE
                            ),
                            'name' => array(
                                'description' => __('Album title.', 'nggallery'),
                                'type'        => 'string'
                            ),
                            'description' => array(
                                'description' => __('Album description.', 'nggallery'),
                                'type'        => array('string', 'null')
                            ),
                            'slug' => array(
                                'description' => __('Slug used to identify album in URL.', 'nggallery'),
                                'type'        => 'string',
                                'readOnly'    => TRUE
                            ),
                            'preview image id' => array(
                                'description' => __('Image ID used for album previews.', 'nggallery'),
                                'type'        => 'integer'
                            ),
                            'number of children' => array(
                                'description' => __('Count of child galleries and sub-albums.', 'nggallery'),
                                'type'        => 'integer',
                                'readOnly'    => TRUE
                            ),
                            'page id' => array(
                                'description'  => __('WordPress page or post ID linked to album. Zero for no association.', 'nggallery'),
                                'type'         => 'integer'
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function albums_list()
    {
        $mapper = C_Album_Mapper::get_instance();
        $retval = array();
        foreach ($mapper->find_all() as $album) {
            $retval[] = array(
                'id'                 => $album->id,
                'name'               => $album->name,
                'description'        => $album->albumdesc,
                'slug'               => $album->slug,
                'preview image id'   => $album->previewpic,
                'number of children' => count($album->sortorder),
                'page id'            => $album->pageid
            );
        }

        return rest_ensure_response($retval);
    }
}