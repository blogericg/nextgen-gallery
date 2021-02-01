<?php

/*
 * ATTENTION: Update C_NextGen_Rest_V1 when adding any new routes
 */

class C_NextGen_Rest_V1_Display_Types extends WP_REST_Controller
{
    public function register_routes()
    {
        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/display_types',
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'display_types_list'),
                    'permission_callback' => '__return_true'
                ),
                'schema' => [$this, 'display_type_list_schema']
            )
        );

        // Because routes are matched on a first-come first-serve basis our endpoints to get and change display type
        // settings must be registered before the generic /display_types/(id)/
        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/display_types/(?P<id>.*)/(?P<key>.*)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Display type ID', 'nggallery'),
                        'type'        => 'string',
                        'required'    => TRUE
                    ),
                    'key' => array(
                        'description' => __('Setting key', 'nggallery'),
                        'type'        => 'string',
                        'required'    => TRUE
                    )
                ),
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'display_type_get_setting'),
                    'permission_callback' => '__return_true'
                ),
                'schema' => array($this, 'display_type_setting_value_schema')
            )
        );

        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/display_types/(?P<id>.*)/(?P<key>.*)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Display type ID', 'nggallery'),
                        'type'        => 'string',
                        'required'    => TRUE
                    ),
                    'key' => array(
                        'description' => __('Setting key', 'nggallery'),
                        'type'        => 'string',
                        'required'    => TRUE
                    ),
                    'value' => array(
                        'description' => __('Setting value', 'nggallery'),
                        'type'        => 'string',
                        'required'    => TRUE
                    )
                ),
                array(
                    'methods'  => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'display_type_set_setting'),
                    'permission_callback' => '__return_true'
                ),
                'schema' => array($this, 'display_type_setting_value_schema')
            )
        );

        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/display_types/(?P<id>.*)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Display type ID', 'nggallery'),
                        'type'        => 'string',
                        'required'    => TRUE
                    )
                ),
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'display_type_list_settings'),
                    'permission_callback' => '__return_true'
                ),
                'schema' => array($this, 'display_type_settings_list_schema')
            )
        );
    }

    /**
     * @return array
     */
    public function display_type_settings_list_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'List of all settings belonging to a display type',
            'type'       => 'array',
            'items'   => array(
                'allOf' => array(
                    array(
                        'type' => 'object'
                    ),
                    array(
                        'properties' => array(
                            'key' => array(
                                'description' => __('Unique identifier for the setting.', 'nggallery'),
                                'type'        => 'string',
                                'readonly'    => TRUE
                            ),
                            'value' => array(
                                'description' => __('Setting value.', 'nggallery'),
                                'type'        => 'mixed'
                            ),
                            '_links' => array(
                                'description' => __('Related resources', 'nggallery'),
                                'type'        => 'object'
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @return array
     */
    public function display_type_list_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'List of all display types',
            'type'       => 'array',
            'properties' => array(
                'id' => array(
                    'description' => __('Display type id.', 'nggallery'),
                    'type'        => 'string',
                    'readonly'    => TRUE
                ),
                'title' => array(
                    'description' => __('Display type title.', 'nggallery'),
                    'type'        => 'string'
                ),
                '_links' => array(
                    'description' => __('Related resources', 'nggallery'),
                    'type'        => 'object'
                )
            )
        );
    }

    /**
     * @return array
     */
    public function display_type_setting_value_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'Individual display type setting',
            'type'       => 'object',
            'properties' => array(
                'key' => array(
                    'description' => __('Unique identifier for the setting.', 'nggallery'),
                    'type'        => 'string',
                    'readonly'    => TRUE
                ),
                'value' => array(
                    'description' => __('Setting value.', 'nggallery'),
                    'type'        => 'mixed'
                )
            )
        );
    }

    /**
     * @return array
     */
    public function display_types_list()
    {
        $mapper = C_Display_Type_Mapper::get_instance();
        $retval = [];
        foreach ($mapper->find_all() as $display) {
            $retval[] = [
                'id' => $display->name,
                'title' => $display->title,
                '_links' => array(
                    'self' => array(
                        'href' => get_rest_url(NULL, 'ngg/v1/display_types/' . $display->name)
                    )
                )
            ];
        }

        return $retval;
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|array
     */
    public function display_type_list_settings($args)
    {
        $mapper = C_Display_Type_Mapper::get_instance();
        $id = $args->get_param('id');
        $retval = [];
        $display = $mapper->find_by_name($id);

        if (!$display)
            return new WP_Error(
                'invalid_display_type_id',
                __('Invalid display type ID', 'nggallery'),
                array('status' => 404)
            );

        $settings = $display->settings;
        foreach ($settings as $key => $value) {
            $retval[] = [
                'key' => $key,
                'value' => $value,
                '_links' => array(
                    'self' => array(
                        'href' => get_rest_url(NULL, 'ngg/v1/display_types/' . $id . '/' . $key)
                    )
                )
            ];
        }

        return $retval;
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|array
     */
    public function display_type_get_setting($args)
    {
        $mapper = C_Display_Type_Mapper::get_instance();
        $id = $args->get_param('id');
        $key = $args->get_param('key');
        $display = $mapper->find_by_name($id);

        if (!$display)
            return new WP_Error(
                'invalid_display_type_id',
                __('Invalid display type ID', 'nggallery'),
                array('status' => 404)
            );

        if (!isset($display->settings[$key]))
            return new WP_Error(
                'invalid_display_type_setting_key',
                __('Invalid setting key for this display type', 'nggallery'),
                array('status' => 404)
            );

        return [
            'key' => $key,
            'value' => $display->settings[$key]
        ];
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|array
     */
    public function display_type_set_setting($args)
    {
        $mapper = C_Display_Type_Mapper::get_instance();
        $id = $args->get_param('id');
        $key = $args->get_param('key');
        $value = $args->get_param('value');
        $display = $mapper->find_by_name($id);

        if (!$display)
            return new WP_Error(
                'invalid_display_type_id',
                __('Invalid display type ID', 'nggallery'),
                array('status' => 404)
            );

        if (!isset($display->settings[$key]))
            return new WP_Error(
                'invalid_display_type_setting_key',
                __('Invalid setting key for this display type', 'nggallery'),
                array('status' => 404)
            );

        $display->settings[$key] = $value;
        $mapper->save($display);

        return [
            'key' => $key,
            'value' => $display->settings[$key]
        ];
    }
}