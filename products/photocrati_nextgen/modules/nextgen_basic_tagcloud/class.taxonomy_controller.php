<?php

/**
 * Class C_Taxonomy_Controller
 * @implements I_Taxonomy_Controller
 */
class C_Taxonomy_Controller extends C_MVC_Controller
{
    static $_instances = array();
    protected $ngg_tag_detection_has_run = FALSE;

    /**
     * Returns an instance of this class
     *
     * @param string|bool $context
     * @return C_Taxonomy_Controller
     */
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context]))
        {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }

    function define($context = FALSE)
    {
        parent::define($context);
        $this->implement('I_Taxonomy_Controller');
    }


    function render_tag($tag)
    {
	    $mapper   = C_Display_Type_Mapper::get_instance();

	    // Respect the global display type setting
	    $display_type = $mapper->find_by_name(NGG_BASIC_TAGCLOUD, TRUE);
	    $display_type = !empty($display_type->settings['gallery_display_type']) ? $display_type->settings['gallery_display_type'] : NGG_BASIC_THUMBNAILS;

	    return "[ngg source='tags' container_ids='{$tag}' slug='{$tag}' display_type='{$display_type}']";
    }

    /**
     * Determines if the current page is /ngg_tag/{*}
     *
     * @param $posts Wordpress post objects
     * @param WP_Query $wp_query_local
     * @return array Wordpress post objects
     */
    function detect_ngg_tag($posts, $wp_query_local)
    {
        global $wp;
        global $wp_query;
        $wp_query_orig = false;
        
        if ($wp_query_local != null && $wp_query_local != $wp_query) {
        	$wp_query_orig = $wp_query;
        	$wp_query = $wp_query_local;
        }

        // This appears to be necessary for multisite installations, but I can't imagine why. More hackery..
        $tag = urldecode(get_query_var('ngg_tag') ? get_query_var('ngg_tag') : get_query_var('name'));
        $tag = stripslashes(M_NextGen_Data::strip_html($tag)); // Tags may not include HTML

        if (!$this->ngg_tag_detection_has_run // don't run more than once; necessary for certain themes
        &&  !is_admin() // will destroy 'view all posts' page without this
        &&  !empty($tag) // only run when a tag has been given to wordpress
        &&  (stripos($wp->request, 'ngg_tag') === 0 // make sure the query begins with /ngg_tag
             || (isset($wp_query->query_vars['page_id'])
                  && $wp_query->query_vars['page_id'] === 'ngg_tag')
            )
           )
        {
            $this->ngg_tag_detection_has_run = TRUE;

            // Wordpress somewhat-correctly generates several notices, so silence them as they're really unnecessary
            if (!defined('WP_DEBUG') || !WP_DEBUG)
                error_reporting(0);

            // Without this all url generated from this page lacks the /ngg_tag/(slug) section of the URL
            add_filter('ngg_wprouting_add_post_permalink', '__return_false');

            // create in-code a fake post; we feed it back to Wordpress as the sole result of the "the_posts" filter
            $posts = NULL;
            $posts[] = $this->create_ngg_tag_post($tag);

            $wp_query->is_404 = FALSE;
            $wp_query->is_page = TRUE;
            $wp_query->is_singular = TRUE;
            $wp_query->is_home = FALSE;
            $wp_query->is_archive = FALSE;
            $wp_query->is_category = FALSE;

            unset($wp_query->query['error']);
            $wp_query->query_vars['error'] = '';
        }

        if ($wp_query_orig !== false)
        {
        	$wp_query = $wp_query_orig;
        	// Commenting this out as it was causing WSOD in 2.2.8
//        	$wp_query->is_page = FALSE; // Prevents comments from displaying on our taxonomy 'page'
        }

        return $posts;
    }

    function create_ngg_tag_post($tag)
    {
        $title = sprintf(__('Images tagged &quot;%s&quot;', 'nggallery'), $tag);
        $title = apply_filters('ngg_basic_tagcloud_title', $title, $tag);

        $post = new stdClass;
        $post->post_author = FALSE;
        $post->post_name = 'ngg_tag';
        $post->guid = get_bloginfo('wpurl') . '/' . 'ngg_tag';
        $post->post_title = $title;
        $post->post_content = $this->render_tag($tag);
        $post->ID = FALSE;
        $post->post_type = 'page';
        $post->post_status = 'publish';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        $post->comment_count = 0;
        $post->post_date = current_time('mysql');
        $post->post_date_gmt = current_time('mysql', 1);

        return($post);
    }
}
