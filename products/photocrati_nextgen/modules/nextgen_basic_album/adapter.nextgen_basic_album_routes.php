<?php

/**
 * Class A_NextGen_Basic_Album_Routes
 * @mixin C_Displayed_Gallery_Renderer
 * @adapts I_Displayed_Gallery_Renderer
 * @property A_NextGen_Basic_Album_Routes|C_Displayed_Gallery_Renderer $object
 */
class A_NextGen_Basic_Album_Routes extends Mixin
{
    protected static $has_ran;

    function do_app_rewrites($displayed_gallery)
    {

        if (self::$has_ran)
            return;
        self::$has_ran = TRUE;

        $this->object->call_parent('do_app_rewrites', $displayed_gallery);

        $do_rewrites = FALSE;
        $app = NULL;

        // Get display types
        $original_display_type = isset($displayed_gallery->display_settings['original_display_type']) ? $displayed_gallery->display_settings['original_display_type'] : '';
        $display_type = $displayed_gallery->display_type;

        // If we're viewing an album, rewrite the urls
        $regex = "/photocrati-nextgen_basic_\\w+_album/";
        if (preg_match($regex, $display_type)) {
            $do_rewrites = TRUE;

            // Get router
            $router = C_Router::get_instance();
            $app 	= $router->get_routed_app();
            $slug	= '/'.C_NextGen_Settings::get_instance()->router_param_slug;

            $app->rewrite("{*}{$slug}/page/{\\d}{*}",		 "{1}{$slug}/nggpage--{2}{3}", FALSE, TRUE);
            $app->rewrite("{*}{$slug}/pid--{*}",		     "{1}{$slug}/pid--{2}", FALSE, TRUE); // avoid conflicts with imagebrowser
            $app->rewrite("{*}{$slug}/{\\w}/{\\w}/{\\w}{*}", "{1}{$slug}/album--{2}/gallery--{3}/{4}{5}", FALSE, TRUE);
            $app->rewrite("{*}{$slug}/{\\w}/{\\w}",          "{1}{$slug}/album--{2}/gallery--{3}", FALSE, TRUE);

            // TODO: We're commenting this out as it was causing a problem with sub-album requests not
            // working when placed beside paginated galleries. But we still need to figure out why, and fix that
            // $app->rewrite("{*}{$slug}/{\\w}", "{1}{$slug}/album--{2}", FALSE, TRUE);
        }
        elseif (preg_match($regex, $original_display_type)) {
            $do_rewrites = TRUE;

            // Get router
            $router = C_Router::get_instance();
            $app 	= $router->get_routed_app();
            $slug	= '/'.C_NextGen_Settings::get_instance()->router_param_slug;

            $app->rewrite("{*}{$slug}/album--{\\w}",                    "{1}{$slug}/{2}");
            $app->rewrite("{*}{$slug}/album--{\\w}/gallery--{\\w}",     "{1}{$slug}/{2}/{3}");
            $app->rewrite("{*}{$slug}/album--{\\w}/gallery--{\\w}/{*}", "{1}{$slug}/{2}/{3}/{4}");
        }

        // Perform rewrites
        if ($do_rewrites && $app) {
            $app->do_rewrites();
        }
    }

	function render($displayed_gallery, $return=FALSE, $mode=NULL)
	{
        $this->object->do_app_rewrites($displayed_gallery);
        return $this->call_parent('render', $displayed_gallery, $return, $mode);
	}
}
