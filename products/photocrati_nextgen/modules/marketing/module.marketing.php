<?php

class M_Marketing extends C_Base_Module
{
    function define($id = 'pope-module',
                    $name = 'Pope Module',
                    $description = '',
                    $version = '',
                    $uri = '',
                    $author = '',
                    $author_uri = '',
                    $context = FALSE)
    {
        parent::define(
            'photocrati-marketing',
            'Marketing',
            'Provides resources for encouraging users to upgrade to NextGen Plus/Pro',
            '3.3.6',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-gallery/',
            'Imagely',
            'https://www.imagely.com'
        );
    }

    public static $big_hitters_block_two_cache = NULL;

    protected static $display_setting_blocks = ['tile', 'mosaic', 'masonry'];

    public function is_plus_or_pro_enabled()
    {
        if (defined('NGG_PRO_PLUGIN_BASENAME') || defined('NGG_PLUS_PLUGIN_BASENAME'))
            return TRUE;
        else
            return FALSE;
    }

    function _register_hooks()
    {
        if (self::is_plus_or_pro_enabled() || !is_admin())
            return;

        add_filter('ngg_manage_lightbox_select_options', function($libraries) {
            $marketing = new stdClass();
            $marketing->name = 'marketing_lightbox';
            $marketing->title = __('NextGEN Pro Lightbox', 'nggallery');
            $libraries[] = $marketing;
            return $libraries;
        });

        add_action('ngg_manage_albums_marketing_block', function() {
            self::enqueue_blocks_style();
            print self::get_big_hitters_block_albums();
        });

        add_action('ngg_manage_galleries_marketing_block', function() {
            self::enqueue_blocks_style();
            print self::get_big_hitters_block_two();
        });

        add_action('ngg_manage_images_marketing_block', function() {
            self::enqueue_blocks_style();
            print self::get_big_hitters_block_two();
        });

        add_action('ngg_sort_images_marketing_block', function() {
            self::enqueue_blocks_style();
            print self::get_big_hitters_block_two();
        });

        add_action('ngg_manage_galleries_above_table', function() {
            $title    = __('Want to sell your images online?', 'nggallery');
            $campaign = 'TODO';
            $source   = 'TODO';
            $block    = new C_Marketing_Block_Single_Line($title, $campaign, $source);
            print $block->render();
        });

        add_action('admin_init', function() {
            $forms = C_Form_Manager::get_instance();
            foreach (self::$display_setting_blocks as $block) {
                $forms->add_form(NGG_DISPLAY_SETTINGS_SLUG, "photocrati-marketing_display_settings_{$block}");
            }

            $forms->add_form(NGG_OTHER_OPTIONS_SLUG, 'marketing_image_protection');
            $forms->add_form(NGG_LIGHTBOX_OPTIONS_SLUG, 'marketing_lightbox');
        });
    }

    function _register_utilities()
    {
    }

    function _register_adapters()
    {
        if (!self::is_plus_or_pro_enabled() && is_admin())
        {
            $registry = $this->get_registry();

            // Add display type upsells in the IGW
            $registry->add_adapter('I_Attach_To_Post_Controller', 'A_Marketing_IGW_Display_Type_Upsells');

            // Add upsell blocks to NGG pages
            $registry->add_adapter('I_MVC_View', 'A_AddGallery_Upsell', 'ngg_addgallery');
            $registry->add_adapter('I_Form', 'A_Marketing_Other_Options_Form', 'marketing_image_protection');
            $registry->add_adapter('I_Form', 'A_Marketing_Lightbox_Options_Form', 'marketing_lightbox');

            // If we call find_all() before init/admin_init an exception is thrown due to is_user_logged_in() being
            // called too early. Don't remove this action hook.
            add_action('admin_init', function() {
                foreach (C_Display_type_Mapper::get_instance()->find_all() as $display_type) {
                    $registry = $this->get_registry();
                    $registry->add_adapter('I_Form', 'A_Marketing_Display_Type_Settings_Form', $display_type->name);
                }
            });

            foreach (self::$display_setting_blocks as $block) {
                $registry->add_adapter(
                    'I_Form',
                    'A_Marketing_Display_Settings_Form',
                    "photocrati-marketing_display_settings_{$block}"
                );
            }
        }
    }

    function initialize()
    {
        wp_register_style(
            'ngg_marketing_blocks_style',
            C_Router::get_instance()->get_static_url('photocrati-marketing#blocks.css'),
            ['wp-block-library'],
            NGG_SCRIPT_VERSION
        );
    }

