<?php

class C_Widget_Slideshow extends WP_Widget
{
    protected static $displayed_gallery_ids = [];

    function __construct()
    {
        $widget_ops = [
            'classname'   => 'widget_slideshow',
            'description' => __('Show a NextGEN Gallery Slideshow', 'nggallery')
        ];

        parent::__construct('slideshow', __('NextGEN Slideshow', 'nggallery'), $widget_ops);

        // Determine what widgets will exist in the future, create their displayed galleries, enqueue their resources,
        // and cache the resulting displayed gallery for later rendering to avoid the ID changing due to misc attributes
        // in $args being different now and at render time ($args is sidebar information that is not relevant)
        add_action('wp_enqueue_scripts', function() {

            global $wp_registered_sidebars;

            $sidebars = wp_get_sidebars_widgets();
            $options = $this->get_settings();

            foreach ($sidebars as $sidebar_name => $sidebar) {
                if ($sidebar_name === 'wp_inactive_widgets' || !$sidebar)
                    continue;
                foreach ($sidebar as $widget) {
                    if (strpos($widget, 'slideshow-', 0) !== 0)
                        continue;
                    $id = str_replace('slideshow-', '', $widget);

                    if (isset($options[$id]))
                    {
                        $sidebar_data = $wp_registered_sidebars[$sidebar_name];
                        $sidebar_data['widget_id'] = $widget;

                        // These are normally replaced at display time but we're building our cache before then
                        $sidebar_data['before_widget'] = str_replace('%1$s', $widget, $sidebar_data['before_widget']);
                        $sidebar_data['before_widget'] = str_replace('%2$s', 'widget_slideshow', $sidebar_data['before_widget']);
                        $sidebar_data['widget_name'] = __('NextGEN Slideshow', 'nggallery');

                        $displayed_gallery = $this->get_displayed_gallery($sidebar_data, $options[$id]);
                        self::$displayed_gallery_ids[$widget] = $displayed_gallery;
                        $controller = C_Display_Type_Controller::get_instance(NGG_BASIC_SLIDESHOW);
                        M_Gallery_Display::enqueue_frontend_resources_for_displayed_gallery($displayed_gallery, $controller);
                    }
                }

            }

            $router = C_Router::get_instance();
            wp_enqueue_style(
                'nextgen_widgets_style',
                $router->get_static_url('photocrati-widget#widgets.css'),
                array(),
                NGG_SCRIPT_VERSION
            );
            wp_enqueue_style(
                'nextgen_basic_slideshow_style',
                $router->get_static_url('photocrati-nextgen_basic_gallery#slideshow/ngg_basic_slideshow.css'),
                array(),
                NGG_SCRIPT_VERSION
            );
        }, 11);
    }

    /**
     * @param array $args
     * @param array $instance
     * @return C_Displayed_Gallery
     */
    public function get_displayed_gallery($args, $instance)
    {
        if (empty($instance['limit']))
            $instance['limit'] = 10;

        $params = array(
            'container_ids'  => $instance['galleryid'],
            'display_type'   => 'photocrati-nextgen_basic_slideshow',
            'gallery_width'  => $instance['width'],
            'gallery_height' => $instance['height'],
            'source'         => 'galleries',
            'slug'           => 'widget-' . $args['widget_id'],
            'entity_types'   => array('image'),
            'show_thumbnail_link'     => FALSE,
            'show_slideshow_link'     => FALSE,
            'use_imagebrowser_effect' => FALSE, // just to be safe
            'ngg_triggers_display'    => 'never'
        );

        if (0 === $instance['galleryid'])
        {
            $params['source'] = 'random_images';
            $params['maximum_entity_count'] = $instance['limit'];
            unset($params['container_ids']);
        }

        $renderer = C_Displayed_Gallery_Renderer::get_instance();
        $displayed_gallery = $renderer->params_to_displayed_gallery($params);
        if (is_null($displayed_gallery->id()))
            $displayed_gallery->id(md5(json_encode($displayed_gallery->get_entity())));

        return $displayed_gallery;
    }

    function form($instance)
    {
        global $wpdb;

        // used for rendering utilities
        $parent = C_Widget::get_instance();

        // defaults
        $instance = wp_parse_args(
            (array)$instance,
            array(
                'galleryid' => '0',
                'height' => '120',
                'title' => 'Slideshow',
                'width' => '160',
                'limit' => '10'
            )
        );

        return $parent->render_partial(
            'photocrati-widget#form_slideshow',
            array(
                'self'     => $this,
                'instance' => $instance,
                'title'    => esc_attr($instance['title']),
                'height'   => esc_attr($instance['height']),
                'width'    => esc_attr($instance['width']),
                'limit'    => esc_attr($instance['limit']),
                'tables'   => $wpdb->get_results("SELECT * FROM {$wpdb->nggallery} ORDER BY 'name' ASC")
            )
        );
    }

    function update($new_instance, $old_instance)
    {
        $nh = $new_instance['height'];
        $nw = $new_instance['width'];
        if (empty($nh) || (int)$nh === 0)
            $new_instance['height'] = 120;
        if (empty($nw) || (int)$nw === 0)
            $new_instance['width'] = 160;
        if (empty($new_instance['limit']))
            $new_instance['limit'] = 10;

        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['galleryid'] = (int) $new_instance['galleryid'];
        $instance['height'] = (int) $new_instance['height'];
        $instance['width'] = (int) $new_instance['width'];
        $instance['limit'] = (int) $new_instance['limit'];
        return $instance;
    }

    function widget($args, $instance)
    {
        // these are handled by extract() but I want to silence my IDE warnings that these vars don't exist
        $before_widget = NULL;
        $before_title = NULL;
        $after_widget = NULL;
        $after_title = NULL;
        $widget_id = NULL;

        extract($args);

        $parent = C_Component_Registry::get_instance()->get_utility('I_Widget');

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Slideshow', 'nggallery') : $instance['title'], $instance, $this->id_base);

        $out = $this->render_slideshow($args, $instance);

        $parent->render_partial(
            'photocrati-widget#display_slideshow',
            array(
                'self'       => $this,
                'instance'   => $instance,
                'title'      => $title,
                'out'        => $out,
                'before_widget' => $before_widget,
                'before_title'  => $before_title,
                'after_widget'  => $after_widget,
                'after_title'   => $after_title,
                'widget_id'     => $widget_id
            )
        );
    }

    function render_slideshow($args, $instance)
    {
        // This displayed gallery is created dynamically at runtime
        if (empty(self::$displayed_gallery_ids[$args['widget_id']]))
        {
            $displayed_gallery = $this->get_displayed_gallery($args, $instance);
            self::$displayed_gallery_ids[$displayed_gallery->id()] = $displayed_gallery;
        }
        else {
            // The displayed gallery was created during the action wp_enqueue_resources and was cached to avoid ID conflicts
            $displayed_gallery = self::$displayed_gallery_ids[$args['widget_id']];
        }

        $renderer = C_Displayed_Gallery_Renderer::get_instance();
        $retval = $renderer->display_images($displayed_gallery);

        $retval = apply_filters(
            'ngg_show_slideshow_widget_content',
            $retval,
            $instance['galleryid'],
            $instance['width'],
            $instance['height']
        );
        return $retval;
    }

}
