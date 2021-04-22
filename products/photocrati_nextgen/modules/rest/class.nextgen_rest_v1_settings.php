<?php

/*
 * ATTENTION: Update C_NextGen_Rest_V1 when adding any new routes
 */

class C_NextGen_Rest_V1_Settings extends WP_REST_Controller
{
    public function register_routes()
    {
        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/settings',
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'settings_list'),
                    'permission_callback' => [$this, 'permission_callback']
                ),
                'schema' => array($this, 'settings_list_schema')
            )
        );

        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/settings/(?P<key>.*)/',
            array(
                'args' => array(
                    'key' => array(
                        'description'       => __('Setting key', 'nggallery'),
                        'type'              => 'string',
                        'required'          => TRUE
                    )
                ),
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'setting_get'),
                    'permission_callback' => [$this, 'permission_callback']
                ),
                'schema' => array($this, 'settings_value_schema')
            )
        );

        register_rest_route(
            C_NextGen_Rest_V1::$namespace,
            '/settings/(?P<key>.*)/',
            array(
                'args' => array(
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
                    'callback' => array($this, 'setting_set'),
                    'permission_callback' => [$this, 'permission_callback']
                ),
                'schema' => array($this, 'settings_value_schema')
            )
        );
    }

    public function permission_callback() {
        if (!current_user_can('NextGEN Change options'))
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
    public function settings_list_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'Listing of all settings',
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
                                'readOnly'    => TRUE
                            ),
                            'value' => array(
                                'description' => __('Setting value.', 'nggallery'),
                                'type'        => ['integer', 'string', 'boolean', 'null', 'number', 'array', 'object']
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
    public function settings_value_schema()
    {
        return array(
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'Individual setting',
            'type'       => 'object',
            'properties' => array(
                'key' => array(
                    'description' => __('Unique identifier for the setting.', 'nggallery'),
                    'type'        => 'string',
                    'readOnly'    => TRUE
                ),
                'value' => array(
                    'description' => __('Setting value.', 'nggallery'),
                    'type'        => ['integer', 'string', 'boolean', 'null', 'number', 'array', 'object']
                )
            )
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function settings_list()
    {
        $settings = C_NextGen_Settings::get_instance();
        $retval = array();
        foreach ($settings->to_array() as $key => $value) {
            $retval[] = array(
                'key'   => $key,
                'value' => $value,
                '_links' => array(
                    'self' => array(
                        'href' => get_rest_url(NULL, 'ngg/v1/settings/' . $key)
                    )
                )
            );
        }

        return rest_ensure_response($retval);
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|array
     */
    public function setting_get($args)
    {
        $settings = C_NextGen_Settings::get_instance();
        $settings_array = $settings->to_array();
        $key = $args->get_param('key');
        $setting = $settings->get($key, NULL);

        if (!isset($settings_array[$key]))
            return new WP_Error(
                'invalid_setting_key',
                __('Invalid setting key', 'nggallery'),
                array('status' => 404)
            );

        return rest_ensure_response([
            'key' => $args->get_param('key'),
            'value' => $setting
        ]);
    }

    /**
     * @param WP_REST_Request $args
     * @return WP_Error|array
     */
    public function setting_set($args)
    {
        $settings = C_NextGen_Settings::get_instance();
        $settings_array = $settings->to_array();
        $POST = $args->get_body_params();
        $key = $args->get_param('key');
        $value = $POST['value'];

        if (!isset($settings_array[$key]))
            return new WP_Error(
                'invalid_setting_key',
                __('Invalid setting key', 'nggallery'),
                array('status' => 404)
            );

        $settings->set($key, $value);
        $settings->save();

        $setting = $settings->get($key, NULL);
        return rest_ensure_response([
            'key' => $key,
            'value' => $setting
        ]);
    }
}