    /**
     * The same links are used by both of the two blocks
     * @return array
     */
    public static function get_big_hitters_links()
    {
        return [[
            ['title' => __('Ecommerce', 'nggallery'),                   'href' => 'https://www.imagely.com/wordpress-gallery-plugin/pro-ecommerce-demo'],
            ['title' => __('Automated Print Fulfillment', 'nggallery'), 'href' => 'https://www.imagely.com/sell-photos-wordpress/'],
            ['title' => __('Automated Tax Calculation', 'nggallery'),   'href' => 'https://www.imagely.com/sell-photos-wordpress/'],
            ['title' => __('Additional Gallery Displays', 'nggallery'), 'href' => 'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/#features'],
            ['title' => __('Additional Album Displays', 'nggallery'),   'href' => 'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/#features']
        ], [
            ['title' => __('Image Proofing', 'nggallery'),    'href' => 'https://www.imagely.com/wordpress-gallery-plugin/pro-proofing-demo'],
            ['title' => __('Image Protection', 'nggallery'),  'href' => 'https://www.imagely.com/docs/turn-image-protection/'],
            ['title' => __('Pro Lightbox', 'nggallery'),      'href' => 'https://www.imagely.com/wordpress-gallery-plugin/pro-lightbox-demo'],
            ['title' => __('Digital Downloads', 'nggallery'), 'href' => 'https://www.imagely.com/wordpress-gallery-plugin/digital-download-demo/'],
            __('Dedicated customer support and so much more!', 'nggallery')
        ]];
    }

    public static function get_big_hitters_block_base()
    {
        return [
            'title'       => __('Want to make your gallery workflow and presentation even better?', 'nggallery'),
            'description' => __('By upgrading to NextGEN Pro, you can get access to numerous other features, including:', 'nggallery'),
            'links'       => self::get_big_hitters_links(),
            'footer'      => __('<strong>Bonus:</strong> NextGEN Gallery users get a discount code for 30% off regular price.', 'nggallery'),
            'campaign'    => 'clickheretoupgrade',
            'medium'      => 'galleryworkflow',
        ];
    }

    public static function get_big_hitters_block_albums()
    {
        $base = self::get_big_hitters_block_base();

        $base['title'] = __('Want to do even more with your albums?', 'nggallery');

        $block = new C_Marketing_Block_Two_Columns(
            $base['title'],
            $base['description'],
            $base['links'],
            $base['footer'],
            $base['campaign'],
            $base['medium']
        );

        return $block->render();
    }

    /**
     * @return string
     */
    public static function get_big_hitters_block_two()
    {
        if (!empty(self::$big_hitters_block_two_cache))
            return self::$big_hitters_block_two_cache;

        $base = self::get_big_hitters_block_base();

        $base['title']       = __('Want to do even more with your gallery display?', 'nggallery');
        $base['description'] = [
            __('We know that you will truly love NextGEN Pro. It has 2,600+ five star ratings and is active on over 900,000 websites.', 'nggallery'),
            __('By upgrading to NextGEN Pro, you can get access to numerous other features, including:', 'nggallery')
        ];

        $block = new C_Marketing_Block_Two_Columns(
            $base['title'],
            $base['description'],
            $base['links'],
            $base['footer'],
            $base['campaign'],
            $base['medium']
        );

        self::$big_hitters_block_two_cache = $block->render();

        return self::$big_hitters_block_two_cache;
    }

    public static function enqueue_blocks_style()
    {
        wp_enqueue_style('ngg_marketing_blocks_style');
    }

    /**
     * @return array
     */
    function get_type_list()
    {
        return [
            'A_AddGallery_Upsell'                    => 'adapter.addgallery_upsell.php',
            'A_Marketing_Display_Settings_Form'      => 'adapter.display_settings_form.php',
            'A_Marketing_Display_Type_Settings_Form' => 'adapter.display_type_settings_form.php',
            'A_Marketing_IGW_Display_Type_Upsells'   => 'adapter.igw_display_type_upsells.php',
            'A_Marketing_Lightbox_Options_Form'      => 'adapter.lightbox_options_form.php',
            'A_Marketing_Other_Options_Form'         => 'adapter.other_options_form.php',
            'C_Marketing_Block_Popup'                => 'class.block_popup.php',
            'C_Marketing_Block_Base'                 => 'class.block_base.php',
            'C_Marketing_Block_Card'                 => 'class.block_card.php',
            'C_Marketing_Block_Large'                => 'class.block_large.php',
            'C_Marketing_Block_Single_Line'          => 'class.block_single_line.php',
            'C_Marketing_Block_Two_Columns'          => 'class.block_two_columns.php'
        ];
    }
}

new M_Marketing;