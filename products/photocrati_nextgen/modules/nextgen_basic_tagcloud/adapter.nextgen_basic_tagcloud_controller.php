<?php

/**
 * Class A_NextGen_Basic_Tagcloud_Controller
 * @mixin C_Display_Type_Controller
 * @adapts I_Display_Type_Controller for "photocrati-nextgen_basic_tagcloud" context
 */
class A_NextGen_Basic_Tagcloud_Controller extends Mixin
{
    protected static $alternate_displayed_galleries = [];

    function get_alternate_displayed_gallery($displayed_gallery)
    {
        // Prevent recursive checks for further alternates causing additional modifications to the settings array
        $id = $displayed_gallery->id();
        if (!empty(self::$alternate_displayed_galleries[$id]))
            return self::$alternate_displayed_galleries[$id];

        $tag = urldecode($this->param('gallerytag'));

        // The display setting 'display_type' has been removed to 'gallery_display_type'
        if (isset($display_settings['display_type'])) {
            $display_settings['gallery_display_type'] = $display_settings['display_type'];
            unset($display_settings['display_type']);
        }

        // we're looking at a tag, so show images w/that tag as a thumbnail gallery
        if (!is_home() && !empty($tag))
        {
            $params = [
                'source'                => 'tags',
                'container_ids'         => array(esc_attr($tag)),
                'display_type'          => $displayed_gallery->display_settings['gallery_display_type'],
                'original_display_type' => $displayed_gallery->display_type,
                'original_settings'     => $displayed_gallery->display_settings
            ];

            $renderer = C_Displayed_Gallery_Renderer::get_instance();
            $alternate_displayed_gallery = $renderer->params_to_displayed_gallery($params);
            if (is_null($alternate_displayed_gallery->id()))
                $alternate_displayed_gallery->id(md5(json_encode($alternate_displayed_gallery->get_entity())));
            self::$alternate_displayed_galleries[$id] = $alternate_displayed_gallery;
            return $alternate_displayed_gallery;
        }

        return $displayed_gallery;
    }

    /**
     * Displays the 'tagcloud' display type
     *
     * @param C_Displayed_Gallery $displayed_gallery
     * @param bool $return (optional)
     * @return string
     */
    function index_action($displayed_gallery, $return = FALSE)
    {
        // we're looking at a tag, so show images w/that tag as a thumbnail gallery
        if (!is_home() && !empty($this->param('gallerytag')))
        {
            $displayed_gallery = $this->get_alternate_displayed_gallery($displayed_gallery);
            return C_Displayed_Gallery_Renderer::get_instance()->display_images($displayed_gallery);
        }

        $application = C_Router::get_instance()->get_routed_app();
        $display_settings = $displayed_gallery->display_settings;
        $defaults = array(
            'exclude'  => '',
            'format'   => 'list',
            'include'  => $displayed_gallery->get_term_ids_for_tags(),
            'largest'  => 22,
            'link'     => 'view',
            'number'   => $display_settings['number'],
            'order'    => 'ASC',
            'orderby'  => 'name',
            'smallest' => 8,
            'taxonomy' => 'ngg_tag',
            'unit'     => 'pt'
        );
        $args = wp_parse_args('', $defaults);

        // Always query top tags
        $tags = get_terms($args['taxonomy'], array_merge($args, array('orderby' => 'count', 'order' => 'DESC')));

        foreach ($tags as $key => $tag) {
            $tags[$key]->link = $this->object->set_param_for($application->get_routed_url(TRUE), 'gallerytag', $tag->slug);
            $tags[$key]->id = $tag->term_id;
        }

        $params = $display_settings;
        $params['inner_content']        = $displayed_gallery->inner_content;
        $params['tagcloud']             = wp_generate_tag_cloud($tags, $args);
        $params['displayed_gallery_id'] = $displayed_gallery->id();
                
        $params = $this->object->prepare_display_parameters($displayed_gallery, $params);
        
        return $this->object->render_partial('photocrati-nextgen_basic_tagcloud#nextgen_basic_tagcloud', $params, $return);
    }

    /**
     * Enqueues all static resources required by this display type
     *
     * @param C_Displayed_Gallery $displayed_gallery
     */
    function enqueue_frontend_resources($displayed_gallery)
    {
		$this->call_parent('enqueue_frontend_resources', $displayed_gallery);

        wp_enqueue_style(
            'photocrati-nextgen_basic_tagcloud-style',
            $this->get_static_url('photocrati-nextgen_basic_tagcloud#nextgen_basic_tagcloud.css'),
            array(),
            NGG_SCRIPT_VERSION
        );

		$this->enqueue_ngg_styles();
    }

}
