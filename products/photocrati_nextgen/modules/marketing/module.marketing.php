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

    public static $big_hitters_block_one_cache = NULL;
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
        if (self::is_plus_or_pro_enabled())
            return;

        add_action('ngg_manage_albums_marketing_block', function() {
            self::enqueue_blocks_style();
            print self::get_big_hitters_block_two();
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
            $block    = new C_Marketing_Single_Line($title, $campaign, $source);
            print $block->render();
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
            $registry->add_adapter('I_MVC_View', 'A_Marketing_Admin_MVC_Injector');

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
            'ngg_marketing_cards_style',
            C_Router::get_instance()->get_static_url('photocrati-marketing#cards.css'),
            [],
            NGG_SCRIPT_VERSION
        );
        wp_register_style(
            'ngg_marketing_blocks_style',
            C_Router::get_instance()->get_static_url('photocrati-marketing#blocks.css'),
            [],
            NGG_SCRIPT_VERSION
        );

        if (!self::is_plus_or_pro_enabled() && is_admin())
        {
            $forms = C_Form_Manager::get_instance();
            foreach (self::$display_setting_blocks as $block) {
                $forms->add_form(NGG_DISPLAY_SETTINGS_SLUG, "photocrati-marketing_display_settings_{$block}");
            }
        }
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

    /**
     * @return string
     */
    public static function get_big_hitters_block_one()
    {
        if (!empty(self::$big_hitters_block_one_cache))
            return self::$big_hitters_block_one_cache;

        $title       = __('Want to make your gallery workflow and presentation even better?', 'nggallery');
        $description = __('By upgrading to NextGEN Pro, you can get access to numerous other features, including:', 'nggallery');
        $links       = self::get_big_hitters_links();
        $footer      = __('<strong>Bonus:</strong> NextGEN Gallery users get a discount code for 30% off regular price.', 'nggallery');
        $campaign    = 'clickheretoupgrade';
        $source      = 'galleryworkflow';

        $block = new C_Marketing_Block_Two_Columns($title, $description, $links, $footer, $campaign, $source);
        self::$big_hitters_block_one_cache = $block->render();

        return self::$big_hitters_block_one_cache;
    }

    public static function get_big_hitters_block_two()
    {
        if (!empty(self::$big_hitters_block_two_cache))
            return self::$big_hitters_block_two_cache;

        $title       = __('Want to do even more with your gallery display?', 'nggallery');
        $description = [
            __('We know that you will truly love NextGEN Pro. It has 2,600+ five star ratings and is active on over 900,000 websites.', 'nggallery'),
            __('By upgrading to NextGEN Pro, you can get access to numerous other features, including:', 'nggallery')
        ];
        $links       = self::get_big_hitters_links();
        $footer      = __('<strong>Bonus:</strong> NextGEN Gallery users get a discount code for 30% off regular price.', 'nggallery');
        $campaign    = 'doevenmore';
        $source      = 'galleryworkflow';

        $block = new C_Marketing_Block_Two_Columns($title, $description, $links, $footer, $campaign, $source);
        self::$big_hitters_block_two_cache = $block->render();

        return self::$big_hitters_block_two_cache;
    }

    public static function enqueue_blocks_style()
    {
        wp_enqueue_style('wp-block-library');
        wp_enqueue_style('ngg_marketing_blocks_style');
    }

    function get_type_list()
    {
        return [
            'C_Marketing_Single_Line'           => 'class.marketing_single_line.php',
            'C_Marketing_Card'                  => 'class.marketing_card.php',
            'C_Marketing_Block_Large'           => 'class.marketing_block_large.php',
            'C_Marketing_Block_Two_Columns'     => 'class.marketing_block_two_columns.php',
            'A_Marketing_Admin_MVC_Injector'    => 'adapter.marketing_admin_mvc_injector.php',
            'A_Marketing_Display_Settings_Form' => 'adapter.marketing_display_settings_form.php'
        ];
    }
}

new M_Marketing;
